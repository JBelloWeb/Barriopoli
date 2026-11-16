<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import EventoDetalle from '@/components/EventoDetalle.vue'
import EventoTarjeta from '@/components/EventoTarjeta.vue'
import Boton from '@/components/ui/Boton.vue'
import EstadoVacio from '@/components/ui/EstadoVacio.vue'
import Icono from '@/components/ui/Icono.vue'
import Insignia from '@/components/ui/Insignia.vue'
import { formatearEvento } from '@/lib/fechas'

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

// Encabezado de día largo ("Sábado 5 de diciembre") en hora de Buenos Aires.
const diaLargo = new Intl.DateTimeFormat('es-AR', { timeZone: 'America/Argentina/Buenos_Aires', weekday: 'long', day: 'numeric', month: 'long' })
const tituloDia = (iso) => {
  const texto = diaLargo.format(new Date(iso)).replace(',', '')
  return texto.charAt(0).toUpperCase() + texto.slice(1)
}

// Agrupado por día (el orden ya viene del servidor).
const porDia = computed(() => {
  const grupos = new Map()
  for (const evento of props.eventos) {
    const dia = tituloDia(evento.inicio)
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

const vistas = [['proximos', 'Próximos'], ['pasados', 'Pasados']]
</script>

<template>
  <div class="mx-auto max-w-2xl px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))] md:px-8">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="titulo-marca text-3xl text-violeta-700 md:text-4xl">Agenda</h1>
        <p class="mt-0.5 font-bold text-niebla-600">Tus planes en el barrio</p>
      </div>
      <!-- Es una descarga, no una página de Inertia. -->
      <Boton
        v-if="vista === 'proximos' && eventos.some((e) => !e.cancelado)"
        href="/agenda/calendario.ics"
        externo
        variante="secundario"
        icono="descarga"
        chico
      >Exportar todo</Boton>
    </header>

    <ul v-if="avisos.length" class="mt-5 space-y-2">
      <li
        v-for="aviso in avisos"
        :key="aviso.id"
        role="status"
        class="flex items-start gap-3 rounded-2xl border-l-4 bg-white p-4 shadow-tarjeta"
        :class="aviso.tipo === 'cancelado' ? 'border-error' : 'border-durazno-400'"
      >
        <span class="grid size-9 shrink-0 place-items-center rounded-full" :class="aviso.tipo === 'cancelado' ? 'bg-error/10 text-error' : 'bg-durazno-100 text-violeta-700'">
          <Icono :nombre="aviso.tipo === 'cancelado' ? 'alerta' : 'campana'" class="size-5" />
        </span>
        <span class="pt-1.5 font-semibold">{{ textoAviso(aviso) }}</span>
      </li>
    </ul>

    <div class="mt-5 grid grid-cols-2 rounded-full bg-niebla-100 p-1" role="tablist" aria-label="Período">
      <button
        v-for="[valor, texto] in vistas"
        :key="valor"
        type="button"
        role="tab"
        :aria-selected="vista === valor"
        class="min-h-11 rounded-full font-bold transition motion-reduce:transition-none"
        :class="vista === valor ? 'bg-white text-violeta-700 shadow-sm' : 'text-niebla-600 hover:text-violeta-700'"
        @click="cambiarVista(valor)"
      >
        {{ texto }}
      </button>
    </div>

    <section v-for="[dia, delDia] in porDia" :key="dia" class="mt-6">
      <h2 class="sticky top-0 z-10 -mx-4 bg-papel/95 px-4 py-2 text-sm font-bold text-niebla-600 backdrop-blur md:-mx-8 md:px-8">{{ dia }}</h2>
      <ul class="mt-1 space-y-3">
        <li v-for="evento in delDia" :key="evento.id">
          <EventoTarjeta :evento="evento" compacta @abrir="activo = $event">
            <template #accion>
              <Boton variante="icono" icono="cerrar" class="size-11!" :aria-label="`Quitar ${evento.titulo} de la agenda`" @click="quitar(evento.id)" />
            </template>
          </EventoTarjeta>
          <Insignia v-if="!evento.cancelado && evento.estado === 'pendiente'" tono="pendiente" class="mt-1">En revisión</Insignia>
        </li>
      </ul>
    </section>

    <EstadoVacio
      v-if="!eventos.length"
      :texto="vista === 'proximos' ? 'Todavía no agendaste eventos próximos.' : 'Todavía no hay eventos pasados.'"
    >
      <Boton href="/eventos" variante="suave" icono="lista">Explorar eventos</Boton>
    </EstadoVacio>

    <BottomSheet v-model="hojaAbierta" :titulo="activo?.titulo ?? ''">
      <EventoDetalle v-if="activo" :evento="activo" />
    </BottomSheet>
  </div>
</template>
