<?php
include 'condb.php';

$stmt = $conn->prepare("SELECT category_id, category_name FROM category ORDER BY category_id ASC");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $data]);
?>
