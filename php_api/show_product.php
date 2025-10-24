<?php

// เชื่อมต่อฐานข้อมูล
include 'condb.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'GET') {
        // ✅ ปรับปรุง: ใช้คำสั่ง JOIN ตาราง 'category'
        // สมมติว่าในตาราง products มีคอลัมน์ category_id (p.category_id)
        // และในตาราง category มีคอลัมน์ category_id (c.category_id) และ category_name
        $stmt = $conn->prepare("
            SELECT 
                p.*, c.category_name 
            FROM 
                products p 
            JOIN 
                category c 
            ON 
                p.category_id = c.category_id
        ");
        
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ข้อมูลที่ได้จะมีฟิลด์ category_name เพิ่มเข้ามา
        echo json_encode(["success" => true, "data" => $data]);
        
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request method"]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}

?>