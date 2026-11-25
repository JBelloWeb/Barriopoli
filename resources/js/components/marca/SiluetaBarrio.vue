<script setup>

  import { computed } from 'vue';
  import { bbox, buffer } from '@turf/turf';

  const props = defineProps({
    geojson: { type: [Object, String], required: true }, 
    contornos: { type: Number, default: 4 },
    separacion: { type: Number, default: 0.12 }, 
    relleno: { type: String, default: 'var(--color-durazno-200)' },
    linea: { type: String, default: 'var(--color-lila-400)' },
    grosor: { type: Number, default: 2.25 }, 
    animar: { type: Boolean, default: true }, 
  })

  const cache = new WeakMap()

  const geometria = computed(() => {
    const dato = typeof props.geojson === 'string' ? JSON.parse(props.geojson) : props.geojson
    return dato.geometry ?? dato
  })

  const dibujo = computed(() => {
    const clave = geometria.value
    const previo = cache.get(clave)
    if (previo && previo.contornos === props.contornos && previo.separacion === props.separacion) return previo

    const anillos = Array.from({ length: props.contornos }, (_, i) =>
      buffer(clave, props.separacion * (i + 1), { units: 'kilometers', steps: 12 }))
    const caja = bbox(anillos.at(-1) ?? clave)

    const k = Math.cos((((caja[1] + caja[3]) / 2) * Math.PI) / 180)
    const escala = 1000 / Math.max((caja[2] - caja[0]) * k, caja[3] - caja[1])
    const punto = ([lon, lat]) => `${(((lon - caja[0]) * k) * escala).toFixed(1)} ${((caja[3] - lat) * escala).toFixed(1)}`
    const aPath = (geo) => {
      const g = geo.geometry ?? geo
      const poligonos = g.type === 'Polygon' ? [g.coordinates] : g.coordinates
      return poligonos.flatMap((p) => p.map((anillo) => `M${anillo.map(punto).join('L')}Z`)).join('')
    }

    const resultado = {
      contornos: props.contornos,
      separacion: props.separacion,
      viewBox: `0 0 ${((caja[2] - caja[0]) * k * escala).toFixed(1)} ${((caja[3] - caja[1]) * escala).toFixed(1)}`,
      anillos: anillos.map(aPath),
      barrio: aPath(clave),
    }
    cache.set(clave, resultado)
    return resultado
  })
</script>

<template>
  <svg :viewBox="dibujo.viewBox" fill="none" aria-hidden="true" class="overflow-visible">
    <path
      v-for="(d, i) in dibujo.anillos"
      :key="i"
      :d="d"
      :class="{ 'contorno-expande': animar }"
      :style="{ '--i': i }"
      :stroke="linea"
      :stroke-width="grosor"
      stroke-linejoin="round"
      vector-effect="non-scaling-stroke"
    />
    <path :d="dibujo.barrio" :fill="relleno" fill-rule="evenodd" />
  </svg>
</template>
