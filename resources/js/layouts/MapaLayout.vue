<script setup>
// Layout persistente del mapa: se monta una sola vez y sobrevive a la
// navegación entre /barrios y /barrios/{slug}. Solo cambian las capas.
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { bbox, mask } from '@turf/turf'
import { maplibregl, ESTILO_MAPA } from '@/lib/mapa'
import BarrioSelector from '@/components/BarrioSelector.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import CategoriaChips from '@/components/CategoriaChips.vue'
import EventoDetalle from '@/components/EventoDetalle.vue'
import { crearCapaEventos3D } from '@/mapa/capaEventos3D.js'

// Límites de CABA [oeste, sur, este, norte] para la vista general.
const CABA_BBOX = [-58.5315, -34.7053, -58.3352, -34.5265]
const VISTA_CIUDAD = { bounds: CABA_BBOX, fitBoundsOptions: { padding: 20 }, pitch: 0, bearing: 0 }
const MARGEN_BOUNDS = 0.015

const page = usePage()
const barrios = computed(() => page.props.barrios ?? [])
const barrio = computed(() => page.props.barrio ?? null)
// Eventos publicados del barrio (EventoResource). El GeoJSON de los pines se arma acá.
const eventos = computed(() => page.props.eventos ?? [])
const aFeatures = (lista) => lista.map((evento) => ({
    type: 'Feature',
    geometry: { type: 'Point', coordinates: [evento.longitud, evento.latitud] },
    properties: { id: evento.id, categoria_id: evento.categoria.id, color: evento.categoria.color },
}))

// Detalle en bottom sheet: se lee sin perder el mapa.
const eventoActivoId = ref(null)
const eventoActivo = computed(() => eventos.value.find((e) => e.id === eventoActivoId.value) ?? null)
const hojaAbierta = computed({
    get: () => eventoActivo.value !== null,
    set: (valor) => { if (!valor) eventoActivoId.value = null },
})

// Filtro por categoría. Vive en el layout persistente, así que se conserva al cambiar de barrio.
const seleccion = ref([])
const categoriasDelBarrio = computed(() => {
    const presentes = new Set(eventos.value.map((e) => e.categoria.id))
    // También las seleccionadas: si no, un filtro activo sin eventos en este barrio no se podría quitar.
    return (page.props.categorias ?? []).filter((c) => presentes.has(c.id) || seleccion.value.includes(c.id))
})

const contenedor = ref(null)
let map = null
let capaEventos = null

const parsear = (dato) => (typeof dato === 'string' ? JSON.parse(dato) : dato)

const limpiarCapas = () => {
    for (const capa of ['eventos-puntos', 'eventos-3d', 'barrio-borde', 'barrio-3d-extrusion', 'mascara-exteriores']) {
        if (map.getLayer(capa)) map.removeLayer(capa)
    }
    for (const fuente of ['eventos', 'barrio', 'mascara']) {
        if (map.getSource(fuente)) map.removeSource(fuente)
    }
    eventoActivoId.value = null
}

const mostrarCiudad = () => {
    map.setMaxBounds(null)
    map.setMinZoom(9)
    map.fitBounds(CABA_BBOX, { padding: 20, pitch: 0, bearing: 0, duration: 1200 })
}

const mostrarBarrio = (datosBarrio, datosEventos) => {
    const geo = parsear(datosBarrio.geojson)
    if (!geo) return

    map.addSource('barrio', { type: 'geojson', data: geo })
    map.addSource('mascara', { type: 'geojson', data: mask(geo) })
    map.addLayer({ id: 'mascara-exteriores', type: 'fill', source: 'mascara', paint: { 'fill-color': '#8e66cc', 'fill-opacity': 0.9 } })
    map.addLayer({
        id: 'barrio-3d-extrusion',
        type: 'fill-extrusion',
        source: 'barrio',
        paint: { 'fill-extrusion-color': '#ffdeb6', 'fill-extrusion-height': 10, 'fill-extrusion-base': 0, 'fill-extrusion-opacity': 0.15 },
    })
    map.addLayer({ id: 'barrio-borde', type: 'line', source: 'barrio', paint: { 'line-color': '#ffdeb6', 'line-width': 3 } })

    const features = aFeatures(datosEventos)
    capaEventos = null
    if (features.length) {
        capaEventos = crearCapaEventos3D(features, '/models/pin.glb')
        map.addLayer(capaEventos)
        map.addSource('eventos', { type: 'geojson', data: { type: 'FeatureCollection', features } })
        // Capa invisible que recibe los clics sobre los pines 3D.
        map.addLayer({ id: 'eventos-puntos', type: 'circle', source: 'eventos', paint: { 'circle-radius': 18, 'circle-opacity': 0 } })
        aplicarFiltro(seleccion.value)
    }

    // Se liberan los límites para que la cámara pueda viajar desde el barrio anterior;
    // al terminar el vuelo se vuelven a fijar alrededor del barrio nuevo.
    const caja = bbox(geo)
    map.setMaxBounds(null)
    map.setMinZoom(9)
    map.fitBounds(caja, { padding: 40, pitch: 50, bearing: -10, duration: 1200 })
    map.once('moveend', () => {
        if (barrio.value?.id !== datosBarrio.id) return
        map.setMaxBounds([
            [caja[0] - MARGEN_BOUNDS, caja[1] - MARGEN_BOUNDS],
            [caja[2] + MARGEN_BOUNDS, caja[3] + MARGEN_BOUNDS],
        ])
        map.setMinZoom(12)
    })
}

