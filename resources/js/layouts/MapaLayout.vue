<script setup>
// Layout persistente del mapa: se monta una sola vez y sobrevive a la
// navegación entre /barrios y /barrios/{slug}. Solo cambian las capas.
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { bbox, buffer, mask } from '@turf/turf'
import { maplibregl, ESTILO_MAPA } from '@/lib/mapa'
import BarrioSelector from '@/components/BarrioSelector.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import CategoriaChips from '@/components/CategoriaChips.vue'
import EventoDetalle from '@/components/EventoDetalle.vue'
import Isotipo from '@/components/marca/Isotipo.vue'
import { crearCapaEventos3D } from '@/mapa/capaEventos3D.js'
import { nombreLegible } from '@/lib/texto'

// Límites de CABA [oeste, sur, este, norte] para la vista general.
const CABA_BBOX = [-58.5315, -34.7053, -58.3352, -34.5265]
// Abajo queda la tarjeta del barrio: se reserva espacio para que no tape el mapa.
const MARGEN_CIUDAD = { top: 16, bottom: 150, left: 16, right: 16 }
const MARGEN_BARRIO = { top: 40, bottom: 210, left: 30, right: 30 }
const VISTA_CIUDAD = { bounds: CABA_BBOX, fitBoundsOptions: { padding: MARGEN_CIUDAD }, pitch: 0, bearing: 0 }
const MARGEN_BOUNDS = 0.015

// Paleta de marca (MapLibre no lee variables CSS).
const COLOR = {
    violeta: '#8e66cc',
    violeta700: '#5f3f96',
    lila: '#b37dff',
    durazno: '#ffdeb6',
    duraznoClaro: '#ffecd5',
    papel: '#f6f7ee',
}
// Líneas de contorno alrededor del barrio (el "Offset Path" de la marca), en km.
const CONTORNOS_KM = [0.05, 0.1, 0.15]
const FUENTE_ETIQUETAS = ['Montserrat SemiBold', 'Open Sans Bold', 'Noto Sans Regular']

const reducirMovimiento = () => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
const duracion = (ms) => (reducirMovimiento() ? 0 : ms)

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
    for (const capa of ['eventos-puntos', 'eventos-3d', 'barrio-borde', 'barrio-contornos', 'barrio-3d-extrusion', 'mascara-exteriores']) {
        if (map.getLayer(capa)) map.removeLayer(capa)
    }
    for (const fuente of ['eventos', 'barrio', 'contornos', 'mascara']) {
        if (map.getSource(fuente)) map.removeSource(fuente)
    }
    eventoActivoId.value = null
}

// --- Vista ciudad: los 48 barrios en violeta con bordes lila (lámina "El Recorrido"). ---
let ciudadLista = false
let barrioBajoPuntero = null

// Mi barrio en durazno; favoritos en durazno claro; el resto en violeta.
const colorCiudad = () => [
    'case',
    ['==', ['get', 'id'], page.props.barrioPropioId ?? -1], COLOR.durazno,
    ['in', ['get', 'id'], ['literal', page.props.favoritosIds ?? []]], COLOR.duraznoClaro,
    COLOR.violeta,
]
// Sobre durazno el nombre va en violeta con halo claro; sobre violeta, en blanco cálido con halo violeta.
const esDestacado = () => ['any', ['==', ['get', 'id'], page.props.barrioPropioId ?? -1], ['in', ['get', 'id'], ['literal', page.props.favoritosIds ?? []]]]
const colorEtiqueta = () => ['case', esDestacado(), COLOR.violeta700, COLOR.papel]
const haloEtiqueta = () => ['case', esDestacado(), 'rgba(255, 247, 236, 0.9)', 'rgba(95, 63, 150, 0.6)']

const agregarCiudad = async () => {
    const datos = await fetch('/geo/barrios.json').then((r) => r.json())
    if (!map) return
    for (const f of datos.features) f.properties.etiqueta = nombreLegible(f.properties.nombre)

    map.addSource('barrios-ciudad', { type: 'geojson', data: datos, promoteId: 'id' })
    map.addLayer({
        id: 'ciudad-relleno',
        type: 'fill',
        source: 'barrios-ciudad',
        paint: {
            'fill-color': colorCiudad(),
            'fill-opacity': ['case', ['boolean', ['feature-state', 'hover'], false], 1, 0.85],
        },
    })
    map.addLayer({
        id: 'ciudad-borde',
        type: 'line',
        source: 'barrios-ciudad',
        paint: { 'line-color': COLOR.lila, 'line-width': ['interpolate', ['linear'], ['zoom'], 10, 1, 14, 2.5] },
    })
    map.addLayer({
        id: 'ciudad-nombres',
        type: 'symbol',
        source: 'barrios-ciudad',
        layout: {
            'text-field': ['get', 'etiqueta'],
            'text-font': FUENTE_ETIQUETAS,
            'text-size': ['interpolate', ['linear'], ['zoom'], 10, 9, 13, 14],
            'text-max-width': 6,
            'text-padding': 2,
        },
        paint: { 'text-color': colorEtiqueta(), 'text-halo-color': haloEtiqueta(), 'text-halo-width': 1 },
    })
    ciudadLista = true
    mostrarCapasCiudad(!barrio.value)
}

const mostrarCapasCiudad = (visible) => {
    if (!ciudadLista) return
    for (const capa of ['ciudad-relleno', 'ciudad-borde', 'ciudad-nombres']) {
        map.setLayoutProperty(capa, 'visibility', visible ? 'visible' : 'none')
    }
}

const repintarCiudad = () => {
    if (!ciudadLista) return
    map.setPaintProperty('ciudad-relleno', 'fill-color', colorCiudad())
    map.setPaintProperty('ciudad-nombres', 'text-color', colorEtiqueta())
    map.setPaintProperty('ciudad-nombres', 'text-halo-color', haloEtiqueta())
}

