<script setup>
  import { computed } from 'vue';
  import { Link, usePage } from '@inertiajs/vue3';
  import Isotipo from '@/components/marca/Isotipo.vue';
  import Icono from '@/components/ui/Icono.vue';

  const page = usePage();

  const secciones = computed(() => [
    { nombre: 'Mapa', href: '/barrios', activaEn: '/barrios', icono: 'pin' },
    { nombre: 'Eventos', href: '/eventos', activaEn: '/eventos', icono: 'lista' },
    { nombre: 'Agenda', href: '/agenda', activaEn: '/agenda', icono: 'calendario' },
    { nombre: 'Perfil', href: page.props.auth?.user ? '/perfil' : '/login', activaEn: '/perfil', icono: 'usuario' },
  ])

  const activa = (seccion) => page.url.startsWith(seccion.activaEn);
</script>

<template>
  <nav
    aria-label="Principal"
    class="z-20 flex shrink-0 border-t border-lila-200 bg-lila-100/95 px-2 pb-[env(safe-area-inset-bottom)] backdrop-blur md:w-24 md:flex-col md:items-center md:gap-1 md:border-r md:border-t-0 md:bg-lila-100 md:px-0 md:py-6"
  >
    <Link href="/barrios" class="mb-6 hidden rounded-2xl p-2 md:block" aria-label="Barriopoli, inicio">
      <Isotipo class="h-11 w-auto" />
    </Link>

    <Link
      v-for="seccion in secciones"
      :key="seccion.nombre"
      :href="seccion.href"
      :aria-current="activa(seccion) ? 'page' : undefined"
      class="group relative flex min-h-16 flex-1 flex-col items-center justify-center gap-1 text-xs md:w-full md:flex-none"
      :class="activa(seccion) ? 'font-bold text-violeta-700' : 'font-semibold text-niebla-600 hover:text-violeta-700'"
    >
      
      <span
        class="grid h-8 w-16 place-items-center rounded-full transition-colors duration-200 motion-reduce:transition-none"
        :class="activa(seccion) ? 'bg-lila-200' : 'group-hover:bg-white/60'"
      >
        <Icono :nombre="seccion.icono" :relleno="false" />
      </span>
      <span
        v-if="seccion.nombre === 'Agenda' && page.props.avisosSinLeer > 0"
        class="absolute left-[calc(50%+0.5rem)] top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-durazno-200 px-1.5 text-xs font-bold text-violeta-800 ring-2 ring-lila-100"
        :aria-label="`${page.props.avisosSinLeer} avisos sin leer`"
      >{{ page.props.avisosSinLeer }}</span>
      {{ seccion.nombre }}
    </Link>
  </nav>
</template>
