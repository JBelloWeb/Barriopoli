<script setup>
  import { ref } from 'vue'
  import { router } from '@inertiajs/vue3'
  import EventoDetalle from '@/components/EventoDetalle.vue'
  import MotivoRechazo from '@/components/moderacion/MotivoRechazo.vue'
  import Boton from '@/components/ui/Boton.vue'
  import EstadoVacio from '@/components/ui/EstadoVacio.vue'
  import Insignia from '@/components/ui/Insignia.vue'
  import Tarjeta from '@/components/ui/Tarjeta.vue'

  defineProps({ eventos: { type: Array, required: true } })

  const aprobar = (id) => router.post(`/moderacion/eventos/${id}/aprobar`, {}, { preserveScroll: true })

  const rechazando = ref(null)
  const hojaRechazo = ref(false)
  const procesando = ref(false)
  const pedirRechazo = (id) => {
    rechazando.value = id
    hojaRechazo.value = true
  }
  const rechazar = (motivo) => router.post(`/moderacion/eventos/${rechazando.value}/rechazar`, { motivo }, {
    preserveScroll: true,
    onStart: () => { procesando.value = true },
    onFinish: () => { procesando.value = false },
    onSuccess: () => { hojaRechazo.value = false },
  })
</script>

<template>
  <ul v-if="eventos.length" class="mt-5 space-y-3">
    <Tarjeta v-for="evento in eventos" :key="evento.id" as="li">
      <Insignia tono="pendiente">En revisión</Insignia>
      <h2 class="titulo-marca mb-3 mt-2 text-2xl text-violeta-700">{{ evento.titulo }}</h2>
      <EventoDetalle :evento="evento" />
      <div class="mt-4 flex gap-2 border-t border-niebla-200 pt-4">
        <Boton class="flex-1" icono="check" @click="aprobar(evento.id)">Publicar</Boton>
        <Boton variante="secundario" class="flex-1" @click="pedirRechazo(evento.id)">Rechazar</Boton>
      </div>
    </Tarjeta>
  </ul>
  <EstadoVacio v-else titulo="Todo al día" texto="No hay eventos pendientes." />

  <MotivoRechazo v-model="hojaRechazo" titulo="Rechazar evento" :procesando="procesando" @confirmar="rechazar" />
</template>
