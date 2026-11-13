<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

defineProps({ titulo: { type: String, required: true } })
const page = usePage()
const estado = computed(() => page.props.status)
</script>

<template>
  <main class="mx-auto flex min-h-dvh w-full max-w-md flex-col justify-center px-5 py-8">
    <p class="text-sm font-bold uppercase tracking-wide text-violeta-oscuro">Barriopoli</p>
    <h1 class="mb-6 mt-1 text-3xl font-bold">{{ titulo }}</h1>
    <p v-if="estado" role="status" class="mb-4 rounded-lg bg-crema p-3">{{ estado }}</p>
    <slot />
  </main>
</template>
