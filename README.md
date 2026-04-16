# DriveSim Ultra 🚗💨

**DriveSim Ultra** is a high-performance, web-based 3D driving simulator designed for driver behavioral monitoring and traffic enforcement simulation. Built using **Three.js**, **PHP**, and **MySQL**, it bridges the gap between interactive web graphics and persistent data logging.

---

## 🌟 Key Features

* **Real-Time 3D Rendering:** Smooth 60FPS physics loop powered by Three.js and WebGL.
* **Behavioral Monitoring System:** Tracks speed and traffic signal compliance in real-time.
* **Violation Enforcement System (VES):** * Automatically detects speeding (>240 KM/H).
    * Logs violations to a MySQL database using a dynamic fine formula: `$50 + (Speed * 2)`.
    * 3-Strike "Terminated" logic for repeat offenders.
* **Dynamic Environment:** Toggle between Day and Night modes with realistic lighting adjustments.
* **Cruise Control:** Engage/Disengage speed lock for controlled testing environments.
* **Database Integration:** Syncs vehicle assets and records violation history persistently.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, CSS3, JavaScript (Three.js R128).
* **Backend:** PHP (API Layer).
* **Database:** MySQL (State Management & Violation Logging).
* **Environment:** XAMPP / Localhost.

---

## 🚀 Installation & Setup

### 1. Database Setup
1. Create a database named `drivesim_db`.
2. Run the SQL script to initialize `user_garage`, `tickets`, and `world_settings` tables.

### 2. File Deployment
1. Move the `DRIVESIM_ULTRA` folder to `C:/xampp/htdocs/`.
2. Access via: `http://localhost/DRIVESIM_ULTRA/index.php`.

---

## 🎮 Controls

| Key | Action |
| :--- | :--- |
| **W / S** | Accelerate / Brake |
| **A / D** | Steering |
| **C** | Toggle Cruise Control |
| **Button** | Toggle Day/Night Mode |
