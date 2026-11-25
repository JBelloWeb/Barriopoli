<script setup>

    import { computed, onBeforeUnmount, ref } from 'vue'
    import { router, useForm, usePage } from '@inertiajs/vue3'
    import AppLayout from '@/layouts/AppLayout.vue'
    import CampoTexto from '@/components/CampoTexto.vue'
    import UbicacionPicker from '@/components/UbicacionPicker.vue'
    import Boton from '@/components/ui/Boton.vue'
    import CampoArea from '@/components/ui/CampoArea.vue'
    import Icono from '@/components/ui/Icono.vue'
    import Tarjeta from '@/components/ui/Tarjeta.vue'
    import { claseError, claseEtiqueta } from '@/lib/campos'
    import { comprimirImagen } from '@/lib/comprimirImagen'

    defineOptions({ layout: AppLayout })
    const props = defineProps({
      evento: { type: Object, default: null },
      categorias: { type: Array, required: true },
    })

    const page = usePage()
    const esModerador = computed(() => page.props.auth.puede.moderar)
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

    const leyenda = 'mb-4 text-xs font-bold uppercase tracking-wider text-niebla-600'
</script>

<template>
  <form class="mx-auto max-w-2xl px-4 pb-32 pt-[max(1rem,env(safe-area-inset-top))] md:px-8 md:pb-10" @submit.prevent="guardar">
    <header>
      <Boton href="/mis-eventos" variante="fantasma" icono="atras" chico class="-ml-2">Mis eventos</Boton>
      <h1 class="titulo-marca mt-2 text-3xl text-violeta-700 md:text-4xl">{{ titulo }}</h1>
      <p v-if="evento?.estado === 'publicado'" class="mt-1 font-semibold text-niebla-600">
        Cambiar fecha u horario se publica al instante. Otros cambios vuelven a revisión.
      </p>
    </header>

    <div class="mt-6 space-y-4">
      <Tarjeta as="fieldset" class="space-y-5">
        <legend class="sr-only">Qué</legend>
        <p :class="leyenda" aria-hidden="true">Qué</p>
        <CampoTexto v-model="form.titulo" etiqueta="Título" maxlength="120" required placeholder="Ej.: Feria de diseño en la plaza" :error="form.errors.titulo" />

        <div role="radiogroup" aria-labelledby="etiqueta-categoria" :aria-describedby="form.errors.categoria_id ? 'error-categoria' : undefined">
          <p id="etiqueta-categoria" :class="claseEtiqueta">Categoría</p>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="c in categorias"
              :key="c.id"
              class="flex min-h-11 cursor-pointer items-center gap-2 rounded-full border-2 px-4 font-semibold transition has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-violeta-700 motion-reduce:transition-none"
              :class="form.categoria_id === c.id ? 'border-violeta-700 bg-violeta-700 text-white' : 'border-niebla-200 bg-white hover:border-violeta-300'"
            >
              <input v-model="form.categoria_id" type="radio" name="categoria" :value="c.id" required class="sr-only" />
              <span aria-hidden="true">{{ c.icono }}</span>{{ c.nombre }}
            </label>
          </div>
          <p v-if="form.errors.categoria_id" id="error-categoria" :class="claseError"><Icono nombre="alerta" class="mt-px size-4" />{{ form.errors.categoria_id }}</p>
        </div>

        <CampoArea v-model="form.descripcion" etiqueta="Descripción" maxlength="2000" placeholder="Contá de qué se trata, si hay que inscribirse, si la entrada es libre…" :error="form.errors.descripcion" />

        <div>
          <p :class="claseEtiqueta" id="etiqueta-imagen">Imagen <span class="font-normal text-niebla-600">(opcional)</span></p>
          <div v-if="vistaPrevia" class="relative">
            <img :src="vistaPrevia" alt="Vista previa de la imagen" class="aspect-video w-full rounded-3xl object-cover" />
            <Boton variante="icono" icono="papelera" class="absolute right-3 top-3 bg-white/90 shadow-tarjeta" aria-label="Quitar imagen" @click="quitarImagen" />
          </div>
          <label
            v-else
            for="imagen"
            class="flex min-h-36 cursor-pointer flex-col items-center justify-center gap-2 rounded-3xl border-2 border-dashed border-violeta-300 bg-violeta-50 p-6 text-center font-semibold text-violeta-700 transition hover:bg-violeta-100 has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-violeta-700 motion-reduce:transition-none"
          >
            <Icono nombre="imagen" class="size-8" />
            Elegí una foto o un flyer
            <span class="text-sm font-normal text-niebla-600">JPG, PNG o WebP · se comprime antes de subir</span>
            <input id="imagen" type="file" accept="image/*" class="sr-only" aria-labelledby="etiqueta-imagen" @change="elegirImagen" />
          </label>
          <p v-if="comprimiendo" class="mt-2 text-sm font-semibold" role="status">Preparando imagen…</p>
          <p v-if="form.errors.imagen" :class="claseError"><Icono nombre="alerta" class="mt-px size-4" />{{ form.errors.imagen }}</p>
        </div>
      </Tarjeta>

      <Tarjeta as="fieldset" class="space-y-5">
        <legend class="sr-only">Cuándo</legend>
        <p :class="leyenda" aria-hidden="true">Cuándo</p>
        <div class="grid gap-5 sm:grid-cols-2">
          <CampoTexto v-model="form.inicio" etiqueta="Empieza" tipo="datetime-local" required :error="form.errors.inicio" />
          <CampoTexto v-model="form.fin" etiqueta="Termina (opcional)" tipo="datetime-local" :min="form.inicio || undefined" :error="form.errors.fin" />
        </div>
      </Tarjeta>

      <Tarjeta as="fieldset">
        <legend class="sr-only">Dónde</legend>
        <p :class="leyenda" aria-hidden="true">Dónde</p>
        <UbicacionPicker
          v-model:direccion="form.direccion"
          v-model:latitud="form.latitud"
          v-model:longitud="form.longitud"
          :error="form.errors.latitud ?? form.errors.longitud"
          :error-direccion="form.errors.direccion"
        />
      </Tarjeta>

      <p v-if="!esEdicion && !esModerador" class="flex items-start gap-2 rounded-2xl bg-durazno-100 px-4 py-3 text-sm font-semibold">
        <Icono nombre="escudo" class="size-5 shrink-0 text-violeta-700" />
        Un moderador lo revisa antes de publicarlo en el mapa.
      </p>

      <Boton v-if="esEdicion" variante="fantasma" icono="papelera" bloque class="text-error!" @click="eliminar">Eliminar evento</Boton>
    </div>

    <div class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 border-t border-niebla-200 bg-papel/95 p-3 backdrop-blur md:static md:mt-6 md:border-0 md:bg-transparent md:p-0">
      <Boton tipo="submit" bloque :cargando="form.processing || comprimiendo">
        {{ form.processing ? 'Guardando…' : esEdicion ? 'Guardar cambios' : esModerador ? 'Publicar' : 'Enviar a revisión' }}
      </Boton>
    </div>
  </form>
</template>
