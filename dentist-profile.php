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

// Get dentist information
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

// Page title
$pageTitle = "Dr. " . $dentist['first_name'] . " " . $dentist['last_name'] . " - Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Nagarik Dental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-section {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .profile-img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dentist-info {
            margin-bottom: 20px;
        }
        
        .dentist-info h2 {
            color: #4e73df;
            margin-bottom: 15px;
        }
        
        .info-item {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .info-item i {
            width: 25px;
            color: #4e73df;
            margin-right: 10px;
            margin-top: 4px;
        }
        
        .specialization-badge {
            background-color: #4e73df;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .experience-badge {
            background-color: #1cc88a;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 15px;
            margin-left: 10px;
        }
        
        .schedule-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #e0e0e0;
        }
        
        .schedule-section h3 {
            color: #4e73df;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .btn-book-appointment {
            background-color: #4e73df;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            margin-top: 20px;
            font-weight: 500;
        }
        
        .btn-book-appointment:hover {
            background-color: #2e59d9;
            color: white;
        }
        
        .btn-back {
            background-color: #6c757d;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'layouts/header.php'; ?>
    
    <div class="container my-5">
        <a href="find-dentist.php" class="btn-back">
            <i class="fas fa-arrow-left me-1"></i> Back to Dentist List
        </a>
        
        <div class="profile-section">
            <div class="row">
                <div class="col-md-4 text-center">
                    <?php 
                    $profileImage = 'assets/img/default-';
                    if ($dentist['gender'] == 'male') {
                        $profileImage .= 'male.png';
                    } elseif ($dentist['gender'] == 'female') {
                        $profileImage .= 'female.png';
                    } else {
                        $profileImage .= 'profile.png';
                    }
                    ?>
                    <img src="<?php echo $profileImage; ?>" alt="<?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>" class="profile-img">
                    
                    <div>
                        <span class="specialization-badge"><?php echo htmlspecialchars($dentist['specialization']); ?></span>
                        <span class="experience-badge"><?php echo htmlspecialchars($dentist['work_experience']); ?> Years Experience</span>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'patient'): ?>
                        <a href="patient/book_appointment.php?dentist_id=<?php echo $dentist['id']; ?>" class="btn-book-appointment">
                            <i class="fas fa-calendar-plus me-1"></i> Book Appointment
                        </a>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="auth/login.php?redirect=dentist-profile.php?id=<?php echo $dentist['id']; ?>" class="btn-book-appointment">
                            <i class="fas fa-sign-in-alt me-1"></i> Login to Book Appointment
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-8">
                    <div class="dentist-info">
                        <h2>Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?></h2>
                        
                        <div class="info-item">
                            <i class="fas fa-user-md"></i>
                            <div>
                                <strong>Specialization:</strong><br>
                                <?php echo htmlspecialchars($dentist['specialization']); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-graduation-cap"></i>
                            <div>
                                <strong>Qualification:</strong><br>
                                <?php echo htmlspecialchars($dentist['degree']); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email:</strong><br>
                                <?php echo htmlspecialchars($dentist['email']); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Phone:</strong><br>
                                <?php echo htmlspecialchars($dentist['phone']); ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-money-bill"></i>
                            <div>
                                <strong>Consultation Charge:</strong><br>
                                $<?php echo htmlspecialchars($dentist['consultation_charge']); ?>
                            </div>
                        </div>
                        
                        <div class="schedule-section">
                            <h3><i class="fas fa-calendar-alt me-2"></i>Availability Schedule</h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Working Days:</strong><br>
                                    <?php echo htmlspecialchars($workingDaysString); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Working Hours:</strong><br>
                                    <?php 
                                    echo date('h:i A', strtotime($dentist['working_hours_start'])) . ' - ' . 
                                         date('h:i A', strtotime($dentist['working_hours_end']));
                                    ?>
                                    </p>
                                </div>
                                <div class="col-md-12">
                                    <p><strong>Break Time:</strong>
                                    <?php 
                                    echo date('h:i A', strtotime($dentist['break_time_start'])) . ' - ' . 
                                         date('h:i A', strtotime($dentist['break_time_end']));
                                    ?>
                                    </p>
                                </div>
                                <div class="col-md-12">
                                    <p><strong>Consultation Duration:</strong> <?php echo htmlspecialchars($dentist['consultation_duration']); ?> minutes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'layouts/footer.php'; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
