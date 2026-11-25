const LADO_MAXIMO = 1600;
const CALIDAD = 0.82;

export async function comprimirImagen(archivo) {
  try {
    const imagen = await createImageBitmap(archivo, { imageOrientation: 'from-image' });
    const escala = Math.min(1, LADO_MAXIMO / Math.max(imagen.width, imagen.height));
    const lienzo = document.createElement('canvas');
    lienzo.width = Math.round(imagen.width * escala);
    lienzo.height = Math.round(imagen.height * escala);
    lienzo.getContext('2d').drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
    imagen.close();

    const blob = await new Promise((resolver) => lienzo.toBlob(resolver, 'image/jpeg', CALIDAD));
    return blob ? new File([blob], 'evento.jpg', { type: 'image/jpeg' }) : archivo;
  } catch {
      return archivo
  }
}
