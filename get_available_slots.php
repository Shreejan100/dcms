<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Validate input
if (!isset($_GET['dentist_id']) || !isset($_GET['date'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing required parameters']));
}

$dentist_id = $_GET['dentist_id'];
$date = $_GET['date'];

try {
    // Get current time - EXACTLY as in patient/get_available_slots.php
    $current_time = new DateTime('now', new DateTimeZone('Asia/Kathmandu'));
    $selected_date = new DateTime($date, new DateTimeZone('Asia/Kathmandu'));

    // Get dentist details
    $stmt = $db->prepare("
        SELECT consultation_duration, working_hours_start, working_hours_end, 
               working_days
        FROM dentists 
        WHERE id = ?
    ");
    $stmt->execute([$dentist_id]);
    $dentist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dentist) {
        http_response_code(404);
        exit(json_encode(['error' => 'Dentist not found']));
    }

    // Check if dentist works on this day
    $day_of_week = strtolower($selected_date->format('l'));
    $working_days = json_decode($dentist['working_days'], true);
    if (!in_array($day_of_week, $working_days)) {
        exit(json_encode([
            'slots' => [],
            'debug' => [
                'message' => "Dentist doesn't work on {$day_of_week}",
                'working_days' => $working_days,
                'current_day' => $day_of_week
            ]
        ]));
    }

    // Get all appointments for this dentist on this date
    $stmt = $db->prepare("
        SELECT appointment_time
        FROM appointments
        WHERE dentist_id = ? 
        AND appointment_date = ?
        AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$dentist_id, $date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert booked slots to blocked times - EXACTLY as in patient/get_available_slots.php
    $blocked_times = [];
    foreach ($booked_slots as $slot) {
        $start_time = new DateTime($date . ' ' . $slot['appointment_time'], new DateTimeZone('Asia/Kathmandu'));
        $end_time = clone $start_time;
        $end_time->modify("+{$dentist['consultation_duration']} minutes");
        
        $blocked_times[] = [
            'start' => $start_time,
            'end' => $end_time
        ];
    }

    // Generate available time slots - EXACTLY as in patient/get_available_slots.php
    $consultation_duration = intval($dentist['consultation_duration']);
    $start_time = new DateTime($date . ' ' . $dentist['working_hours_start'], new DateTimeZone('Asia/Kathmandu'));
    $end_time = new DateTime($date . ' ' . $dentist['working_hours_end'], new DateTimeZone('Asia/Kathmandu'));
    $available_slots = [];

    // If it's today, start from the next possible slot - EXACTLY as in patient/get_available_slots.php
    if ($selected_date->format('Y-m-d') === $current_time->format('Y-m-d')) {
        // Get current minutes and round up to next slot
        $current_minutes = (intval($current_time->format('H')) * 60) + intval($current_time->format('i'));
        $next_slot = ceil($current_minutes / $consultation_duration) * $consultation_duration;
        
        // Create a new time object for the next available slot
        $next_slot_time = clone $current_time;
        $next_slot_time->setTime(
            intval($next_slot / 60),  // hours
            $next_slot % 60,          // minutes
            0                         // seconds
        );
        
        if ($next_slot_time > $start_time) {
            $start_time = $next_slot_time;
        }
    }

    // Generate slots - EXACTLY as in patient/get_available_slots.php
    $slot_time = clone $start_time;
    while ($slot_time < $end_time) {
        $slot_end = clone $slot_time;
        $slot_end->modify("+{$consultation_duration} minutes");

        // Skip if slot is in the past
        if ($slot_time <= $current_time) {
            $slot_time->modify("+{$consultation_duration} minutes");
            continue;
        }

        // Check if this slot overlaps with any blocked time
        $is_available = true;
        foreach ($blocked_times as $blocked) {
            // Check if the current slot overlaps with the blocked time
            if ($slot_time < $blocked['end'] && $slot_end > $blocked['start']) {
                $is_available = false;
                break;
            }
        }

        if ($is_available) {
            $slot_time_str = $slot_time->format('H:i:s');
            $available_slots[] = [
                'value' => $slot_time_str,
                'label' => $slot_time->format('h:i A')
            ];
        }

        $slot_time->modify("+{$consultation_duration} minutes");
    }

    // Return JSON response - EXACTLY as in patient/get_available_slots.php
    header('Content-Type: application/json');
    echo json_encode([
        'slots' => $available_slots
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode([
        'error' => 'Server error: ' . $e->getMessage()
    ]));
}
?>
