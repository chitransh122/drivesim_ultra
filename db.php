<?php
// Prevent accidental output before JSON
ob_start();
// This stops PHP from crashing the page with an error message
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// --- DATABASE CONFIGURATION ---
$db_host = "localhost";
$db_user = "root";
$db_pass = ""; // Change if you set a password
$db_name = "drivesim_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection without crashing the page
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB Connection Failed"]);
    exit;
}

// 1. Fetch Active Car
if (isset($_GET['action']) && $_GET['action'] == 'get_car') {
    $result = $conn->query("SELECT * FROM user_garage WHERE is_active = 1 LIMIT 1");
    $car = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : [
        "car_model" => "default.glb",
        "paint_color" => "#c29f4c",
        "engine_level" => 1
    ];
    echo json_encode($car);
    exit;
}

// 2. Fetch World Settings
if (isset($_GET['action']) && $_GET['action'] == 'get_world') {
    $result = $conn->query("SELECT * FROM world_settings");
    $settings = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    // Fallback if table is empty
    if (empty($settings)) $settings = ["weather" => "CLEAR"];
    
    echo json_encode($settings);
    exit;
}

// 3. Record Violations
if (isset($_POST['type'])) {
    $type = $conn->real_escape_string($_POST['type']);
    $speed = (int)$_POST['speed'];
    $fine = 50 + ($speed * 2);
    
    $stmt = $conn->prepare("INSERT INTO tickets (offense_type, fine_amount, speed_at_time) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sii", $type, $fine, $speed);
        $stmt->execute();
    }
    echo json_encode(["status" => "success"]);
    exit;
}
ob_end_flush();
?>