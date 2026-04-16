<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DriveSim Ultra | Smooth & Warning Systems</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <style>
        body { margin: 0; overflow: hidden; background: #000; font-family: 'Segoe UI', sans-serif; }
        #hud {
            position: absolute; top: 30px; left: 30px; z-index: 100;
            background: rgba(0, 10, 25, 0.8); backdrop-filter: blur(15px);
            padding: 20px; border-radius: 15px; color: white; border-left: 5px solid #00ffcc;
            min-width: 150px;
        }
        #hud-right {
            position: absolute; top: 30px; right: 30px; z-index: 100;
            text-align: right; color: white;
        }
        #speed-num { font-size: 48px; font-weight: 800; color: #00ffcc; display: block; transition: color 0.2s; }
        #cruise-status { font-size: 12px; font-weight: bold; color: #555; margin-top: 5px; }
        #speed-alert { 
            position: fixed; top: 20%; left: 50%; transform: translateX(-50%);
            color: #ff3e3e; font-weight: 900; font-size: 40px; letter-spacing: 10px;
            display: none; text-shadow: 0 0 20px #f00; z-index: 1000;
        }
        #flash {
            position: fixed; top:0; left:0; width:100%; height:100%; 
            background: rgba(255,0,0,0); pointer-events: none; z-index: 2000;
            transition: background 0.1s;
        }
        #loading-screen {
            position: fixed; top:0; left:0; width:100%; height:100%; background:#000;
            color:#00ffcc; display:flex; flex-direction:column; justify-content:center; align-items:center; z-index:5000;
        }
        #violation-card, #congrats-card {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle, rgba(0,20,40,0.9) 0%, #000 100%); color: white; 
            flex-direction: column; justify-content: center; align-items: center; z-index: 4000;
            text-align: center;
        }
        #violation-card { background: radial-gradient(circle, #200 0%, #000 100%); }
        .btn-ui {
            margin-top: 10px; padding: 10px 20px; background: rgba(255,255,255,0.1);
            border: 1px solid #00ffcc; color: #00ffcc; cursor: pointer; border-radius: 5px;
        }
        .btn-main {
            padding: 15px 40px; background: #00ffcc; border:none; cursor:pointer; font-weight:bold; color: black; margin-top: 20px;
        }
    </style>
</head>
<body>

<div id="flash"></div>
<div id="speed-alert">SPEED</div>

<div id="loading-screen">
    <h2 style="letter-spacing:5px;">DRIVESIM ULTRA</h2>
    <p id="load-msg">CONNECTING TO DATABASE...</p>
</div>

<div id="hud">
    <div style="font-size: 10px; opacity: 0.6;">VELOCITY</div>
    <span id="speed-num">0</span>
    <div style="font-size: 10px; opacity: 0.6;">KM/H</div>
    <div id="cruise-status">CRUISE: OFF</div>
    <hr style="opacity:0.1; margin: 10px 0;">
    <div style="color: #ff3e3e;">STRIKES: <span id="v-count">0</span> / 3</div>
</div>

<div id="hud-right">
    <div style="font-size: 12px; color: #00ffcc;">LEVEL <span id="lvl">1</span></div>
    <div style="width: 150px; height: 4px; background: rgba(255,255,255,0.1); margin: 5px 0;">
        <div id="xp-bar" style="width: 0%; height: 100%; background: #00ffcc;"></div>
    </div>
    <button class="btn-ui" onclick="toggleTimeMode()">TOGGLE DAY/NIGHT</button>
</div>

<div id="congrats-card">
    <h1 style="font-size: 40px; color: #00ffcc; margin-bottom: 10px;">LEVEL COMPLETE</h1>
    <p id="congrats-msg" style="font-size: 20px; max-width: 600px; line-height: 1.6;">Congratulations! You completed level 1 with all obeying traffic rules.</p>
    <button class="btn-main" onclick="closeCongrats()">CONTINUE TO LEVEL 2</button>
