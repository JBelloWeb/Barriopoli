<script setup>
    import { onBeforeUnmount, onMounted, ref } from 'vue';
    import { maplibregl, ESTILO_MAPA, CENTRO_CABA, aplicarEstiloBarriopoli } from '@/lib/mapa';
    import Boton from '@/components/ui/Boton.vue';
    import Icono from '@/components/ui/Icono.vue';
    import { claseControl, claseError, claseEtiqueta } from '@/lib/campos';
    import { nombreLegible } from '@/lib/texto';

    const direccion = defineModel('direccion', { type: String, default: '' });
    const latitud = defineModel('latitud', { default: null });
    const longitud = defineModel('longitud', { default: null });
    defineProps({ error: { type: String, default: null }, errorDireccion: { type: String, default: null } });

    const contenedor = ref(null);
    const buscando = ref(false);
    const barrio = ref(null);
    const mensaje = ref(null);
    let map = null;
    let marcador = null;

    const pedirJson = async (url) => {
      const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
      if (respuesta.status === 429) throw new Error('limite');
      if (!respuesta.ok) throw new Error(String(respuesta.status));
      return respuesta.json();
    }

    const detectarBarrio = async (lat, lng) => {
      try {
        const datos = await pedirJson(`/geocodificar/barrio?latitud=${lat}&longitud=${lng}`)
        barrio.value = datos.barrio
        mensaje.value = datos.barrio ? null : 'Ese punto está fuera de CABA.'
      } catch {
        barrio.value = null;
      }
    }

    const ubicar = (lat, lng, moverMapa = true) => {
      latitud.value = lat;
      longitud.value = lng;
      if (!marcador) {
        marcador = new maplibregl.Marker({ draggable: true, color: '#5f3f96' }).setLngLat([lng, lat]).addTo(map);
        marcador.on('dragend', () => {
          const posicion = marcador.getLngLat();
          latitud.value = posicion.lat;
          longitud.value = posicion.lng;
          detectarBarrio(posicion.lat, posicion.lng);
        })
      } else {
        marcador.setLngLat([lng, lat]);
      }
      if (moverMapa) map.flyTo({ center: [lng, lat], zoom: 16 });
    }

    const buscar = async () => {
      if (direccion.value.trim().length < 4) {
        mensaje.value = 'Escribí calle y altura.';
        return;
      }
      buscando.value = true;
      mensaje.value = null;
      try {
        const datos = await pedirJson(`/geocodificar?direccion=${encodeURIComponent(direccion.value)}`);
        if (datos.encontrado) {
          ubicar(datos.latitud, datos.longitud);
          barrio.value = datos.barrio;
          mensaje.value = 'Revisá que el pin esté bien ubicado. Podés arrastrarlo.';
        } else {
          mensaje.value = 'No la encontramos. Tocá el mapa para marcar el lugar.';
        }

      } catch (error) {
        mensaje.value = error.message === 'limite' ? 'Hiciste muchas búsquedas seguidas. Esperá un minuto o tocá el mapa para marcar el lugar.' : 'No pudimos buscarla ahora. Tocá el mapa para marcar el lugar.'
      } finally {
        buscando.value = false;
      }
    }

    onMounted(() => {
      const hayPunto = latitud.value !== null && longitud.value !== null
      map = new maplibregl.Map({
        container: contenedor.value,
        style: ESTILO_MAPA,
        center: hayPunto ? [longitud.value, latitud.value] : CENTRO_CABA,
        zoom: hayPunto ? 16 : 11,
        // Para gestos con dedos
        cooperativeGestures: true,
        attributionControl: { compact: true },
      })
      map.on('load', () => aplicarEstiloBarriopoli(map))
      map.on('click', (evento) => {
        ubicar(evento.lngLat.lat, evento.lngLat.lng, false)
        detectarBarrio(evento.lngLat.lat, evento.lngLat.lng)
      })
      if (hayPunto) {
        ubicar(latitud.value, longitud.value, false)
        detectarBarrio(latitud.value, longitud.value)
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
    <label for="direccion" :class="claseEtiqueta">Dirección</label>
    <div class="flex gap-2">
      <input
        id="direccion"
        v-model="direccion"
        required
        maxlength="255"
        autocomplete="street-address"
        enterkeyhint="search"
        placeholder="Ej.: Av. Corrientes 1500"
        :aria-invalid="Boolean(errorDireccion)"
        :class="[claseControl, 'min-h-12 min-w-0 flex-1']"
        @keydown.enter.prevent="buscar"
      />
      <Boton variante="suave" icono="buscar" :cargando="buscando" class="shrink-0 px-4!" @click="buscar">
        <span class="sr-only sm:not-sr-only">{{ buscando ? 'Buscando…' : 'Buscar' }}</span>
      </Boton>
    </div>
    <p v-if="errorDireccion" :class="claseError"><Icono nombre="alerta" class="mt-px size-4" />{{ errorDireccion }}</p>

    <!-- Envoltorio relative: maplibre-gl.css pisa las utilidades de Tailwind v4 en .maplibregl-map -->
    <div class="relative mt-3 h-64 w-full overflow-hidden rounded-3xl ring-2 ring-niebla-200">
      <div ref="contenedor" class="h-full w-full"></div>
    </div>
    <p class="mt-1.5 text-sm text-niebla-600">Podés mover el pin o tocar el mapa para ajustar el punto.</p>

    <p v-if="barrio" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-violeta-50 px-3 py-1.5 font-bold text-violeta-700">
      <Icono nombre="pin" class="size-5" /> {{ nombreLegible(barrio) }}
    </p>
    <p v-if="mensaje" class="mt-2 text-sm" role="status">{{ mensaje }}</p>
    <p v-if="error" :class="claseError"><Icono nombre="alerta" class="mt-px size-4" />{{ error }}</p>
  </div>
</template>
