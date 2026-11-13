<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'

const abierto = defineModel({ type: Boolean, default: false })
defineProps({ titulo: { type: String, default: '' } })

const desplazamiento = ref(0)
let inicioY = null

const empezar = (evento) => {
  inicioY = evento.clientY
  evento.currentTarget.setPointerCapture(evento.pointerId)
}
const mover = (evento) => {
  if (inicioY !== null) desplazamiento.value = Math.max(0, evento.clientY - inicioY)
}
const soltar = () => {
  if (desplazamiento.value > 100) abierto.value = false
  desplazamiento.value = 0
  inicioY = null
}

const alPresionarTecla = (evento) => {
  if (evento.key === 'Escape') abierto.value = false
}

watch(abierto, (valor) => {
  if (valor) window.addEventListener('keydown', alPresionarTecla)
  else window.removeEventListener('keydown', alPresionarTecla)
})
onBeforeUnmount(() => window.removeEventListener('keydown', alPresionarTecla))
</script>

<template>
  <Teleport to="body">
    <Transition name="sheet">
      <section
        v-if="abierto"
        role="dialog"
        :aria-label="titulo"
        class="fixed inset-x-0 bottom-0 z-40 flex max-h-[85dvh] flex-col rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] shadow-2xl md:bottom-4 md:left-auto md:right-4 md:w-[420px] md:rounded-2xl"
        :style="desplazamiento ? { transform: `translateY(${desplazamiento}px)` } : undefined"
      >
        <div
          class="flex cursor-grab touch-none justify-center py-3"
          @pointerdown="empezar"
          @pointermove="mover"
          @pointerup="soltar"
          @pointercancel="soltar"
        >
          <span class="h-1.5 w-10 rounded-full bg-gray-300"></span>
        </div>
        <header class="flex items-center justify-between gap-2 px-5">
          <h2 class="text-lg font-bold">{{ titulo }}</h2>
          <button type="button" class="min-h-11 min-w-11 text-2xl" aria-label="Cerrar" @click="abierto = false">×</button>
        </header>
        <div class="overflow-y-auto px-5 pb-5">
          <slot />
        </div>
      </section>
    </Transition>
  </Teleport>
</template>
