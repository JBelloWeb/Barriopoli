<script setup>
// Layout persistente del mapa: se monta una sola vez y sobrevive a la
// navegación entre /barrios y /barrios/{slug}. Solo cambian las capas.
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import * as maplibregl from 'maplibre-gl'
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'
import { bbox, mask } from '@turf/turf'
import BarrioSelector from '@/components/BarrioSelector.vue'
import { crearCapaEventos3D } from '@/mapa/capaEventos3D.js'

maplibregl.setWorkerUrl(workerUrl)

// Límites de CABA [oeste, sur, este, norte] para la vista general.
const CABA_BBOX = [-58.5315, -34.7053, -58.3352, -34.5265]
const VISTA_CIUDAD = { bounds: CABA_BBOX, fitBoundsOptions: { padding: 20 }, pitch: 0, bearing: 0 }
const MARGEN_BOUNDS = 0.015

const page = usePage()
const barrios = computed(() => page.props.barrios ?? [])
const barrio = computed(() => page.props.barrio ?? null)
const eventos = computed(() => page.props.eventosGeoJson ?? null)

const contenedor = ref(null)
let map = null
let popup = null

const parsear = (dato) => (typeof dato === 'string' ? JSON.parse(dato) : dato)

const limpiarCapas = () => {
    for (const capa of ['eventos-puntos', 'eventos-3d', 'barrio-borde', 'barrio-3d-extrusion', 'mascara-exteriores']) {
        if (map.getLayer(capa)) map.removeLayer(capa)
    }
    for (const fuente of ['eventos', 'barrio', 'mascara']) {
        if (map.getSource(fuente)) map.removeSource(fuente)
    }
    popup?.remove()
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

    const features = parsear(datosEventos)?.features ?? []
    if (features.length) {
        map.addLayer(crearCapaEventos3D(features, '/models/pin.glb'))
        map.addSource('eventos', { type: 'geojson', data: { type: 'FeatureCollection', features } })
        // Capa invisible que recibe los clics sobre los pines 3D.
        map.addLayer({ id: 'eventos-puntos', type: 'circle', source: 'eventos', paint: { 'circle-radius': 18, 'circle-opacity': 0 } })
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

const sincronizar = () => {
    if (!map?.isStyleLoaded()) return
    limpiarCapas()
    if (barrio.value) mostrarBarrio(barrio.value, eventos.value)
    else mostrarCiudad()
}

// El contenido del popup se arma con textContent: título y dirección los cargan
// usuarios, e insertarlos con setHTML permitiría inyectar scripts (XSS).
const crearContenidoPopup = ({ id, titulo, direccion, fecha }) => {
    const raiz = document.createElement('div')
    raiz.className = 'font-rosario p-1'
    const linea = (etiqueta, texto, clase) => {
        const el = document.createElement(etiqueta)
        el.className = clase
        el.textContent = texto ?? ''
        raiz.append(el)
        return el
    }
    linea('strong', titulo ?? 'Evento', 'block text-sm text-tinta')
    linea('span', direccion, 'block text-xs text-gray-600')
    linea('small', fecha, 'block text-xs text-gray-400')
    if (id) {
        const enlace = linea('a', 'Editar evento', 'mt-1.5 inline-block text-xs font-bold text-violeta-oscuro underline')
        enlace.href = `/eventos/${Number(id)}/edit`
    }
    return raiz
}

const alHacerClick = (e) => {
    if (!map.getLayer('eventos-puntos')) return
    const [feature] = map.queryRenderedFeatures(e.point, { layers: ['eventos-puntos'] })
    if (!feature) return

    popup?.remove()
    popup = new maplibregl.Popup()
        .setLngLat(feature.geometry.coordinates.slice())
        .setDOMContent(crearContenidoPopup(feature.properties))
        .addTo(map)
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
        style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
        ...VISTA_CIUDAD,
        minZoom: 9,
        maxZoom: 18,
        canvasContextAttributes: { antialias: true },
    })
    map.on('load', sincronizar)
    map.on('click', alHacerClick)
    map.on('mousemove', alMoverMouse)
})

watch(() => barrio.value?.id ?? null, sincronizar)

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
    </div>
</template>
