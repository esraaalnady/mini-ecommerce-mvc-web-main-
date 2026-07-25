<?php
/**
 * ============================================================================
 * hot_drinks.php — صفحة عرض قسم "مشروبات ساخنة" فقط
 * ============================================================================
 * ملاحظات:
 * - نفس هيكل cold_drinks.php مع فلتر اسم قسم مختلف.
 * - إعادة استخدام نفس الهيكل مع شرط SQL مختلف.
 * ============================================================================
 */

session_start();

try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("فشل الاتصال: " . $e->getMessage());
}

// فلترة المنتجات حسب اسم قسم "مشروبات ساخنة"
$stmt = $db->query(
    "SELECT p.*
     FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE c.name = 'مشروبات ساخنة'
     ORDER BY p.id DESC"
);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مشروبات ساخنة دافئة ☕</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background-color: #faf6f0; }
        .hero-hot { background: linear-gradient(135deg, #e67e22 0%, #d35400 100%); color: white; padding: 60px 0; border-radius: 0 0 30px 30px; }
        .product-card { border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(211, 84, 0, 0.2); }
        .product-card img { height: 230px; object-fit: cover; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">🧁 متجر شادية</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link fw-bold text-dark" href="index.php">الرئيسية</a>
            <a class="nav-link fw-bold text-primary" href="cold_drinks.php">🥤 مشروبات باردة</a>
            <a class="nav-link fw-bold text-danger" href="hot_drinks.php">☕ مشروبات ساخنة</a>
            <a class="nav-link fw-bold text-danger" href="cart.php">🛒 السلة</a>
        </div>
    </div>
</nav>

<header class="hero-hot text-center">
    <h1 class="fw-bold">☕ مشروبات ساخنة دافئة</h1>
    <p class="lead">أجود أنواع البن والمشروبات الدافئة المجهزة بحب لتعديل مزاجكِ!</p>
</header>

<div class="container my-5">
    <div class="row">
        <?php foreach ($products as $p): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card product-card h-100">
                    <?php
                        // صورة المنتج أو الصورة البديلة عند غياب الملف
                        $product_image = (!empty($p->image) && file_exists($p->image))
                            ? $p->image
                            : 'uploads/default.svg';
                    ?>
                    <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($p->name); ?>">
                    <div class="card-body text-center d-flex flex-column">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($p->name); ?></h5>
                        <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars($p->description); ?></p>
                        <h5 class="text-danger fw-bold mb-3">$<?php echo htmlspecialchars($p->price); ?></h5>
                        <a href="cart.php?action=add&id=<?php echo $p->id; ?>" class="btn btn-outline-danger rounded-pill">اضف للسلة 🛒</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>