// Configuración única de MapLibre: worker para Vite (ver aprendizaje maplibre-v6-worker-vite) y estilo base.
import * as maplibregl from 'maplibre-gl'
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'

maplibregl.setWorkerUrl(workerUrl)

export { maplibregl }
export const ESTILO_MAPA = 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json'
export const CENTRO_CABA = [-58.4370, -34.6083]
