<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
defineProps({ solicitudes: { type: Array, default: () => [] } })

const pestana = ref('solicitudes')

const aprobar = (id) => router.post(`/moderacion/solicitudes/${id}/aprobar`, {}, { preserveScroll: true })
const rechazar = (id) => {
  const motivo = window.prompt('Motivo del rechazo')
  if (motivo) router.post(`/moderacion/solicitudes/${id}/rechazar`, { motivo }, { preserveScroll: true })
}
</script>

<template>
  <div class="mx-auto max-w-2xl p-4">
    <h1 class="text-2xl font-bold">Moderación</h1>

    <div class="mt-3 flex gap-2 overflow-x-auto" role="tablist">
      <button role="tab" :aria-selected="pestana === 'solicitudes'" class="min-h-11 rounded-full px-4" :class="pestana === 'solicitudes' ? 'bg-violeta-oscuro text-white' : 'bg-gray-100'" @click="pestana = 'solicitudes'">
        Solicitudes ({{ solicitudes.length }})
      </button>
      <!-- PLAN-02: pestaña Categorías. PLAN-03: pestaña Eventos. -->
    </div>

    <ul v-if="pestana === 'solicitudes'" class="mt-4 space-y-3">
      <li v-for="s in solicitudes" :key="s.id" class="rounded-xl border p-4">
        <h2 class="font-bold">{{ s.nombre_emprendimiento }}</h2>
        <p class="text-sm text-gray-600">{{ s.user.name }} · {{ s.user.email }}</p>
        <p class="mt-2 whitespace-pre-line">{{ s.descripcion }}</p>
        <a v-if="s.enlace" :href="s.enlace" target="_blank" rel="noopener noreferrer" class="underline">{{ s.enlace }}</a>
        <div class="mt-3 flex gap-2">
          <button class="min-h-11 flex-1 rounded-lg bg-violeta-oscuro font-bold text-white" @click="aprobar(s.id)">Aprobar</button>
          <button class="min-h-11 flex-1 rounded-lg border" @click="rechazar(s.id)">Rechazar</button>
        </div>
      </li>
      <li v-if="!solicitudes.length" class="text-gray-600">No hay solicitudes pendientes.</li>
    </ul>
  </div>
</template>
