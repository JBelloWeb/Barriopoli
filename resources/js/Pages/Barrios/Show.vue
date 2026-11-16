<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import MapaLayout from '@/layouts/MapaLayout.vue'
import FavoritoBoton from '@/components/FavoritoBoton.vue'
import MiBarrioBoton from '@/components/MiBarrioBoton.vue'
import SiluetaBarrio from '@/components/marca/SiluetaBarrio.vue'
import Icono from '@/components/ui/Icono.vue'
import { nombreLegible } from '@/lib/texto'

// El mapa vive en MapaLayout y lee barrio/eventos de las props de la página.
defineOptions({ layout: [AppLayout, MapaLayout] })

const props = defineProps({
    barrio: { type: Object, required: true },
    eventos: { type: Array, default: () => [] },
})

const page = usePage()
const nombre = computed(() => nombreLegible(props.barrio.nombre))
</script>

<template>
    <div class="pointer-events-auto relative mx-auto max-w-md rounded-tarjeta bg-white/95 p-4 shadow-flotante backdrop-blur">
        <div class="flex items-center gap-3">
            <SiluetaBarrio :key="barrio.id" :geojson="barrio.geojson" :contornos="3" :separacion="0.15" class="size-14 shrink-0" />
            <div class="min-w-0 flex-1">
                <h1 class="titulo-marca truncate text-2xl text-violeta-700">{{ nombre }}</h1>
                <p class="text-sm font-semibold text-niebla-600">
                    Comuna {{ barrio.comuna }} · {{ eventos.length }} {{ eventos.length === 1 ? 'evento' : 'eventos' }}
                </p>
            </div>
            <Link
                v-if="page.props.auth.puede.publicar"
                href="/eventos/crear"
                class="grid size-14 shrink-0 place-items-center rounded-full bg-violeta-700 text-durazno-200 shadow-flotante transition hover:bg-violeta-800 active:scale-95 motion-reduce:transition-none"
                :aria-label="`Publicar un evento en ${nombre}`"
            >
                <Icono nombre="mas" class="size-7" />
            </Link>
        </div>
        <div v-if="page.props.auth.user" class="mt-3 flex items-center gap-2">
            <MiBarrioBoton :barrio="barrio" />
            <FavoritoBoton :barrio="barrio" />
        </div>
    </div>
</template>
