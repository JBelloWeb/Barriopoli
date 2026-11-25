<script setup>
  import { computed } from 'vue';
  import { usePage } from '@inertiajs/vue3';
  import BottomNav from '@/components/BottomNav.vue';
  import Icono from '@/components/ui/Icono.vue';

  const page = usePage();
  const aviso = computed(() => {
    if (page.flash?.error) return { texto: page.flash.error, error: true };
    if (page.flash?.exito) return { texto: page.flash.exito, error: false };
    return null;
  });

</script>

<template>
  <div class="flex h-dvh flex-col bg-papel md:flex-row-reverse">
    <main class="relative min-h-0 flex-1 overflow-y-auto">
      <slot />
    </main>
    <BottomNav />
    <p
      v-if="aviso"
      :key="aviso.texto"
      :role="aviso.error ? 'alert' : 'status'"
      class="aviso fixed inset-x-4 top-[max(1rem,env(safe-area-inset-top))] z-50 flex items-center gap-3 rounded-2xl bg-violeta-900 px-4 py-3 font-semibold text-papel shadow-flotante md:left-auto md:right-6 md:w-96"
    >
      <span class="grid size-7 shrink-0 place-items-center rounded-full" :class="aviso.error ? 'bg-error' : 'bg-durazno-200 text-violeta-800'">
        <Icono :nombre="aviso.error ? 'alerta' : 'check'" class="size-4" />
      </span>
      {{ aviso.texto }}
    </p>
  </div>
</template>
