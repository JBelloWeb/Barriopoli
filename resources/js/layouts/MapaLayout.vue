<script setup>
// Layout persistente del mapa: se monta una sola vez y sobrevive a la
// navegación entre /barrios y /barrios/{slug}. Solo cambian las capas.
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { bbox, buffer, mask, rewind, simplify } from '@turf/turf'
import { maplibregl, ESTILO_MAPA, aplicarEstiloBarriopoli } from '@/lib/mapa'
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
// En pantallas bajas (celu en horizontal) la reserva fija dejaba ~150 px para toda CABA.
const margenCiudad = (alto = typeof window === 'undefined' ? 900 : window.innerHeight) => ({ ...MARGEN_CIUDAD, bottom: Math.min(MARGEN_CIUDAD.bottom, Math.round(alto * 0.22)) })
const VISTA_CIUDAD = { bounds: CABA_BBOX, fitBoundsOptions: { padding: margenCiudad() }, pitch: 0, bearing: 0 }
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
// Líneas de contorno alrededor del barrio (el "Offset Path" de la marca).
// La posición final es un buffer geométrico exacto (Turf: resuelve bien las esquinas cóncavas).
// La expansión se anima con line-offset (transición nativa de MapLibre): cada anillo arranca
// corrido hacia adentro, sobre el borde del barrio, y se desliza hasta su lugar real (offset 0).
// La distancia en metros se calcula con el zoom en el que termina la cámara (cameraForBounds):
// así hay siempre ~CONTORNO_SEPARACION_PX entre líneas, sea Palermo o San Telmo, en celu o desktop.
const CONTORNO_SEPARACION_PX = 8
// 5 ecos, como las láminas de la marca (4 a 6 líneas): se afinan y se apagan hacia afuera.
const CONTORNOS = [
    { ancho: 3, opacidad: 1 },
    { ancho: 2.5, opacidad: 0.8 },
    { ancho: 2, opacidad: 0.6 },
    { ancho: 1.75, opacidad: 0.45 },
    { ancho: 1.5, opacidad: 0.3 },
]
const CONTORNO_EXPANSION_MS = 900
const CONTORNO_ESCALON_MS = 120
// Lo que ocupa el eco más lejano + aire: la cámara reserva ese espacio para que ninguno quede cortado.
// Abajo queda la tarjeta del barrio, que ya reserva más.
const ALCANCE_ECOS_PX = CONTORNO_SEPARACION_PX * CONTORNOS.length + 8
// En pantallas bajas (celu en horizontal) los 210 px de la tarjeta dejaban ~60 px para el barrio: como en la
// vista ciudad, la reserva de abajo se limita a una parte del alto.
const margenBarrio = (alto) => ({
    top: Math.max(40, ALCANCE_ECOS_PX),
    bottom: Math.min(210, Math.round(alto * 0.5)),
    left: ALCANCE_ECOS_PX,
    right: ALCANCE_ECOS_PX,
})
const capasContorno = CONTORNOS.map((_, i) => `barrio-contorno-${i}`)
const fuentesContorno = CONTORNOS.map((_, i) => `contorno-${i}`)
let vueloActual = 0 // identifica el último fitBounds a un barrio
let cajaBarrio = null // bbox del barrio vigente, para recalcular sus límites al redimensionar
const metrosPorPixel = (lat, zoom) => (40075016.686 * Math.cos((lat * Math.PI) / 180)) / (512 * 2 ** zoom)
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
// isStyleLoaded() da false mientras cualquier fuente recarga teselas (o tras un setFilter del estilo
// de marca): como guarda dejaba el barrio sin dibujar. Basta saber que el estilo ya cargó una vez.
let estiloCargado = false

const parsear = (dato) => (typeof dato === 'string' ? JSON.parse(dato) : dato)

