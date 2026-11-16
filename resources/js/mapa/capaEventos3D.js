// Capa personalizada de MapLibre que dibuja los pines de eventos como modelos 3D con Three.js.
import * as maplibregl from 'maplibre-gl'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'

const ALTURA_PIN_M = 40    // altura mínima en el mundo (zoom cercano)
const ALTURA_PIN_PX = 44   // altura mínima en pantalla: a zoom de barrio 40 m eran 3–4 px
const BASE_ALTITUD_M = 10  // sube respecto a los 40 m de la extrusión para evitar z-fighting
const CIRCUNFERENCIA_M = 40075016.686
const TAMANO_TESELA = 512
const COLLIDER_RADIO_M = 28      // ancho clicable (el pin mide ~40 m de ancho)
const COLLIDER_EXTRA_M = 25      // cuánto sobresale por arriba y por abajo del pin

// pin.glb tiene un único material sin texturas: se tiñe cambiando material.color.
const COLOR_POR_DEFECTO = '#8e66cc'

export const crearCapaEventos3D = (features, modelUrl) => {
    let camera, scene, renderer, mapInstance
    const items = []
    const rotX = new THREE.Matrix4().makeRotationX(Math.PI / 2)

    // Un material por color, no por pin: el modelo tiene 91 mallas y clonarlo
    // por malla multiplicaría la memoria sin ninguna diferencia visual.
    const materiales = new Map()
    let categoriasVisibles = null // null = todas
    let eliminada = false

    const materialPara = (color, base) => {
        if (!materiales.has(color)) {
            const material = base.clone()
            material.color.set(color)
            materiales.set(color, material)
        }
        return materiales.get(color)
    }

    return {
        id: 'eventos-3d',
        type: 'custom',
        renderingMode: '3d',

        setCategoriasVisibles(ids) {
            categoriasVisibles = ids.length ? new Set(ids) : null
            mapInstance?.triggerRepaint()
        },

        onAdd(mapCtx, gl) {
            mapInstance = mapCtx
            camera = new THREE.Camera()
            scene = new THREE.Scene()

            scene.add(new THREE.AmbientLight(0xffffff, 2.5))
            const dir = new THREE.DirectionalLight(0xffffff, 2.0)
            dir.position.set(0, -70, 100).normalize()
            scene.add(dir)

            renderer = new THREE.WebGLRenderer({
                canvas: mapCtx.getCanvas(),
                context: gl,
                antialias: true
            })
            renderer.autoClear = false

            new GLTFLoader().load(
                modelUrl,
                (gltf) => {
                    const box = new THREE.Box3().setFromObject(gltf.scene)
                    const size = box.getSize(new THREE.Vector3())
                    const center = box.getCenter(new THREE.Vector3())
                    const altoModelo = size.y || 1

                    features.forEach((f) => {
                        const [lng, lat] = f.geometry.coordinates
                        const mc = maplibregl.MercatorCoordinate.fromLngLat([lng, lat], BASE_ALTITUD_M)

                        const pin = gltf.scene.clone(true)
                        pin.position.set(-center.x, -box.min.y, -center.z)
                        const color = f.properties.color ?? COLOR_POR_DEFECTO
                        pin.traverse((o) => { if (o.isMesh) o.material = materialPara(color, o.material) })

                        // rotator: gira el pin sobre su propio eje vertical
                        const rotator = new THREE.Group()
                        rotator.add(pin)

                        const group = new THREE.Group()
                        group.add(rotator)
                        group.visible = false
                        group.traverse((o) => { o.frustumCulled = false })
                        scene.add(group)

                        // La escala depende del zoom: el transform se arma en cada render.
                        items.push({ group, rotator, mc, lat, altoModelo, transform: new THREE.Matrix4(), categoriaId: f.properties.categoria_id })
                    })
                    mapInstance.triggerRepaint()
                },
                undefined,
                // Si se salió del mapa mientras bajaba el modelo, el fetch abortado no es un error.
                (err) => { if (!eliminada) console.error('Error cargando el modelo 3D:', err) }
            )
        },

        render(gl, args) {
            if (!items.length) return

            const mat = (Array.isArray(args) || ArrayBuffer.isView(args))
                ? args
                : (args.defaultProjectionData?.mainMatrix ?? args.modelViewProjectionMatrix)

            const mapMatrix = new THREE.Matrix4().fromArray(mat)
            const bearing = (mapInstance.getBearing() * Math.PI) / 180
            const zoom = mapInstance.getZoom()

            renderer.resetState()
            for (const it of items) {
                if (categoriasVisibles && !categoriasVisibles.has(it.categoriaId)) continue
                // Metros por píxel en la latitud del pin → el pin nunca baja de ALTURA_PIN_PX en pantalla.
                const metrosPorPx = (CIRCUNFERENCIA_M * Math.cos((it.lat * Math.PI) / 180)) / (TAMANO_TESELA * 2 ** zoom)
                const alturaM = Math.max(ALTURA_PIN_M, metrosPorPx * ALTURA_PIN_PX)
                const s = it.mc.meterInMercatorCoordinateUnits() * (alturaM / it.altoModelo)
                // T · S · R en doble precisión (orden del ejemplo oficial)
                it.transform.makeTranslation(it.mc.x, it.mc.y, it.mc.z).scale(new THREE.Vector3(s, -s, s)).multiply(rotX)

                it.rotator.rotation.y = bearing
                camera.projectionMatrix.copy(mapMatrix).multiply(it.transform)
                it.group.visible = true
                renderer.render(scene, camera)
                it.group.visible = false
            }
        },

        onRemove() {
            eliminada = true
            items.length = 0
            materiales.forEach((m) => m.dispose())
            materiales.clear()
            scene?.clear()
            renderer?.dispose()
        }
    }
}
