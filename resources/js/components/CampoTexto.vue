<script setup>
import { useId } from 'vue'

defineOptions({ inheritAttrs: false })
const modelo = defineModel({ default: '' })
defineProps({
  etiqueta: { type: String, required: true },
  error: { type: String, default: null },
  tipo: { type: String, default: 'text' },
})
const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="mb-1 block font-bold">{{ etiqueta }}</label>
    <input
      :id="id"
      v-model="modelo"
      v-bind="$attrs"
      :type="tipo"
      :aria-invalid="Boolean(error)"
      :aria-describedby="error ? `${id}-error` : undefined"
      class="min-h-12 w-full rounded-lg border border-gray-300 px-3 text-base focus:outline-2 focus:outline-violeta"
    />
    <p v-if="error" :id="`${id}-error`" class="mt-1 text-sm text-red-700">{{ error }}</p>
  </div>
</template>
