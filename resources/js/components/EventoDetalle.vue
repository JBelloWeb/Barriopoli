<script setup>
// Vue escapa todo el contenido: reemplaza al popup con setHTML (XSS).
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { formatearEvento } from '@/lib/fechas'

const props = defineProps({ evento: { type: Object, required: true } })
const fecha = computed(() => formatearEvento(props.evento.inicio, props.evento.fin))
</script>

<template>
  <article>
    <img v-if="evento.imagen_url" :src="evento.imagen_url" :alt="evento.titulo" loading="lazy" class="mb-3 aspect-video w-full rounded-xl object-cover" />
    <p v-if="evento.cancelado" class="mb-2 rounded-lg bg-red-100 px-3 py-1 font-bold text-red-800">Cancelado</p>
    <p class="font-bold" :style="{ color: evento.categoria.color }">{{ evento.categoria.icono }} {{ evento.categoria.nombre }}</p>
    <p class="mt-1 font-bold">{{ fecha }}</p>
    <p class="text-gray-600">{{ evento.direccion }} · {{ evento.barrio.nombre }}</p>
    <p v-if="evento.organizador" class="mt-1 text-sm text-gray-600">Organiza {{ evento.organizador }}</p>
    <p v-if="evento.descripcion" class="mt-3 whitespace-pre-line">{{ evento.descripcion }}</p>
    <!-- PLAN-05: <AgendaBoton :evento="evento" /> -->
    <div class="mt-3 flex gap-4">
      <Link :href="`/eventos/${evento.id}`" class="min-h-11 py-2 underline">Ver página</Link>
      <Link v-if="evento.editable" :href="`/eventos/${evento.id}/editar`" class="min-h-11 py-2 underline">Editar</Link>
    </div>
  </article>
</template>