</div>

<div id="violation-card">
    <h1 style="font-size: 60px; margin: 0;">TERMINATED</h1>
    <p id="v-msg" style="opacity: 0.7;"></p>
    <button class="btn-main" onclick="location.reload()">REBOOT</button>
</div>

<script>
let scene, camera, renderer, playerCar, canDrive = false;
let speed = 0, targetRotation = 0, violationCount = 0, lastViolationTime = 0;
let keys = {}, trafficCars = [], maxSpeedLimit = 1.05;
let isDay = false, sunLight, hemiLight;
let headLightL, headLightR, headLightLensL, headLightLensR;
let cruiseActive = false, cruiseSetSpeed = 0;
let trafficLights = [], playerXP = 0, playerLevel = 1;

// Database Variables
let carColor = "#00ffcc";

async function fetchDatabaseSettings() {
    try {
        const response = await fetch('db.php?action=get_car');
        const data = await response.json();
        if(data.paint_color) carColor = data.paint_color;
        console.log("Database Linked. Car Color:", carColor);
        init(); // Start the game only after data is found
    } catch (e) {
        console.error("DB Fetch Failed, using defaults", e);
        init(); // Start anyway with defaults
    }
}

function createTrafficLight(z) {
    const group = new THREE.Group();
    const pole = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.2, 22), new THREE.MeshStandardMaterial({color: 0x111111}));
    pole.position.y = 11;
    const box = new THREE.Mesh(new THREE.BoxGeometry(2, 6, 2), new THREE.MeshStandardMaterial({color: 0x000000}));
    box.position.y = 20;
    
    const red = new THREE.PointLight(0xff0000, 0, 40);
    const green = new THREE.PointLight(0x00ff00, 40, 40);
    red.position.set(0, 22, 1.2);
    green.position.set(0, 18, 1.2);

    group.add(pole, box, red, green);
    group.position.set(-18, 0, z);
    scene.add(group);
    trafficLights.push({ group, red, green, state: 'GREEN', timer: Math.random() * 6, z, passed: false });
}

function toggleTimeMode() {
    isDay = !isDay;
    const sky = isDay ? 0x87ceeb : 0x000205;
    scene.background = new THREE.Color(sky);
    scene.fog.color = new THREE.Color(sky);
    hemiLight.intensity = isDay ? 1.0 : 0.05;
    sunLight.intensity = isDay ? 1.5 : 0;

    const hIntensity = isDay ? 0 : 60;
    headLightL.intensity = hIntensity;
    headLightR.intensity = hIntensity;
    headLightLensL.material.opacity = isDay ? 0.1 : 1;
    headLightLensR.material.opacity = isDay ? 0.1 : 1;
}

function closeCongrats() {
    document.getElementById('congrats-card').style.display = 'none';
    canDrive = true;
}

