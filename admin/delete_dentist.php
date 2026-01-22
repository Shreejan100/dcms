<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php'); // Consider updating to absolute if needed
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: manage_dentists.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $dentist_id = (int)$_GET['id'];
    
    // Start transaction
    $db->beginTransaction();
    
    // First, verify the dentist exists
    $stmt = $db->prepare("SELECT user_id FROM dentists WHERE id = ?");
    $stmt->execute([$dentist_id]);
    $dentist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dentist) {
        throw new Exception("Dentist not found");
    }
    
    // Delete appointments associated with the dentist
    $stmt = $db->prepare("DELETE FROM appointments WHERE dentist_id = ?");
    $stmt->execute([$dentist_id]);
    
    // Delete from dentists table
    $stmt = $db->prepare("DELETE FROM dentists WHERE id = ?");
    $stmt->execute([$dentist_id]);
    
    // Delete from users table
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$dentist['user_id']]);
    
    // Commit transaction
    $db->commit();
    
    // Redirect with success message
    header('Location: manage_dentists.php?success=deleted');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    
    // Redirect with error message
    header('Location: manage_dentists.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>
