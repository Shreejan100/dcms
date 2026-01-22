<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    // Handle redirect
    $redirect = isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '';
    $error_url = isset($_POST['user_type']) && $_POST['user_type'] === 'patient' ? '../websignup.php' : 'register.php';
    $error_url .= $redirect ? '?redirect=' . urlencode($_GET['redirect']) . '&error=' : '?error=';

    // Get and trim form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = trim($_POST['gender'] ?? 'other');
    $dob = trim($_POST['dob'] ?? date('Y-m-d')); // Use form DOB or default to today
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $user_type = trim($_POST['user_type'] ?? 'patient'); // Default to patient for backward compatibility

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($gender) || empty($dob) || empty($email) || empty($phone) || empty($username) || empty($password)) {
        header("Location: {$error_url}Please fill in all required fields");
        exit();
    }

    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        header("Location: register.php?error=Username must be 4-20 characters long and can only contain letters, numbers, and underscores");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: {$error_url}Please enter a valid email address");
        exit();
    }
    
    // Validate phone format (10-15 digits with optional + prefix)
    if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        header("Location: {$error_url}Please enter a valid phone number (10-15 digits)");
        exit();
    }
    
    // Validate gender
    if (!in_array($gender, ['male', 'female', 'other'])) {
        header("Location: {$error_url}Please select a valid gender");
        exit();
    }
    
    // Validate password complexity
    $password_errors = [];
    if (strlen($password) < 6) {
        $password_errors[] = "Password must be at least 6 characters long";
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password)) {
        $password_errors[] = "Password must include both uppercase and lowercase letters";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $password_errors[] = "Password must include at least one number";
    }
    
    if (count($password_errors) > 0) {
        header("Location: {$error_url}" . urlencode(implode(". ", $password_errors)));
        exit();
    }
    
    // Validate date of birth
    $date_regex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_regex, $dob) || !strtotime($dob)) {
        header("Location: register.php?error=Please enter a valid date of birth");
        exit();
    }
    
    // Validate date is not in the future
    if (strtotime($dob) > time()) {
        header("Location: {$error_url}Date of birth cannot be in the future");
        exit();
    }

    try {
        $db->beginTransaction();

        // Check if username exists
        $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already exists");
        }

        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM patients WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already exists");
        }

        // Create user account
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $role = $user_type === 'dentist' ? 'dentist' : 'patient';
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if (!$stmt->execute([$username, $hashed_password, $role])) {
            throw new Exception("Error creating user account");
        }
        $user_id = $db->lastInsertId();

        // Create patient or dentist record
        $user_id = $db->lastInsertId();
        
        if ($role === 'patient') {
            $stmt = $db->prepare("INSERT INTO patients (user_id, first_name, last_name, gender, dob, email, phone) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt->execute([$user_id, $first_name, $last_name, $gender, $dob, $email, $phone])) {
                throw new Exception("Error creating patient record");
            }
        } else if ($role === 'dentist') {
            // For dentist registration, we need more fields which would typically come from a different form
            // This is a placeholder - in a real implementation, you'd collect these fields from the form
            $specialization = trim($_POST['specialization'] ?? 'General Dentistry');
            $work_experience = (int)($_POST['work_experience'] ?? 0);
            $degree = trim($_POST['degree'] ?? 'DDS');
            $consultation_charge = (float)($_POST['consultation_charge'] ?? 50.00);
            
            // Check database schema for correct column names
            $stmt = $db->prepare("INSERT INTO dentists (user_id, first_name, last_name, gender, dob, email, phone, 
                                 specialization, work_experience, degree, consultation_charge, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            if (!$stmt->execute([$user_id, $first_name, $last_name, $gender, $dob, $email, $phone, 
                            $specialization, $work_experience, $degree, $consultation_charge])) {
                throw new Exception("Error creating dentist record");
            }
        }

        $db->commit();

        // Set session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        
        // Handle redirect after registration
        if (isset($_GET['redirect']) && $_GET['redirect'] === 'booking' && $role === 'patient') {
            // Check if dentist_id is provided in the URL
            if (isset($_GET['dentist_id']) && !empty($_GET['dentist_id'])) {
                $dentist_id = $_GET['dentist_id'];
                
                // Store the registration session variables to ensure they're cleared
                $username_to_show = $username;
                
                // Clear the session variables to force re-login
                unset($_SESSION['user_id']);
                unset($_SESSION['username']);
                unset($_SESSION['role']);
                
                // Redirect back to login page with success message and preserve dentist_id
                header("Location: /project/shreejandcms/dcms/weblogin.php?redirect=booking&dentist_id={$dentist_id}&success=" . urlencode("Your account has been successfully created. Please log in with your credentials to proceed with booking your dental appointment."));
                exit();
            } else {
                // Get the patient ID for the newly created patient
                $stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);
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
                    $stmt = $db->prepare("SELECT id FROM dentists WHERE id = ? AND status = 'active'");
                    $stmt->execute([$dentist_id]);
                    if ($stmt->rowCount() > 0) {
                        // Insert the appointment into the database
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
                        header("Location: /project/shreejandcms/dcms/patient/my_appointments.php");
                        exit();
                    }
                    
                    // Clear the pending appointment if the dentist wasn't found
                    unset($_SESSION['pending_appointment']);
                }
                
                // If no pending appointment or booking failed, just redirect to book_appointment.php
                header("Location: /project/shreejandcms/dcms/patient/book_appointment.php");
            }
        } else {
            header("Location: /project/shreejandcms/dcms/{$role}/dashboard.php");
        }
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        $error_message = urlencode($e->getMessage());
        header("Location: {$error_url}{$error_message}");
        exit();
    }
}

header("Location: register.php");
exit();
