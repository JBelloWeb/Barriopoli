<script setup>
// Dirección → Nominatim (vía servidor) → pin arrastrable. El barrio lo confirma el servidor con ST_Contains.
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { maplibregl, ESTILO_MAPA, CENTRO_CABA } from '@/lib/mapa'

const direccion = defineModel('direccion', { type: String, default: '' })
const latitud = defineModel('latitud', { default: null })
const longitud = defineModel('longitud', { default: null })
defineProps({ error: { type: String, default: null }, errorDireccion: { type: String, default: null } })

const contenedor = ref(null)
const buscando = ref(false)
const barrio = ref(null)
const mensaje = ref(null)
let map = null
let marcador = null

const pedirJson = async (url) => {
  const respuesta = await fetch(url, { headers: { Accept: 'application/json' } })
  if (respuesta.status === 429) throw new Error('limite')
  if (!respuesta.ok) throw new Error(String(respuesta.status))
  return respuesta.json()
}

const detectarBarrio = async () => {
  try {
    const datos = await pedirJson(`/geocodificar/barrio?latitud=${latitud.value}&longitud=${longitud.value}`)
    barrio.value = datos.barrio
    mensaje.value = datos.barrio ? null : 'Ese punto está fuera de CABA.'
  } catch {
    barrio.value = null
  }
}

const ubicar = (lat, lng, moverMapa = true) => {
  latitud.value = lat
  longitud.value = lng
  if (!marcador) {
    marcador = new maplibregl.Marker({ draggable: true, color: '#5f3f96' }).setLngLat([lng, lat]).addTo(map)
    marcador.on('dragend', () => {
      const posicion = marcador.getLngLat()
      latitud.value = posicion.lat
      longitud.value = posicion.lng
      detectarBarrio()
    })
  } else {
    marcador.setLngLat([lng, lat])
  }
  if (moverMapa) map.flyTo({ center: [lng, lat], zoom: 16 })
}

const buscar = async () => {
  if (direccion.value.trim().length < 4) {
    mensaje.value = 'Escribí calle y altura.'
    return
  }
  buscando.value = true
  mensaje.value = null
  try {
    const datos = await pedirJson(`/geocodificar?direccion=${encodeURIComponent(direccion.value)}`)
    if (datos.encontrado) {
      ubicar(datos.latitud, datos.longitud)
      barrio.value = datos.barrio
      mensaje.value = 'Revisá que el pin esté bien ubicado. Podés arrastrarlo.'
    } else {
      mensaje.value = 'No la encontramos. Tocá el mapa para marcar el lugar.'
    }
  } catch (error) {
    mensaje.value = error.message === 'limite'
      ? 'Hiciste muchas búsquedas seguidas. Esperá un minuto o tocá el mapa para marcar el lugar.'
      : 'No pudimos buscarla ahora. Tocá el mapa para marcar el lugar.'
  } finally {
    buscando.value = false
  }
}

onMounted(() => {
  const hayPunto = latitud.value !== null && longitud.value !== null
  map = new maplibregl.Map({
    container: contenedor.value,
    style: ESTILO_MAPA,
    center: hayPunto ? [longitud.value, latitud.value] : CENTRO_CABA,
    zoom: hayPunto ? 16 : 11,
    // Dentro de un formulario el mapa no debe atrapar el scroll: se mueve con dos dedos.
    cooperativeGestures: true,
    // Atribución obligatoria pero plegada: en 360 px tapaba un tercio del mapa.
    attributionControl: { compact: true },
  })
  map.on('click', (evento) => {
    ubicar(evento.lngLat.lat, evento.lngLat.lng, false)
    detectarBarrio()
  })
  if (hayPunto) {
    ubicar(latitud.value, longitud.value, false)
    detectarBarrio()
  }
})

onBeforeUnmount(() => {
  map?.remove()
  map = null
  marcador = null
})
</script>

<template>
  <div>
    <label for="direccion" class="mb-1 block font-bold">Dirección</label>
    <div class="flex gap-2">
      <input
        id="direccion"
        v-model="direccion"
        required
        maxlength="255"
        autocomplete="street-address"
        enterkeyhint="search"
        placeholder="Ej.: Av. Corrientes 1500"
        class="min-h-12 min-w-0 flex-1 rounded-lg border border-gray-300 px-3 text-base focus:outline-2 focus:outline-violeta"
        @keydown.enter.prevent="buscar"
      />
      <button type="button" :disabled="buscando" class="min-h-12 rounded-lg bg-crema px-4 font-bold disabled:opacity-60" @click="buscar">
        {{ buscando ? 'Buscando…' : 'Buscar' }}
      </button>
    </div>
    <p v-if="errorDireccion" class="mt-1 text-sm text-red-700">{{ errorDireccion }}</p>

    <!-- Envoltorio relative: maplibre-gl.css pisa las utilidades de Tailwind v4 en .maplibregl-map -->
    <div class="relative mt-3 h-64 w-full overflow-hidden rounded-xl border">
      <div ref="contenedor" class="h-full w-full"></div>
    </div>

    <p v-if="barrio" class="mt-2 font-bold">Barrio: {{ barrio }}</p>
    <p v-if="mensaje" class="mt-1 text-sm" role="status">{{ mensaje }}</p>
    <p v-if="error" class="mt-1 text-sm text-red-700">{{ error }}</p>
  </div>
</template>
