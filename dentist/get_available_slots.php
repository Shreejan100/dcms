<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dentist') {
    http_response_code(403);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get dentist ID
$stmt = $db->prepare("SELECT id FROM dentists WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$dentist = $stmt->fetch(PDO::FETCH_ASSOC);

$date = $_GET['date'] ?? null;

if (!$date) {
    http_response_code(400);
    exit();
}

try {
    $available_slots = getAvailableTimeSlots($dentist['id'], $date, $db);
    header('Content-Type: application/json');
    echo json_encode($available_slots);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
