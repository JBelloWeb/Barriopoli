<script setup>
import BarrioSelector from '@/Components/BarrioSelector.vue';
import { ref, onMounted } from 'vue';
import * as maplibregl from 'maplibre-gl'
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'
import { router } from '@inertiajs/vue3';

let map = null;
maplibregl.setWorkerUrl(workerUrl)
const contenedor = ref(null)

defineProps({
    barrios: {
        type: Array, 
        required: true
    } 
    });

const handleSelection = (barrio) => {
    router.visit(`/barrios/${barrio.slug}`);
};

onMounted(() =>{
    map = new maplibregl.Map({
        container: contenedor.value,
        style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
        center: [-58.3816, -34.6037],
        zoom: 11,
    })
})
</script>

<template>
    <div class="font-sans">
        <h1>Barriopoli - Elegí un barrio</h1>
        
        <BarrioSelector 
            :barrios="barrios"
            @selected="handleSelection"
        />

        <div ref="contenedor" style="width: 100%; height: 80vh;"></div>

    </div>
</template>