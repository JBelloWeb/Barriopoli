<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import * as maplibregl from 'maplibre-gl'
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'
import { bbox, mask } from '@turf/turf'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'

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

// Custom Layer para cargar pines 3D con Three.js
const ALTURA_PIN_M = 40
const BASE_ALTITUD_M = 10  // sube respecto a los 40 m de la extrusión para evitar z-fighting
const COLLIDER_RADIO_M = 28      // ancho clicable (el pin mide ~40 m de ancho)
const COLLIDER_EXTRA_M = 25      // cuánto sobresale por arriba y por abajo del pin

const crearCapaEventos3D = (features, modelUrl) => {
    let camera, scene, renderer, mapInstance
    const items = []
    const rotX = new THREE.Matrix4().makeRotationX(Math.PI / 2)

    return {
        id: 'eventos-3d',
        type: 'custom',
        renderingMode: '3d',

        onAdd(mapCtx, gl) {
            mapInstance = mapCtx
            camera = new THREE.Camera()
            scene = new THREE.Scene()

            scene.add(new THREE.AmbientLight(0xffffff, 2.5))
            const dir = new THREE.DirectionalLight(0xffffff, 2.0)
            dir.position.set(0, -70, 100).normalize()
            scene.add(dir)

            renderer = new THREE.WebGLRenderer({
                canvas: mapCtx.getCanvas(),
                context: gl,
                antialias: true
            })
            renderer.autoClear = false

            new GLTFLoader().load(
                modelUrl,
                (gltf) => {
                    const box = new THREE.Box3().setFromObject(gltf.scene)
                    const size = box.getSize(new THREE.Vector3())
                    const center = box.getCenter(new THREE.Vector3())
                    const k = ALTURA_PIN_M / (size.y || 1)

                    features.forEach((f) => {
                        const [lng, lat] = f.geometry.coordinates
                        const mc = maplibregl.MercatorCoordinate.fromLngLat([lng, lat], BASE_ALTITUD_M)
                        const s = mc.meterInMercatorCoordinateUnits() * k

                        const pin = gltf.scene.clone(true)
                        pin.position.set(-center.x, -box.min.y, -center.z)

                        // rotator: gira el pin sobre su propio eje vertical
                        const rotator = new THREE.Group()
                        rotator.add(pin)

                        const group = new THREE.Group()
                        group.add(rotator)
                        group.visible = false
                        group.traverse((o) => { o.frustumCulled = false })
                        scene.add(group)

                        // T · S · R en doble precisión (orden del ejemplo oficial)
                        const transform = new THREE.Matrix4()
                            .makeTranslation(mc.x, mc.y, mc.z)
                            .scale(new THREE.Vector3(s, -s, s))
                            .multiply(rotX)

                        items.push({ group, rotator, transform })
                    })
                    mapInstance.triggerRepaint()
                },
                undefined,
                (err) => console.error('Error cargando el modelo 3D:', err)
            )
        },

        render(gl, args) {
            if (!items.length) return

            const mat = (Array.isArray(args) || ArrayBuffer.isView(args))
                ? args
                : (args.defaultProjectionData?.mainMatrix ?? args.modelViewProjectionMatrix)

            const mapMatrix = new THREE.Matrix4().fromArray(mat)
            const bearing = (mapInstance.getBearing() * Math.PI) / 180

            renderer.resetState()
            for (const it of items) {
                it.rotator.rotation.y = bearing
                camera.projectionMatrix.copy(mapMatrix).multiply(it.transform)
                it.group.visible = true
                renderer.render(scene, camera)
                it.group.visible = false
            }
        },

        onRemove() {
            items.length = 0
            scene?.clear()
            renderer?.dispose()
        }
    }
}

const cargarCapas = () => {
    if (!map) return

    const barrioGeoJson = parseGeoJson(props.barrio.geojson)
    const eventosData = parseGeoJson(props.eventosGeoJson)

    if (!barrioGeoJson) return


    const barrioBbox = bbox(barrioGeoJson);
    const padding = 0.015
    const maxBounds = [
        [barrioBbox[0] - padding, barrioBbox[1] - padding], //Suroeste [lng, lat]
        [barrioBbox[2] + padding, barrioBbox[3] + padding] //Noreste [lng, lat]
    ]
    
    map.setMaxBounds(maxBounds);

    // 1. Capas del Barrio y Máscara
    map.addSource('barrio', { type: 'geojson', data: barrioGeoJson })
    map.addSource('mascara', { type: 'geojson', data: mask(barrioGeoJson) })

    map.addLayer({
        id: 'mascara-exteriores',
        type: 'fill',
        source: 'mascara',
        paint: { 'fill-color': '#8e66cc', 'fill-opacity': 0.9 },
    })
    map.addLayer({
        id: 'barrio-3d-extrusion',
        type: 'fill-extrusion',
        source: 'barrio',
        paint: {
            'fill-extrusion-color': '#ffdeb6', // Color de resalte
            'fill-extrusion-height': 10,        // Altura en metros[cite: 2]
            'fill-extrusion-base': 0,
            'fill-extrusion-opacity': 0.15
        }
    })
    map.addLayer({
        id: 'barrio-borde',
        type: 'line',
        source: 'barrio',
        paint: { 'line-color': '#ffdeb6', 'line-width': 3 },
    })

    // 2. Capas de Eventos (Puntos interactivos)
    if (eventosData?.features?.length > 0) {
    map.addLayer(crearCapaEventos3D(eventosData.features, '/models/pin.glb'))

        // Capa invisible para recibir los clics sobre el pin 3D
        map.addSource('eventos', { type: 'geojson', data: eventosData })
        map.addLayer({
            id: 'eventos-puntos',
            type: 'circle',
            source: 'eventos',
            paint: {
                'circle-radius': 18,
                'circle-opacity': 0
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
        zoom: 13,
        pitch: 50,
        bearing: -10,
        minZoom: 12,
        maxZoom: 18,
        canvasContextAttributes: { antialias: true },
    })

    map.on('load', cargarCapas)
})

// Soporte para navegación Inertia sin recargar la página
watch(() => props.barrio, () => {
    if (map && map.isStyleLoaded()) {
        // Remover capas de modelos 3D dinámicos
        const layers = map.getStyle().layers
        if (map.getLayer('eventos-3d')) map.removeLayer('eventos-3d')
if (map.getLayer('eventos-puntos')) map.removeLayer('eventos-puntos')
if (map.getSource('eventos')) map.removeSource('eventos')
        
        // Limpiar capas del barrio
        if (map.getLayer('barrio-3d-extrusion')) map.removeLayer('barrio-3d-extrusion')
        if (map.getLayer('barrio-borde')) map.removeLayer('barrio-borde')
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