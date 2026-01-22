<?php
session_start();
require_once 'config/database.php';

// Get specialization from URL if present
$selected_specialization = '';
if (isset($_GET['specialization'])) {
    $selected_specialization = $_GET['specialization'];
}

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Fetch all dentists from the database
$query = "SELECT d.* 
          FROM dentists d 
          JOIN users u ON d.user_id = u.id 
          WHERE u.role = 'dentist' AND u.is_active = 1 AND d.status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$dentists = $stmt->fetchAll();

// Define common dental specializations
$specializations = [
    'General Dentist',
    'Orthodontist',
    'Endodontist',
    'Periodontist',
    'Prosthodontist',
    'Pediatric Dentist',
    'Cosmetic Dentist',
    'Oral and Maxillofacial Surgeon'
];

// Fetch unique work_experience values
$experience_query = "SELECT DISTINCT work_experience FROM dentists WHERE work_experience IS NOT NULL ORDER BY work_experience";
$exp_stmt = $conn->prepare($experience_query);
$exp_stmt->execute();
$experiences = [];
while ($row = $exp_stmt->fetch()) {
    $experiences[] = $row['work_experience'];
}

// If no experiences found, provide some default values
if (empty($experiences)) {
    $experiences = [1, 2, 3, 5, 10, 15, 20];
}

// Fetch unique consultation fee values
$fee_query = "SELECT DISTINCT consultation_charge FROM dentists WHERE consultation_charge IS NOT NULL ORDER BY consultation_charge";
$fee_stmt = $conn->prepare($fee_query);
$fee_stmt->execute();
$fees = [];
while ($row = $fee_stmt->fetch()) {
    $fees[] = $row['consultation_charge'];
}

