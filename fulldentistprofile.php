<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
session_start();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if dentist ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: find-dentist.php");
    exit;
}

$dentist_id = (int)$_GET['id'];

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

// Check if services_offered exists before trying to use it
if (isset($dentist['services_offered']) && !empty($dentist['services_offered'])) {
    $services = json_decode($dentist['services_offered'], true);
    if (!is_array($services)) {
        $services = [];
    }
} else {
    $services = [];
}

// Page title
$pageTitle = "Dr. " . $dentist['first_name'] . " " . $dentist['last_name'] . " - Full Profile";
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
        .profile-header {
            background-color: #f8f9fa;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .profile-img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 0 auto 20px;
            display: block;
        }
        
        .dentist-name {
            color: #17a2b8;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .badge-specialization {
            background-color: #17a2b8;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 50px;
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .badge-experience {
            background-color: #28a745;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 50px;
            margin-bottom: 10px;
            margin-left: 5px;
            display: inline-block;
        }
        
        .info-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .info-card h3 {
            color: #17a2b8;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .info-item i {
            color: #17a2b8;
            width: 25px;
            margin-right: 15px;
            margin-top: 4px;
            font-size: 1.1rem;
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
        
        .btn-back {
            color: #6c757d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .btn-back:hover {
            color: #17a2b8;
        }
        
        .btn-back i {
            margin-right: 8px;
        }
        
        .service-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        
        .section-title {
            color: #17a2b8;
            margin-bottom: 25px;
            font-weight: 600;
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php#dentists">Our Dentists</a>
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
    
    <div class="container" style="margin-top: 100px; padding-top: 30px; padding-bottom: 50px;">
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="bookappointment.php?dentist_id=<?php echo $dentist['id']; ?>" class="btn btn-appointment" style="background-color: #28a745;">
                <i class="fas fa-clock me-2"></i> Book Day and Time
            </a>
        </div>
        
        <!-- Dentist Profile Header Card -->
        <div class="card shadow-sm mb-4 border-0 rounded-3 overflow-hidden">
            <!-- Cover Image/Background -->
            <div class="bg-primary text-white py-4 px-4">
                <div class="row align-items-center">
                    <div class="col-lg-2 col-md-3 text-center">
                        <img src="<?php echo !empty($dentist['profile_image']) ? 'uploads/profile_images/' . $dentist['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                             class="profile-img border border-4 border-white" 
                             alt="Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>">
                    </div>
                    <div class="col-lg-10 col-md-9">
                        <h1 class="display-6 fw-bold mb-2">Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?></h1>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-info rounded-pill fs-6 px-3 py-2"><?php echo htmlspecialchars($dentist['specialization'] ?? 'General Dentist'); ?></span>
                            <?php if (!empty($dentist['work_experience'])): ?>
                            <span class="badge bg-success rounded-pill fs-6 px-3 py-2"><i class="fas fa-briefcase me-1"></i> <?php echo htmlspecialchars($dentist['work_experience']); ?> Years Experience</span>
                            <?php endif; ?>
                            <?php if (!empty($dentist['degree'])): ?>
                            <span class="badge bg-secondary rounded-pill fs-6 px-3 py-2"><i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($dentist['degree']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="card-body p-4">
                <?php if (!empty($dentist['about'])): ?>
                <div class="mb-4 p-3 bg-light rounded-3">
                    <h5 class="text-primary mb-3"><i class="fas fa-user-md me-2"></i>About</h5>
                    <p class="lead"><?php echo nl2br(htmlspecialchars($dentist['about'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Key Information -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-money-bill-wave fa-2x"></i>
                                </div>
                                <h5 class="fw-bold mb-0">NRs. <?php echo number_format((int)($dentist['consultation_charge'] ?? 0)); ?></h5>
                                <p class="text-muted">Consultation Fee</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($dentist['consultation_duration'] ?? '30'); ?> min</h5>
                                <p class="text-muted">Session Duration</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-venus-mars fa-2x"></i>
                                </div>
                                <h5 class="fw-bold mb-0"><?php echo ucfirst(htmlspecialchars($dentist['gender'] ?? 'Not specified')); ?></h5>
                                <p class="text-muted">Gender</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-star fa-2x"></i>
                                </div>
                                <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($dentist['work_experience'] ?? '0'); ?>+ Years</h5>
                                <p class="text-muted">Experience</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Contact Information -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100 border-0 rounded-3">
                    <div class="card-header bg-primary text-white py-3">
                        <h3 class="m-0 fs-5"><i class="fas fa-address-card me-2"></i>Contact Information</h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="list-group list-group-flush">
                            <?php if (!empty($dentist['email'])): ?>
                            <div class="list-group-item px-0 py-3 d-flex border-0 border-bottom">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Email</h6>
                                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($dentist['email']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($dentist['phone'])): ?>
                            <div class="list-group-item px-0 py-3 d-flex border-0 border-bottom">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Phone</h6>
                                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($dentist['phone']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($dentist['address'])): ?>
                            <div class="list-group-item px-0 py-3 d-flex border-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Address</h6>
                                    <p class="mb-0 fs-5"><?php echo nl2br(htmlspecialchars($dentist['address'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Schedule Information -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100 border-0 rounded-3">
                    <div class="card-header bg-success text-white py-3">
                        <h3 class="m-0 fs-5"><i class="fas fa-calendar-alt me-2"></i>Availability Schedule</h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 py-3 d-flex border-0 border-bottom">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Working Days</h6>
                                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($workingDaysString); ?></p>
                                </div>
                            </div>
                            
                            <div class="list-group-item px-0 py-3 d-flex border-0 border-bottom">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Working Hours</h6>
                                    <p class="mb-0 fs-5">
                                    <?php 
                                    if (!empty($dentist['working_hours_start']) && !empty($dentist['working_hours_end'])) {
                                        echo date('h:i A', strtotime($dentist['working_hours_start'])) . ' - ' . 
                                             date('h:i A', strtotime($dentist['working_hours_end']));
                                    } else {
                                        echo 'Not specified';
                                    }
                                    ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if (!empty($dentist['break_time_start']) && !empty($dentist['break_time_end'])): ?>
                            <div class="list-group-item px-0 py-3 d-flex border-0 border-bottom">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-coffee"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Break Time</h6>
                                    <p class="mb-0 fs-5">
                                    <?php 
                                    echo date('h:i A', strtotime($dentist['break_time_start'])) . ' - ' . 
                                         date('h:i A', strtotime($dentist['break_time_end']));
                                    ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($dentist['consultation_duration'])): ?>
                            <div class="list-group-item px-0 py-3 d-flex border-0">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">Session Duration</h6>
                                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($dentist['consultation_duration']); ?> minutes</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Offered -->
            <?php if (!empty($services)): ?>
            <div class="col-12 mt-4">
                <div class="info-card">
                    <h3><i class="fas fa-list-check me-2"></i>Services Offered</h3>
                    
                    <div class="row">
                        <?php foreach ($services as $service): ?>
                        <div class="col-md-4 mb-2">
                            <div class="service-item">
                                <i class="fas fa-tooth me-2"></i> <?php echo htmlspecialchars($service); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Additional Information -->
            <?php if (!empty($dentist['additional_details'])): ?>
            <div class="col-12 mt-4">
                <div class="info-card">
                    <h3><i class="fas fa-info-circle me-2"></i>Additional Information</h3>
                    
                    <div>
                        <?php echo nl2br(htmlspecialchars($dentist['additional_details'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- No footer needed -->
    
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
