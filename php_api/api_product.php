<?php

include 'condb.php';

$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    switch ($action) {
        case 'add':
            $product_name = $_POST['product_name'];
            $category_id = $_POST['category_id']; // ✅ เพิ่มบรรทัดนี้
            $description = $_POST['description'];
            $price = $_POST['price'];
            $stock = $_POST['stock'];

            // อัพโหลดรูป
            $filename = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $filename = time() . '_' . basename($_FILES['image']['name']);
                $targetFile = $targetDir . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
            }

            $sql = "INSERT INTO products (product_name, category_id, description, price, stock, image)
                    VALUES (:product_name, :category_id, :description, :price, :stock, :image)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':image', $filename);

            if ($stmt->execute()) {
                echo json_encode(["message" => "เพิ่มสินค้าสำเร็จ"]);
            } else {
                echo json_encode(["error" => "เพิ่มสินค้าล้มเหลว"]);
            }
            break;

        case 'update':
            $product_id = $_POST['product_id'];
            $product_name = $_POST['product_name'];
            $category_id = $_POST['category_id']; // ✅ เพิ่มตรงนี้
            $description = $_POST['description'];
            $price = $_POST['price'];
            $stock = $_POST['stock'];

            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $targetDir = "uploads/";
                $filename = time() . '_' . basename($_FILES['image']['name']);
                $targetFile = $targetDir . $filename;
                move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
                $imageSQL = ", image = :image";
            } else {
                $imageSQL = "";
            }

            $sql = "UPDATE products SET 
                        product_name = :product_name,
                        category_id = :category_id,
                        description = :description,
                        price = :price,
                        stock = :stock
                        $imageSQL
                    WHERE product_id = :product_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':product_id', $product_id);
            if (isset($filename)) $stmt->bindParam(':image', $filename);

            if ($stmt->execute()) {
                echo json_encode(["message" => "แก้ไขสินค้าสำเร็จ"]);
            } else {
                echo json_encode(["error" => "แก้ไขสินค้าล้มเหลว"]);
            }
            break;

        case 'delete':
            $product_id = $_POST['product_id'];

            // 🔍 ดึงชื่อไฟล์รูปก่อนลบ
            $stmt = $conn->prepare("SELECT image FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product && !empty($product['image'])) {
                $filePath = "uploads/" . $product['image'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id);

            if ($stmt->execute()) {
                echo json_encode(["message" => "ลบสินค้าสำเร็จ และลบรูปภาพออกจากโฟลเดอร์แล้ว"]);
            } else {
                echo json_encode(["error" => "ลบสินค้าล้มเหลว"]);
            }
            break;

        default:
            echo json_encode(["error" => "Action ไม่ถูกต้อง"]);
            break;
    }

} else {
    // ✅ GET: ดึงข้อมูลสินค้า + ชื่อประเภท
    $sql = "SELECT p.*, c.category_name 
            FROM products p
            LEFT JOIN category c ON p.category_id = c.category_id
            ORDER BY p.product_id DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute()) {
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $products]);
    } else {
        echo json_encode(["success" => false, "data" => []]);
    }
}
?>
