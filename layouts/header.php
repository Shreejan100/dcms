<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Calculate base path for admin links
$current_dir = dirname($_SERVER['PHP_SELF']);
$admin_path = '';
if (strpos($current_dir, '/dentist') !== false || strpos($current_dir, '/patient') !== false) {
    $admin_path = '../admin/';
} else if (strpos($current_dir, '/admin') === false) {
    $admin_path = 'admin/';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCMS - <?php echo ucfirst($role); ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 bg-dark sidebar">
                <div class="d-flex flex-column p-3 text-white">
                    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <span class="fs-4">DCMS</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <?php if ($role === 'admin' || (isset($_GET['admin_view']) && $_GET['admin_view'] == 1)): ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link text-white dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['manage_dentists.php', 'approve_dentists.php']) ? 'active' : ''; ?>" data-bs-toggle="dropdown">
                                <i class="bi bi-person-badge me-2"></i>
                                Manage Dentists
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li>
                                    <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_dentists.php' ? 'active' : ''; ?>" href="<?php echo $admin_path; ?>manage_dentists.php">
                                        <i class="bi bi-list-check me-2"></i>All Dentists
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'approve_dentists.php' ? 'active' : ''; ?>" href="<?php echo $admin_path; ?>approve_dentists.php">
                                        <i class="bi bi-person-check me-2"></i>Pending Approvals
                                        <?php
                                        // Get count of pending dentists
                                        $database = new Database();
                                        $db = $database->getConnection();
                                        $stmt = $db->query("SELECT COUNT(*) as count FROM dentists WHERE status = 'pending'");
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                        if ($result['count'] > 0) {
                                            echo '<span class="badge bg-danger ms-2">' . $result['count'] . '</span>';
                                        }
                                        ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li>
                            <a href="<?php echo $admin_path; ?>manage_patients.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_patients.php' ? 'active' : ''; ?>">
                                <i class="bi bi-people me-2"></i>
                                Manage Patients
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($role === 'patient'): ?>
                        <li>
                            <a href="book_appointment.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'book_appointment.php' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-plus me-2"></i>
                                Book Appointment
                            </a>
                        </li>
                        <li>
                            <a href="my_appointments.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'my_appointments.php' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-check me-2"></i>
                                My Appointments
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($role === 'dentist'): ?>
                        <li>
                            <a href="appointments.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar3 me-2"></i>
                                Appointments
                            </a>
                        </li>
                        <li>
                            <a href="treatment_reports.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'treatment_reports.php' ? 'active' : ''; ?>">
                                <i class="bi bi-file-text me-2"></i>
                                Treatment Reports
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li>
                            <a href="profile.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                                <i class="bi bi-person-circle me-2"></i>
                                Profile
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container-fluid">
                        <span class="navbar-brand"><?php echo ucfirst($role); ?> Dashboard</span>
                        <div class="d-flex">
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($username); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <main class="py-4">
