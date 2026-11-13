<script setup>
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
defineProps({ seccion: { type: String, required: true } })
</script>

<template>
  <div class="mx-auto flex h-full max-w-lg flex-col items-center justify-center p-5 text-center">
    <h1 class="text-2xl font-bold">{{ seccion }}</h1>
    <p class="mt-2 text-gray-600">Estamos armando esta sección. Muy pronto vas a poder usarla.</p>
  </div>
</template>
