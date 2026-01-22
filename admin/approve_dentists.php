<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['dentist_id'])) {
    $dentist_id = (int)$_POST['dentist_id'];
    $action = $_POST['action'];
    
    try {
        $db->beginTransaction();
        
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE dentists SET status = 'active' WHERE id = ?");
            
            // Get dentist name for message
            $nameStmt = $db->prepare("SELECT first_name, last_name FROM dentists WHERE id = ?");
            $nameStmt->execute([$dentist_id]);
            $dentist = $nameStmt->fetch(PDO::FETCH_ASSOC);
            
            $message = "Doctor " . $dentist['first_name'] . " " . $dentist['last_name'] . " has been approved successfully";
            $_SESSION['status_message'] = [
                'type' => 'success',
                'text' => $message,
                'icon' => '<i class="bi bi-check-circle-fill"></i>'
            ];
            
            $stmt->execute([$dentist_id]);
            $db->commit();
            
            header("Location: manage_dentists.php?success=" . urlencode($message));
            exit();
        } else if ($action === 'reject') {
            // Get dentist details before deletion
            $nameStmt = $db->prepare("SELECT d.first_name, d.last_name, d.user_id 
                                    FROM dentists d 
                                    WHERE d.id = ?");
            $nameStmt->execute([$dentist_id]);
            $dentist = $nameStmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete from dentists table
            $stmt = $db->prepare("DELETE FROM dentists WHERE id = ?");
            $stmt->execute([$dentist_id]);
            
            // Delete from users table
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$dentist['user_id']]);
            
            $message = "Doctor " . $dentist['first_name'] . " " . $dentist['last_name'] . " has been rejected";
            $_SESSION['status_message'] = [
                'type' => 'danger',
                'text' => $message,
                'icon' => '<i class="bi bi-x-circle-fill"></i>'
            ];
        }
        
        $db->commit();
        
        header("Location: approve_dentists.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error processing request: " . $e->getMessage();
    }
}

include '../layouts/header.php';
?>

<div class="container-fluid">
    <?php if (isset($_SESSION['status_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['status_message']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['status_message']['icon']; ?> 
            <?php echo htmlspecialchars($_SESSION['status_message']['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['status_message']); ?>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Pending Dentists List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Pending Dentist Approvals</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Degree</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->query("SELECT d.*, u.username, u.created_at as registration_date 
                                          FROM dentists d 
                                          JOIN users u ON d.user_id = u.id 
                                          WHERE d.status = 'pending' 
                                          ORDER BY u.created_at DESC");
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['work_experience']) . " years</td>";
                            echo "<td>" . htmlspecialchars($row['degree']) . "</td>";
                            echo "<td>" . date('Y-m-d H:i', strtotime($row['registration_date'])) . "</td>";
                            echo "<td class='text-nowrap'>";
                            // View button
                            echo "<a href='view_dentist.php?id=" . $row['id'] . "' class='btn btn-sm btn-info text-white me-1' title='View Details'>";
                            echo "<i class='bi bi-eye'></i></a>";
                            
                            // Approve button
                            echo "<form method='POST' class='d-inline me-1'>";
                            echo "<input type='hidden' name='dentist_id' value='" . $row['id'] . "'>";
                            echo "<input type='hidden' name='action' value='approve'>";
                            echo "<button type='submit' class='btn btn-sm btn-success' title='Approve' onclick='return confirm(\"Are you sure you want to approve this dentist?\")'>";
                            echo "<i class='bi bi-check-lg'></i></button>";
                            echo "</form>";
                            
                            // Reject button
                            echo "<form method='POST' class='d-inline'>";
                            echo "<input type='hidden' name='dentist_id' value='" . $row['id'] . "'>";
                            echo "<input type='hidden' name='action' value='reject'>";
                            echo "<button type='submit' class='btn btn-sm btn-danger' title='Reject' onclick='return confirm(\"Are you sure you want to reject this dentist?\")'>";
                            echo "<i class='bi bi-x-lg'></i></button>";
                            echo "</form>";
                            
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        if ($stmt->rowCount() === 0) {
                            echo "<tr><td colspan='8' class='text-center'>No pending dentist approvals.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
