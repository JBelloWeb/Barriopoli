<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const secciones = computed(() => [
  { nombre: 'Mapa', href: '/barrios', activaEn: '/barrios', icono: 'M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z' },
  { nombre: 'Eventos', href: '/eventos', activaEn: '/eventos', icono: 'M4 6h16M4 12h16M4 18h16' },
  { nombre: 'Agenda', href: '/agenda', activaEn: '/agenda', icono: 'M8 3v4M16 3v4M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z' },
  { nombre: 'Perfil', href: page.props.auth?.user ? '/perfil' : '/login', activaEn: '/perfil', icono: 'M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8z' },
])

const activa = (seccion) => page.url.startsWith(seccion.activaEn)
</script>

<template>
  <nav
    aria-label="Principal"
    class="flex shrink-0 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] md:w-20 md:flex-col md:justify-center md:gap-2 md:border-r md:border-t-0"
  >
    <Link
      v-for="seccion in secciones"
      :key="seccion.nombre"
      :href="seccion.href"
      :aria-current="activa(seccion) ? 'page' : undefined"
      class="relative flex min-h-14 flex-1 flex-col items-center justify-center gap-0.5 text-xs md:flex-none md:py-3"
      :class="activa(seccion) ? 'font-bold text-violeta-oscuro' : 'text-gray-500'"
    >
      <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path :d="seccion.icono" />
      </svg>
      {{ seccion.nombre }}
    </Link>
  </nav>
</template>
