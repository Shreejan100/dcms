<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo isset($_SESSION['role']) ? '../' . $_SESSION['role'] . '/dashboard.php' : '../index.php'; ?>">
            DCMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (isset($_SESSION['role'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage_dentists.php">Manage Dentists</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/manage_patients.php">Manage Patients</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../admin/appointments.php">Appointments</a>
                        </li>
                    </ul>
                <?php elseif ($_SESSION['role'] === 'dentist'): ?>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="../dentist/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../dentist/appointments.php">My Appointments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../dentist/schedule.php">My Schedule</a>
                        </li>
                    </ul>
                <?php elseif ($_SESSION['role'] === 'patient'): ?>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="../patient/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../patient/book_appointment.php">Book Appointment</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../patient/my_appointments.php">My Appointments</a>
                        </li>
                    </ul>
                <?php endif; ?>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../weblogin.php">Login</a>
                    </li>
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle btn btn-primary text-white px-4 rounded-pill" href="#" id="registerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Register
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="registerDropdown">
                            <li><a class="dropdown-item" href="../auth/register.php"><i class="fas fa-user-plus me-2"></i>Patient Registration</a></li>
                            <li><a class="dropdown-item" href="../auth/register_dentist.php"><i class="fas fa-user-md me-2"></i>Dentist Registration</a></li>
                        </ul>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
