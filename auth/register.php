<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCMS - Patient Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center py-5">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Patient Registration</h3>
                        <form action="register_process.php<?php 
                            // Add query parameters for redirect and dentist_id if present
                            $query_params = [];
                            if (isset($_GET['redirect'])) {
                                $query_params[] = 'redirect=' . urlencode($_GET['redirect']);
                            }
                            if (isset($_GET['dentist_id'])) {
                                $query_params[] = 'dentist_id=' . urlencode($_GET['dentist_id']);
                            }
                            echo !empty($query_params) ? '?' . implode('&', $query_params) : '';
                        ?>" method="POST">
                            <?php if (isset($_GET['error'])) { ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" required max="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g., +9771234567890" required>
                                    <div class="form-text">Enter a valid phone number (10-15 digits)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="example@email.com" required>
                                    <div class="form-text">Enter a valid email address</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">
                                        Password must have: 6+ characters, uppercase & lowercase letters, and at least one number.
                                        Special characters (!@#$%^&*) recommended.
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Register</button>
                                <a href="../weblogin.php?redirect=booking<?php echo isset($_GET['dentist_id']) ? '&dentist_id=' . urlencode($_GET['dentist_id']) : ''; ?>" class="btn btn-secondary">Go to Login</a>
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
            const phoneInput = document.getElementById('phone');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
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
