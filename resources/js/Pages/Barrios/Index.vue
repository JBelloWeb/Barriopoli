<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import MapaLayout from '@/layouts/MapaLayout.vue'

// Layouts anidados: AppLayout (barra inferior) envuelve a MapaLayout (mapa + buscador).
// Ambos persisten entre /barrios y /barrios/{slug}, así el mapa no se recarga.
defineOptions({ layout: [AppLayout, MapaLayout] })
</script>

<template>
    <p class="pointer-events-auto mx-auto max-w-md rounded-xl bg-white/95 px-4 py-3 text-center shadow-lg">
        Elegí un barrio para explorarlo
    </p>
</template>
