<script setup>
// Actualización optimista: el corazón responde al instante; Inertia revierte si la petición falla.
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

import Icono from '@/components/ui/Icono.vue'
import { nombreLegible } from '@/lib/texto'

const props = defineProps({ barrio: { type: Object, required: true } })
const nombre = computed(() => nombreLegible(props.barrio.nombre))
const page = usePage()

const esFavorito = computed(() => (page.props.favoritosIds ?? []).includes(props.barrio.id))

const alternar = () => {
  const quitar = esFavorito.value
  const opciones = {
    preserveScroll: true,
    preserveState: true,
    // Solo estas props: no se reenvía el GeoJSON del barrio ni los eventos.
    only: ['favoritosIds', 'barrioPropioId'],
    optimistic: (actuales) => ({
      favoritosIds: quitar
        ? actuales.favoritosIds.filter((id) => id !== props.barrio.id)
        : [...actuales.favoritosIds, props.barrio.id],
      // Regla: Mi barrio siempre es favorito. Quitarlo de favoritos lo desmarca.
      ...(quitar && actuales.barrioPropioId === props.barrio.id ? { barrioPropioId: null } : {}),
    }),
  }
  const url = `/favoritos/${props.barrio.slug}`
  if (quitar) router.delete(url, opciones)
  else router.put(url, {}, opciones)
}
</script>

<template>
  <button
    v-if="page.props.auth.user"
    type="button"
    class="grid size-11 shrink-0 place-items-center rounded-full transition hover:bg-violeta-50 active:scale-90 motion-reduce:transition-none"
    :class="esFavorito ? 'text-violeta-500' : 'text-niebla-600'"
    :aria-pressed="esFavorito"
    :aria-label="esFavorito ? `Quitar ${nombre} de favoritos` : `Agregar ${nombre} a favoritos`"
    @click.stop.prevent="alternar"
  >
    <Icono nombre="corazon" :relleno="esFavorito" />
  </button>
  <Link v-else href="/login" class="grid size-11 shrink-0 place-items-center rounded-full text-niebla-600 hover:bg-violeta-50" aria-label="Ingresá para guardar favoritos">
    <Icono nombre="corazon" />
  </Link>
</template>
