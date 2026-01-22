<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: {$role}/dashboard.php");
    exit();
}

// Get redirect URL if provided
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nagarik Dental</title>
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
                <span class="text-primary">Nagarik</span> Dental
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
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#dentists">Our Dentists</a>
                    </li>                    <li class="nav-item">
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
                            <a class="nav-link active" href="weblogin.php">Login</a>
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

    <!-- Login Section -->
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h3 class="mb-0"><?php echo (isset($_GET['redirect']) && $_GET['redirect'] === 'booking') ? 'Patient Login' : 'Welcome Back'; ?></h3>
                            <p class="mb-0">
                                <?php 
                                if (isset($_GET['redirect']) && $_GET['redirect'] === 'booking') {
                                    echo 'Sign in to book an appointment';
                                } else {
                                    echo 'Sign in to your account';
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
                            
                            <form action="auth/login.php" method="POST">
                                <!-- Hidden redirect field -->
                                <?php if (isset($_GET['redirect'])): ?>
                                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                                    <?php if ($_GET['redirect'] === 'booking'): ?>
                                        <input type="hidden" name="is_booking_flow" value="1">
                                        <?php if (isset($_GET['dentist_id'])): ?>
                                            <input type="hidden" name="dentist_id" value="<?php echo htmlspecialchars($_GET['dentist_id']); ?>">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary rounded-pill py-2">Login</button>
                                </div>
                            </form>
                            
                            <?php if (!isset($_GET['redirect']) || $_GET['redirect'] !== 'booking'): ?>
                            <div class="mt-4">
                                <div class="text-center mb-3">
                                    <p class="mb-0">Don't have an account?</p>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <a href="auth/register.php" class="btn btn-outline-primary w-100 rounded-pill">
                                            <i class="fas fa-user-plus me-2"></i> Patient Signup
                                        </a>
                                    </div>
                                    <div class="col-12">
                                        <a href="auth/register_dentist.php" class="btn btn-outline-secondary w-100 rounded-pill">
                                            <i class="fas fa-user-md me-2"></i> Dentist Signup
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center mt-4">
                                <p class="mb-0">
                                    Don't have an account? <a href="auth/register.php?redirect=booking" class="fw-bold">Create one</a>
                                </p>
                            </div>
                            <?php endif; ?>
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
                    <p class="mb-0 text-muted">© <?php echo date('Y'); ?> Nagarik Dental. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
