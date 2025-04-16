<?php
session_start();
include('config.php');

// Make sure we have a logged-in user
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if all required fields are present
if (!isset($_POST['student_id']) || !isset($_POST['content']) || !isset($_POST['type']) || !isset($_POST['date_sent'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    // Prepare the insert statement
    $stmt = $pdo->prepare("INSERT INTO notifications (student_id, type, content, date_sent, created_by, document_ref) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    
    // Extracting values
    $student_id = $_POST['student_id'];
    $type = $_POST['type'];
    $content = $_POST['content'];
    $date_sent = $_POST['date_sent'];
    $created_by = $_SESSION['username'];
    $document_ref = $_POST['document_ref'] ?? null; // Optional field

    // Execute with values
    $result = $stmt->execute([
        $student_id,
        $type,
        $content,
        $date_sent,
        $created_by,
        $document_ref
    ]);
    
    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Notification saved successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save notification']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>