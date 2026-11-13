<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import MapaLayout from '@/layouts/MapaLayout.vue'

// El mapa vive en MapaLayout y lee barrio/eventosGeoJson de las props de la página.
defineOptions({ layout: [AppLayout, MapaLayout] })

defineProps({
    barrio: { type: Object, required: true },
    eventosGeoJson: { type: Object, default: () => ({ type: 'FeatureCollection', features: [] }) },
})
</script>

<template>
    <div class="pointer-events-auto mx-auto flex max-w-md items-center justify-between gap-3 rounded-xl bg-white/95 px-4 py-3 shadow-lg">
        <div>
            <h1 class="text-xl font-bold leading-tight">{{ barrio.nombre }}</h1>
            <p class="text-sm text-gray-600">
                Comuna {{ barrio.comuna }} · {{ eventosGeoJson.features.length }} {{ eventosGeoJson.features.length === 1 ? 'evento' : 'eventos' }}
            </p>
        </div>
    </div>
</template>
