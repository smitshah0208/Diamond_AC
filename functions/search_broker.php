<?php
header('Content-Type: application/json');
include "../config/db.php";

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // Search brokers - case-insensitive search using LOWER()
    $stmt = $conn->prepare("SELECT id, name FROM brokers WHERE LOWER(name) LIKE LOWER(?) ORDER BY name ASC LIMIT 15");
    $searchTerm = $query . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $brokers = [];
    while ($row = $result->fetch_assoc()) {
        $brokers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'info' => ''
        ];
    }
    
    echo json_encode($brokers);
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Search broker error: " . $e->getMessage());
    echo json_encode([]);
}

$conn->close();
?>