// Se filtra en dos capas: el pin 3D (Three.js) y la zona táctil (circle de MapLibre).
// Filtrando solo una quedarían pines que no responden o toques sobre pines ocultos.
const aplicarFiltro = (ids) => {
    capaEventos?.setCategoriasVisibles(ids)
    if (map?.getLayer('eventos-puntos')) {
        map.setFilter('eventos-puntos', ids.length ? ['in', ['get', 'categoria_id'], ['literal', ids]] : null)
    }
    eventoActivoId.value = null
}

const sincronizar = () => {
    if (!map?.isStyleLoaded()) return
    limpiarCapas()
    if (barrio.value) mostrarBarrio(barrio.value, eventos.value)
    else mostrarCiudad()
}

// Un solo handler registrado en onMounted: si viviera en mostrarBarrio se acumularía en cada cambio de barrio.
const alHacerClick = (e) => {
    if (!map.getLayer('eventos-puntos')) return
    const [feature] = map.queryRenderedFeatures(e.point, { layers: ['eventos-puntos'] })
    if (feature) eventoActivoId.value = feature.properties.id
}

const alMoverMouse = (e) => {
    const sobrePin = map.getLayer('eventos-puntos')
        && map.queryRenderedFeatures(e.point, { layers: ['eventos-puntos'] }).length > 0
    map.getCanvas().style.cursor = sobrePin ? 'pointer' : ''
}

const irABarrio = (b) => router.visit(`/barrios/${b.slug}`)

onMounted(() => {
    map = new maplibregl.Map({
        container: contenedor.value,
        style: ESTILO_MAPA,
        ...VISTA_CIUDAD,
        minZoom: 9,
        maxZoom: 18,
        canvasContextAttributes: { antialias: true },
    })
    // Solo en desarrollo: acceso al mapa desde la consola y los tests E2E. Vite lo elimina del build.
    if (import.meta.env.DEV) window.__mapa = map
    map.on('load', sincronizar)
    map.on('click', alHacerClick)
    map.on('mousemove', alMoverMouse)
})

watch(() => barrio.value?.id ?? null, sincronizar)
watch(seleccion, aplicarFiltro)

onBeforeUnmount(() => {
    map?.remove()
    map = null
})
</script>

<template>
    <div class="flex h-full flex-col">
        <header class="relative z-20 shrink-0 border-b border-gray-200 bg-white px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
            <BarrioSelector :barrios="barrios" :actual="barrio" @selected="irABarrio" />
        </header>
        <CategoriaChips
            v-if="barrio && categoriasDelBarrio.length"
            v-model="seleccion"
            :categorias="categoriasDelBarrio"
            class="relative z-10 shrink-0 border-b border-gray-200 bg-white"
        />
        <div class="relative min-h-0 flex-1">
            <!-- El absolute va en un envoltorio: maplibre-gl.css fuerza position:relative en
                 .maplibregl-map y, al no estar en un @layer, le gana a las utilidades de Tailwind v4. -->
            <div class="absolute inset-0">
                <div ref="contenedor" class="h-full w-full"></div>
            </div>
            <!-- bottom-6: deja visible la atribución de OSM/CARTO, que es obligatoria -->
            <div class="pointer-events-none absolute inset-x-0 bottom-6 z-10 p-3">
                <slot />
            </div>
        </div>

        <BottomSheet v-model="hojaAbierta" :titulo="eventoActivo?.titulo ?? ''">
            <EventoDetalle v-if="eventoActivo" :evento="eventoActivo" />
        </BottomSheet>
    </div>
</template>
