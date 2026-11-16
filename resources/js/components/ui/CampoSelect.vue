<script setup>
import { useId } from 'vue'
import Icono from '@/components/ui/Icono.vue'
import { claseControl, claseError, claseEtiqueta } from '@/lib/campos'

defineOptions({ inheritAttrs: false })
const modelo = defineModel({ default: '' })
defineProps({
  etiqueta: { type: String, required: true },
  error: { type: String, default: null },
  opciones: { type: Array, required: true }, // [{ valor, texto }]
  placeholder: { type: String, default: 'Elegí una opción' },
})
const id = useId()
</script>

<template>
  <div>
    <label :for="id" :class="claseEtiqueta">{{ etiqueta }}</label>
    <div class="relative">
      <select
        :id="id"
        v-model="modelo"
        v-bind="$attrs"
        :aria-invalid="Boolean(error)"
        :aria-describedby="error ? `${id}-error` : undefined"
        :class="[claseControl, 'min-h-12 appearance-none pr-11']"
      >
        <option value="" disabled>{{ placeholder }}</option>
        <option v-for="o in opciones" :key="o.valor" :value="o.valor">{{ o.texto }}</option>
      </select>
      <Icono nombre="chevron" class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 rotate-90 text-violeta-700" />
    </div>
    <p v-if="error" :id="`${id}-error`" :class="claseError"><Icono nombre="alerta" class="mt-px size-4" />{{ error }}</p>
  </div>
</template>
