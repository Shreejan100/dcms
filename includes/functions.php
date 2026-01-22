<?php
require_once __DIR__ . '/../config/database.php';

function checkAppointmentConflict($dentist_id, $patient_id, $date, $time, $db, $appointment_id = null) {
    $query = "SELECT id FROM appointments 
              WHERE date(appointment_date) = ? 
              AND time(appointment_time) = ? 
              AND status IN ('pending', 'confirmed') 
              AND (dentist_id = ? OR patient_id = ?)";
    
    $params = [$date, $time, $dentist_id, $patient_id];
    
    if ($appointment_id) {
        $query .= " AND id != ?";
        $params[] = $appointment_id;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    return $stmt->rowCount() > 0;
}

function getDentistSchedule($dentist_id, $date, $db) {
    $stmt = $db->prepare("SELECT appointment_time, status FROM appointments WHERE dentist_id = ? AND date(appointment_date) = ?");
    $stmt->execute([$dentist_id, $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPatientDetails($patient_id, $db) {
    $stmt = $db->prepare("SELECT p.*, u.username, u.role FROM patients p 
                         JOIN users u ON p.user_id = u.id 
                         WHERE p.id = ?");
    $stmt->execute([$patient_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDentistDetails($dentist_id, $db) {
    $stmt = $db->prepare("SELECT d.*, u.username, u.role FROM dentists d 
                         JOIN users u ON d.user_id = u.id 
                         WHERE d.id = ?");
    $stmt->execute([$dentist_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function formatAppointmentStatus($status) {
    return '<span class="status-' . $status . '">' . ucfirst($status) . '</span>';
}

function getStatusColor($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'primary';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        case 'missed':
            return 'secondary';
        default:
            return 'info';
    }
}

function isValidAppointmentStatus($current_status, $new_status, $appointment_date) {
    $today = date('Y-m-d');
    $is_past = $appointment_date < $today;
    
    $past_valid_statuses = ['completed', 'missed', 'pending'];
    $future_valid_statuses = ['confirmed', 'cancelled', 'pending'];
    
    if ($is_past) {
        return in_array($new_status, $past_valid_statuses);
    } else {
        return in_array($new_status, $future_valid_statuses);
    }
}

function getAvailableTimeSlots($dentist_id, $date, $db) {
    // Check if date is in the past
    $today = date('Y-m-d');
    if ($date < $today) {
        return [];
    }

    // Get dentist's working hours, break times and consultation duration
    $stmt = $db->prepare("SELECT working_hours_start, working_hours_end, 
                         break_time_start, break_time_end, consultation_duration,
                         working_days
                         FROM dentists WHERE id = ?");
    $stmt->execute([$dentist_id]);
    $dentist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dentist) {
        return [];
    }

    // Check if dentist works on this day
    $day_of_week = strtolower(date('l', strtotime($date)));
    $working_days = json_decode($dentist['working_days'], true);
    if (!in_array($day_of_week, $working_days)) {
        return [];
    }

    // Convert times to timestamps for the selected date
    $date_str = date('Y-m-d ', strtotime($date));
    $start_time = strtotime($date_str . $dentist['working_hours_start']);
    $end_time = strtotime($date_str . $dentist['working_hours_end']);
    $break_start = strtotime($date_str . $dentist['break_time_start']);
    $break_end = strtotime($date_str . $dentist['break_time_end']);
    $duration = intval($dentist['consultation_duration']); // Use dentist's consultation duration
    
    // Ensure minimum duration is 15 minutes
    $duration = max(15, $duration);
    
    // If date is today, start from next available slot
    if ($date === $today) {
        $current_time = time();
        // Round up to next slot based on consultation duration
        $minutes = date('i', $current_time);
        $round_up = ceil($minutes / $duration) * $duration;
        $current_time = strtotime(date('Y-m-d H:', $current_time) . $round_up . ':00');
        $start_time = max($start_time, $current_time);
    }
    
    // Get booked appointments for the day
    $stmt = $db->prepare("SELECT appointment_time 
                         FROM appointments 
                         WHERE dentist_id = ? 
                         AND appointment_date = ? 
                         AND status = 'confirmed'");
    $stmt->execute([$dentist_id, $date]);
    $booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $available_slots = [];
    $current_time = $start_time;
    
    while ($current_time < $end_time) {
        // Ensure we're only using intervals based on consultation duration
        $minutes = date('i', $current_time);
        if ($minutes % $duration !== 0) {
            $current_time = strtotime('+' . ($duration - ($minutes % $duration)) . ' minutes', $current_time);
            continue;
        }

        $time_string = date('H:i:s', $current_time);
        $is_available = true;

        // Skip if time is during break
        if ($current_time >= $break_start && $current_time < $break_end) {
            $current_time += ($duration * 60);
            continue;
        }

        // Check if this time slot or adjacent slots are booked
        foreach ($booked_times as $booked_time) {
            $booked = strtotime($date_str . $booked_time);
            $diff = abs($current_time - $booked);
            // Use consultation duration for gap check
            if ($diff < $duration * 60) {
                $is_available = false;
                break;
            }
        }
        
        if ($is_available) {
            // Format time in 12-hour format with AM/PM
            $formatted_time = date('h:i A', $current_time);
            $available_slots[$time_string] = $formatted_time;
        }
        
        $current_time += ($duration * 60);
    }
    
    return $available_slots;
}

function checkUserAppointmentConflict($patient_id, $appointment_date, $appointment_time, $db) {
    // Check if user has any appointment within 15 minutes of the requested time
    $stmt = $db->prepare("SELECT COUNT(*) FROM appointments 
                         WHERE patient_id = ? 
                         AND appointment_date = ? 
                         AND ABS(TIME_TO_SEC(TIMEDIFF(appointment_time, ?))) < 900
                         AND status != 'cancelled'");
    $stmt->execute([$patient_id, $appointment_date, $appointment_time]);
    return $stmt->fetchColumn() > 0;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
