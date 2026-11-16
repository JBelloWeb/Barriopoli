<script setup>
// Optimista y con recarga parcial de agendaIds: consistente en mapa, listado y detalle.
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Boton from '@/components/ui/Boton.vue'

const props = defineProps({ evento: { type: Object, required: true } })
const page = usePage()

const agendado = computed(() => (page.props.agendaIds ?? []).includes(props.evento.id))

const alternar = () => {
  const quitar = agendado.value
  const opciones = {
    preserveScroll: true,
    preserveState: true,
    only: ['agendaIds'],
    optimistic: (actuales) => ({
      agendaIds: quitar
        ? actuales.agendaIds.filter((id) => id !== props.evento.id)
        : [...actuales.agendaIds, props.evento.id],
    }),
  }
  const url = `/agenda/${props.evento.id}`
  if (quitar) router.delete(url, opciones)
  else router.put(url, {}, opciones)
}
</script>

<template>
  <div class="mt-5 flex flex-col gap-1">
    <Boton
      v-if="page.props.auth.user"
      :variante="agendado ? 'secundario' : 'primario'"
      :icono="agendado ? 'check' : 'calendario'"
      bloque
      :aria-pressed="agendado"
      @click="alternar"
    >
      {{ agendado ? 'En tu agenda' : 'Agendar' }}
    </Boton>
    <Boton v-else href="/login" icono="calendario" bloque>Ingresá para agendar</Boton>

    <!-- <a> y no <Link>: Inertia intentaría tratar la descarga como una página. -->
    <Boton :href="`/eventos/${evento.id}/calendario.ics`" externo variante="fantasma" icono="descarga" chico class="self-center">
      Agregar a mi calendario
    </Boton>
  </div>
</template>
