<script setup>
import { computed, ref, watch } from 'vue'
import { InfiniteScroll, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import CategoriaChips from '@/components/CategoriaChips.vue'
import EventoDetalle from '@/components/EventoDetalle.vue'
import { formatearEvento } from '@/lib/fechas'

defineOptions({ layout: AppLayout })
const props = defineProps({
  eventos: { type: Object, required: true },
  categorias: { type: Array, required: true },
  filtros: { type: Object, required: true },
})

const seleccion = ref(props.filtros.categorias)
const activo = ref(null)
const hojaAbierta = computed({
  get: () => activo.value !== null,
  set: (valor) => { if (!valor) activo.value = null },
})

// reset: al cambiar el filtro, la lista se reemplaza en lugar de sumarse al scroll infinito.
watch(seleccion, (categorias) => {
  router.get('/eventos', { categorias }, { preserveState: true, preserveScroll: false, replace: true, reset: ['eventos'] })
})
</script>

<template>
  <div class="mx-auto max-w-2xl">
    <header class="sticky top-0 z-10 border-b border-gray-200 bg-white pt-[max(0.75rem,env(safe-area-inset-top))]">
      <h1 class="px-4 text-2xl font-bold">Eventos</h1>
      <CategoriaChips v-model="seleccion" :categorias="categorias" />
    </header>

    <InfiniteScroll data="eventos" :buffer="400">
      <ul class="space-y-3 p-4">
        <li v-for="evento in eventos.data" :key="evento.id">
          <button type="button" class="flex w-full gap-3 rounded-xl border p-3 text-left" @click="activo = evento">
            <img v-if="evento.imagen_url" :src="evento.imagen_url" alt="" loading="lazy" class="size-20 shrink-0 rounded-lg object-cover" />
            <span class="min-w-0">
              <span class="block text-sm font-bold" :style="{ color: evento.categoria.color }">{{ evento.categoria.icono }} {{ evento.categoria.nombre }}</span>
              <span class="block truncate font-bold">{{ evento.titulo }}</span>
              <span class="block text-sm text-gray-600">{{ formatearEvento(evento.inicio, evento.fin) }}</span>
              <span class="block truncate text-sm text-gray-600">{{ evento.barrio.nombre }}</span>
            </span>
          </button>
        </li>
      </ul>
      <p v-if="!eventos.data.length" class="p-6 text-center text-gray-600">No hay eventos con estos filtros.</p>
    </InfiniteScroll>

    <BottomSheet v-model="hojaAbierta" :titulo="activo?.titulo ?? ''">
      <EventoDetalle v-if="activo" :evento="activo" />
    </BottomSheet>
  </div>
</template>
