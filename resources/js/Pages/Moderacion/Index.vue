<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CategoriasPanel from '@/components/moderacion/CategoriasPanel.vue'
import EventosPanel from '@/components/moderacion/EventosPanel.vue'
import MotivoRechazo from '@/components/moderacion/MotivoRechazo.vue'
import Boton from '@/components/ui/Boton.vue'
import EstadoVacio from '@/components/ui/EstadoVacio.vue'
import Insignia from '@/components/ui/Insignia.vue'
import Tarjeta from '@/components/ui/Tarjeta.vue'

defineOptions({ layout: AppLayout })
const props = defineProps({
  solicitudes: { type: Array, default: () => [] },
  categorias: { type: Array, default: () => [] },
  eventosPendientes: { type: Array, default: () => [] },
})

const pestana = ref(props.solicitudes.length || !props.eventosPendientes.length ? 'solicitudes' : 'eventos')
const pestanas = [
  { valor: 'solicitudes', texto: 'Solicitudes', cantidad: () => props.solicitudes.length },
  { valor: 'eventos', texto: 'Eventos', cantidad: () => props.eventosPendientes.length },
  { valor: 'categorias', texto: 'Categorías', cantidad: () => props.categorias.length },
]

const aprobar = (id) => router.post(`/moderacion/solicitudes/${id}/aprobar`, {}, { preserveScroll: true })

const rechazando = ref(null)
const hojaRechazo = ref(false)
const procesando = ref(false)
const pedirRechazo = (id) => {
  rechazando.value = id
  hojaRechazo.value = true
}
const rechazar = (motivo) => router.post(`/moderacion/solicitudes/${rechazando.value}/rechazar`, { motivo }, {
  preserveScroll: true,
  onStart: () => { procesando.value = true },
  onFinish: () => { procesando.value = false },
  onSuccess: () => { hojaRechazo.value = false },
})
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))] md:px-8">
    <h1 class="titulo-marca text-3xl text-violeta-700 md:text-4xl">Moderación</h1>
    <p class="mt-0.5 font-bold text-niebla-600">Lo que espera tu revisión</p>

    <div class="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none]" role="tablist" aria-label="Secciones de moderación">
      <button
        v-for="p in pestanas"
        :key="p.valor"
        type="button"
        role="tab"
        :aria-selected="pestana === p.valor"
        class="flex min-h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border-2 px-4 font-semibold transition motion-reduce:transition-none"
        :class="pestana === p.valor ? 'border-violeta-700 bg-violeta-700 text-white' : 'border-niebla-200 bg-white text-tinta hover:border-violeta-300'"
        @click="pestana = p.valor"
      >
        {{ p.texto }}
        <Insignia tono="contador">{{ p.cantidad() }}</Insignia>
      </button>
    </div>

    <template v-if="pestana === 'solicitudes'">
      <ul v-if="solicitudes.length" class="mt-5 space-y-3">
        <Tarjeta v-for="s in solicitudes" :key="s.id" as="li">
          <Insignia>{{ s.tipo === 'cambio_nombre' ? 'Cambio de nombre' : 'Quiere ser emprendedor' }}</Insignia>
          <p v-if="s.tipo === 'cambio_nombre'" class="mt-2 text-sm font-semibold text-niebla-600">
            Actual: {{ s.user.emprendimiento }} <span aria-hidden="true">→</span><span class="sr-only">. Propuesto:</span>
          </p>
          <h2 class="mt-2 text-xl font-bold leading-tight">{{ s.nombre_emprendimiento }}</h2>
          <p class="text-sm text-niebla-600">{{ s.user.name }} · {{ s.user.email }}</p>
          <p v-if="s.descripcion" class="mt-3 whitespace-pre-line leading-relaxed">{{ s.descripcion }}</p>
          <a v-if="s.enlace" :href="s.enlace" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block break-all font-semibold text-violeta-700 underline underline-offset-4">{{ s.enlace }}</a>
          <div class="mt-4 flex gap-2">
            <Boton class="flex-1" icono="check" @click="aprobar(s.id)">Aprobar</Boton>
            <Boton variante="secundario" class="flex-1" @click="pedirRechazo(s.id)">Rechazar</Boton>
          </div>
        </Tarjeta>
      </ul>
      <EstadoVacio v-else titulo="Todo al día" texto="No hay solicitudes pendientes." />
    </template>

    <EventosPanel v-if="pestana === 'eventos'" :eventos="eventosPendientes" />
    <CategoriasPanel v-if="pestana === 'categorias'" :categorias="categorias" />

    <MotivoRechazo v-model="hojaRechazo" titulo="Rechazar solicitud" :procesando="procesando" @confirmar="rechazar" />
  </div>
</template>
