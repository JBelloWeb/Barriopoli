<script setup>
  import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
  import Icono from '@/components/ui/Icono.vue';

  const abierto = defineModel({ type: Boolean, default: false });
  defineProps({ titulo: { type: String, default: '' } });

  const desplazamiento = ref(0);
  let inicioY = null;

  const empezar = (evento) => {
    inicioY = evento.clientY;
    evento.currentTarget.setPointerCapture(evento.pointerId);
  }

  const mover = (evento) => {
    if (inicioY !== null) desplazamiento.value = Math.max(0, evento.clientY - inicioY);
  }

  const soltar = () => {
    if (desplazamiento.value > 100) abierto.value = false;
    desplazamiento.value = 0;
    inicioY = null;
  }

  const alPresionarTecla = (evento) => {
    if (evento.key === 'Escape') abierto.value = false;
  }

  watch(abierto, (valor) => {
    if (valor) window.addEventListener('keydown', alPresionarTecla);
    else window.removeEventListener('keydown', alPresionarTecla);
  })

  onBeforeUnmount(() => window.removeEventListener('keydown', alPresionarTecla));

  const montado = ref(false);
  onMounted(() => { montado.value = true });
</script>

<template>
  <Teleport v-if="montado" to="body">
    <Transition name="sheet">
      <section
        v-if="abierto"
        role="dialog"
        :aria-label="titulo"
        class="fixed inset-x-0 bottom-0 z-40 flex max-h-[85dvh] flex-col rounded-t-[1.75rem] bg-papel pb-[env(safe-area-inset-bottom)] shadow-flotante md:bottom-6 md:left-auto md:right-6 md:max-h-[calc(100dvh-3rem)] md:w-[440px] md:rounded-[1.75rem]"
        :style="desplazamiento ? { transform: `translateY(${desplazamiento}px)` } : undefined"
      >
        <div
          class="flex cursor-grab touch-none justify-center pb-2 pt-3"
          @pointerdown="empezar"
          @pointermove="mover"
          @pointerup="soltar"
          @pointercancel="soltar"
        >
          <span class="h-1.5 w-12 rounded-full bg-niebla-300"></span>
        </div>
        <header class="flex items-start justify-between gap-2 px-5">
          <h2 class="titulo-marca pt-1.5 text-2xl text-violeta-700">{{ titulo }}</h2>
          <button type="button" class="-mr-2 grid size-11 shrink-0 place-items-center rounded-full text-violeta-700 hover:bg-violeta-50" aria-label="Cerrar" @click="abierto = false">
            <Icono nombre="cerrar" />
          </button>
        </header>
        <div class="overflow-y-auto px-5 pb-6 pt-3">
          <slot />
        </div>
      </section>
    </Transition>
  </Teleport>
</template>
