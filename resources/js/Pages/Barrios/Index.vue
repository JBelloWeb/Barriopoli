<script setup>
    import { computed } from 'vue'
    import { Link, usePage } from '@inertiajs/vue3'
    import AppLayout from '@/layouts/AppLayout.vue'
    import MapaLayout from '@/layouts/MapaLayout.vue'
    import FavoritoBoton from '@/components/FavoritoBoton.vue'
    import Icono from '@/components/ui/Icono.vue'
    import { nombreLegible } from '@/lib/texto'

    defineOptions({ layout: [AppLayout, MapaLayout] })

    const props = defineProps({ barrios: { type: Array, default: () => [] } })

    const page = usePage()

    const favoritos = computed(() => {
        const ids = page.props.favoritosIds ?? []
        return props.barrios
            .filter((b) => ids.includes(b.id))
            .sort((a, b) => (b.id === page.props.barrioPropioId) - (a.id === page.props.barrioPropioId))
    })
</script>

<template>
    <div class="pointer-events-auto mx-auto max-w-md rounded-tarjeta bg-white/95 p-4 shadow-flotante backdrop-blur">
        <template v-if="favoritos.length">
            <h1 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Tus barrios</h1>
            <ul class="-mx-4 mt-2 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none]">
                <li v-for="b in favoritos" :key="b.id" class="flex shrink-0 items-center rounded-full pl-4" :class="b.id === page.props.barrioPropioId ? 'bg-durazno-200' : 'bg-durazno-100'">
                    <Link :href="`/barrios/${b.slug}`" class="flex min-h-11 items-center gap-1.5 font-bold text-violeta-700">
                        <Icono v-if="b.id === page.props.barrioPropioId" nombre="casa" relleno class="size-4" />
                        <span v-if="b.id === page.props.barrioPropioId" class="sr-only">Mi barrio:</span>
                        {{ nombreLegible(b.nombre) }}
                    </Link>
                    <FavoritoBoton :barrio="b" />
                </li>
            </ul>
        </template>
        <template v-else>
            <h1 class="titulo-marca text-xl text-violeta-700">Elegí un barrio</h1>
            <p class="mt-0.5 text-sm text-niebla-600">
                Tocalo en el mapa o buscalo arriba.
                <template v-if="page.props.auth.user"> Con el corazón lo guardás en tus barrios.</template>
            </p>
        </template>

        <ul v-if="page.props.auth.user" class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-niebla-600">
            <li class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-durazno-200 ring-1 ring-lila-400"></span> Mi barrio</li>
            <li class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-durazno-100 ring-1 ring-lila-400"></span> Favoritos</li>
            <li class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-violeta-500 ring-1 ring-lila-400"></span> Otros barrios</li>
        </ul>
    </div>
</template>
