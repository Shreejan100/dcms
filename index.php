<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nagarik Dental - Your Smile, Our Priority</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/modern-style.css">
    <style>
        /* Dentist card styles - matching find-dentist.php */
        .dentist-card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dentist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .profile-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: -60px auto 15px;
            display: block;
        }
        .specialization {
            color: #17a2b8;
            font-weight: 500;
        }
        .btn-view-profile {
            background-color: #17a2b8;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        .btn-view-profile:hover {
            background-color: #138496;
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
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#dentists">Our Dentists</a>
                    </li>                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
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

    <!-- Hero Section -->
    <section class="hero-section text-white d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-text" style="position: relative; z-index: 10;">
                    <h1 class="display-4 fw-bold mb-4">Your Smile Is Our Priority</h1>
                    <p class="lead mb-4">Experience top-quality dental care with our team of expert dentists. Book your appointment today!</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="find-dentist.php" class="btn btn-primary btn-lg px-4 rounded-pill">Find a Dentist and Book Appointment</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="assets/images/hero-image.png" alt="Dental Care" class="img-fluid rounded-4 shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="feature-card p-4 bg-white rounded-4 shadow-sm h-100">
                        <div class="icon-box mb-3">
                            <i class="fas fa-user-md fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 mb-3">Expert Dentists</h3>
                        <p class="text-muted">Our qualified dental specialists are here to provide exceptional care for all your dental needs.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card p-4 bg-white rounded-4 shadow-sm h-100">
                        <div class="icon-box mb-3">
                            <i class="fas fa-calendar-check fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 mb-3">Easy Appointments</h3>
                        <p class="text-muted">Schedule your dental appointment with just a few clicks through our online booking system.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card p-4 bg-white rounded-4 shadow-sm h-100">
                        <div class="icon-box mb-3">
                            <i class="fas fa-tooth fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 mb-3">Modern Technology</h3>
                        <p class="text-muted">We use the latest dental technologies to ensure comfortable and effective treatments.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Our Dental Services</h2>
                <p class="lead text-muted">Comprehensive care for all your dental needs</p>
            </div>
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/images/service-general.jpg" class="card-img-top" alt="General Dentistry">
                        <div class="card-body">
                            <h5 class="card-title">General Dentistry</h5>
                            <p class="card-text">Regular checkups, cleanings, and preventive care to maintain oral health.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/images/service-cosmetic.jpg" class="card-img-top" alt="Cosmetic Dentistry">
                        <div class="card-body">
                            <h5 class="card-title">Cosmetic Dentistry</h5>
                            <p class="card-text">Improve your smile with teeth whitening, veneers, and other aesthetic procedures.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/images/service-ortho.jpg" class="card-img-top" alt="Orthodontics">
                        <div class="card-body">
                            <h5 class="card-title">Orthodontics</h5>
                            <p class="card-text">Straighten your teeth with braces or clear aligners for a perfect smile.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="services.php" class="btn btn-outline-primary px-4 rounded-pill">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Our Dentists Section -->
    <section id="dentists" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Our Expert Dentists</h2>
                <p class="lead text-muted">Meet our team of qualified dental professionals</p>
            </div>
            <div class="row">
                <?php
                // Fetch dentists from database
                $database = new Database();
                $conn = $database->getConnection();
                
                // Query to get featured dentists (limit to 3)
                $query = "SELECT d.* 
                          FROM dentists d 
                          JOIN users u ON d.user_id = u.id 
                          WHERE u.role = 'dentist' AND u.is_active = 1 AND d.status = 'active'
                          LIMIT 3";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $dentists = $stmt->fetchAll();
                
                // Display dentists
                if (!empty($dentists)) {
                    foreach ($dentists as $dentist) :
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card dentist-card">
                            <div class="card-img-top bg-light" style="height: 100px;"></div>
                            <img src="<?php echo !empty($dentist['profile_image']) ? 'uploads/profile_images/' . $dentist['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                 class="profile-image" 
                                 alt="Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>">
                            <div class="card-body text-center">
                                <h5 class="card-title">Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?></h5>
                                <p class="specialization"><?php echo htmlspecialchars($dentist['specialization']); ?></p>
                                <p class="text-muted">
                                    <i class="fas fa-briefcase"></i> 
                                    <?php echo $dentist['work_experience']; ?> years experience
                                </p>
                                <?php if (!empty($dentist['consultation_charge'])): ?>
                                <p class="text-muted">
                                    <i class="fas fa-money-bill-wave"></i> 
                                    Fee: NRs. <?php echo number_format((int)$dentist['consultation_charge']); ?>
                                </p>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="fulldentistprofile.php?id=<?php echo $dentist['id']; ?>" class="btn btn-primary w-100 btn-view-profile">
                                        <i class="fas fa-user-md me-1"></i> View Profile & Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                } else {
                    // If no dentists found
                    echo '<div class="col-12 text-center"><p class="text-muted">No dentists available at the moment.</p></div>';
                }
                ?>
            </div>
            <div class="text-center mt-4">
                <a href="find-dentist.php" class="btn btn-view-profile">
                    <i class="fas fa-user-md me-2"></i>View All Dentists
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center mb-4">
                    <h2 class="display-5 fw-bold mb-4">Contact Us</h2>
                    <p class="lead mb-5">Have questions or ready to schedule your appointment? Get in touch with us.</p>
                </div>
                <div class="col-lg-6 col-md-8 mx-auto">
                    <div class="contact-info card border-0 shadow-sm p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-box me-3 bg-primary text-white rounded-circle p-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <p class="mb-0">New Road, Pokhara</p>
                        </div>
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-box me-3 bg-primary text-white rounded-circle p-3">
                                <i class="fas fa-phone"></i>
                            </div>
                            <p class="mb-0">+977 9840043916</p>
                        </div>
                        <div class="d-flex align-items-center mb-4">
                            <div class="icon-box me-3 bg-primary text-white rounded-circle p-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <p class="mb-0">info@nagarikdental.com</p>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="icon-box me-3 bg-primary text-white rounded-circle p-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <p class="mb-1">Sunday - Friday: 9:00 AM - 5:00 PM</p>
                                <p class="mb-0">Saturday: Closed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12 mb-4 mb-md-0 text-center">
                    <h5 class="mb-3">Nagarik Dental</h5>
                    <p class="text-muted">Providing quality dental care services to enhance your smile and maintain your oral health.</p>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/profile.php?id=61572520474275" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>

                        <a href="https://www.instagram.com/nagarikdentalclinic?igsh=bTcwc3k1eDQzcmh4" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                    
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="text-center text-muted">
                <p class="mb-0">© <?php echo date('Y'); ?> Nagarik Dental. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap and JavaScript libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
