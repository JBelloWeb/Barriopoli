// El GeoJSON oficial trae los barrios en MAYÚSCULAS y sin tildes ("SAN NICOLAS").
// Para mostrar: tildes y nombres de uso común. La búsqueda sigue usando el original.
const EXCEPCIONES = {
    AGRONOMIA: 'Agronomía',
    BOCA: 'La Boca',
    CONSTITUCION: 'Constitución',
    'NUÑEZ': 'Núñez',
    NUNEZ: 'Núñez',
    'SAN CRISTOBAL': 'San Cristóbal',
    'SAN NICOLAS': 'San Nicolás',
    'VELEZ SARSFIELD': 'Vélez Sarsfield',
    'VILLA GRAL. MITRE': 'Villa General Mitre',
    'VILLA ORTUZAR': 'Villa Ortúzar',
    'VILLA PUEYRREDON': 'Villa Pueyrredón',
}

const MENORES = new Set(['de', 'del', 'la', 'las', 'los', 'y'])

export function nombreLegible(nombre) {
    if (!nombre) return ''
    const clave = nombre.trim().toUpperCase()
    if (EXCEPCIONES[clave]) return EXCEPCIONES[clave]

    return clave
        .toLocaleLowerCase('es-AR')
        .split(' ')
        .map((palabra, i) => (i > 0 && MENORES.has(palabra) ? palabra : palabra.charAt(0).toLocaleUpperCase('es-AR') + palabra.slice(1)))
        .join(' ')
}
