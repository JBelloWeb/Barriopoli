<script setup>
// Optimista y con recarga parcial de agendaIds: consistente en mapa, listado y detalle.
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

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
  <div class="mt-4 flex flex-col gap-2">
    <button
      v-if="page.props.auth.user"
      type="button"
      class="min-h-12 w-full rounded-lg font-bold"
      :class="agendado ? 'border-2 border-violeta-oscuro text-violeta-oscuro' : 'bg-violeta-oscuro text-white'"
      :aria-pressed="agendado"
      @click="alternar"
    >
      {{ agendado ? 'En tu agenda ✓' : 'Agendar' }}
    </button>
    <Link v-else href="/login" class="flex min-h-12 w-full items-center justify-center rounded-lg bg-violeta-oscuro font-bold text-white">
      Ingresá para agendar
    </Link>

    <!-- <a> y no <Link>: Inertia intentaría tratar la descarga como una página. -->
    <a :href="`/eventos/${evento.id}/calendario.ics`" class="min-h-11 py-2 text-center underline">Agregar a mi calendario</a>
  </div>
</template>
