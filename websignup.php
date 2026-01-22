<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: {$role}/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Nagarik Dental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/modern-style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-primary">Dental</span>Care
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#services">Services</a>
                    </li>
                    <?php if (!isset($_GET['redirect']) || $_GET['redirect'] !== 'booking'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="find-dentist.php">Our Dentists</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="weblogin.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active btn btn-primary text-white px-4 rounded-pill ms-2" href="websignup.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Register Section -->
    <?php
    $redirect = isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '';
    ?>
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="auth-card">
                        <div class="auth-header text-center">
                            <h3 class="mb-2"><?php echo (isset($_GET['redirect']) && $_GET['redirect'] === 'booking') ? 'Patient Registration' : 'Create an Account'; ?></h3>
                            <p class="mb-0">
                                <?php 
                                if (isset($_GET['redirect']) && $_GET['redirect'] === 'booking') {
                                    echo 'Create an account to book appointments';
                                } else {
                                    echo 'Sign up to book appointments with our dentists';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="auth-body">
                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($_GET['error']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($_GET['success']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="auth/register_process.php<?php echo $redirect; ?>" method="POST" class="needs-validation" novalidate>
                                <!-- User Type Selection -->
                                <div class="mb-4 text-center">
                                    <div class="btn-group w-100" role="group" aria-label="User Type">
                                        <input type="radio" class="btn-check" name="user_type" id="patient" value="patient" autocomplete="off" checked>
                                        <label class="btn btn-outline-primary" for="patient">
                                            <i class="fas fa-user me-2"></i> I'm a Patient
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="user_type" id="dentist" value="dentist" autocomplete="off">
                                        <label class="btn btn-outline-primary" for="dentist">
                                            <i class="fas fa-user-md me-2"></i> I'm a Dentist
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Common Fields -->
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <!-- Dentist-Specific Fields (initially hidden) -->
                                <div id="dentistFields" style="display: none;">
                                    <h5 class="mt-4 mb-3 text-primary">Dentist Information</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="specialization" class="form-label">Specialization</label>
                                            <input type="text" class="form-control" id="specialization" name="specialization">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="degree" class="form-label">Highest Degree</label>
                                            <input type="text" class="form-control" id="degree" name="degree">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="work_experience" class="form-label">Years of Experience</label>
                                        <input type="number" class="form-control" id="work_experience" name="work_experience" min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label for="consultation_charge" class="form-label">Consultation Charge (NRS)</label>
                                        <input type="number" class="form-control" id="consultation_charge" name="consultation_charge" min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="male" value="male" checked>
                                            <label class="form-check-label" for="male">Male</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="gender" id="female" value="female">
                                            <label class="form-check-label" for="female">Female</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Biography</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                                    </div>
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary rounded-pill py-2">Create Account</button>
                                </div>
                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p class="mb-0">Already have an account? <a href="weblogin.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="text-primary">Sign In</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <a class="navbar-brand fw-bold text-white" href="index.php">
                        <span class="text-primary">Nagarik</span> Dental
                    </a>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Nagarik Dental. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Show/hide dentist-specific fields based on user type selection
        document.addEventListener('DOMContentLoaded', function() {
            const patientRadio = document.getElementById('patient');
            const dentistRadio = document.getElementById('dentist');
            const dentistFields = document.getElementById('dentistFields');
            
            function toggleDentistFields() {
                if (dentistRadio.checked) {
                    dentistFields.style.display = 'block';
                    // Make dentist-specific fields required
                    const dentistInputs = dentistFields.querySelectorAll('input, textarea');
                    dentistInputs.forEach(input => {
                        input.required = true;
                    });
                } else {
                    dentistFields.style.display = 'none';
                    // Remove required from dentist fields when not selected
                    const dentistInputs = dentistFields.querySelectorAll('input, textarea');
                    dentistInputs.forEach(input => {
                        input.required = false;
                    });
                }
            }
            
            // Add event listeners
            patientRadio.addEventListener('change', toggleDentistFields);
            dentistRadio.addEventListener('change', toggleDentistFields);
            
            // Initial check
            toggleDentistFields();
        });
    </script>
</body>
</html>
