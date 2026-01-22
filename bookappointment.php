<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
session_start();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if dentist ID is provided
if (!isset($_GET['dentist_id']) || empty($_GET['dentist_id'])) {
    header("Location: find-dentist.php");
    exit;
}

$dentist_id = (int)$_GET['dentist_id'];

// Get detailed dentist information
$query = "SELECT d.*, u.username 
          FROM dentists d 
          JOIN users u ON d.user_id = u.id 
          WHERE d.id = :dentist_id AND u.is_active = 1 AND d.status = 'active'";

$stmt = $db->prepare($query);
$stmt->bindParam(':dentist_id', $dentist_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: find-dentist.php");
    exit;
}

$dentist = $stmt->fetch();

// Get working days from JSON
$workingDays = json_decode($dentist['working_days'], true);
if (!is_array($workingDays)) {
    $workingDays = [];
}

// Format working days for display
$formattedWorkingDays = array_map('ucfirst', $workingDays);
$workingDaysString = implode(', ', $formattedWorkingDays);

// Get working hours
$workingHoursStart = $dentist['working_hours_start'] ?? '09:00:00';
$workingHoursEnd = $dentist['working_hours_end'] ?? '17:00:00';
$consultationDuration = $dentist['consultation_duration'] ?? 30;

// Handle form submission
$message = '';
$success = false;
$login_required = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['appointment_date']) || empty($_POST['appointment_time'])) {
        $message = '<div class="alert alert-danger">Please select both date and time for your appointment.</div>';
    } else {
        // Check if the user is logged in
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['role'] === 'patient') {
            // User is logged in as a patient, proceed with booking appointment
            
            // Get patient ID based on user_id
            $get_patient_id_query = "SELECT id FROM patients WHERE user_id = :user_id";
            $stmt = $db->prepare($get_patient_id_query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                $message = '<div class="alert alert-danger">Patient record not found. Please contact support.</div>';
            } else {
                $patient = $stmt->fetch();
                $patient_id = $patient['id'];
                
                // Check for overlapping appointments
                $check_overlap_query = "SELECT a.*, d.consultation_duration, d.first_name, d.last_name 
                                       FROM appointments a 
                                       JOIN dentists d ON a.dentist_id = d.id 
                                       WHERE a.patient_id = :patient_id 
                                       AND a.appointment_date = :appointment_date 
                                       AND a.status IN ('pending', 'confirmed')";
                $stmt = $db->prepare($check_overlap_query);
                $stmt->bindParam(':patient_id', $patient_id);
                $stmt->bindParam(':appointment_date', $_POST['appointment_date']);
                $stmt->execute();
                
                $existing_appointments = $stmt->fetchAll();
                $selected_time = strtotime($_POST['appointment_date'] . ' ' . $_POST['appointment_time']);
                $overlap_found = false;
                
                foreach ($existing_appointments as $existing) {
                    $existing_duration = !empty($existing['consultation_duration']) ? intval($existing['consultation_duration']) : 30;
                    $existing_time = strtotime($existing['appointment_date'] . ' ' . $existing['appointment_time']);
                    $existing_end_time = strtotime("+{$existing_duration} minutes", $existing_time);
                    
                    if ($selected_time < $existing_end_time && $selected_time >= $existing_time) {
                        $message = '<div class="alert alert-danger">
                            You already have an appointment with Dr. ' . $existing['first_name'] . ' ' . $existing['last_name'] . ' 
                            from ' . date('h:i A', $existing_time) . ' to ' . date('h:i A', $existing_end_time) . '. 
                            Please select a different time.
                        </div>';
                        $overlap_found = true;
                        break;
                    }
                }
                
                if (!$overlap_found) {
                    // Insert appointment into database
                    $insert_query = "INSERT INTO appointments (patient_id, dentist_id, appointment_date, appointment_time, status) 
                                    VALUES (:patient_id, :dentist_id, :appointment_date, :appointment_time, 'pending')";
                    $stmt = $db->prepare($insert_query);
                    $stmt->bindParam(':patient_id', $patient_id);
                    $stmt->bindParam(':dentist_id', $dentist_id);
                    $stmt->bindParam(':appointment_date', $_POST['appointment_date']);
                    $stmt->bindParam(':appointment_time', $_POST['appointment_time']);
                    
                    if ($stmt->execute()) {
                        // Set success message and redirect
                        $_SESSION['success_message'] = "Appointment booked successfully! The doctor will review and confirm your appointment soon.";
                        header("Location: patient/my_appointments.php");
                        exit;
                    } else {
                        $message = '<div class="alert alert-danger">There was an error booking your appointment. Please try again.</div>';
                    }
                }
            }
        } else if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'patient') {
            // User is logged in but not as a patient
            $message = '<div class="alert alert-danger">Only patients can book appointments. Please log in with a patient account.</div>';
            $login_required = true;
        } else {
            // User is not logged in - store the appointment details in session
            $_SESSION['pending_appointment'] = [
                'dentist_id' => $dentist_id,
                'appointment_date' => $_POST['appointment_date'],
                'appointment_time' => $_POST['appointment_time']
            ];
            
            // Redirect to login_required page with the dentist_id
            header("Location: login_required.php?dentist_id=" . $dentist_id . "&from=bookappointment");
            exit;
        }
    }
}

