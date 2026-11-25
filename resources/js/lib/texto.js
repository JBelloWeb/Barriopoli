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

const CONECTORES = new Set(['de', 'del', 'la', 'las', 'los', 'y'])

export function nombreLegible(nombre) {
    if (!nombre) return ''
    const clave = nombre.trim().toUpperCase()
    if (EXCEPCIONES[clave]) return EXCEPCIONES[clave]

    return clave
        .toLocaleLowerCase('es-AR')
        .split(' ')
        .map((palabra, i) => (i > 0 && CONECTORES.has(palabra) ? palabra : palabra.charAt(0).toLocaleUpperCase('es-AR') + palabra.slice(1)))
        .join(' ')
}
