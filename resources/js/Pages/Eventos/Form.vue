<script setup>
// Alta, edición y duplicado de eventos (reemplaza a Edit.vue).
import { computed, onBeforeUnmount, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'
import UbicacionPicker from '@/components/UbicacionPicker.vue'
import { comprimirImagen } from '@/lib/comprimirImagen'

defineOptions({ layout: AppLayout })
const props = defineProps({
  evento: { type: Object, default: null },
  categorias: { type: Array, required: true },
})

const esEdicion = computed(() => Boolean(props.evento?.id))
const titulo = computed(() => (esEdicion.value ? 'Editar evento' : props.evento ? 'Duplicar evento' : 'Nuevo evento'))
const vistaPrevia = ref(props.evento?.imagen_url ?? null)
const comprimiendo = ref(false)

const form = useForm({
  titulo: props.evento?.titulo ?? '',
  descripcion: props.evento?.descripcion ?? '',
  categoria_id: props.evento?.categoria_id ?? '',
  direccion: props.evento?.direccion ?? '',
  latitud: props.evento?.latitud ?? null,
  longitud: props.evento?.longitud ?? null,
  inicio: props.evento?.inicio ?? '',
  fin: props.evento?.fin ?? '',
  imagen: null,
  quitar_imagen: false,
})

const liberarVistaPrevia = () => {
  if (vistaPrevia.value?.startsWith('blob:')) URL.revokeObjectURL(vistaPrevia.value)
}

const elegirImagen = async (evento) => {
  const archivo = evento.target.files[0]
  if (!archivo) return
  comprimiendo.value = true
  form.imagen = await comprimirImagen(archivo)
  comprimiendo.value = false
  form.quitar_imagen = false
  liberarVistaPrevia()
  vistaPrevia.value = URL.createObjectURL(form.imagen)
}

const quitarImagen = () => {
  form.imagen = null
  form.quitar_imagen = esEdicion.value
  liberarVistaPrevia()
  vistaPrevia.value = null
}

onBeforeUnmount(liberarVistaPrevia)

const guardar = () => {
  const opciones = { forceFormData: true, preserveScroll: true }
  if (esEdicion.value) {
    // PHP no procesa multipart en un PUT real: se envía POST con _method=put para que llegue la imagen.
    form.transform((datos) => ({ ...datos, _method: 'put' })).post(`/eventos/${props.evento.id}`, opciones)
  } else {
    form.post('/eventos', opciones)
  }
}

const eliminar = () => {
  if (window.confirm('¿Eliminar este evento? Quienes lo agendaron lo verán como cancelado.')) {
    router.delete(`/eventos/${props.evento.id}`)
  }
}
</script>

<template>
  <form class="mx-auto max-w-lg space-y-5 p-5 pb-28 md:pb-5" @submit.prevent="guardar">
    <div>
      <Link href="/mis-eventos" class="inline-block min-h-11 py-2 underline">← Mis eventos</Link>
      <h1 class="text-2xl font-bold">{{ titulo }}</h1>
      <p v-if="evento?.estado === 'publicado'" class="mt-1 text-sm text-gray-600">
        Cambiar fecha u horario se publica al instante. Otros cambios vuelven a revisión.
      </p>
    </div>

    <CampoTexto v-model="form.titulo" etiqueta="Título" maxlength="120" required :error="form.errors.titulo" />

    <div>
      <label for="categoria" class="mb-1 block font-bold">Categoría</label>
      <select id="categoria" v-model="form.categoria_id" required class="min-h-12 w-full rounded-lg border border-gray-300 bg-white px-3 text-base">
        <option value="" disabled>Elegí una</option>
        <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.icono }} {{ c.nombre }}</option>
      </select>
      <p v-if="form.errors.categoria_id" class="mt-1 text-sm text-red-700">{{ form.errors.categoria_id }}</p>
    </div>

    <div>
      <label for="descripcion" class="mb-1 block font-bold">Descripción</label>
      <textarea id="descripcion" v-model="form.descripcion" rows="4" maxlength="2000" class="w-full rounded-lg border border-gray-300 p-3 text-base"></textarea>
      <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-700">{{ form.errors.descripcion }}</p>
    </div>

    <CampoTexto v-model="form.inicio" etiqueta="Empieza" tipo="datetime-local" required :error="form.errors.inicio" />
    <CampoTexto v-model="form.fin" etiqueta="Termina (opcional)" tipo="datetime-local" :min="form.inicio || undefined" :error="form.errors.fin" />

    <UbicacionPicker
      v-model:direccion="form.direccion"
      v-model:latitud="form.latitud"
      v-model:longitud="form.longitud"
      :error="form.errors.latitud ?? form.errors.longitud"
      :error-direccion="form.errors.direccion"
    />

    <div>
      <label for="imagen" class="mb-1 block font-bold">Imagen (opcional)</label>
      <img v-if="vistaPrevia" :src="vistaPrevia" alt="Vista previa" class="mb-2 aspect-video w-full rounded-lg object-cover" />
      <input id="imagen" type="file" accept="image/*" class="min-h-11 w-full text-base" @change="elegirImagen" />
      <p v-if="comprimiendo" class="mt-1 text-sm" role="status">Preparando imagen…</p>
      <button v-if="vistaPrevia" type="button" class="mt-1 min-h-11 underline" @click="quitarImagen">Quitar imagen</button>
      <p v-if="form.errors.imagen" class="mt-1 text-sm text-red-700">{{ form.errors.imagen }}</p>
    </div>

    <button v-if="esEdicion" type="button" class="min-h-11 w-full text-red-700" @click="eliminar">Eliminar evento</button>

    <!-- Fijo sobre la barra inferior en mobile; en escritorio vuelve al flujo del formulario. -->
    <div class="fixed inset-x-0 bottom-[calc(3.5rem+env(safe-area-inset-bottom))] z-20 border-t bg-white p-3 md:static md:border-0 md:p-0">
      <button :disabled="form.processing || comprimiendo" class="min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white disabled:opacity-60">
        {{ form.processing ? 'Guardando…' : 'Guardar' }}
      </button>
    </div>
  </form>
</template>