// Page title
$pageTitle = "Book Appointment with Dr. " . $dentist['first_name'] . " " . $dentist['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Nagarik Dental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/modern-style.css">
    <style>
        .appointment-section {
            background-color: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .btn-appointment {
            background-color: #17a2b8;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .btn-appointment:hover {
            background-color: #138496;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .time-slot {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 8px 15px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .time-slot:hover {
            background-color: #e9ecef;
        }
        
        .time-slot.selected {
            background-color: #17a2b8;
            color: white;
            border-color: #17a2b8;
        }
        
        /* Additional styles for improved layout */
        .form-floating > .form-control,
        .form-floating > .form-select {
            height: 58px;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: #4dabf7;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .btn-back {
            color: #6c757d;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-back:hover {
            color: #343a40;
            text-decoration: none;
        }
        
        /* Ensure navbar and register button match index.php */
        .navbar {
            background-color: white !important;
        }
        
        .btn-primary {
            background-color: #3498db !important;
            border-color: #3498db !important;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2980b9 !important;
            border-color: #2980b9 !important;
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-primary">Nagarik</span> Dental
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#dentists">Our Dentists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="weblogin.php">Login</a>
                        </li>
                        <li class="nav-item dropdown ms-2">
                            <a class="nav-link dropdown-toggle btn btn-primary text-white px-4 rounded-pill" href="#" id="registerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Register
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="registerDropdown">
                                <li><a class="dropdown-item" href="auth/register.php"><i class="fas fa-user-plus me-2"></i>Patient Registration</a></li>
                                <li><a class="dropdown-item" href="auth/register_dentist.php"><i class="fas fa-user-md me-2"></i>Dentist Registration</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container" style="margin-top: 100px; padding-top: 30px; padding-bottom: 50px; max-width: 1000px;">
        
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                    <div class="card-header bg-primary text-white py-3 text-center">
                        <h3 class="m-0"><i class="fas fa-calendar-plus me-2"></i>Book Appointment with Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?></h3>
                    </div>
                    <div class="card-body p-4">
                        <?php echo $message; ?>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error_message']; ?>
                                <?php unset($_SESSION['error_message']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$success): ?>
                            <?php if ($login_required): ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-user-lock text-warning fa-5x mb-3"></i>
                                    <p class="lead">You need to login to book an appointment with Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>.</p>
                                    <a href="weblogin.php?redirect=booking&dentist_id=<?php echo $dentist_id; ?>" class="btn btn-primary btn-lg mt-3">
                                        <i class="fas fa-sign-in-alt me-2"></i> Proceed to Login / Signup
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <h4 class="mb-3 text-primary"><i class="fas fa-info-circle me-2"></i>Appointment Information</h4>
                                                <p>Please select your preferred date and time for your appointment with Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>.</p>
                                                <p>Each appointment session is <?php echo $consultationDuration; ?> minutes long.</p>
                                                <p>You will receive a confirmation once your appointment is approved by the dentist.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <form method="post" id="appointmentForm" class="row g-4 justify-content-center">
                                    <div class="col-md-5 mb-3">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" placeholder="Appointment Date" required>
                                            <label for="appointment_date">Appointment Date</label>
                                        </div>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <div class="form-floating">
                                            <select class="form-select" id="appointment_time" name="appointment_time" required>
                                                <option value="">Select date first</option>
                                            </select>
                                            <label for="appointment_time">Appointment Time</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-4 text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="fas fa-calendar-check me-2"></i> Book Appointment
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                            <p class="lead">Your appointment request has been submitted successfully!</p>
                            <a href="index.php" class="btn btn-primary mt-3">Return to Home</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const appointmentDateInput = document.getElementById('appointment_date');
            const appointmentTimeSelect = document.getElementById('appointment_time');
            
            // Set min date to today
            const today = new Date();
            const dateString = today.toISOString().split('T')[0];
            appointmentDateInput.setAttribute('min', dateString);
            
            // Listen for date changes
            appointmentDateInput.addEventListener('change', function() {
                const selectedDate = this.value;
                if (!selectedDate) return;
                
                // Get the dentist ID from URL
                const dentistId = <?php echo $dentist_id; ?>;
                
                // Load available time slots via AJAX
                loadTimeSlots(dentistId, selectedDate);
            });
            
            // Function to load available time slots
            function loadTimeSlots(dentistId, date) {
                // Disable time select and show loading message
                appointmentTimeSelect.disabled = true;
                appointmentTimeSelect.innerHTML = '<option value="">Loading time slots...</option>';
                
                // Make AJAX request to get available slots
                fetch(`get_available_slots.php?dentist_id=${dentistId}&date=${date}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Clear existing options
                        appointmentTimeSelect.innerHTML = '<option value="">Select a time slot...</option>';
                        
                        // Check for errors
                        if (data.error) {
                            console.error('Error:', data.error);
                            appointmentTimeSelect.innerHTML = `<option value="">Error: ${data.error}</option>`;
                            appointmentTimeSelect.disabled = true;
                            return;
                        }
                        
                        // Check if slots are available
                        const slots = data.slots;
                        if (slots.length === 0) {
                            let message = data.debug?.current_day ? 
                                `No available slots (${data.debug.current_day})` : 
                                'No available slots for this date';
                                
                            appointmentTimeSelect.innerHTML = `<option value="">${message}</option>`;
                            appointmentTimeSelect.disabled = true;
                            return;
                        }
                        
                        // Add time slots to select dropdown
                        slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.value;
                            option.textContent = slot.label;
                            appointmentTimeSelect.appendChild(option);
                        });
                        
                        // Add current time info at the top
                        // Use local browser time instead of server time for display
                        const now = new Date();
                        const hours = now.getHours();
                        const minutes = now.getMinutes();
                        const ampm = hours >= 12 ? 'PM' : 'AM';
                        const formattedHours = hours % 12 || 12;
                        const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
                        const timeString = `${formattedHours}:${formattedMinutes} ${ampm}`;
                        
                        const currentTimeOption = document.createElement('option');
                        currentTimeOption.disabled = true;
                        currentTimeOption.textContent = `Current time: ${timeString}`;
                        appointmentTimeSelect.insertBefore(currentTimeOption, appointmentTimeSelect.firstChild);
                        
                        // Enable time select
                        appointmentTimeSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        appointmentTimeSelect.innerHTML = '<option value="">Error loading time slots</option>';
                        appointmentTimeSelect.disabled = true;
                    });
            }
        });
    </script>
</body>
</html>