// Fetch unique degree values
$degree_query = "SELECT DISTINCT degree FROM dentists WHERE degree IS NOT NULL";
$degree_stmt = $conn->prepare($degree_query);
$degree_stmt->execute();
$degrees = [];
while ($row = $degree_stmt->fetch()) {
    $degrees[] = $row['degree'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Dentist - Nagarik Dental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/modern-style.css">
    <style>
        .filter-sidebar {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px; /* Keeps the sidebar visible when scrolling, just below navbar */
        }
        .filter-heading {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
            margin-bottom: 15px;
            color: #17a2b8;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        .filter-group {
            margin-bottom: 20px;
        }
        .filter-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
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
        .card-body {
            padding: 1.5rem;
            text-align: center;
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
        .range-values {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }
        .range-slider {
            width: 100%;
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

    <div class="container" style="margin-top: 130px; padding-top: 30px; padding-bottom: 30px;">
        <h2 class="text-center mb-4">Find a Dentist</h2>
        
        <div class="row">
            <!-- Filter Sidebar (Left Column) -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h3 class="filter-heading">Find Your Dentist</h3>
                    <form id="dentistFilterForm">
                        <!-- Quick Search -->
                        <div class="filter-group">
                            <label for="searchName" class="filter-label"><i class="fas fa-search me-2"></i>Search by Name</label>
                            <input type="text" class="form-control" id="searchName" placeholder="Enter dentist name">
                        </div>
                        
                        <!-- Primary Filters -->
                        <div class="mt-4 mb-2"><strong>Primary Filters</strong></div>
                        
                        <div class="filter-group">
                            <label for="specialization" class="filter-label"><i class="fas fa-tooth me-2"></i>Specialization</label>
                            <select class="form-select" id="specialization">
                                <option value="">All Specializations</option>
                                <?php foreach ($specializations as $spec): ?>
                                    <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($selected_specialization == $spec) ? 'selected' : ''; ?>><?php echo htmlspecialchars($spec); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="experience" class="filter-label"><i class="fas fa-user-md me-2"></i>Experience</label>
                            <select class="form-select" id="experience">
                                <option value="">Any Experience</option>
                                <option value="1">1+ years</option>
                                <option value="3">3+ years</option>
                                <option value="5">5+ years</option>
                                <option value="10">10+ years</option>
                            </select>
                        </div>
                        
                        <!-- Secondary Filters -->
                        <div class="mt-4 mb-2"><strong>Additional Filters</strong></div>
                        
                        <div class="filter-group">
                            <label for="gender" class="filter-label"><i class="fas fa-venus-mars me-2"></i>Gender</label>
                            <select class="form-select" id="gender">
                                <option value="">All Genders</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="fee" class="filter-label"><i class="fas fa-money-bill-wave me-2"></i>Consultation Fee</label>
                            <select class="form-select" id="fee">
                                <option value="">Any Fee</option>
                                <option value="1000">Under NRs. 1000</option>
                                <option value="2000">Under NRs. 2000</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="preferredDay" class="filter-label"><i class="fas fa-calendar-day me-2"></i>Preferred Day</label>
                            <select class="form-select" id="preferredDay">
                                <option value="">Any Day</option>
                                <option value="monday">Monday</option>
                                <option value="tuesday">Tuesday</option>
                                <option value="wednesday">Wednesday</option>
                                <option value="thursday">Thursday</option>
                                <option value="friday">Friday</option>
                                <option value="saturday">Saturday</option>
                                <option value="sunday">Sunday</option>
                            </select>
                        </div>
                            
                        <button type="button" id="resetFilters" class="btn btn-primary w-100 mt-4"><i class="fas fa-redo me-2"></i>Reset All Filters</button>
                    </form>
                </div>
            </div>
            
            <!-- Dentists Grid (Right Column) -->
            <div class="col-lg-9">
                <div class="row" id="dentistsContainer">
                    <?php if (empty($dentists)): ?>
                        <div class="col-12 text-center">
                            <p class="text-muted">No dentists found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($dentists as $dentist): ?>
                            <?php
                                // Parse working days
                                $workingDays = json_decode($dentist['working_days'] ?? '[]', true);
                                if (!is_array($workingDays)) {
                                    $workingDays = [];
                                }
                                $workingDaysString = implode(',', $workingDays);
                                
                                // Format working hours
                                $workingHoursStart = isset($dentist['working_hours_start']) ? date('H:i', strtotime($dentist['working_hours_start'])) : '';
                                $workingHoursEnd = isset($dentist['working_hours_end']) ? date('H:i', strtotime($dentist['working_hours_end'])) : '';
                            ?>
                            <div class="col-md-6 mb-4 dentist-item" 
                                 data-name="<?php echo strtolower($dentist['first_name'] . ' ' . $dentist['last_name']); ?>"
                                 data-specialization="<?php echo strtolower($dentist['specialization'] ?? ''); ?>"
                                 data-gender="<?php echo strtolower($dentist['gender'] ?? ''); ?>"
                                 data-experience="<?php echo (int)($dentist['work_experience'] ?? 0); ?>"
                                 data-fee="<?php echo (int)($dentist['consultation_charge'] ?? 0); ?>"
                                 data-degree="<?php echo strtolower($dentist['degree'] ?? ''); ?>"
                                 data-working-days="<?php echo $workingDaysString; ?>"
                                 data-working-hours-start="<?php echo $workingHoursStart; ?>"
                                 data-working-hours-end="<?php echo $workingHoursEnd; ?>">
                                <div class="card dentist-card">
                                    <div class="card-img-top bg-light" style="height: 100px;"></div>
                                    <img src="<?php echo !empty($dentist['profile_image']) ? 'uploads/profile_images/' . $dentist['profile_image'] : 'assets/images/default-avatar.png'; ?>" 
                                         class="profile-image" 
                                         alt="Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title">Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?></h5>
                                        <p class="specialization"><?php echo htmlspecialchars($dentist['specialization'] ?? 'General Dentist'); ?></p>
                                        <p class="text-muted">
                                            <i class="fas fa-briefcase"></i> 
                                            <?php echo $dentist['work_experience'] ?? '0'; ?> years experience
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap and JavaScript libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    
    <script>
        $(document).ready(function() {
            // Debug function to check data attributes
            function logDentistData() {
                $('.dentist-item').each(function() {
                    const $item = $(this);
                    console.log('Dentist Data:', {
                        name: $item.data('name'),
                        specialization: $item.data('specialization'),
                        gender: $item.data('gender'),
                        experience: $item.data('experience'),
                        fee: $item.data('fee'),
                        degree: $item.data('degree')
                    });
                });
            }
            
            // Log data attributes for debugging
            logDentistData();
            
            // Check if a specialization is selected on page load and apply the filter
            if ($('#specialization').val()) {
                filterDentists();
            }
            
            function filterDentists() {
                console.log('Filtering with values:', {
                    name: $('#searchName').val().toLowerCase(),
                    specialization: $('#specialization').val().toLowerCase(),
                    gender: $('#gender').val().toLowerCase(),
                    experience: parseInt($('#experience').val() || 0),
                    fee: parseInt($('#fee').val() || 0),
                    preferredDay: $('#preferredDay').val(),
                    preferredTime: $('#preferredTime').val()
                });
                
                const name = $('#searchName').val().toLowerCase();
                const specialization = $('#specialization').val().toLowerCase();
                const gender = $('#gender').val().toLowerCase();
                const experience = parseInt($('#experience').val() || 0);
                const fee = parseInt($('#fee').val() || 0);
                const preferredDay = $('#preferredDay').val();
                const preferredTime = $('#preferredTime').val();

                $('.dentist-item').each(function() {
                    const $item = $(this);
                    const matchesName = !name || $item.data('name').includes(name);
                    const matchesSpecialization = !specialization || $item.data('specialization') === specialization;
                    const matchesGender = !gender || $item.data('gender') === gender;
                    const matchesExperience = !experience || $item.data('experience') >= experience;
                    const matchesFee = !fee || $item.data('fee') <= fee; // Changed to 'less than or equal' for 'under' filtering
                    
                    // Check if dentist works on preferred day
                    let matchesDay = true;
                    if (preferredDay) {
                        const workingDays = $item.data('working-days').split(',');
                        matchesDay = workingDays.includes(preferredDay);
                    }
                    
                    // Check if dentist works during preferred time
                    let matchesTime = true;
                    if (preferredTime) {
                        const dentistStart = $item.data('working-hours-start');
                        const dentistEnd = $item.data('working-hours-end');
                        
                        if (!dentistStart || !dentistEnd) {
                            matchesTime = false;
                        } else {
                            // Convert times to 24-hour format for easier comparison
                            const [startHour] = dentistStart.split(':').map(Number);
                            const [endHour] = dentistEnd.split(':').map(Number);
                            
                            if (preferredTime === 'morning' && (startHour > 12 || endHour < 8)) {
                                matchesTime = false;
                            } else if (preferredTime === 'afternoon' && (startHour > 16 || endHour < 12)) {
                                matchesTime = false;
                            } else if (preferredTime === 'evening' && (startHour > 20 || endHour < 16)) {
                                matchesTime = false;
                            }
                        }
                    }
                    
                    // Log matching criteria for debugging
                    console.log('Item matches:', {
                        name: matchesName,
                        specialization: matchesSpecialization,
                        gender: matchesGender,
                        experience: matchesExperience,
                        fee: matchesFee,
                        day: matchesDay,
                        time: matchesTime
                    });

                    if (matchesName && matchesSpecialization && matchesGender && 
                        matchesExperience && matchesFee && matchesDay && matchesTime) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                });
                
                // Show a message if no results are found
                const visibleItems = $('.dentist-item:visible').length;
                if (visibleItems === 0) {
                    if ($('#no-results-message').length === 0) {
                        $('#dentistsContainer').append('<div id="no-results-message" class="col-12 text-center"><p class="text-muted">No dentists match your filter criteria.</p></div>');
                    }
                } else {
                    $('#no-results-message').remove();
                }
            }

            // Trigger filtering on any filter change
            $('#searchName, #specialization, #gender, #degree, #experience, #fee, #preferredDay, #preferredTime').on('input change', filterDentists);

            // Reset all filters
            $('#resetFilters').click(function() {
                $('#searchName').val('');
                $('#specialization').val('');
                $('#gender').val('');
                $('#experience').val('');
                $('#fee').val('');
                $('#preferredDay').val('');
                $('#preferredTime').val('');
                $('.dentist-item').show();
                $('#no-results-message').remove();
            });
        });
    </script>
</body>
</html>
