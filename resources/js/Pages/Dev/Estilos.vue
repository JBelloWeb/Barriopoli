<script setup>
// Guía de estilos de Barriopoli (solo local). Muestra tokens, tipografía y recursos de marca.
import Isotipo from '@/components/marca/Isotipo.vue'
import Logo from '@/components/marca/Logo.vue'
import Onda from '@/components/marca/Onda.vue'
import SiluetaBarrio from '@/components/marca/SiluetaBarrio.vue'
import { nombreLegible } from '@/lib/texto'

defineProps({ barrios: { type: Array, default: () => [] } })

const marca = [
  { nombre: 'Violeta', token: 'violeta-500', hex: '#8E66CC', clase: 'bg-violeta-500 text-durazno-200' },
  { nombre: 'Lila', token: 'lila-400', hex: '#B37DFF', clase: 'bg-lila-400 text-violeta-900' },
  { nombre: 'Durazno', token: 'durazno-200', hex: '#FFDEB6', clase: 'bg-durazno-200 text-violeta-700' },
  { nombre: 'Blanco cálido', token: 'papel', hex: '#F6F7EE', clase: 'bg-papel text-violeta-700 ring-1 ring-niebla-200' },
  { nombre: 'Tinta', token: 'tinta', hex: '#080713', clase: 'bg-tinta text-durazno-200' },
]
const escalas = {
  violeta: ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900'],
  lila: ['100', '200', '400'],
  durazno: ['50', '100', '200', '400'],
  niebla: ['100', '200', '300', '500', '600'],
}
// Clases completas para que Tailwind las detecte (no se arman con interpolación).
const muestras = {
  violeta: ['bg-violeta-50', 'bg-violeta-100', 'bg-violeta-200', 'bg-violeta-300', 'bg-violeta-400', 'bg-violeta-500', 'bg-violeta-600', 'bg-violeta-700', 'bg-violeta-800', 'bg-violeta-900'],
  lila: ['bg-lila-100', 'bg-lila-200', 'bg-lila-400'],
  durazno: ['bg-durazno-50', 'bg-durazno-100', 'bg-durazno-200', 'bg-durazno-400'],
  niebla: ['bg-niebla-100', 'bg-niebla-200', 'bg-niebla-300', 'bg-niebla-500', 'bg-niebla-600'],
}
const contraste = [
  { texto: 'text-violeta-700', fondo: 'bg-white', ratio: '7.96', uso: 'Texto y botón primario' },
  { texto: 'text-white', fondo: 'bg-violeta-700', ratio: '7.96', uso: 'Texto de botón' },
  { texto: 'text-violeta-700', fondo: 'bg-durazno-200', ratio: '6.21', uso: 'Pastillas' },
  { texto: 'text-niebla-600', fondo: 'bg-papel', ratio: '6.33', uso: 'Texto secundario' },
  { texto: 'text-violeta-500', fondo: 'bg-papel', ratio: '3.95', uso: 'Solo ≥ 24 px / íconos' },
  { texto: 'text-durazno-200', fondo: 'bg-violeta-500', ratio: '3.33', uso: 'Solo títulos grandes' },
]
</script>

