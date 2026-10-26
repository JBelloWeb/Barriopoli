<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    evento: Object,
    barrios: Array
})

// Inicializamos el formulario con los datos existentes del evento
const form = useForm({
    barrio_id: props.evento.barrio_id,
    titulo: props.evento.titulo,
    categoria: props.evento.categoria,
    direccion: props.evento.direccion,
    fecha: props.evento.fecha,
})

const submit = () => {
    form.put(`/eventos/${props.evento.id}`)
}
</script>

<template>
    <div class="max-w-md mx-auto p-6 bg-white rounded-lg shadow mt-8">
        <h2 class="text-xl font-bold mb-4">Editar Evento #{{ evento.id }}</h2>
        
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block text-sm font-medium">Barrio:</label>
                <select v-model="form.barrio_id" class="w-full border rounded p-2">
                    <option v-for="barrio in barrios" :key="barrio.id" :value="barrio.id">
                        {{ barrio.nombre }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium">Título del evento:</label>
                <input type="text" v-model="form.titulo" class="w-full border rounded p-2" required />
            </div>

            <div>
                <label class="block text-sm font-medium">Categoría:</label>
                <input type="text" v-model="form.categoria" class="w-full border rounded p-2" required />
            </div>

            <div>
                <label class="block text-sm font-medium">Dirección:</label>
                <input type="text" v-model="form.direccion" class="w-full border rounded p-2" required />
            </div>

            <div>
                <label class="block text-sm font-medium">Fecha:</label>
                <input type="datetime-local" v-model="form.fecha" class="w-full border rounded p-2" required />
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-4 py-2 rounded">
                    Guardar Cambios
                </button>
                <a :href="`/barrios`" class="text-gray-600 hover:underline">Cancelar</a>
            </div>
        </form>
    </div>
</template>