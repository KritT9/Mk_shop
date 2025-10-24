<?php
include('condb.php');

// ดึงข้อมูลประเภทสินค้า
$cat_sql = "SELECT * FROM category";
$cat_result = mysqli_query($conn, $cat_sql);

// ตรวจสอบว่าลูกค้าเลือกประเภทใด
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';

// ดึงข้อมูลสินค้าตามประเภท (หรือทั้งหมดถ้าไม่ได้เลือก)
if ($category_id) {
    $sql = "SELECT p.*, c.category_name 
            FROM product p 
            LEFT JOIN category c ON p.category_id = c.category_id 
            WHERE p.category_id = '$category_id'";
} else {
    $sql = "SELECT p.*, c.category_name 
            FROM product p 
            LEFT JOIN category c ON p.category_id = c.category_id";
}
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>MK Shop - สั่งอาหารออนไลน์</title>
    <style>
        body { font-family: Tahoma, sans-serif; background: #fafafa; }
        .category-menu { margin-bottom: 20px; }
        .category-menu a {
            display: inline-block;
            background: #ccc;
            color: #000;
            padding: 8px 15px;
            margin-right: 5px;
            border-radius: 5px;
            text-decoration: none;
        }
        .category-menu a.active { background: #009900; color: #fff; }
        .product-grid { display: flex; flex-wrap: wrap; gap: 15px; }
        .product-card {
            width: 200px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }
        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .product-card h4 { margin: 10px 0 5px; }
    </style>
</head>
<body>

<h2>🍲 MK Shop - เมนูอาหาร</h2>

<!-- เมนูเลือกประเภทสินค้า -->
<div class="category-menu">
    <a href="shop.php" class="<?php if(!$category_id) echo 'active'; ?>">ทั้งหมด</a>
    <?php while($cat = mysqli_fetch_assoc($cat_result)) { ?>
        <a href="shop.php?category_id=<?php echo $cat['category_id']; ?>"
           class="<?php if($category_id == $cat['category_id']) echo 'active'; ?>">
           <?php echo $cat['category_name']; ?>
        </a>
    <?php } ?>
</div>

<!-- แสดงรายการสินค้า -->
<div class="product-grid">
    <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <div class="product-card">
            <img src="images/<?php echo $row['image'] ?: 'noimage.jpg'; ?>" alt="">
            <h4><?php echo $row['product_name']; ?></h4>
            <p>ประเภท: <?php echo $row['category_name']; ?></p>
            <p>ราคา: <?php echo number_format($row['price'], 2); ?> บาท</p>
            <form action="cart_add.php" method="post">
                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                <button type="submit">เพิ่มในตะกร้า</button>
            </form>
        </div>
    <?php } ?>
</div>

</body>
</html>
