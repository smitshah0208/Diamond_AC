<?php
header('Content-Type: application/json');
include "../config/db.php";

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // Search parties - case-insensitive search using LOWER()
    $stmt = $conn->prepare("SELECT id, name FROM parties WHERE LOWER(name) LIKE LOWER(?) ORDER BY name ASC LIMIT 15");
    $searchTerm = $query . '%';
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $parties = [];
    while ($row = $result->fetch_assoc()) {
        $parties[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'info' => ''
        ];
    }
    
    echo json_encode($parties);
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Search party error: " . $e->getMessage());
    echo json_encode([]);
}

$conn->close();
?>  