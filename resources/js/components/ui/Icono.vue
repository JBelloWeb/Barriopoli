<script setup>
  import { computed } from 'vue';

  const props = defineProps({
    nombre: { type: String, required: true },
    relleno: { type: Boolean, default: false }, 
  })

  const ICONOS = {
    pin: 'M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    lista: 'M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01',
    calendario: 'M8 3v4M16 3v4M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
    usuario: 'M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
    corazon: 'M12 20s-7.5-4.6-9.2-9.3C1.6 7.4 3.8 4 7.2 4c2 0 3.6 1.1 4.8 2.8C13.2 5.1 14.8 4 16.8 4c3.4 0 5.6 3.4 4.4 6.7C19.5 15.4 12 20 12 20z',
    casa: 'M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5.5h-5V20H5a1 1 0 0 1-1-1z',
    mas: 'M12 5v14M5 12h14',
    cerrar: 'M6 6l12 12M18 6 6 18',
    chevron: 'm9 6 6 6-6 6',
    atras: 'M15 6l-6 6 6 6',
    campana: 'M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15zM10 20a2 2 0 0 0 4 0',
    reloj: 'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    check: 'm5 12.5 4.5 4.5L19 7.5',
    alerta: 'M12 8v5M12 16.5h.01M10.3 3.9 2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
    descarga: 'M12 4v11M7 10.5l5 5 5-5M5 20h14',
    buscar: 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
    salir: 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4',
    llave: 'M15 9a3 3 0 1 0-3-3M12 6 4 14v3h3v-2h2v-2h2l1.5-1.5M15 9a3 3 0 0 0 0-6',
    imagen: 'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM4 16l5-5 4 4 2-2 5 5M15.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
    editar: 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
    papelera: 'M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13',
    escudo: 'M12 3 5 6v5c0 4.5 3 8.3 7 10 4-1.7 7-5.5 7-10V6z',
    tienda: 'M4 9 5.5 4h13L20 9M4 9h16v11H4zM4 9a2.7 2.7 0 0 0 5.3 0 2.7 2.7 0 0 0 5.4 0A2.7 2.7 0 0 0 20 9M10 20v-5h4v5',
    filtro: 'M4 6h16M7 12h10M10 18h4',
    copiar: 'M9 9h10v11H9zM5 15V4h10',
  }

  const d = computed(() => ICONOS[props.nombre] ?? ICONOS.pin);
</script>

<template>
  <svg
    viewBox="0 0 24 24"
    :fill="relleno ? 'currentColor' : 'none'"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    class="size-6 shrink-0"
  >
    <path :d="d" />
  </svg>
</template>
