<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { formatearEvento } from '@/lib/fechas'

defineOptions({ layout: AppLayout })
defineProps({ eventos: { type: Array, required: true } })

const page = usePage()

const estados = {
  pendiente: { texto: 'En revisión', clase: 'bg-crema' },
  publicado: { texto: 'Publicado', clase: 'bg-green-100' },
  rechazado: { texto: 'Rechazado', clase: 'bg-red-100' },
}
</script>

<template>
  <div class="mx-auto max-w-2xl p-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">{{ page.props.auth.puede.moderar ? 'Todos los eventos' : 'Mis eventos' }}</h1>
      <Link href="/eventos/crear" class="flex min-h-11 items-center rounded-lg bg-violeta-oscuro px-4 font-bold text-white">Nuevo</Link>
    </div>

    <ul class="mt-4 space-y-3">
      <li v-for="evento in eventos" :key="evento.id" class="rounded-xl border p-3">
        <span class="rounded-full px-2 py-0.5 text-sm" :class="estados[evento.estado].clase">{{ estados[evento.estado].texto }}</span>
        <h2 class="mt-1 font-bold">{{ evento.titulo }}</h2>
        <p class="text-sm text-gray-600">{{ formatearEvento(evento.inicio, evento.fin) }} · {{ evento.barrio.nombre }}</p>
        <p v-if="evento.motivo_rechazo" class="mt-1 text-sm text-red-700">Motivo: {{ evento.motivo_rechazo }}</p>
        <div class="mt-2 flex gap-4">
          <Link :href="`/eventos/${evento.id}`" class="min-h-11 py-2 underline">Ver</Link>
          <Link :href="`/eventos/${evento.id}/editar`" class="min-h-11 py-2 underline">Editar</Link>
          <Link :href="`/eventos/${evento.id}/duplicar`" class="min-h-11 py-2 underline">Duplicar</Link>
        </div>
      </li>
      <li v-if="!eventos.length" class="text-gray-600">Todavía no publicaste eventos.</li>
    </ul>
  </div>
</template>
