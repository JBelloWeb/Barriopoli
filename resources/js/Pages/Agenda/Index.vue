<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import EventoDetalle from '@/components/EventoDetalle.vue'
import { formatearDia, formatearEvento } from '@/lib/fechas'

defineOptions({ layout: AppLayout })
const props = defineProps({
  eventos: { type: Array, required: true },
  vista: { type: String, required: true },
  avisos: { type: Array, default: () => [] },
})

const activo = ref(null)
const hojaAbierta = computed({
  get: () => activo.value !== null,
  set: (valor) => { if (!valor) activo.value = null },
})

// Agrupado por día en hora de Buenos Aires (el orden ya viene del servidor).
const porDia = computed(() => {
  const grupos = new Map()
  for (const evento of props.eventos) {
    const dia = formatearDia(evento.inicio)
    if (!grupos.has(dia)) grupos.set(dia, [])
    grupos.get(dia).push(evento)
  }
  return [...grupos]
})

const cambiarVista = (vista) => router.get('/agenda', vista === 'pasados' ? { vista } : {}, { preserveState: true, replace: true })
const quitar = (id) => router.delete(`/agenda/${id}`, { preserveScroll: true })

const textoAviso = (aviso) => aviso.tipo === 'cancelado'
  ? `Se canceló «${aviso.titulo}».`
  : `Cambió el horario de «${aviso.titulo}»: ${formatearEvento(aviso.inicio, aviso.fin)}.`
</script>

<template>
  <div class="mx-auto max-w-2xl p-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Agenda</h1>
      <!-- <a>: es una descarga, no una página de Inertia -->
      <a v-if="vista === 'proximos' && eventos.some((e) => !e.cancelado)" href="/agenda/calendario.ics" class="min-h-11 py-2 underline">Exportar todo</a>
    </div>

    <ul v-if="avisos.length" class="mt-3 space-y-2">
      <li v-for="aviso in avisos" :key="aviso.id" class="rounded-lg bg-crema p-3" role="status">{{ textoAviso(aviso) }}</li>
    </ul>

    <div class="mt-3 grid grid-cols-2 rounded-lg bg-gray-100 p-1" role="tablist">
      <button
        v-for="[valor, texto] in [['proximos', 'Próximos'], ['pasados', 'Pasados']]"
        :key="valor"
        role="tab"
        :aria-selected="vista === valor"
        class="min-h-11 rounded-md"
        :class="vista === valor ? 'bg-white font-bold shadow' : ''"
        @click="cambiarVista(valor)"
      >
        {{ texto }}
      </button>
    </div>

    <section v-for="[dia, delDia] in porDia" :key="dia" class="mt-5">
      <h2 class="sticky top-0 z-10 bg-white py-1 font-bold capitalize">{{ dia }}</h2>
      <ul class="mt-1 space-y-2">
        <li v-for="evento in delDia" :key="evento.id" class="flex items-center gap-2 rounded-xl border p-3" :class="{ 'opacity-60': evento.cancelado }">
          <button type="button" class="min-w-0 flex-1 text-left" @click="activo = evento">
            <span class="block truncate font-bold" :class="{ 'line-through': evento.cancelado }">{{ evento.titulo }}</span>
            <span class="block text-sm text-gray-600">{{ formatearEvento(evento.inicio, evento.fin) }}</span>
            <span class="block truncate text-sm text-gray-600">{{ evento.barrio.nombre }}</span>
            <span v-if="evento.cancelado" class="text-sm font-bold text-red-700">Cancelado</span>
            <span v-else-if="evento.estado === 'pendiente'" class="text-sm font-bold">En revisión</span>
          </button>
          <button type="button" class="min-h-11 min-w-11 text-xl" :aria-label="`Quitar ${evento.titulo} de la agenda`" @click="quitar(evento.id)">×</button>
        </li>
      </ul>
    </section>

    <div v-if="!eventos.length" class="mt-10 text-center text-gray-600">
      <p>{{ vista === 'proximos' ? 'No tenés eventos próximos.' : 'Todavía no hay eventos pasados.' }}</p>
      <Link href="/eventos" class="mt-2 inline-block min-h-11 py-2 underline">Explorar eventos</Link>
    </div>

    <BottomSheet v-model="hojaAbierta" :titulo="activo?.titulo ?? ''">
      <EventoDetalle v-if="activo" :evento="activo" />
    </BottomSheet>
  </div>
</template>
