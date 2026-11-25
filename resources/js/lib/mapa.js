import * as maplibregl from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import 'maplibre-gl/dist/maplibre-gl.css';

maplibregl.setWorkerUrl(workerUrl)

export { maplibregl }
export const ESTILO_MAPA = 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json';
export const CENTRO_CABA = [-58.4370, -34.6083];

// Zonas verdes
export const COLOR_VERDE = '#cfe3c0'
export const COLOR_VERDE_BORDE = '#a8cc94'

const TIPOS_VERDES = [
    'park', 'garden', 'grass', 'grassland', 'meadow', 'recreation_ground', 'village_green',
    'playground', 'pitch', 'wood', 'forest', 'scrub', 'allotments', 'golf_course', 'heath', 'zoo',
]

// Edificios
const COLOR_EDIFICIO = '#ece6f4'
const COLOR_EDIFICIO_CON_ALTURA = '#ddd0ee'
const ALTURA_POR_DEFECTO_M = 9

export const aplicarEstiloBarriopoli = (map) => {
    try {
        estilizar(map)
    } catch (error) {
        console.error('No se pudo aplicar el estilo Barriopoli al mapa base:', error)
    }
}

const estilizar = (map) => {
const existe = (id) => Boolean(map.getLayer(id));

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

    for (const id of ['building', 'building-top']) {
        if (existe(id)) map.setLayoutProperty(id, 'visibility', 'none')
    }

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
            
            // Al acercarse aumenta el extrude
            'fill-extrusion-height': ['interpolate', ['linear'], ['zoom'], 14, 0, 15, altura],
            'fill-extrusion-base': 0,
            'fill-extrusion-opacity': 0.9,
            'fill-extrusion-vertical-gradient': true,
        },
    }, existe('boundary_country_outline') ? 'boundary_country_outline' : undefined)
}
