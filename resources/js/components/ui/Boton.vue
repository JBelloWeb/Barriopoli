<script setup>
// Botón de marca. Con "href" se vuelve <Link> de Inertia (o <a> si "externo", p. ej. descargas .ics).
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Icono from '@/components/ui/Icono.vue'

const props = defineProps({
  variante: {
    type: String,
    default: 'primario',
    validator: (v) => ['primario', 'marca', 'secundario', 'suave', 'fantasma', 'peligro', 'icono'].includes(v),
  },
  href: { type: String, default: null },
  externo: { type: Boolean, default: false },
  icono: { type: String, default: null },
  iconoRelleno: { type: Boolean, default: false },
  tipo: { type: String, default: 'button' },
  bloque: { type: Boolean, default: false }, // ancho completo
  chico: { type: Boolean, default: false },
  cargando: { type: Boolean, default: false },
})

const VARIANTES = {
  primario: 'bg-violeta-700 text-white hover:bg-violeta-800',
  marca: 'bg-violeta-500 text-papel text-lg font-bold hover:bg-violeta-600',
  secundario: 'border-2 border-violeta-700 text-violeta-700 hover:bg-violeta-50',
  suave: 'bg-durazno-200 text-violeta-700 hover:bg-durazno-400/60',
  fantasma: 'text-violeta-700 underline-offset-4 hover:underline',
  peligro: 'bg-error text-white hover:bg-error/90',
  icono: 'text-violeta-700 hover:bg-violeta-50',
}

const clases = computed(() => [
  'inline-flex items-center justify-center gap-2 rounded-full font-semibold transition duration-150 ease-marca',
  'active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 motion-reduce:transition-none motion-reduce:active:scale-100',
  props.variante === 'icono' ? 'size-12' : props.chico ? 'min-h-11 px-4 text-sm' : 'min-h-12 px-6',
  props.variante === 'fantasma' && 'px-2',
  props.bloque && 'w-full',
  VARIANTES[props.variante],
])

const componente = computed(() => (props.href ? (props.externo ? 'a' : Link) : 'button'))
</script>

<template>
  <component
    :is="componente"
    :href="href ?? undefined"
    :type="href ? undefined : tipo"
    :disabled="href ? undefined : cargando || undefined"
    :aria-busy="cargando || undefined"
    :class="clases"
  >
    <Icono v-if="icono" :nombre="icono" :relleno="iconoRelleno" :class="chico ? 'size-5' : 'size-6'" />
    <slot />
  </component>
</template>
