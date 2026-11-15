<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import MapaLayout from '@/layouts/MapaLayout.vue'
import FavoritoBoton from '@/components/FavoritoBoton.vue'

// Layouts anidados: AppLayout (barra inferior) envuelve a MapaLayout (mapa + buscador).
// Ambos persisten entre /barrios y /barrios/{slug}, así el mapa no se recarga.
defineOptions({ layout: [AppLayout, MapaLayout] })

const props = defineProps({ barrios: { type: Array, default: () => [] } })

const page = usePage()
// Mi barrio primero; el resto en orden alfabético (como llega la lista).
const favoritos = computed(() => {
    const ids = page.props.favoritosIds ?? []
    return props.barrios
        .filter((b) => ids.includes(b.id))
        .sort((a, b) => (b.id === page.props.barrioPropioId) - (a.id === page.props.barrioPropioId))
})
</script>

<template>
    <div class="pointer-events-auto mx-auto max-w-md rounded-xl bg-white/95 px-4 py-3 shadow-lg">
        <template v-if="favoritos.length">
            <h1 class="text-sm font-bold text-gray-600">Tus barrios</h1>
            <ul class="mt-2 flex gap-2 overflow-x-auto [scrollbar-width:none]">
                <li v-for="b in favoritos" :key="b.id" class="flex shrink-0 items-center rounded-full border border-gray-200 bg-white pl-3">
                    <Link :href="`/barrios/${b.slug}`" class="flex min-h-11 items-center font-bold">
                        <span v-if="b.id === page.props.barrioPropioId" class="mr-1" aria-label="Mi barrio">🏠</span>{{ b.nombre }}
                    </Link>
                    <FavoritoBoton :barrio="b" />
                </li>
            </ul>
        </template>
        <p v-else class="text-center">
            Elegí un barrio para explorarlo
            <span v-if="page.props.auth.user" class="block text-sm text-gray-600">Tocá ♡ para guardarlo en tus barrios.</span>
        </p>
    </div>
</template>
