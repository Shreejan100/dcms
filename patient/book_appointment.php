<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only allow patients and admins to book appointments
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['patient', 'admin'])) {
    $_SESSION['error'] = "You don't have permission to book appointments.";
    header("Location: ../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get patient ID - either from session or URL parameter for admin
$patient_id = null;
if ($_SESSION['role'] === 'admin' && isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
    
    // Verify patient exists
    $stmt = $db->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as patient_name FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        header("Location: ../admin/manage_patients.php");
        exit();
    }
} else {
    // For patient role, get their own ID
    $stmt = $db->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    $patient_id = $patient['id'];
}

// Get all dentists with full details
$stmt = $db->query("SELECT id, first_name, last_name, specialization, consultation_charge, 
                    degree, work_experience, working_days,
                    working_hours_start, working_hours_end, consultation_duration
                    FROM dentists 
                    ORDER BY first_name, last_name");
$dentists = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatWorkingDays($days) {
    $days_array = json_decode($days, true);
    if (!$days_array) return 'Not specified';
    return implode(', ', array_map('ucfirst', $days_array));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        if (empty($_POST['dentist_id']) || empty($_POST['appointment_date']) || empty($_POST['appointment_time'])) {
            throw new Exception("Please fill in all required fields.");
        }

        // Check if dentist exists
        $stmt = $db->prepare("SELECT id FROM dentists WHERE id = ?");
        $stmt->execute([$_POST['dentist_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Selected dentist not found.");
        }

        // Check for overlapping appointments
        $selected_time = strtotime($_POST['appointment_date'] . ' ' . $_POST['appointment_time']);
        $stmt = $db->prepare("
            SELECT a.*, d.consultation_duration, d.first_name, d.last_name 
            FROM appointments a 
            JOIN dentists d ON a.dentist_id = d.id 
            WHERE a.patient_id = ? 
            AND a.appointment_date = ? 
            AND a.status IN ('pending', 'confirmed')
        ");
        $stmt->execute([$patient_id, $_POST['appointment_date']]);
        $existing_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existing_appointments as $existing) {
            $existing_duration = !empty($existing['consultation_duration']) ? intval($existing['consultation_duration']) : 15;
            $existing_time = strtotime($existing['appointment_date'] . ' ' . $existing['appointment_time']);
            $existing_end_time = strtotime("+{$existing_duration} minutes", $existing_time);

            if ($selected_time < $existing_end_time && $selected_time >= $existing_time) {
                throw new Exception(
                    "You already have an appointment with Dr. {$existing['first_name']} {$existing['last_name']} " .
                    "from " . date('h:i A', $existing_time) . " to " . date('h:i A', $existing_end_time) . ". " .
                    "Please select a different time."
                );
            }
        }

        // Insert appointment
        $stmt = $db->prepare("INSERT INTO appointments (patient_id, dentist_id, appointment_date, appointment_time, status) 
                             VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([
            $patient_id,
            $_POST['dentist_id'],
            $_POST['appointment_date'],
            $_POST['appointment_time']
        ]);

        $_SESSION['success_message'] = "Appointment booked successfully! The doctor will review and confirm your appointment soon.";

        // Redirect back to appointments page
        $redirect_url = "my_appointments.php";
        if ($_SESSION['role'] === 'admin') {
            $redirect_url .= "?patient_id=" . $patient_id . "&admin_view=1";
        }
        header("Location: " . $redirect_url);
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../layouts/header.php';
?>

<style>
.dentist-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.dentist-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
}

.dentist-card.selected {
    border-color: #0d6efd;
    background-color: #f8f9ff;
}

.form-check-input:checked + .form-check-label .dentist-card {
    border-color: #0d6efd;
    background-color: #f8f9ff;
}

.degree-text, .specialization-text {
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.specialization-text {
    color: #0d6efd;
}
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <h4>Book Appointment for <?php echo htmlspecialchars($patient['patient_name']); ?></h4>
                    <a href="my_appointments.php?patient_id=<?php echo $patient_id; ?>&admin_view=1" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Appointments
                    </a>
                <?php else: ?>
                    <h4>Book New Appointment</h4>
                    <a href="my_appointments.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Appointments
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="appointmentForm" method="POST" onsubmit="return validateAppointmentForm()">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i> 
                            Note: All appointments require doctor's confirmation. You will be notified once the doctor confirms your appointment.
                        </div>
                        <div class="mb-3">
                            <label for="dentist_select" class="form-label">Select Dentist</label>
                            <select class="form-select mb-3" id="dentist_select" name="dentist_select" required>
                                <option value="">Choose a dentist...</option>
                                <?php 
                                $selectedDentistId = isset($_GET['dentist_id']) ? (int)$_GET['dentist_id'] : 0;
                                $selectedDentist = null;
                                
                                foreach ($dentists as $dentist): 
                                    $selected = ($dentist['id'] == $selectedDentistId) ? 'selected' : '';
                                    if ($selected) {
                                        $selectedDentist = $dentist;
                                    }
                                ?>
                                    <option value="<?php echo $dentist['id']; ?>" <?php echo $selected; ?>>
                                        Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?> 
                                        (<?php echo htmlspecialchars($dentist['specialization']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <?php if ($selectedDentist): ?>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Show the selected dentist's details immediately
                                const detailsDiv = document.getElementById('dentist_details_<?php echo $selectedDentist['id']; ?>');
                                if (detailsDiv) {
                                    detailsDiv.style.display = 'block';
                                    
                                    // Enable date field and set min date
                                    const appointmentDate = document.getElementById('appointment_date');
                                    if (appointmentDate) {
                                        appointmentDate.disabled = false;
                                        const today = new Date().toISOString().split('T')[0];
                                        appointmentDate.min = today;
                                    }
                                }
                            });
                            </script>
                            <?php endif; ?>

                            <div id="dentistCards" class="row g-3">
                                <?php foreach ($dentists as $dentist): ?>
                                <div class="col-12 dentist-details" id="dentist_details_<?php echo $dentist['id']; ?>" style="display: none;">
                                    <div class="card dentist-card">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="dentist_id" 
                                                       value="<?php echo $dentist['id']; ?>" 
                                                       id="dentist_<?php echo $dentist['id']; ?>" 
                                                       required>
                                                <label class="form-check-label w-100" for="dentist_<?php echo $dentist['id']; ?>">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <h5 class="mb-1">Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?></h5>
                                                            <p class="mb-1">
                                                                <strong>Specialization:</strong> 
                                                                <span class="specialization-text"><?php echo htmlspecialchars($dentist['specialization']); ?></span>
                                                            </p>
                                                            <p class="mb-1 text-muted">
                                                                <strong>Degree:</strong> 
                                                                <span class="degree-text"><?php echo htmlspecialchars($dentist['degree']); ?></span>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <ul class="list-unstyled mb-0">
                                                                <li class="mb-2">
                                                                    <i class="bi bi-calendar me-2"></i>
                                                                    <strong>Available Days:</strong> <?php echo formatWorkingDays($dentist['working_days']); ?>
                                                                </li>
                                                                <li class="mb-2">
                                                                    <i class="bi bi-clock me-2"></i>
                                                                    <strong>Working Hours:</strong> <?php echo formatTime($dentist['working_hours_start']); ?> - <?php echo formatTime($dentist['working_hours_end']); ?>
                                                                </li>
                                                                <li class="mb-2">
                                                                    <i class="bi bi-hourglass-split me-2"></i>
                                                                    <strong>Duration:</strong> <?php echo $dentist['consultation_duration']; ?> minutes per session
                                                                </li>
                                                                <li class="mb-2">
                                                                    <i class="bi bi-briefcase me-2"></i>
                                                                    <strong>Experience:</strong> <?php echo $dentist['work_experience']; ?> years
                                                                </li>
                                                                <li>
                                                                    <i class="bi bi-currency-rupee me-2"></i>
                                                                    <strong>Consultation Fee:</strong> Rs. <?php echo number_format($dentist['consultation_charge'], 0); ?>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Appointment Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Appointment Time</label>
                            <select class="form-select" id="appointment_time" name="appointment_time" required disabled>
                                <option value="">Select date first...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">
                                Book Appointment
                            </button>
                            <small class="form-text text-muted d-block mt-2">
                                Your appointment will be pending until confirmed by the doctor.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Booking Instructions</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-success"></i> Select your preferred dentist</li>
                        <li><i class="bi bi-check-circle text-success"></i> Choose an available date</li>
                        <li><i class="bi bi-check-circle text-success"></i> Pick a convenient time slot</li>
                        <li><i class="bi bi-check-circle text-success"></i> Wait for confirmation</li>
                    </ul>
                    <hr>
                    <h6>Note:</h6>
                    <p class="small text-muted">
                        Appointments are subject to dentist's confirmation. You will be notified once your appointment is confirmed.
                        Please arrive 10 minutes before your scheduled time.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form elements
    const dentistSelect = document.getElementById('dentist_select');
    const dentistCards = document.querySelectorAll('.dentist-details');
    const appointmentDate = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const urlParams = new URLSearchParams(window.location.search);
    const dentistId = urlParams.get('dentist_id');

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    appointmentDate.min = today;
    
    // If we have a dentist_id in URL, select that dentist
    if (dentistId) {
        // Set the dropdown value
        if (dentistSelect) {
            dentistSelect.value = dentistId;
            // Show the selected dentist's details
            const detailsDiv = document.getElementById(`dentist_details_${dentistId}`);
            if (detailsDiv) {
                // Hide all other cards first
                dentistCards.forEach(card => card.style.display = 'none');
                // Show selected dentist's card
                detailsDiv.style.display = 'block';
                // Enable date field
                appointmentDate.disabled = false;
                
                // Check the corresponding radio button
                const radio = detailsDiv.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        }
    }
    // Initialize was already done at the top of the script

    // Handle dentist selection from dropdown
    dentistSelect.addEventListener('change', function() {
        const selectedId = this.value;
        
        // Hide all cards first
        dentistCards.forEach(card => card.style.display = 'none');
        
        // Enable/disable date field based on selection
        appointmentDate.disabled = !selectedId;
        if (!selectedId) {
            appointmentDate.value = '';
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Select date first...</option>';
            return;
        }

        // Show selected dentist's card
        const selectedCard = document.getElementById(`dentist_details_${selectedId}`);
        if (selectedCard) {
            selectedCard.style.display = 'block';
            
            // Select the radio button
            const radio = selectedCard.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        }
    });

    // Handle dentist selection from radio buttons
    document.querySelectorAll('input[name="dentist_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                // Update dropdown to match selection
                dentistSelect.value = this.value;
                
                // Enable date selection
                appointmentDate.disabled = false;

                // Show the corresponding card
                dentistCards.forEach(card => {
                    card.style.display = card.id === `dentist_details_${this.value}` ? 'block' : 'none';
                });
            }
        });
    });

    // Make dentist cards clickable
    document.querySelectorAll('.dentist-card').forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });

    // Handle date selection
    appointmentDate.addEventListener('change', function() {
        // Check both the dropdown and radio button for selected dentist
        const selectedDentist = document.querySelector('input[name="dentist_id"]:checked')?.value || 
                              dentistSelect.value;
        if (!selectedDentist || selectedDentist === '') {
            alert('Please select a dentist first');
            this.value = '';
            return;
        }

        // Validate date is not in the past
        const selectedDate = new Date(this.value);
        const todayDate = new Date();
        todayDate.setHours(0, 0, 0, 0);
        selectedDate.setHours(0, 0, 0, 0);
        
        if (selectedDate < todayDate) {
            alert('Cannot select a past date');
            this.value = '';
            return;
        }
        
        loadTimeSlots(selectedDentist, this.value);
    });

    // Function to load time slots
    function loadTimeSlots(dentistId, date) {
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">Loading time slots...</option>';
        
        let url = `get_available_slots.php?dentist_id=${dentistId}&date=${date}`;
        <?php if ($_SESSION['role'] === 'admin' && isset($_GET['patient_id'])): ?>
        url += `&patient_id=<?php echo $_GET['patient_id']; ?>`;
        <?php endif; ?>
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                timeSelect.innerHTML = '<option value="">Select time...</option>';
                
                if (data.error) {
                    console.error('Error:', data.error, 'Debug:', data.debug);
                    timeSelect.innerHTML = `<option value="">Error: ${data.error}</option>`;
                    timeSelect.disabled = true;
                    return;
                }

                const slots = data.slots;
                if (slots.length === 0) {
                    console.log('Debug info:', data.debug);
                    let message = data.debug?.current_day ? 
                        `No available slots (${data.debug.current_day})` : 
                        'No available slots';
                        
                    timeSelect.innerHTML = `<option value="">${message}</option>`;
                    timeSelect.disabled = true;
                    return;
                }
                
                slots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.value;
                    option.textContent = slot.label;
                    timeSelect.appendChild(option);
                });
                
                // Add break time at the top if available
                if (data.debug?.break_time) {
                    const breakTimeOption = document.createElement('option');
                    breakTimeOption.disabled = true;
                    breakTimeOption.textContent = `Break time: ${data.debug.break_time}`;
                    timeSelect.insertBefore(breakTimeOption, timeSelect.firstChild);
                }
                
                // Add current time at the top
                if (data.debug?.current_time) {
                    const currentTimeOption = document.createElement('option');
                    currentTimeOption.disabled = true;
                    currentTimeOption.textContent = `Current time: ${data.debug.current_time}`;
                    timeSelect.insertBefore(currentTimeOption, timeSelect.firstChild);
                }
                
                timeSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                timeSelect.innerHTML = '<option value="">Error loading time slots</option>';
                timeSelect.disabled = true;
            });
    }

    // Basic form validation
    window.validateAppointmentForm = function() {
        // Check both the dropdown and radio button for selected dentist
        const dentistId = document.querySelector('input[name="dentist_id"]:checked')?.value || 
                         dentistSelect.value;
        const date = appointmentDate.value;
        const time = timeSelect.value;
        
        if (!dentistId || dentistId === '' || !date || !time) {
            alert('Please fill in all required fields');
            return false;
        }

        // Additional validation for past times
        const selectedDateTime = new Date(`${date} ${time}`);
        const now = new Date();
        
        if (selectedDateTime <= now) {
            alert('Cannot book an appointment in the past');
            return false;
        }
        
        return true;
    }
});
</script>

<?php include '../layouts/footer.php'; ?>
