<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
    barrios: { type: Array, required: true },
    actual: { type: Object, default: null },
})

const emit = defineEmits(['selected'])

const busqueda = ref(props.actual?.nombre ?? '')
const abierto = ref(false)

// El layout persiste: al navegar con la barra o el botón atrás, el texto acompaña al barrio actual.
watch(() => props.actual?.id, () => { busqueda.value = props.actual?.nombre ?? '' })

const normalizar = (texto) => texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()

const filtrados = computed(() => {
    const q = normalizar(busqueda.value)
    if (!q || q === normalizar(props.actual?.nombre ?? '')) return props.barrios
    return props.barrios.filter((b) => normalizar(b.nombre).includes(q))
})

const elegir = (barrio) => {
    busqueda.value = barrio.nombre
    abierto.value = false
    emit('selected', barrio)
}

const alPresionarTecla = (evento) => {
    if (evento.key === 'Escape') abierto.value = false
    if (evento.key === 'Enter' && filtrados.value.length) elegir(filtrados.value[0])
}
</script>

<template>
    <div class="relative mx-auto w-full max-w-md">
        <label for="buscar-barrio" class="sr-only">Buscar barrio</label>
        <input
            id="buscar-barrio"
            v-model="busqueda"
            type="search"
            autocomplete="off"
            enterkeyhint="go"
            placeholder="Buscar barrio..."
            role="combobox"
            aria-controls="lista-barrios"
            :aria-expanded="abierto"
            class="min-h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-base focus:outline-2 focus:outline-violeta"
            @focus="abierto = true"
            @click="abierto = true"
            @input="abierto = true"
            @blur="abierto = false"
            @keydown="alPresionarTecla"
        />

        <ul
            v-show="abierto && filtrados.length"
            id="lista-barrios"
            role="listbox"
            class="absolute inset-x-0 top-full mt-1 max-h-[50dvh] divide-y divide-gray-100 overflow-y-auto rounded-lg border bg-white shadow-lg"
        >
            <!-- mousedown.prevent: elige antes de que el blur del input cierre la lista -->
            <li
                v-for="b in filtrados"
                :key="b.id"
                role="option"
                :aria-selected="b.id === actual?.id"
                class="flex min-h-11 cursor-pointer items-center justify-between px-4 py-2 hover:bg-gray-100"
                :class="{ 'font-bold text-violeta-oscuro': b.id === actual?.id }"
                @mousedown.prevent="elegir(b)"
            >
                <span>{{ b.nombre }}</span>
                <span class="text-xs text-gray-500">Comuna {{ b.comuna }}</span>
            </li>
        </ul>

        <p v-show="abierto && !filtrados.length" class="absolute inset-x-0 top-full mt-1 rounded-lg border bg-white px-4 py-3 text-center text-sm text-gray-500 shadow-lg">
            No se encontraron barrios
        </p>
    </div>
</template>
