// Fechas siempre en hora de Buenos Aires, aunque el dispositivo tenga otra zona.
// 24 h y espacios normalizados: con SSR, Node y el navegador traen ICU distintos
// (p. ej. U+202F en "p. m.") y el texto no coincidiría al hidratar.
const zona = 'America/Argentina/Buenos_Aires'
const dia = new Intl.DateTimeFormat('es-AR', { timeZone: zona, weekday: 'short', day: 'numeric', month: 'short' })
const hora = new Intl.DateTimeFormat('es-AR', { timeZone: zona, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })

const limpiar = (texto) => texto.replace(/[  ]/g, ' ')

export const formatearDia = (iso) => limpiar(dia.format(new Date(iso)))
const formatearHora = (iso) => limpiar(hora.format(new Date(iso)))

export const formatearEvento = (inicio, fin) => {
  const desde = `${formatearDia(inicio)} · ${formatearHora(inicio)}`
  if (!fin) return desde
  const mismoDia = formatearDia(inicio) === formatearDia(fin)
  return mismoDia ? `${desde} a ${formatearHora(fin)}` : `${desde} al ${formatearDia(fin)} · ${formatearHora(fin)}`
}