<template>
  <div class="relative min-h-dvh overflow-hidden bg-papel pb-24">
    <Onda class="pointer-events-none absolute -left-4 -top-4 w-40 text-violeta-500 md:w-56" />

    <header class="relative mx-auto max-w-5xl px-4 pt-40 md:px-8 md:pt-48">
      <Logo orientacion="apilado" />
      <p class="mt-4"><span class="pastilla">Tu barrio, más cerca.</span></p>
      <h1 class="titulo-marca mt-10 text-3xl text-violeta-700 md:text-4xl">Guía de estilos</h1>
      <p class="-mt-0.5 font-bold text-niebla-600">PLAN-07 · tokens y recursos de marca</p>
    </header>

    <main class="relative mx-auto mt-10 max-w-5xl space-y-14 px-4 md:px-8">
      <section>
        <h2 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Paleta de marca</h2>
        <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          <li v-for="c in marca" :key="c.token" class="flex aspect-[4/3] flex-col justify-end rounded-tarjeta p-4" :class="c.clase">
            <p class="text-lg font-bold">{{ c.nombre }}</p>
            <p class="text-sm font-bold opacity-90">{{ c.hex }} · {{ c.token }}</p>
          </li>
        </ul>
      </section>

      <section>
        <h2 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Escalas</h2>
        <div v-for="(tonos, nombre) in escalas" :key="nombre" class="mt-4">
          <p class="mb-1 font-bold">{{ nombre }}</p>
          <ul class="flex overflow-hidden rounded-2xl">
            <li v-for="(tono, i) in tonos" :key="tono" class="h-14 flex-1 p-1.5 text-xs font-bold" :class="[muestras[nombre][i], Number(tono) >= 500 ? 'text-white' : 'text-tinta']">{{ tono }}</li>
          </ul>
        </div>
      </section>

      <section>
        <h2 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Tipografía · Rosario</h2>
        <div class="mt-4 space-y-4 rounded-tarjeta bg-white p-6 shadow-tarjeta">
          <p class="titulo-marca text-5xl text-violeta-500">Barriopoli</p>
          <div>
            <p class="titulo-marca text-3xl text-violeta-700 md:text-4xl">Eventos</p>
            <p class="-mt-0.5 font-bold text-niebla-600">Lo que pasa en tus barrios</p>
          </div>
          <p class="text-lg font-bold leading-tight">Feria de diseño independiente</p>
          <p class="max-w-prose leading-relaxed">Barriopoli es una plataforma digital hiperlocal que fusiona la identidad urbana porteña con la vitalidad de la comunidad. ¿Conocés todos los rincones de tu barrio? Ñandú, Núñez, Constitución.</p>
          <p class="text-sm text-niebla-600">Plaza Italia · Palermo</p>
          <p class="text-xs font-bold uppercase tracking-wider text-niebla-600">Tus barrios</p>
          <p class="flex flex-wrap gap-x-6 gap-y-1 text-lg"><span class="font-light">Light 300</span><span>Regular 400</span><span class="font-medium">Medium 500</span><span class="font-semibold">Semibold 600</span><span class="font-bold">Bold 700</span></p>
        </div>
      </section>

      <section>
        <h2 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Isotipo</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <div class="grid place-items-center rounded-tarjeta bg-papel p-8 ring-1 ring-niebla-200"><Isotipo class="h-28" titulo="Barriopoli" /></div>
          <div class="grid place-items-center rounded-tarjeta bg-violeta-500 p-8"><Isotipo variante="mono" class="h-28 text-lila-400" /></div>
          <div class="flex items-end justify-center gap-4 rounded-tarjeta bg-white p-8 shadow-tarjeta">
            <Isotipo class="h-16" /><Isotipo class="h-10" /><Isotipo class="h-6" /><Isotipo class="h-4" />
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Siluetas con líneas de contorno</h2>
        <ul class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3">
          <li v-for="(b, i) in barrios" :key="b.id" class="flex flex-col rounded-tarjeta p-5" :class="['bg-violeta-500', 'bg-papel ring-1 ring-niebla-200', 'bg-durazno-200', 'bg-lila-400', 'bg-white shadow-tarjeta', 'bg-violeta-700'][i % 6]">
            <SiluetaBarrio
              :geojson="b.geojson"
              class="mx-auto aspect-square w-full max-w-48"
              :relleno="['var(--color-lila-400)', 'var(--color-durazno-200)', 'var(--color-papel)', 'var(--color-violeta-500)', 'var(--color-durazno-200)', 'var(--color-durazno-200)'][i % 6]"
              :linea="['var(--color-lila-400)', 'var(--color-durazno-200)', 'var(--color-papel)', 'var(--color-violeta-500)', 'var(--color-lila-400)', 'var(--color-lila-400)'][i % 6]"
            />
            <p class="mt-3 font-bold" :class="['text-durazno-200', 'text-violeta-700', 'text-violeta-700', 'text-violeta-900', 'text-violeta-700', 'text-durazno-200'][i % 6]">Barrio de {{ nombreLegible(b.nombre) }}</p>
          </li>
        </ul>
      </section>

      <section>
        <h2 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Recursos</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <div class="marco-marca bg-violeta-500 p-6 text-papel">
            <p class="titulo-marca text-3xl text-durazno-200">Evento de Lanzamiento</p>
            <p class="font-bold">Lugar · La Noche de los Museos</p>
            <div class="mt-6 flex flex-wrap gap-2">
              <span class="pastilla">Noviembre</span><span class="pastilla">20 a 3 h</span><span class="pastilla">Entrada libre</span>
            </div>
          </div>
          <div class="marco-marca bg-violeta-500 p-6 text-papel">
            <p class="titulo-marca text-3xl text-durazno-200">Pasaporte Barriopoli</p>
            <ol class="mt-4 space-y-3">
              <li v-for="(paso, i) in ['Descargá la app', 'Recorré los barrios', 'Escaneá y sumá sellos']" :key="paso" class="flex items-center gap-3 font-bold">
                <span class="grid size-9 place-items-center rounded-full bg-durazno-200 text-violeta-700">{{ i + 1 }}</span>{{ paso }}
              </li>
            </ol>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-xs font-bold uppercase tracking-wider text-niebla-600">Contraste (WCAG)</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <li v-for="c in contraste" :key="c.texto + c.fondo" class="rounded-2xl p-4 ring-1 ring-niebla-200" :class="c.fondo">
            <p class="text-lg font-bold" :class="c.texto">{{ c.ratio }}:1 · Aa</p>
            <p class="text-sm" :class="c.texto">{{ c.uso }}</p>
          </li>
        </ul>
      </section>
    </main>

    <Isotipo variante="mono" class="fixed bottom-4 right-4 h-10 text-lila-400" />
  </div>
</template>
