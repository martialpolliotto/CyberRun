<?php
/**
 * Charge three.js + GLTFLoader (1x par page) et initialise tous les viewers
 * marqués data-viewer="three". Idempotent : safe à inclure plusieurs fois.
 */

if (defined('CYBERRUN_THREE_INCLUDED')) return;
define('CYBERRUN_THREE_INCLUDED', true);
?>

<script type="importmap">
{
    "imports": {
        "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
    }
}
</script>
<script type="module">
import * as THREE from 'three';
import { GLTFLoader }    from 'three/addons/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

function initViewer(el) {
    const src = el.dataset.modelSrc;
    if (!src) return;

    const w = el.clientWidth;
    const h = el.clientHeight;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x000000);

    const camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 100);
    camera.position.set(2, 2, 3);

    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(w, h);
    renderer.setPixelRatio(window.devicePixelRatio);
    el.appendChild(renderer.domElement);

    // Lumières basiques cyberpunk
    scene.add(new THREE.AmbientLight(0xffffff, 0.4));
    const dir = new THREE.DirectionalLight(0x22d3ee, 1.2);   // cyan accent
    dir.position.set(5, 5, 5);
    scene.add(dir);
    const fill = new THREE.DirectionalLight(0xec4899, 0.6);  // pink fill
    fill.position.set(-5, -3, 2);
    scene.add(fill);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 1.0;

    new GLTFLoader().load(src, (gltf) => {
        const obj = gltf.scene;
        // Centrer + scaler pour que ça rentre dans la vue
        const box = new THREE.Box3().setFromObject(obj);
        const size = box.getSize(new THREE.Vector3()).length();
        const center = box.getCenter(new THREE.Vector3());
        obj.position.sub(center);
        const scale = 1.5 / size;
        obj.scale.setScalar(scale);
        scene.add(obj);
    }, undefined, (err) => {
        console.error('[item_viewer] failed to load', src, err);
    });

    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }
    animate();
}

document.querySelectorAll('[data-viewer="three"]').forEach(initViewer);
</script>
