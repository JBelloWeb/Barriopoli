<script setup>
// URL propia del evento: permite compartirlo.
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import EventoDetalle from '@/components/EventoDetalle.vue'

defineOptions({ layout: AppLayout })
defineProps({ evento: { type: Object, required: true } })
</script>

<template>
  <div class="mx-auto max-w-lg p-5">
    <div class="flex gap-4">
      <Link href="/eventos" class="inline-block min-h-11 py-2 underline">← Eventos</Link>
      <Link :href="`/barrios/${evento.barrio.slug}`" class="inline-block min-h-11 py-2 underline">Ver en el mapa</Link>
    </div>
    <h1 class="mb-3 text-2xl font-bold">{{ evento.titulo }}</h1>
    <p v-if="evento.estado !== 'publicado'" class="mb-3 rounded-lg bg-crema px-3 py-2 text-sm">
      {{ evento.estado === 'pendiente' ? 'En revisión: todavía no es público.' : 'Rechazado: no es público.' }}
    </p>
    <EventoDetalle :evento="evento" />
  </div>
</template>
