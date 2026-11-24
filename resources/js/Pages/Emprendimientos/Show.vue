<script setup>
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import EventoDetalle from '@/components/EventoDetalle.vue'
import EventoTarjeta from '@/components/EventoTarjeta.vue'
import Onda from '@/components/marca/Onda.vue'
import EstadoVacio from '@/components/ui/EstadoVacio.vue'
import Icono from '@/components/ui/Icono.vue'

defineOptions({ layout: AppLayout })
const props = defineProps({
  emprendimiento: { type: Object, required: true },
  eventos: { type: Array, default: () => [] },
})

const activo = ref(null)
const hojaAbierta = computed({
  get: () => activo.value !== null,
  set: (valor) => { if (!valor) activo.value = null },
})
const cantidad = computed(() => props.eventos.length === 1 ? '1 evento próximo' : `${props.eventos.length} eventos próximos`)
</script>

<template>
  <div class="pb-10">
    <header class="relative overflow-hidden rounded-b-[2rem] bg-violeta-600 px-4 pb-8 pt-[max(2.5rem,env(safe-area-inset-top))] text-papel">
      <Onda espejo class="pointer-events-none absolute -right-6 -top-6 w-40 text-violeta-400 md:w-56" />
      <div class="relative mx-auto max-w-3xl">
        <p class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider">
          <Icono nombre="tienda" class="size-5" /> Emprendimiento
        </p>
        <h1 class="titulo-marca mt-1 text-3xl text-durazno-200 md:text-4xl">{{ emprendimiento.nombre }}</h1>
        <p class="mt-1 font-semibold">{{ cantidad }}</p>
      </div>
    </header>

    <ul v-if="eventos.length" class="mx-auto grid max-w-3xl gap-4 p-4 sm:grid-cols-2 md:px-8">
      <li v-for="evento in eventos" :key="evento.id" class="flex">
        <EventoTarjeta :evento="evento" class="w-full" @abrir="activo = $event" />
      </li>
    </ul>
    <EstadoVacio v-else titulo="Sin fechas por ahora" texto="No tiene eventos próximos." />

    <BottomSheet v-model="hojaAbierta" :titulo="activo?.titulo ?? ''">
      <EventoDetalle v-if="activo" :evento="activo" />
    </BottomSheet>
  </div>
</template>
