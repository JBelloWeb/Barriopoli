<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BottomNav from '@/components/BottomNav.vue'

const page = usePage()
const aviso = computed(() => page.flash?.exito ?? page.flash?.error ?? null)
</script>

<template>
  <div class="flex h-dvh flex-col md:flex-row-reverse">
    <main class="relative min-h-0 flex-1 overflow-y-auto">
      <slot />
    </main>
    <BottomNav />
    <p
      v-if="aviso"
      :key="aviso"
      role="status"
      class="aviso fixed inset-x-4 top-4 z-50 rounded-xl bg-tinta px-4 py-3 text-center text-white shadow-lg md:left-auto md:w-96"
    >
      {{ aviso }}
    </p>
  </div>
</template>
