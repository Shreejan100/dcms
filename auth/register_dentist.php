<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $gender = sanitizeInput($_POST['gender']);
    $dob = $_POST['dob'];
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $specialization = sanitizeInput($_POST['specialization']);
    $work_experience = (int)$_POST['work_experience'];
    $degree = sanitizeInput($_POST['degree']);
    $consultation_charge = (float)$_POST['consultation_charge'];
    $working_days = isset($_POST['working_days']) ? json_encode($_POST['working_days']) : json_encode([]);
    $working_hours_start = $_POST['working_hours_start'];
    $working_hours_end = $_POST['working_hours_end'];
    $consultation_duration = (int)$_POST['consultation_duration'];
    $break_time_start = $_POST['break_time_start'];
    $break_time_end = $_POST['break_time_end'];
    
    // Validate required fields
    if (empty($username) || empty($password) || empty($first_name) || empty($last_name) || 
        empty($email) || empty($phone)) {
        $error = "Please fill in all required fields";
    }
    // Validate email format
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    }
    // Validate phone format (10-15 digits with optional + prefix)
    else if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $error = "Please enter a valid phone number (10-15 digits)";
    }
    // Validate date of birth is not in the future
    else if (strtotime($dob) > time()) {
        $error = "Date of birth cannot be in the future";
    }
    // Validate password complexity
    else {
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
            $error = implode(". ", $password_errors);
        }
    }
    
    try {
        $db->beginTransaction();

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM dentists WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already registered with another dentist");
        }
        
        // Check if username already exists in users table
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username already taken");
        }

        // Create user account
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'dentist')");
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->execute([$username, $hashed_password]);
        $user_id = $db->lastInsertId();
        
        // Create dentist profile
        $stmt = $db->prepare("INSERT INTO dentists (user_id, first_name, last_name, gender, dob, phone, email,
                            specialization, work_experience, degree, consultation_charge, working_days, 
                            working_hours_start, working_hours_end, consultation_duration,
                            break_time_start, break_time_end) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $user_id, $first_name, $last_name, $gender, $dob, $phone, $email,
            $specialization, $work_experience, $degree, $consultation_charge, $working_days,
            $working_hours_start, $working_hours_end, $consultation_duration,
            $break_time_start, $break_time_end
        ]);
        
        $db->commit();
        header("Location: ../index.php?success=Registration successful! Please wait for admin approval before logging in.");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCMS - Dentist Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Dentist Registration</h3>
                        <p class="text-muted text-center mb-4">Please fill in your details to register as a dentist. Your registration will be reviewed by the admin.</p>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <!-- Account Information -->
                            <h6 class="mb-3">Account Information</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                    <div class="form-text">
                                        Password must have: 6+ characters, uppercase & lowercase letters, and at least one number.
                                        Special characters (!@#$%^&*) recommended.
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <h6 class="mb-3">Personal Information</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="dob" required max="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" placeholder="e.g., +9771234567890" required>
                                    <div class="form-text">Enter a valid phone number (10-15 digits)</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" placeholder="example@email.com" required>
                                    <div class="form-text">Enter a valid email address</div>
                                </div>
                            </div>

                            <!-- Professional Information -->
                            <h6 class="mb-3">Professional Information</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Specialization</label>
                                    <select class="form-select" name="specialization" required>
                                        <option value="">Select Specialization</option>
                                        <option value="General Dentist">General Dentist</option>
                                        <option value="Orthodontist">Orthodontist</option>
                                        <option value="Endodontist">Endodontist</option>
                                        <option value="Periodontist">Periodontist</option>
                                        <option value="Prosthodontist">Prosthodontist</option>
                                        <option value="Pediatric Dentist">Pediatric Dentist</option>
                                        <option value="Cosmetic Dentist">Cosmetic Dentist</option>
                                        <option value="Oral and Maxillofacial Surgeon">Oral and Maxillofacial Surgeon</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Work Experience (years)</label>
                                    <input type="number" class="form-control" name="work_experience" min="0" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Degree</label>
                                    <input type="text" class="form-control" name="degree" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Consultation Charge (Rs.)</label>
                                    <input type="number" class="form-control" name="consultation_charge" min="0" step="0.01" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consultation Duration (minutes)</label>
                                    <input type="number" class="form-control" name="consultation_duration" min="15" step="15" value="30" required>
                                </div>
                            </div>

                            <!-- Schedule Information -->
                            <h6 class="mb-3">Schedule Information</h6>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Working Days</label>
                                    <div class="form-check">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <input type="checkbox" class="form-check-input" name="working_days[]" value="monday" checked>
                                                <label class="form-check-label">Monday</label>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="checkbox" class="form-check-input" name="working_days[]" value="tuesday" checked>
                                                <label class="form-check-label">Tuesday</label>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="checkbox" class="form-check-input" name="working_days[]" value="wednesday" checked>
                                                <label class="form-check-label">Wednesday</label>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="checkbox" class="form-check-input" name="working_days[]" value="thursday" checked>
                                                <label class="form-check-label">Thursday</label>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="checkbox" class="form-check-input" name="working_days[]" value="friday" checked>
                                                <label class="form-check-label">Friday</label>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="checkbox" class="form-check-input" name="working_days[]" value="saturday">
                                                <label class="form-check-label">Saturday</label>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="checkbox" class="form-check-input" name="working_days[]" value="sunday">
                                                <label class="form-check-label">Sunday</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Working Hours Start</label>
                                    <input type="time" class="form-control" name="working_hours_start" value="09:00" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Working Hours End</label>
                                    <input type="time" class="form-control" name="working_hours_end" value="17:00" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Break Time Start</label>
                                    <input type="time" class="form-control" name="break_time_start" value="13:00" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Break Time End</label>
                                    <input type="time" class="form-control" name="break_time_end" value="14:00" required>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <a href="../index.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const phoneInput = document.querySelector('input[name="phone"]');
            const emailInput = document.querySelector('input[name="email"]');
            const passwordInput = document.querySelector('input[name="password"]');
            
            // Add feedback elements
            addFeedbackElement(phoneInput, 'phoneFeedback');
            addFeedbackElement(emailInput, 'emailFeedback');
            addFeedbackElement(passwordInput, 'passwordFeedback');
            
            // Validation functions
            function validatePhone() {
                const phoneRegex = /^\+?[0-9]{10,15}$/;
                const isValid = phoneRegex.test(phoneInput.value.trim());
                updateValidationUI(phoneInput, isValid, 'phoneFeedback', 'Please enter a valid phone number (10-15 digits, may include + prefix)');
                return isValid;
            }
            
            function validateEmail() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const isValid = emailRegex.test(emailInput.value.trim());
                updateValidationUI(emailInput, isValid, 'emailFeedback', 'Please enter a valid email address');
                return isValid;
            }
            
            function validatePassword() {
                const password = passwordInput.value;
                let isValid = true;
                let message = '';
                
                if (password.length < 6) {
                    isValid = false;
                    message = 'Password must be at least 6 characters long';
                } else if (!/[A-Z]/.test(password) || !/[a-z]/.test(password)) {
                    isValid = false;
                    message = 'Password must include both uppercase and lowercase letters';
                } else if (!/[0-9]/.test(password)) {
                    isValid = false;
                    message = 'Password must include at least one number';
                } else if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                    // Still valid but show recommendation
                    message = 'Recommended: Add special characters (!@#$%^&*) for stronger security';
                }
                
                updateValidationUI(passwordInput, isValid, 'passwordFeedback', message);
                return isValid;
            }
            
            // Helper functions
            function addFeedbackElement(input, feedbackId) {
                const div = document.createElement('div');
                div.id = feedbackId;
                div.className = 'invalid-feedback';
                input.parentNode.appendChild(div);
            }
            
            function updateValidationUI(input, isValid, feedbackId, message) {
                const feedbackElement = document.getElementById(feedbackId);
                
                if (isValid) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    feedbackElement.style.display = 'none';
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    feedbackElement.textContent = message;
                    feedbackElement.style.display = 'block';
                }
            }
            
            // Add event listeners
            phoneInput.addEventListener('blur', validatePhone);
            emailInput.addEventListener('blur', validateEmail);
            passwordInput.addEventListener('blur', validatePassword);
            
            // Form submission validation
            form.addEventListener('submit', function(event) {
                const isPhoneValid = validatePhone();
                const isEmailValid = validateEmail();
                const isPasswordValid = validatePassword();
                
                if (!isPhoneValid || !isEmailValid || !isPasswordValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
