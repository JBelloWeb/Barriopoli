// Estilo compartido por CampoTexto, CampoArea y CampoSelect.
export const claseEtiqueta = 'mb-1.5 block text-sm font-bold text-tinta'

export const claseControl = [
    'w-full rounded-xl border-2 border-niebla-200 bg-white px-4 text-base text-tinta',
    'placeholder:text-niebla-500 transition duration-150 motion-reduce:transition-none',
    'focus:border-violeta-500 focus:outline-none focus:ring-4 focus:ring-violeta-100',
    'aria-invalid:border-error aria-invalid:focus:ring-error/15',
    'disabled:bg-niebla-100 disabled:text-niebla-600',
].join(' ')

export const claseAyuda = 'mt-1.5 text-sm text-niebla-600'
export const claseError = 'mt-1.5 flex items-start gap-1 text-sm font-bold text-error'
