<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';

    if (empty($username) || empty($password)) {
        header("Location: ../index.php?error=Please fill in all fields");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Username not found
            $error = "Invalid username or password. Please try again.";
            $redirect_param = $redirect ? '?redirect=' . urlencode($redirect) . '&error=' : '?error=';
            header("Location: ../weblogin.php{$redirect_param}" . urlencode($error));
            exit();
        }

        if (!password_verify($password, $user['password'])) {
            // Invalid password
            $error = "Invalid username or password. Please try again.";
            $redirect_param = $redirect ? '?redirect=' . urlencode($redirect) . '&error=' : '?error=';
            header("Location: ../weblogin.php{$redirect_param}" . urlencode($error));
            exit();
        }
        // If user is a dentist, check their status
        if ($user['role'] === 'dentist') {
            $stmt = $db->prepare("SELECT status FROM dentists WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $dentist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$dentist) {
                header("Location: ../index.php?error=Account verification failed. Please contact the administrator.");
                exit();
            }
            
            if ($dentist['status'] === 'pending') {
                header("Location: ../index.php?error=Your account is currently under review. Our administrative team will verify your credentials and approve your account within 24 hours.");
                exit();
            }
        }

        // Check if this is a booking flow and user is not a patient
        if (isset($_POST['is_booking_flow']) && $user['role'] !== 'patient') {
            // Show generic error message for security
            $error = "Invalid username or password. Please try again.";
            header("Location: ../weblogin.php?redirect=booking&error=" . urlencode($error));
            exit();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Handle redirect after login
        if (isset($_POST['is_booking_flow']) && $user['role'] === 'patient') {
            // First get the patient ID
            $stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$patient) {
                // If patient record doesn't exist, redirect to dashboard
                header("Location: ../{$user['role']}/dashboard.php");
                exit();
            }
            
            $patient_id = $patient['id'];
            
            // Check if there's a pending appointment in the session
            if (isset($_SESSION['pending_appointment']) && 
                isset($_SESSION['pending_appointment']['dentist_id']) && 
                isset($_SESSION['pending_appointment']['appointment_date']) && 
                isset($_SESSION['pending_appointment']['appointment_time'])) {
                
                $pendingAppointment = $_SESSION['pending_appointment'];
                $dentist_id = $pendingAppointment['dentist_id'];
                $appointment_date = $pendingAppointment['appointment_date'];
                $appointment_time = $pendingAppointment['appointment_time'];
                
                // Check if the dentist exists and is active
                $stmt = $db->prepare("SELECT d.*, d.first_name, d.last_name, d.consultation_duration FROM dentists d WHERE d.id = ? AND d.status = 'active'");
                $stmt->execute([$dentist_id]);
                
                if ($stmt->rowCount() > 0) {
                    $dentist = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check for overlapping appointments with the same dentist
                    $check_dentist_overlap_query = "SELECT a.* FROM appointments a 
                                                  WHERE a.dentist_id = ? 
                                                  AND a.appointment_date = ? 
                                                  AND a.status IN ('pending', 'confirmed')"; 
                    $stmt = $db->prepare($check_dentist_overlap_query);
                    $stmt->execute([$dentist_id, $appointment_date]);
                    
                    $existing_dentist_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $selected_time = strtotime($appointment_date . ' ' . $appointment_time);
                    $consultation_duration = !empty($dentist['consultation_duration']) ? intval($dentist['consultation_duration']) : 30;
                    $selected_end_time = strtotime("+{$consultation_duration} minutes", $selected_time);
                    $dentist_overlap_found = false;
                    
                    foreach ($existing_dentist_appointments as $existing) {
                        $existing_duration = !empty($dentist['consultation_duration']) ? intval($dentist['consultation_duration']) : 30;
                        $existing_time = strtotime($existing['appointment_date'] . ' ' . $existing['appointment_time']);
                        $existing_end_time = strtotime("+{$existing_duration} minutes", $existing_time);
                        
                        // Check if the selected time overlaps with existing appointment
                        if (($selected_time < $existing_end_time && $selected_end_time > $existing_time)) {
                            // This time slot is already booked with this dentist
                            $error_message = "This time slot is already booked with Dr. {$dentist['first_name']} {$dentist['last_name']} at " . date('h:i A', $existing_time) . ". Please select a different time.";
                            
                            // Clear the pending appointment
                            unset($_SESSION['pending_appointment']);
                            
                            // Redirect back to the booking page for this dentist
                            header("Location: ../bookappointment.php?dentist_id=" . $dentist_id . "&error=" . urlencode($error_message));
                            exit();
                        }
                    }
                    
                    // Check for overlapping appointments for this patient
                    $check_overlap_query = "SELECT a.*, d.consultation_duration, d.first_name, d.last_name 
                                          FROM appointments a 
                                          JOIN dentists d ON a.dentist_id = d.id 
                                          WHERE a.patient_id = ? 
                                          AND a.appointment_date = ? 
                                          AND a.status IN ('pending', 'confirmed')";
                    $stmt = $db->prepare($check_overlap_query);
                    $stmt->execute([$patient_id, $appointment_date]);
                    
                    $existing_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $overlap_found = false;
                    
                    foreach ($existing_appointments as $existing) {
                        $existing_duration = !empty($existing['consultation_duration']) ? intval($existing['consultation_duration']) : 30;
                        $existing_time = strtotime($existing['appointment_date'] . ' ' . $existing['appointment_time']);
                        $existing_end_time = strtotime("+{$existing_duration} minutes", $existing_time);
                        
                        if ($selected_time < $existing_end_time && $selected_time >= $existing_time) {
                            $error_message = "You already have an appointment with Dr. " . $existing['first_name'] . " " . $existing['last_name'] . 
                                              " from " . date('h:i A', $existing_time) . " to " . date('h:i A', $existing_end_time) . ". Please select a different time.";
                            
                            // Clear the pending appointment
                            unset($_SESSION['pending_appointment']);
                            
                            // Redirect back to the booking page for this dentist
                            header("Location: ../bookappointment.php?dentist_id=" . $dentist_id . "&error=" . urlencode($error_message));
                            exit();
                        }
                    }
                    
                    // If no conflicts, insert the appointment into database
                    $stmt = $db->prepare("INSERT INTO appointments (patient_id, dentist_id, appointment_date, appointment_time, status) 
                                         VALUES (?, ?, ?, ?, 'pending')");
                    $stmt->execute([
                        $patient_id,
                        $dentist_id,
                        $appointment_date,
                        $appointment_time
                    ]);
                    
                    // Clear the pending appointment from session
                    unset($_SESSION['pending_appointment']);
                    
                    // Set success message
                    $_SESSION['success_message'] = "Appointment booked successfully! The doctor will review and confirm your appointment soon.";
                    
                    // Redirect to my appointments page
                    header("Location: ../patient/my_appointments.php");
                    exit();
                }
                
                // Clear the pending appointment if the dentist wasn't found
                unset($_SESSION['pending_appointment']);
            }
            
            // If no valid pending appointment or it couldn't be processed,
            // check if dentist_id was provided in the form
            if (isset($_POST['dentist_id']) && !empty($_POST['dentist_id'])) {
                $dentist_id = intval($_POST['dentist_id']);
                
                // Check if the dentist exists and is active
                $stmt = $db->prepare("SELECT id FROM dentists WHERE id = ? AND status = 'active'");
                $stmt->execute([$dentist_id]);
                if ($stmt->rowCount() > 0) {
                    // Redirect to book_appointment.php with the dentist_id
                    header("Location: ../patient/book_appointment.php?dentist_id=" . $dentist_id);
                    exit();
                }
            }
            
            // Default fallback - redirect to book_appointment.php
            header("Location: ../patient/book_appointment.php");
        } else {
            // Regular login - redirect to appropriate dashboard
            header("Location: ../{$user['role']}/dashboard.php");
        }
        exit();
    } catch (Exception $e) {
        // Log the error (in a production environment, you'd log this to a file)
        error_log("Login error: " . $e->getMessage());
        
        // Generic error message for security
        $error = "An error occurred during login. Please try again later.";
        $redirect_param = $redirect ? '?redirect=' . urlencode($redirect) . '&error=' : '?error=';
        header("Location: ../weblogin.php{$redirect_param}" . urlencode($error));
        exit();
    }
}

header("Location: ../index.php");

exit();
?>
