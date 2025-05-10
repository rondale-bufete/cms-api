document.addEventListener('DOMContentLoaded', function() {
    // Check if viewer container exists
    const viewerContainer = document.getElementById('model-viewer');
    if (!viewerContainer) return;
    
    // Get file data from the container
    const filePath = viewerContainer.getAttribute('data-file-path');
    const fileType = viewerContainer.getAttribute('data-file-type');
    
    if (!filePath || !fileType) {
        viewerContainer.innerHTML = '<div class="alert alert-danger">Error: File information is missing.</div>';
        return;
    }
    
    // Initialize viewer based on file type
    if (fileType === 'glb') {
        initializeThreeJSViewer(filePath, viewerContainer);
    } else if (fileType === 'obj') {
        initializeOBJViewer(filePath, viewerContainer);
    } else {
        viewerContainer.innerHTML = '<div class="alert alert-warning">Preview not available for this file type. You can download the file to view it.</div>';
    }
});

function initializeThreeJSViewer(filePath, container) {
    // Add loading indicator
    container.innerHTML = '<div class="spinner-border text-primary position-absolute top-50 start-50" role="status"><span class="visually-hidden">Loading...</span></div>';
    
    // Load Three.js from CDN
    loadScript('https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js')
        .then(() => loadScript('https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js'))
        .then(() => loadScript('https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/GLTFLoader.js'))
        .then(() => {
            // Clear container
            container.innerHTML = '';
            
            // Create scene
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf0f0f0);
            
            // Create camera
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.z = 5;
            
            // Create renderer
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);
            
            // Add lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.5);
            directionalLight.position.set(0, 1, 0);
            scene.add(directionalLight);
            
            // Add controls
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            
            // Load model
            const loader = new THREE.GLTFLoader();
            loader.load(
                filePath,
                function(gltf) {
                    scene.add(gltf.scene);
                    
                    // Auto-adjust camera to fit the model
                    const box = new THREE.Box3().setFromObject(gltf.scene);
                    const size = box.getSize(new THREE.Vector3()).length();
                    const center = box.getCenter(new THREE.Vector3());
                    
                    controls.target.copy(center);
                    camera.position.copy(center);
                    camera.position.z += size * 1.5;
                    camera.updateProjectionMatrix();
                    
                    // Update controls
                    controls.update();
                },
                function(xhr) {
                    // Progress
                    const percent = (xhr.loaded / xhr.total * 100).toFixed(0);
                    container.innerHTML = `<div class="position-absolute top-50 start-50 translate-middle text-center">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <p>Loading: ${percent}%</p>
                    </div>`;
                },
                function(error) {
                    console.error('Error loading GLB file:', error);
                    container.innerHTML = `<div class="alert alert-danger position-absolute top-50 start-50 translate-middle">
                        Error loading model: ${error.message}
                    </div>`;
                }
            );
            
            // Handle window resize
            window.addEventListener('resize', function() {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                controls.update();
                renderer.render(scene, camera);
            }
            
            animate();
        })
        .catch(error => {
            console.error('Error loading Three.js:', error);
            container.innerHTML = `<div class="alert alert-danger">Error loading 3D viewer: ${error.message}</div>`;
        });
}

function initializeOBJViewer(filePath, container) {
    // Similar approach as GLB viewer but with OBJLoader
    // Add loading indicator
    container.innerHTML = '<div class="spinner-border text-primary position-absolute top-50 start-50" role="status"><span class="visually-hidden">Loading...</span></div>';
    
    // Load Three.js and OBJLoader
    loadScript('https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js')
        .then(() => loadScript('https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/controls/OrbitControls.js'))
        .then(() => loadScript('https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/OBJLoader.js'))
        .then(() => loadScript('https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/MTLLoader.js'))
        .then(() => {
            // Clear container
            container.innerHTML = '';
            
            // Create scene, camera, renderer similar to GLB viewer
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf0f0f0);
            
            const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.z = 5;
            
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);
            
            // Add lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
            scene.add(ambientLight);
            
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.5);
            directionalLight.position.set(0, 1, 0);
            scene.add(directionalLight);
            
            // Add controls
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
            
            // Load model
            const objLoader = new THREE.OBJLoader();
            
            // Check if an MTL file with the same name exists
            const mtlFilePath = filePath.replace('.obj', '.mtl');
            
            // Function to load OBJ with or without materials
            function loadOBJ(materials = null) {
                if (materials) {
                    objLoader.setMaterials(materials);
                }
                
                objLoader.load(
                    filePath,
                    function(object) {
                        scene.add(object);
                        
                        // Auto-adjust camera to fit the model
                        const box = new THREE.Box3().setFromObject(object);
                        const size = box.getSize(new THREE.Vector3()).length();
                        const center = box.getCenter(new THREE.Vector3());
                        
                        controls.target.copy(center);
                        camera.position.copy(center);
                        camera.position.z += size * 1.5;
                        camera.updateProjectionMatrix();
                        
                        // Update controls
                        controls.update();
                    },
                    function(xhr) {
                        // Progress
                        const percent = (xhr.loaded / xhr.total * 100).toFixed(0);
                        container.innerHTML = `<div class="position-absolute top-50 start-50 translate-middle text-center">
                            <div class="spinner-border text-primary mb-2" role="status"></div>
                            <p>Loading: ${percent}%</p>
                        </div>`;
                    },
                    function(error) {
                        console.error('Error loading OBJ file:', error);
                        container.innerHTML = `<div class="alert alert-danger position-absolute top-50 start-50 translate-middle">
                            Error loading model: ${error.message}
                        </div>`;
                    }
                );
            }
            
            // Try to load MTL file first
            const mtlLoader = new THREE.MTLLoader();
            mtlLoader.load(
                mtlFilePath,
                function(materials) {
                    materials.preload();
                    loadOBJ(materials);
                },
                function() {
                    // MTL loading progress (not used)
                },
                function() {
                    // MTL loading failed, load OBJ without materials
                    console.warn('No MTL file found or error loading MTL, loading OBJ without materials');
                    loadOBJ();
                }
            );
            
            // Handle window resize
            window.addEventListener('resize', function() {
                camera.aspect = container.clientWidth / container.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(container.clientWidth, container.clientHeight);
            });
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                controls.update();
                renderer.render(scene, camera);
            }
            
            animate();
        })
        .catch(error => {
            console.error('Error loading Three.js:', error);
            container.innerHTML = `<div class="alert alert-danger">Error loading 3D viewer: ${error.message}</div>`;
        });
}

// Helper function to load scripts dynamically
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
}
