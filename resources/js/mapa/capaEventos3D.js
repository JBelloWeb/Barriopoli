// Capa personalizada de MapLibre que dibuja los pines de eventos como modelos 3D con Three.js.
import * as maplibregl from 'maplibre-gl'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'

const ALTURA_PIN_M = 40
const BASE_ALTITUD_M = 10  // sube respecto a los 40 m de la extrusión para evitar z-fighting
const COLLIDER_RADIO_M = 28      // ancho clicable (el pin mide ~40 m de ancho)
const COLLIDER_EXTRA_M = 25      // cuánto sobresale por arriba y por abajo del pin

export const crearCapaEventos3D = (features, modelUrl) => {
    let camera, scene, renderer, mapInstance
    const items = []
    const rotX = new THREE.Matrix4().makeRotationX(Math.PI / 2)

    return {
        id: 'eventos-3d',
        type: 'custom',
        renderingMode: '3d',

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
                    const k = ALTURA_PIN_M / (size.y || 1)

                    features.forEach((f) => {
                        const [lng, lat] = f.geometry.coordinates
                        const mc = maplibregl.MercatorCoordinate.fromLngLat([lng, lat], BASE_ALTITUD_M)
                        const s = mc.meterInMercatorCoordinateUnits() * k

                        const pin = gltf.scene.clone(true)
                        pin.position.set(-center.x, -box.min.y, -center.z)

                        // rotator: gira el pin sobre su propio eje vertical
                        const rotator = new THREE.Group()
                        rotator.add(pin)

                        const group = new THREE.Group()
                        group.add(rotator)
                        group.visible = false
                        group.traverse((o) => { o.frustumCulled = false })
                        scene.add(group)

                        // T · S · R en doble precisión (orden del ejemplo oficial)
                        const transform = new THREE.Matrix4()
                            .makeTranslation(mc.x, mc.y, mc.z)
                            .scale(new THREE.Vector3(s, -s, s))
                            .multiply(rotX)

                        items.push({ group, rotator, transform })
                    })
                    mapInstance.triggerRepaint()
                },
                undefined,
                (err) => console.error('Error cargando el modelo 3D:', err)
            )
        },

        render(gl, args) {
            if (!items.length) return

            const mat = (Array.isArray(args) || ArrayBuffer.isView(args))
                ? args
                : (args.defaultProjectionData?.mainMatrix ?? args.modelViewProjectionMatrix)

            const mapMatrix = new THREE.Matrix4().fromArray(mat)
            const bearing = (mapInstance.getBearing() * Math.PI) / 180

            renderer.resetState()
            for (const it of items) {
                it.rotator.rotation.y = bearing
                camera.projectionMatrix.copy(mapMatrix).multiply(it.transform)
                it.group.visible = true
                renderer.render(scene, camera)
                it.group.visible = false
            }
        },

        onRemove() {
            items.length = 0
            scene?.clear()
            renderer?.dispose()
        }
    }
}
