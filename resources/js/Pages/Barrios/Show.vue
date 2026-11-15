<script setup>
import { usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import MapaLayout from '@/layouts/MapaLayout.vue'

// El mapa vive en MapaLayout y lee barrio/eventos de las props de la página.
defineOptions({ layout: [AppLayout, MapaLayout] })

defineProps({
    barrio: { type: Object, required: true },
    eventos: { type: Array, default: () => [] },
})

const page = usePage()
</script>

<template>
    <div class="pointer-events-auto mx-auto flex max-w-md items-center justify-between gap-3 rounded-xl bg-white/95 px-4 py-3 shadow-lg">
        <div>
            <h1 class="text-xl font-bold leading-tight">{{ barrio.nombre }}</h1>
            <p class="text-sm text-gray-600">
                Comuna {{ barrio.comuna }} · {{ eventos.length }} {{ eventos.length === 1 ? 'evento' : 'eventos' }}
            </p>
        </div>
        <Link
            v-if="page.props.auth.puede.publicar"
            href="/eventos/crear"
            class="flex size-12 shrink-0 items-center justify-center rounded-full bg-violeta-oscuro text-3xl text-white shadow"
            aria-label="Publicar evento"
        >+</Link>
    </div>
</template>
