<?php
header('Content-Type: application/json');
include "../config/db.php";

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // Search broker from your database
    // Adjust the table name and columns according to your actual broker table
    $stmt = $conn->prepare("SELECT id, name, contact FROM brokers WHERE name LIKE ? LIMIT 10");
    $searchTerm = $query . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $brokers = [];
    while ($row = $result->fetch_assoc()) {
        $brokers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'info' => isset($row['contact']) ? $row['contact'] : ''
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