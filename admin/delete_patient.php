<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); // Consider updating to absolute if needed
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Check if patient exists and get user_id
        $stmt = $db->prepare("SELECT user_id FROM patients WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$patient) {
            throw new Exception("Patient not found");
        }
        
        // Delete related appointments first
        $stmt = $db->prepare("DELETE FROM appointments WHERE patient_id = ?");
        $stmt->execute([$_GET['id']]);
        
        // Delete patient
        $stmt = $db->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        // Delete user account
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$patient['user_id']]);
        
        $db->commit();
        header("Location: manage_patients.php?success=deleted");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: manage_patients.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: manage_patients.php");
    exit();
}