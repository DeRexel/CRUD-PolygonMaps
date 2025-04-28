<?php
ob_start();
require 'db.php';
header('Content-Type: application/json');

// Get the ID from the request
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    ob_end_flush();
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM areas WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Area not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

ob_end_flush();
?>
