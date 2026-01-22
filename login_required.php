<?php
session_start();

// Check if dentist ID is provided
if (!isset($_GET['dentist_id']) || empty($_GET['dentist_id'])) {
    header("Location: find-dentist.php");
    exit;
}

$dentist_id = (int)$_GET['dentist_id'];

// If user is already logged in and is a patient, redirect to appointment page
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['role'] === 'patient') {
    header("Location: bookappointment.php?dentist_id=" . $dentist_id);
    exit;
}

// Get referrer URL
$came_from = isset($_GET['from']) ? $_GET['from'] : 'unknown';

// Page title
$pageTitle = "Login Required - Nagarik Dental";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/modern-style.css">
    <style>
        .notification-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }
        
        .icon-container {
            background-color: #f8f9fa;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        
        .icon-container i {
            font-size: 40px;
            color: #17a2b8;
        }
        
        .btn-proceed {
            background-color: #17a2b8;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-proceed:hover {
            background-color: #138496;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-register {
            background-color: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-top: 20px;
            margin-left: 10px;
        }
        
        .btn-register:hover {
            background-color: #5a6268;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
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

    <!-- Notification Section -->
    <div class="container" style="margin-top: 100px; padding-top: 50px; padding-bottom: 100px;">
        <div class="notification-card">
            <div class="icon-container">
                <i class="fas fa-user-lock"></i>
            </div>
            <h2 class="mb-4">Login Required</h2>
            <p class="lead mb-4">You need to be logged in to book an appointment with our dentist.</p>
            <p class="mb-4">Please log in to your existing account or create a new one to proceed with your appointment booking.</p>
            <div class="d-flex justify-content-center flex-wrap">
                <a href="weblogin.php?redirect=booking&dentist_id=<?php echo $dentist_id; ?>" class="btn-proceed">
                    <i class="fas fa-sign-in-alt me-2"></i> Proceed to Login
                </a>
                <a href="auth/register.php?redirect=booking&dentist_id=<?php echo $dentist_id; ?>" class="btn-register">
                    <i class="fas fa-user-plus me-2"></i> Sign Up
                </a>
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
