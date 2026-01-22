<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Debug session and role
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Session role: " . ($_SESSION['role'] ?? 'not set'));

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'dentist' && $_SESSION['role'] !== 'admin')) {
    error_log("Access denied: Invalid session or role");
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Initialize filter variables
    $status = $_GET['status'] ?? '';
    $date = $_GET['date'] ?? '';
    $search = $_GET['search'] ?? '';

    // Debug database connection and tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    error_log("Database tables: " . print_r($tables, true));
    
    // Get dentist ID based on role and parameters
    if ($_SESSION['role'] === 'admin' && isset($_GET['dentist_id'])) {
        // Admin viewing specific dentist's appointments
        $stmt = $db->prepare("SELECT id, first_name, last_name FROM dentists WHERE id = ?");
        $stmt->execute([$_GET['dentist_id']]);
    } else {
        // Dentist viewing their own appointments
        $stmt = $db->prepare("SELECT id, first_name, last_name FROM dentists WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    $dentist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dentist) {
        throw new Exception("Dentist not found for user_id: " . $_SESSION['user_id']);
    }
    
    error_log("Dentist found: " . print_r($dentist, true));

    // Check appointments table structure
    $stmt = $db->query("DESCRIBE appointments");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Appointments table columns: " . print_r($columns, true));

    // Handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
        try {
            $db->beginTransaction();

            // Check if the appointment still exists and hasn't been modified
            $stmt = $db->prepare("SELECT a.*, p.id as patient_id 
                                FROM appointments a 
                                JOIN patients p ON a.patient_id = p.id 
                                WHERE a.id = ? AND a.dentist_id = ? 
                                FOR UPDATE");
            $stmt->execute([$_POST['appointment_id'], $dentist['id']]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                throw new Exception("Appointment not found");
            }

            // If confirming appointment, check for conflicts
            if ($_POST['status'] === 'confirmed') {
                // Check if patient has another confirmed appointment within 15 minutes
                $stmt = $db->prepare("SELECT COUNT(*) FROM appointments 
                                    WHERE patient_id = ? 
                                    AND appointment_date = ? 
                                    AND id != ?
                                    AND status = 'confirmed'
                                    AND ABS(TIME_TO_SEC(TIMEDIFF(appointment_time, ?))) < 900");
                $stmt->execute([
                    $appointment['patient_id'],
                    $appointment['appointment_date'],
                    $appointment['id'],
                    $appointment['appointment_time']
                ]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Unable to confirm: The patient has another appointment scheduled at this time. Please choose a different time slot.");
                }

                // Check if dentist has another confirmed appointment at the same time
                $stmt = $db->prepare("SELECT COUNT(*) FROM appointments 
                                    WHERE dentist_id = ? 
                                    AND appointment_date = ? 
                                    AND appointment_time = ?
                                    AND status = 'confirmed'
                                    AND id != ?");
                $stmt->execute([
                    $dentist['id'],
                    $appointment['appointment_date'],
                    $appointment['appointment_time'],
                    $appointment['id']
                ]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Another appointment is already confirmed for this time slot");
                }
            }

            // Update the appointment status
            $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ? AND dentist_id = ?");
            $stmt->execute([$_POST['status'], $_POST['appointment_id'], $dentist['id']]);

            $db->commit();
            $success = "Appointment status updated successfully!";
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }

    // Build query
    $query = "SELECT a.*, 
              CONCAT(p.first_name, ' ', p.last_name) as patient_name,
              p.phone, p.email,
              DATE(a.appointment_date) as formatted_date,
              TIME(a.appointment_time) as formatted_time,
              DATE_FORMAT(a.created_at, '%Y-%m-%d %h:%i %p') as booked_at
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              WHERE a.dentist_id = ?";
    $params = [$dentist['id']];

    if ($status) {
        $query .= " AND a.status = ?";
        $params[] = $status;
    }

    if ($date) {
        $query .= " AND DATE(a.appointment_date) = ?";
        $params[] = $date;
    }

    if ($search) {
        $query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.phone LIKE ? OR p.email LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }

    $query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    // Debug the query and parameters
    error_log("Dentist ID: " . ($dentist['id'] ?? 'not found'));
    error_log("Appointments query: " . str_replace('?', '%s', $query));
    error_log("Query params: " . print_r($params, true));

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found appointments: " . count($appointments));
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h4>Manage Appointments</h4>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <input type="hidden" name="dentist_id" value="<?php echo $dentist['id']; ?>">
                    <input type="hidden" name="admin_view" value="1">
                <?php endif; ?>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="missed" <?php echo $status === 'missed' ? 'selected' : ''; ?>>Missed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Specific Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Patient</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, Phone, or Email">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <?php if ($status || $date || $search): ?>
                            <a href="<?php echo $_SESSION['role'] === 'admin' ? '?dentist_id=' . $dentist['id'] . '&admin_view=1' : ''; ?>" class="btn btn-secondary">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointments List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Booked At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No appointments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        echo date('M d, Y', strtotime($appointment['formatted_date'])) . '<br>';
                                        echo date('h:i A', strtotime($appointment['formatted_time']));
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                    <td>
                                        Phone: <?php echo htmlspecialchars($appointment['phone']); ?><br>
                                        Email: <?php echo htmlspecialchars($appointment['email']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusColor($appointment['status']); ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $appointment['booked_at']; ?>
                                    </td>
                                    <td>
                                        <?php if ($appointment['status'] === 'confirmed'): ?>
                                            <?php 
                                            date_default_timezone_set('Asia/Kathmandu');
                                            
                                            $appointment_date = $appointment['formatted_date'];
                                            $appointment_time = $appointment['formatted_time'];
                                            $current_date = date('Y-m-d');
                                            $current_time = date('H:i:s');
                                            
                                            // Debug information
                                            error_log("Raw Appointment Date: " . $appointment['formatted_date']);
                                            error_log("Raw Appointment Time: " . $appointment['formatted_time']);
                                            error_log("Current Date: " . $current_date);
                                            error_log("Current Time: " . $current_time);
                                            
                                            // Check if appointment is in the past
                                            $is_past = false;
                                            
                                            if ($appointment_date < $current_date) {
                                                $is_past = true;
                                                error_log("Past appointment - different date");
                                            } elseif ($appointment_date == $current_date && $appointment_time <= $current_time) {
                                                $is_past = true;
                                                error_log("Past appointment - same date, time passed");
                                            }
                                            
                                            error_log("Is Past Appointment: " . ($is_past ? "Yes" : "No"));
                                            
                                            if ($is_past): 
                                            ?>
                                                <a href="treatment_report.php?id=<?php echo $appointment['id']; 
                                                    echo isset($_GET['admin_view']) ? '&admin_view=1&dentist_id=' . $_GET['dentist_id'] : ''; ?>" 
                                                   class="btn btn-sm btn-info me-1">Complete & Report</a>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="updateStatus(<?php echo $appointment['id']; ?>, 'missed')">
                                                    Mark as Missed
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="updateStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success me-1" 
                                                    onclick="updateStatus(<?php echo $appointment['id']; ?>, 'confirmed')">
                                                Confirm
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger me-1" 
                                                    onclick="updateStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                                Cancel
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($appointment['status'] === 'completed'): ?>
                                            <a href="view_report.php?id=<?php echo $appointment['id']; 
                                                echo isset($_GET['admin_view']) ? '&admin_view=1&dentist_id=' . $_GET['dentist_id'] : ''; ?>" 
                                               class="btn btn-sm btn-secondary">View Report</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Form -->
<form id="updateStatusForm" action="" method="POST" style="display: none;">
    <input type="hidden" name="appointment_id" id="update_appointment_id">
    <input type="hidden" name="status" id="update_status">
</form>

<script>
function updateStatus(id, status) {
    if (confirm('Are you sure you want to update this appointment status?')) {
        document.getElementById('update_appointment_id').value = id;
        document.getElementById('update_status').value = status;
        document.getElementById('updateStatusForm').submit();
    }
}
</script>

<?php include '../layouts/footer.php'; ?>
