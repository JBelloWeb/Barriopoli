<script setup>
import { computed, useId } from 'vue'
import Icono from '@/components/ui/Icono.vue'
import { claseAyuda, claseControl, claseError, claseEtiqueta } from '@/lib/campos'

defineOptions({ inheritAttrs: false })
const modelo = defineModel({ default: '' })
const props = defineProps({
  etiqueta: { type: String, required: true },
  error: { type: String, default: null },
  ayuda: { type: String, default: null },
  tipo: { type: String, default: 'text' },
})
const id = useId()
const descripcion = computed(() => [props.error && `${id}-error`, props.ayuda && `${id}-ayuda`].filter(Boolean).join(' ') || undefined)
</script>

<template>
  <div>
    <label :for="id" :class="claseEtiqueta">{{ etiqueta }}</label>
    <input
      :id="id"
      v-model="modelo"
      v-bind="$attrs"
      :type="tipo"
      :aria-invalid="Boolean(error)"
      :aria-describedby="descripcion"
      :class="[claseControl, 'min-h-12']"
    />
    <p v-if="ayuda && !error" :id="`${id}-ayuda`" :class="claseAyuda">{{ ayuda }}</p>
    <p v-if="error" :id="`${id}-error`" :class="claseError"><Icono nombre="alerta" class="mt-px size-4" />{{ error }}</p>
  </div>
</template>
