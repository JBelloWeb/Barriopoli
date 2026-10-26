<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    barrios: {
        type: Array,
        required: true,
    }
});

const emit = defineEmits(['selected']);

const searchQuery = ref('');
const isOpen = ref(false);

const filteredBarrios = computed (() => {
    if(!searchQuery.value) return props.barrios;

    return props.barrios.filter(barrio =>
        barrio.nombre.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const selectBarrio = (barrio) => {
    searchQuery.value = barrio.nombre;
    isOpen.value = false;
    emit('selected', barrio);
}
</script>

<template>
    <div class="fixed top-6 left-1/2 -translate-x-1/2 w-full max-w-md px-4 z-50">
        <input 
            type="text"
            v-model="searchQuery"
            @focus="isOpen = true"
            placeholder = "Buscar barrio..."
            class="w-full px-4 py-2 border rounded-md shadow-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        />

        <ul
            v-show = "isOpen && filteredBarrios.length > 0"
            class="mt-1 w-full bg-white border rounded-md shadow-lg max-h-60 overflow-y-auto divide-y divide-gray-100"
        >
            <li
                v-for="barrio in filteredBarrios"
                :key="barrio.id"
                @click="selectBarrio(barrio)"
                class="px-4 py-2 cursor-pointer hover:bg-gray-100 transition-colors"
            >
            <div class="font-medium text-gray-800">{{ barrio.nombre }}</div>
            <div class="text-xs text-gray-500">{{ barrio.comuna }}</div>
            </li>
        </ul>

        <div v-show="isOpen && filteredBarrios.length === 0" class="mt-1 w-full bg-white border rounded-md shadow-lg px-4 py-3 text-sm text-gray-500 text-center">
            No se encontraro barrios
        </div>
    </div>
</template>