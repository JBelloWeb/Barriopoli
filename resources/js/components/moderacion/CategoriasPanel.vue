<script setup>
  import { ref } from 'vue';
  import { router, useForm } from '@inertiajs/vue3';
  import BottomSheet from '@/components/BottomSheet.vue';
  import CampoTexto from '@/components/CampoTexto.vue';
  import Boton from '@/components/ui/Boton.vue';
  import Icono from '@/components/ui/Icono.vue';
  import Insignia from '@/components/ui/Insignia.vue';
  import Tarjeta from '@/components/ui/Tarjeta.vue';
  import { claseError, claseEtiqueta } from '@/lib/campos';

  defineProps({ categorias: { type: Array, required: true } });

  const hojaAbierta = ref(false);
  const edicionId = ref(null);
  const form = useForm({ nombre: '', color: '#8e66cc', icono: '', activa: true, orden: 0 });

  const abrir = (categoria = null) => {
    edicionId.value = categoria?.id ?? null
    form.clearErrors()
    form.nombre = categoria?.nombre ?? ''
    form.color = categoria?.color ?? '#8e66cc'
    form.icono = categoria?.icono ?? ''
    form.activa = categoria?.activa ?? true
    form.orden = categoria?.orden ?? 0
    hojaAbierta.value = true
  }

  const guardar = () => {
    const opciones = { preserveScroll: true, onSuccess: () => { hojaAbierta.value = false } }
    if (edicionId.value) form.put(`/moderacion/categorias/${edicionId.value}`, opciones);
    else form.post('/moderacion/categorias', opciones);
  }

  const eliminar = () => {
    if (window.confirm('¿Eliminar esta categoría?')) {
      router.delete(`/moderacion/categorias/${edicionId.value}`, {
        preserveScroll: true,
        onSuccess: () => { hojaAbierta.value = false },
      })
    }
  }
</script>

<template>
  <div class="mt-5">
    <Boton bloque icono="mas" @click="abrir()">Nueva categoría</Boton>

    <Tarjeta as="ul" sin-padding class="mt-4 divide-y divide-niebla-200 overflow-hidden">
      <li v-for="categoria in categorias" :key="categoria.id">
        <button type="button" class="flex min-h-14 w-full items-center gap-3 px-4 text-left hover:bg-violeta-50" @click="abrir(categoria)">
          <span class="size-4 shrink-0 rounded-full ring-2 ring-white" :style="{ backgroundColor: categoria.color }" aria-hidden="true"></span>
          <span class="flex-1 font-semibold"><span aria-hidden="true">{{ categoria.icono }}</span> {{ categoria.nombre }}</span>
          <Insignia v-if="!categoria.activa" tono="neutro">Inactiva</Insignia>
          <Icono nombre="chevron" class="size-5 text-niebla-500" />
        </button>
      </li>
    </Tarjeta>
  </div>

  <BottomSheet v-model="hojaAbierta" :titulo="edicionId ? 'Editar categoría' : 'Nueva categoría'">
    <form class="space-y-4" @submit.prevent="guardar">
      <CampoTexto v-model="form.nombre" etiqueta="Nombre" required :error="form.errors.nombre" />
      <CampoTexto v-model="form.icono" etiqueta="Ícono (un emoji)" :error="form.errors.icono" />
      <div>
        <label for="color-categoria" :class="claseEtiqueta">Color</label>
        <div class="flex items-center gap-3">
          <input id="color-categoria" v-model="form.color" type="color" class="h-12 w-20 cursor-pointer rounded-xl border-2 border-niebla-200 bg-white p-1" />
          <span class="font-mono text-sm text-niebla-600">{{ form.color }}</span>
        </div>
        <p class="mt-1.5 text-sm text-niebla-600">Se muestra como un punto junto al nombre; el texto siempre va en negro.</p>
        <p v-if="form.errors.color" :class="claseError"><Icono nombre="alerta" class="mt-px size-4" />{{ form.errors.color }}</p>
      </div>
      <label class="flex min-h-11 cursor-pointer items-center gap-3 font-semibold">
        <input v-model="form.activa" type="checkbox" class="size-5 accent-violeta-700" /> Activa
      </label>
      <Boton tipo="submit" bloque :cargando="form.processing">Guardar</Boton>
      <Boton v-if="edicionId" variante="fantasma" icono="papelera" bloque class="text-error!" @click="eliminar">Eliminar</Boton>
    </form>
  </BottomSheet>
</template>
