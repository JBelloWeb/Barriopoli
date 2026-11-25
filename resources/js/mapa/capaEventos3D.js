import * as maplibregl from 'maplibre-gl'
import * as THREE from 'three'
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js'

const ALTURA_PIN_M = 40;    // minimo
const ALTURA_PIN_PX = 44; // min con barrio seleccionado
const BASE_ALTITUD_M = 10;  // para el z idnex
const CIRCUNFERENCIA_M = 40075016.686;
const TAMANO_TESELA = 512;
const COLLIDER_RADIO_M = 28 ;     // clic-collider
const COLLIDER_EXTRA_M = 25;      // clic-collider

const COLOR_POR_DEFECTO = '#8e66cc';

export const crearCapaEventos3D = (features, modelUrl) => {
    let camera, scene, renderer, mapInstance;
    const items = [];
    const rotX= new THREE.Matrix4().makeRotationX(Math.PI / 2);

    const materiales = new Map();
    let categoriasVisibles = null;
    let eliminada = false;

    const materialPara = (color, base) => {
        if (!materiales.has(color)) {
            const material = base.clone();
            material.color.set(color);
            materiales.set(color, material);
        }
        return materiales.get(color);
    }

    return {
        id: 'eventos-3d',
        type: 'custom',
        renderingMode: '3d',

        setCategoriasVisibles(ids) {
            categoriasVisibles = ids.length ? new Set(ids) : null;
            mapInstance?.triggerRepaint();
        },

        onAdd(mapCtx, gl) {
            mapInstance = mapCtx;
            camera = new THREE.Camera();
            scene = new THREE.Scene();

            scene.add(new THREE.AmbientLight(0xffffff, 2.5)); //INENCIDAD Y TONO
            const dir = new THREE.DirectionalLight(0xffffff, 2.0);
            dir.position.set(0, -70, 100).normalize();
            scene.add(dir);

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
                        const [lng, lat] = f.geometry.coordinates;
                        const mc = maplibregl.MercatorCoordinate.fromLngLat([lng, lat], BASE_ALTITUD_M);

                        const pin = gltf.scene.clone(true);
                        pin.position.set(-center.x, -box.min.y, -center.z);
                        const color = f.properties.color ?? COLOR_POR_DEFECTO;
                        pin.traverse((o) => { if (o.isMesh) o.material = materialPara(color, o.material) });

                        //Para que el pin sea fixed de cara a la camara, rota sobre su propio eje
                        const rotator = new THREE.Group();
                        rotator.add(pin);

                        const group = new THREE.Group();
                        group.add(rotator);
                        group.visible = false;
                        group.traverse((o) => { o.frustumCulled = false });
                        scene.add(group);
                        items.push({ group, rotator, mc, lat, altoModelo, transform: new THREE.Matrix4(), categoriaId: f.properties.categoria_id });
                    })
                    mapInstance.triggerRepaint();
                },
                undefined,

                (err) => { if (!eliminada) console.error('Error al cargar el modelo 3D:', err) }
            )
        },

        render(gl, args) {
            if (!items.length) return;

            const mat = (Array.isArray(args) || ArrayBuffer.isView(args)) ? args : (args.defaultProjectionData?.mainMatrix ?? args.modelViewProjectionMatrix);
            const mapMatrix = new THREE.Matrix4().fromArray(mat);
            const bearing = (mapInstance.getBearing() * Math.PI) / 180;
            const zoom = mapInstance.getZoom();


            renderer.resetState();

            for (const it of items) {
                if (categoriasVisibles && !categoriasVisibles.has(it.categoriaId)) continue;

                const metrosPorPx = (CIRCUNFERENCIA_M * Math.cos((it.lat * Math.PI) / 180)) / (TAMANO_TESELA * 2 ** zoom);
                const alturaM = Math.max(ALTURA_PIN_M, metrosPorPx * ALTURA_PIN_PX);
                const s = it.mc.meterInMercatorCoordinateUnits() * (alturaM / it.altoModelo);

                it.transform.makeTranslation(it.mc.x, it.mc.y, it.mc.z).scale(new THREE.Vector3(s, -s, s)).multiply(rotX);
                it.rotator.rotation.y = bearing;
                camera.projectionMatrix.copy(mapMatrix).multiply(it.transform);
                it.group.visible = true;
                renderer.render(scene, camera);
                it.group.visible = false;
            }
        },

        onRemove() {
            eliminada = true;
            items.length = 0;
            materiales.forEach((m) => m.dispose());
            materiales.clear();
            scene?.clear();
            renderer?.dispose();
        }
    }
}