const mostrarCiudad = () => {
    mostrarCapasCiudad(true)
    map.setMaxBounds(null)
    map.setMinZoom(9)
    map.fitBounds(CABA_BBOX, { padding: MARGEN_CIUDAD, pitch: 0, bearing: 0, duration: duracion(1200) })
}

const mostrarBarrio = (datosBarrio, datosEventos) => {
    const geo = parsear(datosBarrio.geojson)
    if (!geo) return

    mostrarCapasCiudad(false)
    map.addSource('barrio', { type: 'geojson', data: geo })
    map.addSource('mascara', { type: 'geojson', data: mask(geo) })
    map.addSource('contornos', {
        type: 'geojson',
        data: { type: 'FeatureCollection', features: CONTORNOS_KM.map((km) => buffer(geo, km, { units: 'kilometers', steps: 8 })) },
    })
    map.addLayer({ id: 'mascara-exteriores', type: 'fill', source: 'mascara', paint: { 'fill-color': COLOR.violeta, 'fill-opacity': 0.92 } })
    map.addLayer({
        id: 'barrio-contornos',
        type: 'line',
        source: 'contornos',
        paint: { 'line-color': COLOR.lila, 'line-width': 1.5, 'line-opacity': 0.9 },
    })
    map.addLayer({
        id: 'barrio-3d-extrusion',
        type: 'fill-extrusion',
        source: 'barrio',
        paint: { 'fill-extrusion-color': COLOR.durazno, 'fill-extrusion-height': 10, 'fill-extrusion-base': 0, 'fill-extrusion-opacity': 0.15 },
    })
    map.addLayer({ id: 'barrio-borde', type: 'line', source: 'barrio', paint: { 'line-color': COLOR.durazno, 'line-width': 3 } })

    const features = aFeatures(datosEventos)
    capaEventos = null
    if (features.length) {
        capaEventos = crearCapaEventos3D(features, '/models/pin.glb')
        map.addLayer(capaEventos)
        map.addSource('eventos', { type: 'geojson', data: { type: 'FeatureCollection', features } })
        // Capa invisible que recibe los clics sobre los pines 3D.
        // Centrada en el cuerpo del pin (mide ≥ 44 px de alto en pantalla), no en su base.
        map.addLayer({
            id: 'eventos-puntos',
            type: 'circle',
            source: 'eventos',
            paint: { 'circle-radius': 24, 'circle-opacity': 0, 'circle-translate': [0, -20], 'circle-translate-anchor': 'viewport' },
        })
        aplicarFiltro(seleccion.value)
    }

    // Se liberan los límites para que la cámara pueda viajar desde el barrio anterior;
    // al terminar el vuelo se vuelven a fijar alrededor del barrio nuevo.
    const caja = bbox(geo)
    map.setMaxBounds(null)
    map.setMinZoom(9)
    map.fitBounds(caja, { padding: MARGEN_BARRIO, pitch: 50, bearing: -10, duration: duracion(1200) })
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
    // Vista ciudad: tocar un barrio lo abre.
    if (!barrio.value && ciudadLista) {
        const [zona] = map.queryRenderedFeatures(e.point, { layers: ['ciudad-relleno'] })
        if (zona) router.visit(`/barrios/${zona.properties.slug}`)
        return
    }
    if (!map.getLayer('eventos-puntos')) return
    const [feature] = map.queryRenderedFeatures(e.point, { layers: ['eventos-puntos'] })
    if (feature) eventoActivoId.value = feature.properties.id
}

const marcarHover = (id) => {
    if (barrioBajoPuntero === id) return
    if (barrioBajoPuntero !== null) map.setFeatureState({ source: 'barrios-ciudad', id: barrioBajoPuntero }, { hover: false })
    barrioBajoPuntero = id
    if (id !== null) map.setFeatureState({ source: 'barrios-ciudad', id }, { hover: true })
}

const alMoverMouse = (e) => {
    if (!barrio.value && ciudadLista) {
        const [zona] = map.queryRenderedFeatures(e.point, { layers: ['ciudad-relleno'] })
        marcarHover(zona?.id ?? null)
        map.getCanvas().style.cursor = zona ? 'pointer' : ''
        return
    }
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
    map.on('load', () => {
        sincronizar()
        agregarCiudad()
    })
    map.on('click', alHacerClick)
    map.on('mousemove', alMoverMouse)
    map.on('mouseout', () => marcarHover(null))
})

watch(() => barrio.value?.id ?? null, () => {
    if (ciudadLista) marcarHover(null)
    sincronizar()
})
watch(seleccion, aplicarFiltro)
// Marcar favorito o Mi barrio repinta la ciudad sin recargar la fuente.
watch(() => [page.props.favoritosIds, page.props.barrioPropioId], repintarCiudad, { deep: true })

onBeforeUnmount(() => {
    map?.remove()
    map = null
})
</script>

<template>
    <div class="flex h-full flex-col">
        <header class="relative z-20 flex shrink-0 items-center gap-3 border-b border-niebla-200 bg-papel px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
            <!-- En mobile la marca vive acá; en desktop está en la barra lateral. -->
            <Isotipo class="h-9 w-auto shrink-0 md:hidden" titulo="Barriopoli" />
            <BarrioSelector :barrios="barrios" :actual="barrio" @selected="irABarrio" />
        </header>
        <CategoriaChips
            v-if="barrio && categoriasDelBarrio.length"
            v-model="seleccion"
            :categorias="categoriasDelBarrio"
            class="relative z-10 shrink-0 border-b border-niebla-200 bg-papel"
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