const limpiarCapas = () => {
    for (const capa of ['eventos-puntos', 'eventos-3d', 'barrio-borde', ...capasContorno, 'barrio-3d-extrusion', 'mascara-exteriores']) {
        if (map.getLayer(capa)) map.removeLayer(capa)
    }
    for (const fuente of ['eventos', 'barrio', ...fuentesContorno, 'mascara']) {
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

// Extensión geográfica que muestra una cámara (sin inclinación) en el tamaño actual del mapa.
const extensionVista = ({ center, zoom }) => {
    const canvas = map.getCanvas()
    const mundoPx = 512 * 2 ** zoom
    const medioAncho = canvas.clientWidth / 2 / mundoPx
    const medioAlto = canvas.clientHeight / 2 / mundoPx
    const c = maplibregl.MercatorCoordinate.fromLngLat(center)
    const so = new maplibregl.MercatorCoordinate(c.x - medioAncho, c.y + medioAlto).toLngLat()
    const ne = new maplibregl.MercatorCoordinate(c.x + medioAncho, c.y - medioAlto).toLngLat()
    return [[so.lng, so.lat], [ne.lng, ne.lat]]
}

// Vista ciudad: no se puede alejar más que CABA completa ni arrastrar fuera de ese encuadre.
// Los límites son exactamente lo que se ve con CABA encuadrada (incluye el margen de la tarjeta).
let limitesCiudadActivos = false
const fijarLimitesCiudad = () => {
    const camara = map.cameraForBounds(CABA_BBOX, { padding: margenCiudad(map.getCanvas().clientHeight), bearing: 0 })
    if (!camara) return
    map.setMaxBounds(extensionVista(camara))
    map.setMinZoom(camara.zoom)
    limitesCiudadActivos = true
}
// Vista barrio: los límites CONTIENEN la vista de llegada. Si no, setMaxBounds corrige la cámara en el
// acto y se ve un salto justo cuando arrancan los ecos (antes: bbox ± MARGEN_BOUNDS fijo y minZoom 12,
// que movían la cámara hasta 1,5 km y un nivel de zoom). Se suma el área del barrio para recorrerlo al acercarse.
let limitesBarrioActivos = false
let zoomLlegada = null
const fijarLimitesBarrio = (alLlegar = false) => {
    if (!cajaBarrio) return
    if (alLlegar) zoomLlegada = map.getZoom()
    const [[oeste, sur], [este, norte]] = extensionVista({ center: map.getCenter(), zoom: map.getZoom() })
    map.setMaxBounds([
        [Math.min(oeste, cajaBarrio[0] - MARGEN_BOUNDS), Math.min(sur, cajaBarrio[1] - MARGEN_BOUNDS)],
        [Math.max(este, cajaBarrio[2] + MARGEN_BOUNDS), Math.max(norte, cajaBarrio[3] + MARGEN_BOUNDS)],
    ])
    // No alejarse más que la vista de llegada. Si al rotar la cámara queda más lejos, ese pasa a ser el mínimo:
    // un minZoom mayor que el zoom actual también la corregiría (salto).
    map.setMinZoom(Math.min(zoomLlegada ?? map.getZoom(), map.getZoom()))
    limitesBarrioActivos = true
}

// Al cambiar el tamaño (rotar el celu, redimensionar la ventana) cambia lo que se ve. El resize automático de
// MapLibre restringe la cámara con los límites VIEJOS antes de avisar (se veía un salto de +0,9 de zoom al
// rotar en un barrio): por eso el resize es propio y los límites se liberan antes de cambiar el tamaño.
const alRedimensionar = () => {
    if (!map) return
    const enBarrio = limitesBarrioActivos && barrio.value && !map.isMoving()
    if (enBarrio) {
        map.setMaxBounds(null)
        map.setMinZoom(9)
    }
    map.resize()
    if (limitesCiudadActivos && !barrio.value) fijarLimitesCiudad()
    if (enBarrio) fijarLimitesBarrio()
}
let observadorTamano = null

const mostrarCiudad = () => {
    mostrarCapasCiudad(true)
    // Se liberan los límites para que la cámara pueda volver desde un barrio (inclinada);
    // se fijan al terminar el vuelo. Mismo guard de vuelo que mostrarBarrio.
    const esteVuelo = ++vueloActual
    limitesCiudadActivos = false
    limitesBarrioActivos = false
    cajaBarrio = null
    map.setMaxBounds(null)
    map.setMinZoom(9)
    map.fitBounds(CABA_BBOX, { padding: margenCiudad(map.getCanvas().clientHeight), pitch: 0, bearing: 0, duration: duracion(1200) })
    const alTerminarVuelo = () => {
        if (!map || esteVuelo !== vueloActual) return
        if (map.isMoving()) {
            map.once('moveend', alTerminarVuelo)
            return
        }
        fijarLimitesCiudad()
    }
    map.once('moveend', alTerminarVuelo)
}

const mostrarBarrio = (datosBarrio, datosEventos) => {
    const geo = parsear(datosBarrio.geojson)
    if (!geo) return

    mostrarCapasCiudad(false)
    map.addSource('barrio', { type: 'geojson', data: geo })
    map.addSource('mascara', { type: 'geojson', data: mask(geo) })
    map.addLayer({ id: 'mascara-exteriores', type: 'fill', source: 'mascara', paint: { 'fill-color': COLOR.violeta, 'fill-opacity': 0.92 } })
    // Buffers sobre la geometría simplificada (~20 m, invisible a esta escala y mucho más rápido).
    // Winding RFC 7946 (exterior antihorario): con el eje Y de pantalla invertido, un offset
    // POSITIVO corre la línea hacia adentro del barrio, que es donde arranca la animación.
    const base = simplify(geo, { tolerance: 0.0002, highQuality: true })
    const caja = bbox(geo)
    const margen = margenBarrio(map.getCanvas().clientHeight)
    const camara = map.cameraForBounds(caja, { padding: margen, pitch: 50, bearing: -10 })
    const metrosPx = metrosPorPixel((caja[1] + caja[3]) / 2, camara?.zoom ?? 13.5)
    const separacionesPx = CONTORNOS.map((_, i) => CONTORNO_SEPARACION_PX * (i + 1))
    CONTORNOS.forEach((contorno, i) => {
        map.addSource(fuentesContorno[i], {
            type: 'geojson',
            data: rewind(buffer(base, (separacionesPx[i] * metrosPx) / 1000, { units: 'kilometers', steps: 8 }), { reverse: false }),
        })
        // Invisibles hasta que termina el vuelo de la cámara (ver expandirContornos).
        map.addLayer({
            id: capasContorno[i],
            type: 'line',
            source: fuentesContorno[i],
            layout: { 'line-join': 'round', 'line-cap': 'round' },
            paint: { 'line-color': COLOR.lila, 'line-width': contorno.ancho, 'line-opacity': 0, 'line-offset': 0 },
        })
    })
    // Tinte durazno plano (antes era una losa 3D de 10 m que ahora taparía los edificios 3D).
    map.addLayer({
        id: 'barrio-3d-extrusion',
        type: 'fill',
        source: 'barrio',
        paint: { 'fill-color': COLOR.durazno, 'fill-opacity': 0.12 },
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
    const esteVuelo = ++vueloActual
    limitesCiudadActivos = false
    limitesBarrioActivos = false
    cajaBarrio = caja
    map.setMaxBounds(null)
    map.setMinZoom(9)
    map.fitBounds(caja, { padding: margen, pitch: 50, bearing: -10, duration: duracion(1200) })
    // Un moveend puede llegar antes de tiempo (p. ej. de un movimiento anterior que fitBounds
    // interrumpe): se ignora si es de otro vuelo y, si la cámara sigue en movimiento, se espera al próximo.
    // Sin esto, la expansión se calculaba con el zoom de la ciudad y arrancaba casi sin recorrido.
    const alTerminarVuelo = () => {
        if (esteVuelo !== vueloActual || barrio.value?.id !== datosBarrio.id) return
        if (map.isMoving()) {
            map.once('moveend', alTerminarVuelo)
            return
        }
        // Primero los límites (no mueven la cámara: contienen la vista de llegada), después los ecos.
        fijarLimitesBarrio(true)
        expandirContornos(separacionesPx)
    }
    map.once('moveend', alTerminarVuelo)
}

// 1) Sin transición: cada anillo se corre hacia adentro su distancia en píxeles (queda sobre el borde).
// 2) En el cuadro siguiente, con transición: vuelve a offset 0 (su buffer real) mientras aparece.
// Las transiciones de pintura de MapLibre interpolan desde el valor vigente hasta el nuevo.
const expandirContornos = (separacionesPx) => {
    const vigentes = capasContorno.filter((id) => map.getLayer(id))
    if (!vigentes.length) return

    vigentes.forEach((id, i) => {
        map.setPaintProperty(id, 'line-offset-transition', { duration: 0, delay: 0 })
        map.setPaintProperty(id, 'line-offset', separacionesPx[i])
    })
    requestAnimationFrame(() => requestAnimationFrame(() => {
        vigentes.forEach((id, i) => {
            if (!map?.getLayer(id)) return
            const escalon = { duration: duracion(CONTORNO_EXPANSION_MS), delay: duracion(i * CONTORNO_ESCALON_MS) }
            map.setPaintProperty(id, 'line-offset-transition', escalon)
            map.setPaintProperty(id, 'line-opacity-transition', { ...escalon, duration: duracion(CONTORNO_EXPANSION_MS / 2) })
            map.setPaintProperty(id, 'line-offset', 0)
            map.setPaintProperty(id, 'line-opacity', CONTORNOS[i].opacidad)
        })
    }))
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
    if (!map || !estiloCargado) return
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
        trackResize: false, // ver alRedimensionar
    })
    // Solo en desarrollo: acceso al mapa desde la consola y los tests E2E. Vite lo elimina del build.
    if (import.meta.env.DEV) window.__mapa = map
    map.on('load', () => {
        estiloCargado = true
        aplicarEstiloBarriopoli(map)
        sincronizar()
        agregarCiudad()
    })
    observadorTamano = new ResizeObserver(alRedimensionar)
    observadorTamano.observe(contenedor.value)
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
    observadorTamano?.disconnect()
    map?.remove()
    map = null
})
</script>

<template>
    <div class="flex h-full flex-col">
        <header class="relative z-20 flex shrink-0 items-center gap-3 border-b border-lila-200 bg-lila-100 px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
            <!-- En mobile la marca vive acá; en desktop está en la barra lateral. -->
            <Isotipo class="h-9 w-auto shrink-0 md:hidden" titulo="Barriopoli" />
            <BarrioSelector :barrios="barrios" :actual="barrio" @selected="irABarrio" />
        </header>
        <CategoriaChips
            v-if="barrio && categoriasDelBarrio.length"
            v-model="seleccion"
            :categorias="categoriasDelBarrio"
            class="relative z-10 shrink-0 border-b border-lila-200 bg-lila-100"
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
