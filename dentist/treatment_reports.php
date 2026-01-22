<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dentist') {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get dentist ID
$stmt = $db->prepare("SELECT id FROM dentists WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$dentist = $stmt->fetch(PDO::FETCH_ASSOC);

// Get search parameters
$search = $_GET['search'] ?? '';
$date_range = $_GET['date_range'] ?? '';

// Build query
$query = "SELECT tr.*, 
          a.appointment_date, a.appointment_time,
          CONCAT(p.first_name, ' ', p.last_name) as patient_name,
          p.phone, p.email, p.gender, p.dob
          FROM treatment_reports tr
          JOIN appointments a ON tr.appointment_id = a.id
          JOIN patients p ON a.patient_id = p.id
          WHERE a.dentist_id = ?";
$params = [$dentist['id']];

if ($search) {
    $query .= " AND (
        p.first_name LIKE ? OR 
        p.last_name LIKE ? OR 
        p.phone LIKE ? OR 
        p.email LIKE ?
    )";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($date_range) {
    switch ($date_range) {
        case 'today':
            $query .= " AND DATE(a.appointment_date) = CURDATE()";
            break;
        case 'week':
            $query .= " AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $query .= " AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $query .= " AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
    }
}

// Order by most recent first
$query .= " ORDER BY tr.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Treatment Reports</h4>
                <a href="appointments.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to Appointments
                </a>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search Patient</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Name, Phone, or Email">
                </div>
                <div class="col-md-4">
                    <label for="date_range" class="form-label">Date Range</label>
                    <select class="form-select" id="date_range" name="date_range">
                        <option value="">All Time</option>
                        <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Reports List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($reports)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No treatment reports found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient Information</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        echo date('M d, Y', strtotime($report['appointment_date'])) . '<br>';
                                        echo date('h:i A', strtotime($report['appointment_time']));
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($report['patient_name']); ?></strong><br>
                                        <small class="text-muted">
                                            Phone: <?php echo htmlspecialchars($report['phone']); ?><br>
                                            Email: <?php echo htmlspecialchars($report['email']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php 
                                        $diagnosis = htmlspecialchars($report['diagnosis']);
                                        echo strlen($diagnosis) > 100 ? substr($diagnosis, 0, 100) . '...' : $diagnosis;
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $treatment = htmlspecialchars($report['treatment']);
                                        echo strlen($treatment) > 100 ? substr($treatment, 0, 100) . '...' : $treatment;
                                        ?>
                                    </td>
                                    <td>
                                        <a href="view_report.php?id=<?php echo $report['appointment_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> View Full Report
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.table td {
    vertical-align: middle;
}
</style>

<?php include '../layouts/footer.php'; ?>
