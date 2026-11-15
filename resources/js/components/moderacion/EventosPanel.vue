<script setup>
import { router } from '@inertiajs/vue3'
import EventoDetalle from '@/components/EventoDetalle.vue'

defineProps({ eventos: { type: Array, required: true } })

const aprobar = (id) => router.post(`/moderacion/eventos/${id}/aprobar`, {}, { preserveScroll: true })
const rechazar = (id) => {
  const motivo = window.prompt('Motivo del rechazo')
  if (motivo) router.post(`/moderacion/eventos/${id}/rechazar`, { motivo }, { preserveScroll: true })
}
</script>

<template>
  <ul class="mt-4 space-y-3">
    <li v-for="evento in eventos" :key="evento.id" class="rounded-xl border p-4">
      <h2 class="mb-2 text-lg font-bold">{{ evento.titulo }}</h2>
      <EventoDetalle :evento="evento" />
      <div class="mt-3 flex gap-2">
        <button class="min-h-11 flex-1 rounded-lg bg-violeta-oscuro font-bold text-white" @click="aprobar(evento.id)">Aprobar</button>
        <button class="min-h-11 flex-1 rounded-lg border" @click="rechazar(evento.id)">Rechazar</button>
      </div>
    </li>
    <li v-if="!eventos.length" class="text-gray-600">No hay eventos pendientes.</li>
  </ul>
</template>
