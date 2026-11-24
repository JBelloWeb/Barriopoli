/** Ejecuta `fn` recién cuando pasan `espera` ms sin una nueva llamada (buscador en vivo). */
export function debounce(fn, espera = 300) {
    let temporizador
    return (...args) => {
        clearTimeout(temporizador)
        temporizador = setTimeout(() => fn(...args), espera)
    }
}

/** Copia del objeto sin los valores vacíos: la URL queda limpia (`?q=ana` en vez de `?q=ana&rol=`). */
export function sinVacios(objeto) {
    return Object.fromEntries(Object.entries(objeto).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined))
}
