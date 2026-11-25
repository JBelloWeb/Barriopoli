<script setup>
    import { computed, ref, watch } from 'vue';
    import Icono from '@/components/ui/Icono.vue';
    import { nombreLegible } from '@/lib/texto';

    const props = defineProps({
        barrios: { type: Array, required: true },
        actual: { type: Object, default: null },
    })

    const emit = defineEmits(['selected']);

    const busqueda = ref(nombreLegible(props.actual?.nombre));
    const abierto = ref(false);

    watch(() => props.actual?.id, () => { busqueda.value = nombreLegible(props.actual?.nombre) });

    const normalizar = (texto) => texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    const filtrados = computed(() => {
        const q = normalizar(busqueda.value);
        if (!q || q === normalizar(nombreLegible(props.actual?.nombre))) return props.barrios;

        return props.barrios.filter((b) => normalizar(b.nombre).includes(q) || normalizar(nombreLegible(b.nombre)).includes(q));
    });

    const elegir = (barrio) => {
        busqueda.value = nombreLegible(barrio.nombre);
        abierto.value = false;
        emit('selected', barrio);
    }

    const alPresionarTecla = (evento) => {
        if (evento.key === 'Escape') abierto.value = false;
        if (evento.key === 'Enter' && filtrados.value.length) elegir(filtrados.value[0]);
    }
</script>

<template>
    <div class="relative mx-auto w-full max-w-md">
        <label for="buscar-barrio" class="sr-only">Buscar barrio</label>
        <Icono nombre="buscar" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-violeta-500" />

        <input
            id="buscar-barrio"
            v-model="busqueda"
            type="search"
            autocomplete="off"
            enterkeyhint="go"
            placeholder="Buscá un barrio"
            role="combobox"
            aria-controls="lista-barrios"
            :aria-expanded="abierto"
            class="min-h-12 w-full rounded-full border-2 border-lila-200 bg-white pl-11 pr-4 text-base font-semibold text-tinta placeholder:font-normal placeholder:text-niebla-500 focus:border-violeta-500 focus:outline-none focus:ring-4 focus:ring-violeta-100"
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
            class="absolute inset-x-0 top-full z-30 mt-2 max-h-[50dvh] overflow-y-auto rounded-3xl bg-white p-2 shadow-flotante"
        >

            <li
                v-for="b in filtrados"
                :key="b.id"
                role="option"
                :aria-selected="b.id === actual?.id"
                class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-2xl px-4 py-2 hover:bg-violeta-50"
                :class="b.id === actual?.id ? 'bg-violeta-100 font-bold text-violeta-700' : 'text-tinta'"
                @mousedown.prevent="elegir(b)"
            >
                <span>{{ nombreLegible(b.nombre) }}</span>
                <span class="shrink-0 text-xs font-semibold text-niebla-600">Comuna {{ b.comuna }}</span>
            </li>
        </ul>

        <p v-show="abierto && !filtrados.length" class="absolute inset-x-0 top-full z-30 mt-2 rounded-3xl bg-white px-4 py-4 text-center text-sm text-niebla-600 shadow-flotante">
            No encontramos ese barrio
        </p>
    </div>
</template>
