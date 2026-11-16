<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Isotipo from '@/components/marca/Isotipo.vue'
import Onda from '@/components/marca/Onda.vue'
import Tarjeta from '@/components/ui/Tarjeta.vue'

defineProps({
  titulo: { type: String, required: true },
  subtitulo: { type: String, default: '' },
})
const page = usePage()
const estado = computed(() => page.props.status)
</script>

<template>
  <div class="relative min-h-dvh overflow-hidden bg-papel">
    <!-- Recursos de las portadas: onda arriba a la izquierda (y abajo a la derecha en desktop). -->
    <Onda class="pointer-events-none absolute -left-6 -top-6 w-32 text-violeta-500 md:w-56" />
    <Onda espejo class="pointer-events-none absolute -bottom-6 -right-6 hidden w-56 text-violeta-500 md:block" />

    <main class="relative mx-auto flex min-h-dvh w-full max-w-sm flex-col justify-center px-4 pb-10 pt-24 md:pt-10">
      <Link href="/barrios" class="flex flex-col items-center gap-2 self-center rounded-3xl text-violeta-500" aria-label="Barriopoli, ir al mapa">
        <Isotipo class="h-16 w-auto" />
        <span class="titulo-marca text-4xl">Barriopoli</span>
      </Link>
      <p class="mt-3 self-center"><span class="pastilla text-sm">Tu barrio, más cerca.</span></p>

      <Tarjeta class="mt-8 p-5!">
        <h1 class="titulo-marca text-2xl text-violeta-700">{{ titulo }}</h1>
        <p v-if="subtitulo" class="mt-0.5 text-sm font-bold text-niebla-600">{{ subtitulo }}</p>
        <p v-if="estado" role="status" class="mt-4 rounded-2xl bg-durazno-100 p-3 text-sm font-semibold">{{ estado }}</p>
        <div class="mt-5"><slot /></div>
      </Tarjeta>

      <div v-if="$slots.pie" class="mt-6 text-center"><slot name="pie" /></div>
    </main>
  </div>
</template>
