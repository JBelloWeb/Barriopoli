// Configuración única de MapLibre: worker para Vite (ver aprendizaje maplibre-v6-worker-vite) y estilo base.
import * as maplibregl from 'maplibre-gl'
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'

maplibregl.setWorkerUrl(workerUrl)

export { maplibregl }
export const ESTILO_MAPA = 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json'
export const CENTRO_CABA = [-58.4370, -34.6083]

// --- Estilo Barriopoli sobre el mapa base de Carto (fuente "protomaps", esquema Protomaps) ---

// Verde salvia: apagado para convivir con violeta, lila y durazno (no el verde saturado de los mapas).
export const COLOR_VERDE = '#cfe3c0'
export const COLOR_VERDE_BORDE = '#a8cc94'

// Edificios: lavanda claro; un tono más intenso para los que tienen altura real en OpenStreetMap.
const COLOR_EDIFICIO = '#ece6f4'
const COLOR_EDIFICIO_CON_ALTURA = '#ddd0ee'
// Solo ~6 % de los edificios de CABA traen altura en OSM: al resto se le da una casa de 3 pisos.
const ALTURA_POR_DEFECTO_M = 9

// Plazas y parques: en OSM muchas plazas porteñas son "playground" o "pitch", no solo "park".
const TIPOS_VERDES = [
    'park', 'garden', 'grass', 'grassland', 'meadow', 'recreation_ground', 'village_green',
    'playground', 'pitch', 'wood', 'forest', 'scrub', 'allotments', 'golf_course', 'heath', 'zoo',
]

/**
 * Aplica la paleta de marca al mapa base. Llamar en el evento 'load' de cada mapa.
 * Las capas se insertan entre las del mapa base (antes de los nombres), así que las capas
 * propias que se agregan después (máscara, contornos, pines) siempre quedan arriba.
 */
export const aplicarEstiloBarriopoli = (map) => {
    // Es decoración: si Carto cambia su estilo y algo falla, el mapa tiene que seguir funcionando.
    try {
        estilizar(map)
    } catch (error) {
        console.error('No se pudo aplicar el estilo Barriopoli al mapa base:', error)
    }
}

const estilizar = (map) => {
    const existe = (id) => Boolean(map.getLayer(id))

    // Parques y plazas en verde salvia.
    if (existe('landcover')) {
        map.setFilter('landcover', ['in', ['get', 'kind'], ['literal', TIPOS_VERDES]])
        map.setPaintProperty('landcover', 'fill-color', COLOR_VERDE)
        map.setPaintProperty('landcover', 'fill-opacity', ['interpolate', ['linear'], ['zoom'], 10, 0.6, 14, 1])
        map.addLayer({
            id: 'verdes-borde',
            type: 'line',
            source: 'protomaps',
            'source-layer': 'landuse',
            minzoom: 14,
            filter: ['in', ['get', 'kind'], ['literal', TIPOS_VERDES]],
            paint: { 'line-color': COLOR_VERDE_BORDE, 'line-width': ['interpolate', ['linear'], ['zoom'], 14, 0.5, 17, 1.5] },
        }, 'landuse_residential')
    }
    for (const id of ['park_national_park', 'park_nature_reserve']) {
        if (existe(id)) map.setPaintProperty(id, 'fill-color', COLOR_VERDE)
    }

    // Edificios en 3D: reemplazan a las dos capas planas de Carto.
    for (const id of ['building', 'building-top']) {
        if (existe(id)) map.setLayoutProperty(id, 'visibility', 'none')
    }
    // interpolate exige salidas de tipo number: coalesce(get) tipa como "value" y el addLayer falla.
    const altura = ['case', ['has', 'height'], ['to-number', ['get', 'height'], ALTURA_POR_DEFECTO_M], ALTURA_POR_DEFECTO_M]
    map.addLayer({
        id: 'edificios-3d',
        type: 'fill-extrusion',
        source: 'protomaps',
        'source-layer': 'buildings',
        minzoom: 13.5,
        filter: ['>=', ['coalesce', ['get', 'layer'], 0], 0],
        paint: {
            'fill-extrusion-color': ['case', ['has', 'height'], COLOR_EDIFICIO_CON_ALTURA, COLOR_EDIFICIO],
            // "Crecen" al acercarse: planos a zoom 14, altura completa a zoom 15.
            'fill-extrusion-height': ['interpolate', ['linear'], ['zoom'], 14, 0, 15, altura],
            'fill-extrusion-base': 0,
            'fill-extrusion-opacity': 0.9,
            'fill-extrusion-vertical-gradient': true,
        },
    }, existe('boundary_country_outline') ? 'boundary_country_outline' : undefined)
}