function init() {
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x000205, 0.004);
    camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 10000);
    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    document.body.appendChild(renderer.domElement);
    
    hemiLight = new THREE.HemisphereLight(0xffffff, 0x444444, 0.1);
    scene.add(hemiLight);
    sunLight = new THREE.DirectionalLight(0xffffff, 0);
    sunLight.position.set(200, 500, 100);
    scene.add(sunLight);

    const road = new THREE.Mesh(new THREE.PlaneGeometry(45, 100000), new THREE.MeshLambertMaterial({color: 0x0a0a0a}));
    road.rotation.x = -Math.PI/2;
    scene.add(road);

    // Borders
    const barrierGeo = new THREE.BoxGeometry(1, 2, 100000);
    const barrierMat = new THREE.MeshStandardMaterial({color: 0x222222});
    const leftBarrier = new THREE.Mesh(barrierGeo, barrierMat);
    leftBarrier.position.set(-22.5, 1, -50000);
    scene.add(leftBarrier);
    const rightBarrier = new THREE.Mesh(barrierGeo, barrierMat);
    rightBarrier.position.set(22.5, 1, -50000);
    scene.add(rightBarrier);

    for(let i=0; i<1500; i++) {
        const stripe = new THREE.Mesh(new THREE.PlaneGeometry(0.5, 6), new THREE.MeshBasicMaterial({color: 0xffffff, transparent: true, opacity: 0.6}));
        stripe.rotation.x = -Math.PI/2;
        stripe.position.set(0, 0.05, -i * 30);
        scene.add(stripe);
    }

    // Car setup
    playerCar = new THREE.Group();
    const pBody = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.8, 5), new THREE.MeshPhysicalMaterial({color: carColor, metalness: 1, roughness: 0.2}));
    pBody.position.y = 0.6;
    playerCar.add(pBody);

    const createHeadlight = (x) => {
        const light = new THREE.SpotLight(0xffffff, 0, 70, 0.5, 0.5);
        light.position.set(x, 0.6, 2.5);
        light.target.position.set(x, 0, 20);
        const lens = new THREE.Mesh(new THREE.BoxGeometry(0.5, 0.3, 0.1), new THREE.MeshBasicMaterial({color: 0xffffff, transparent: true, opacity: 0.1}));
        lens.position.set(x, 0.6, 2.51);
        playerCar.add(light, light.target, lens);
        return { light, lens };
    };

    const leftH = createHeadlight(-0.9);
    headLightL = leftH.light; headLightLensL = leftH.lens;
    const rightH = createHeadlight(0.9);
    headLightR = rightH.light; headLightLensR = rightH.lens;

    scene.add(playerCar);

    for(let i=1; i<100; i++) createTrafficLight(-i * 500);

    for(let i=0; i<45; i++) {
        const tc = new THREE.Group();
        const b = new THREE.Mesh(new THREE.BoxGeometry(2.4, 1.2, 5), new THREE.MeshStandardMaterial({color: 0x111111}));
        b.position.y = 0.6;
        const tail = new THREE.Mesh(new THREE.BoxGeometry(1.8, 0.3, 0.1), new THREE.MeshBasicMaterial({color: 0xaa0000}));
        tail.position.set(0, 0.8, -2.55);
        tc.add(b, tail);
        tc.position.set(Math.random() > 0.5 ? -10 : -4, 0, -i * 200);
        tc.userData = { speed: 0.2 + Math.random() * 0.4 };
        scene.add(tc); trafficCars.push(tc);
    }

    setTimeout(() => {
        document.getElementById('loading-screen').style.display = 'none';
        canDrive = true;
    }, 1500);

    animate();
}

async function recordViolation(type, msg) {
    if (Date.now() - lastViolationTime < 3000) return;
    violationCount++; lastViolationTime = Date.now();
    
    // Send to Database
    const formData = new FormData();
    formData.append('type', type);
    formData.append('speed', Math.round(speed * 476));
    fetch('db.php', { method: 'POST', body: formData });

    document.getElementById('flash').style.background = "rgba(255,0,0,0.4)";
    setTimeout(() => { document.getElementById('flash').style.background = "rgba(255,0,0,0)"; }, 150);
    document.getElementById('v-count').innerText = violationCount;
    if (violationCount >= 3) {
        canDrive = false;
        document.getElementById('violation-card').style.display = 'flex';
        document.getElementById('v-msg').innerText = msg;
    }
}

