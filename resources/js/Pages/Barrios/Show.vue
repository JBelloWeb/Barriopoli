<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import * as maplibregl from 'maplibre-gl'
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'
import { bbox, mask } from '@turf/turf'

const props = defineProps({
    barrio: { type: Object, required: true },
    eventosGeoJson: { type: Object, default: () => ({ type: 'FeatureCollection', features: [] }) }
})

maplibregl.setWorkerUrl(workerUrl)

const contenedor = ref(null)
let map = null

// Helper para parsear GeoJSON si llega desde Laravel como JSON String
const parseGeoJson = (data) => {
    if (!data) return null
    return typeof data === 'string' ? JSON.parse(data) : data
}

const cargarCapas = () => {
    if (!map) return

    const barrioGeoJson = parseGeoJson(props.barrio.geojson)
    const eventosData = parseGeoJson(props.eventosGeoJson)

    if (!barrioGeoJson) return

    // 1. Capas del Barrio y Máscara
    map.addSource('barrio', { type: 'geojson', data: barrioGeoJson })
    map.addSource('mascara', { type: 'geojson', data: mask(barrioGeoJson) })

    map.addLayer({
        id: 'mascara-exteriores',
        type: 'fill',
        source: 'mascara',
        paint: { 'fill-color': '#8e66cc', 'fill-opacity': 0.5 },
    })
    map.addLayer({
        id: 'barrio-relleno',
        type: 'fill',
        source: 'barrio',
        paint: { 'fill-color': '#ffdeb6', 'fill-opacity': 0.15 },
    })
    map.addLayer({
        id: 'barrio-borde',
        type: 'line',
        source: 'barrio',
        paint: { 'line-color': '#ffdeb6', 'line-width': 3 },
    })

    // 2. Capas de Eventos (Puntos interactivos)
    if (eventosData && eventosData.features && eventosData.features.length > 0) {
        map.addSource('eventos', {
            type: 'geojson',
            data: eventosData
        })

        // Borde blanco exterior
        map.addLayer({
            id: 'eventos-circulo-borde',
            type: 'circle',
            source: 'eventos',
            paint: {
                'circle-radius': 9,
                'circle-color': '#ffffff',
                'circle-opacity': 0.9
            }
        })

        // Punto rojo interior
        map.addLayer({
            id: 'eventos-puntos',
            type: 'circle',
            source: 'eventos',
            paint: {
                'circle-radius': 6,
                'circle-color': '#ef4444',
                'circle-stroke-width': 1,
                'circle-stroke-color': '#ffffff'
            }
        })

        // Popup interactivo
map.on('click', 'eventos-puntos', (e) => {
    const feature = e.features[0]
    const coordinates = feature.geometry.coordinates.slice()

    // 1. Extraer 'id' de properties o de la feature misma
    const { id, titulo, direccion, fecha } = feature.properties
    const eventoId = id || feature.id

    // Corrección para evitar desfasajes en mapas continuos
    while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
        coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360
    }

    // 2. Renderizar Popup usando 'eventoId'
    new maplibregl.Popup()
        .setLngLat(coordinates)
        .setHTML(`
            <div style="font-family: system-ui, -apple-system, sans-serif; padding: 4px;">
                <strong style="font-size: 14px; color: #1f2937;">${titulo ?? 'Evento'}</strong><br>
                <span style="font-size: 12px; color: #4b5563;">${direccion ?? ''}</span><br>
                <small style="font-size: 11px; color: #9ca3af;">${fecha ?? ''}</small><br>
                ${eventoId ? `<a href="/eventos/${eventoId}/edit" style="color: #2563eb; font-size: 12px; font-weight: bold; text-decoration: underline; display: inline-block; margin-top: 6px;">✏️ Editar evento</a>` : ''}
            </div>
        `)
        .addTo(map)
})

        map.on('mouseenter', 'eventos-puntos', () => { map.getCanvas().style.cursor = 'pointer' })
        map.on('mouseleave', 'eventos-puntos', () => { map.getCanvas().style.cursor = '' })
    }

    // Centrar mapa en el barrio
    map.fitBounds(bbox(barrioGeoJson), { padding: 50, duration: 0 })
}

onMounted(() => {
    map = new maplibregl.Map({
        container: contenedor.value,
        style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
        center: [-58.3816, -34.6037],
        zoom: 11,
    })

    map.on('load', cargarCapas)
})

// Soporte para navegación Inertia sin recargar la página
watch(() => props.barrio, () => {
    if (map && map.isStyleLoaded()) {
        if (map.getLayer('eventos-puntos')) map.removeLayer('eventos-puntos')
        if (map.getLayer('eventos-circulo-borde')) map.removeLayer('eventos-circulo-borde')
        if (map.getSource('eventos')) map.removeSource('eventos')
        if (map.getLayer('barrio-borde')) map.removeLayer('barrio-borde')
        if (map.getLayer('barrio-relleno')) map.removeLayer('barrio-relleno')
        if (map.getLayer('mascara-exteriores')) map.removeLayer('mascara-exteriores')
        if (map.getSource('barrio')) map.removeSource('barrio')
        if (map.getSource('mascara')) map.removeSource('mascara')

        cargarCapas()
    }
})

onBeforeUnmount(() => {
    if (map) {
        map.remove()
        map = null
    }
})
</script>

<template>
    <div class="p-4">
        <a href="/barrios" class="inline-block mb-2 text-blue-600 hover:underline">← Volver</a>
        <h1 class="text-2xl font-bold mb-4">{{ barrio.nombre }} (Comuna {{ barrio.comuna }})</h1>
        <div ref="contenedor" class="w-full h-[80vh] rounded-lg shadow border"></div>
    </div>
</template>