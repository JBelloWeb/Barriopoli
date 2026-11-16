// Fechas siempre en hora de Buenos Aires, aunque el dispositivo tenga otra zona.
// 24 h y espacios normalizados: con SSR, Node y el navegador traen ICU distintos
// (p. ej. U+202F en "p. m.") y el texto no coincidiría al hidratar.
const zona = 'America/Argentina/Buenos_Aires'
const dia = new Intl.DateTimeFormat('es-AR', { timeZone: zona, weekday: 'short', day: 'numeric', month: 'short' })
const hora = new Intl.DateTimeFormat('es-AR', { timeZone: zona, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })

const limpiar = (texto) => texto.replace(/[  ]/g, ' ')

export const formatearDia = (iso) => limpiar(dia.format(new Date(iso)))
const formatearHora = (iso) => limpiar(hora.format(new Date(iso)))

// "18:00" → "18", "18:30" → "18:30" (como "20 a 3 h" en las piezas de marca).
const horaCorta = (iso) => formatearHora(iso).replace(/:00$/, '')
const DOCE_HORAS_MS = 12 * 60 * 60 * 1000

/** Datos para las pastillas del evento: ['sáb 5 dic', '18 a 22 h'] (o '+ hasta dom 6 dic' si dura días). */
export const pastillasEvento = (inicio, fin) => {
  const pastillas = [formatearDia(inicio).replace(',', '')]
  if (!fin) return [...pastillas, `${horaCorta(inicio)} h`]

  const duracion = new Date(fin) - new Date(inicio)
  // Mismo día o una noche (p. ej. 20 a 3 h): un solo rango horario.
  if (formatearDia(inicio) === formatearDia(fin) || duracion <= DOCE_HORAS_MS) {
    return [...pastillas, `${horaCorta(inicio)} a ${horaCorta(fin)} h`]
  }
  return [...pastillas, `${horaCorta(inicio)} h`, `hasta ${formatearDia(fin).replace(',', '')}`]
}

export const formatearEvento = (inicio, fin) => {
  const desde = `${formatearDia(inicio)} · ${formatearHora(inicio)}`
  if (!fin) return desde
  const mismoDia = formatearDia(inicio) === formatearDia(fin)
  return mismoDia ? `${desde} a ${formatearHora(fin)}` : `${desde} al ${formatearDia(fin)} · ${formatearHora(fin)}`
}