function animate() {
    requestAnimationFrame(animate);
    if(canDrive) {
        if (!cruiseActive) {
            if(keys['arrowup'] || keys['w']) speed += 0.006;
            if(keys['arrowdown'] || keys['s']) speed -= 0.012;
            speed = Math.max(0, Math.min(speed * 0.992, maxSpeedLimit));
        } else {
            if(keys['arrowup'] || keys['w']) cruiseSetSpeed = Math.min(cruiseSetSpeed + 0.005, maxSpeedLimit);
            if(keys['arrowdown'] || keys['s']) cruiseActive = false;
            speed += (cruiseSetSpeed - speed) * 0.05;
        }

        playerCar.translateZ(speed);

        let steerTarget = 0;
        if(keys['arrowleft'] || keys['a']) steerTarget = 0.025;
        if(keys['arrowright'] || keys['d']) steerTarget = -0.025;
        targetRotation += (steerTarget - targetRotation) * 0.1;
        playerCar.rotation.y += targetRotation;

        const kmh = Math.round(speed * 476);
        const speedNum = document.getElementById('speed-num');
        const speedAlert = document.getElementById('speed-alert');

        if (kmh > 240) {
            speedNum.style.color = "#ff3e3e";
            speedAlert.style.display = "block";
            recordViolation("SPEEDING", "Critical velocity violation!");
        } else {
            speedNum.style.color = "#00ffcc";
            speedAlert.style.display = "none";
        }

        trafficLights.forEach(light => {
            light.timer += 0.016;
            if(light.timer > 6) {
                light.state = (light.state === 'GREEN') ? 'RED' : 'GREEN';
                light.red.intensity = (light.state === 'RED') ? 100 : 0;
                light.green.intensity = (light.state === 'GREEN') ? 100 : 0;
                light.timer = 0;
            }
            if(playerCar.position.z < light.z && playerCar.position.z > light.z - 12 && !light.passed) {
                if(light.state === 'RED') {
                    recordViolation("SIGNAL", "Passed a red light!");
                    cruiseActive = false;
                } else {
                    playerXP += 25;
                }
                light.passed = true;
            }
        });

        if(playerXP >= playerLevel * 100) { 
            if(playerLevel === 1) {
                canDrive = false;
                document.getElementById('congrats-card').style.display = 'flex';
            }
            playerLevel++; 
            playerXP = 0; 
        }
        
        document.getElementById('lvl').innerText = playerLevel;
        document.getElementById('xp-bar').style.width = (playerXP / (playerLevel * 100) * 100) + "%";
        speedNum.innerText = kmh;
        
        const cruiseEl = document.getElementById('cruise-status');
        cruiseEl.innerText = cruiseActive ? `CRUISE: ${Math.round(cruiseSetSpeed * 476)} KM/H` : "CRUISE: OFF";
        cruiseEl.style.color = cruiseActive ? "#00ffcc" : "#555";

        if(Math.abs(playerCar.position.x) > 21) {
             playerCar.position.x = playerCar.position.x > 0 ? 21 : -21;
             speed *= 0.4; cruiseActive = false;
             recordViolation("COLLISION", "Vehicle hit the side barrier.");
        }

        trafficCars.forEach(tc => {
            tc.position.z += tc.userData.speed;
            if(tc.position.z > playerCar.position.z + 100) tc.position.z = playerCar.position.z - 2000;
            if(playerCar.position.distanceTo(tc.position) < 4.8) {
                speed = 0; cruiseActive = false;
                recordViolation("CRASH", "Totaled vehicle in traffic collision.");
            }
        });

        const idealOffset = new THREE.Vector3(0, 4.5, -14).applyQuaternion(playerCar.quaternion);
        idealOffset.add(playerCar.position);
        camera.position.lerp(idealOffset, 0.08);
        const lookAtTarget = playerCar.position.clone().add(new THREE.Vector3(0, 1, 10).applyQuaternion(playerCar.quaternion));
        camera.lookAt(lookAtTarget);
    }
    renderer.render(scene, camera);
}

window.addEventListener('keydown', e => {
    const key = e.key.toLowerCase();
    keys[key] = true;
    if (key === 'c' && canDrive) {
        cruiseActive = !cruiseActive;
        if (cruiseActive) cruiseSetSpeed = speed;
    }
});
window.addEventListener('keyup', e => keys[e.key.toLowerCase()] = false);

// START THE LOAD SEQUENCE
fetchDatabaseSettings();
</script>
</body>
</html>