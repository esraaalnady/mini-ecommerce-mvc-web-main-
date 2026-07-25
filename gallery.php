<?php
/**
 * ============================================================================
 * gallery.php — معرض عرض المنتجات (مناسب للطباعة/المعاينة)
 * ============================================================================
 * ملاحظات:
 * - جلب المنتجات مع اسم القسم عبر LEFT JOIN.
 * - إذا لم توجد صورة للمنتج تُعرض uploads/default.svg.
 * - الصفحة مخصّصة للعرض أكثر من الشراء.
 * ============================================================================
 */

session_start();

try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// جلب كل المنتجات مع اسم القسم للعرض في المعرض
$stmt = $db->query(
    "SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY p.id DESC"
);
$products = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معرض صور المنتجات 📸</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #ffffff;
            text-align: center;
            padding: 40px 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 2.2rem;
        }
        .header p {
            color: #777;
            margin-top: 10px;
            font-size: 1.1rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 50px 20px;
        }
        .back-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 25px;
            font-weight: bold;
            transition: 0.3s;
        }
        .back-btn:hover {
            background-color: #0056b3;
        }
        /* شبكة الصور المخصصة */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        .gallery-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .image-container {
            width: 100%;
            height: 250px;
            overflow: hidden;
            position: relative;
            background-color: #eaeaea;
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .gallery-card:hover .image-container img {
            transform: scale(1.08); /* تأثير تكبير الصورة عند تمرير الماوس */
        }
        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card-details {
            padding: 20px;
            text-align: center;
        }
        .card-details h3 {
            margin: 0 0 10px 0;
            color: #2d3748;
            font-size: 1.25rem;
        }
        .card-price {
            font-size: 1.15rem;
            color: #28a745;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .card-desc {
            font-size: 0.9rem;
            color: #718096;
            margin: 0;
            line-height: 1.4;
        }
        .no-products {
            text-align: center;
            padding: 50px;
            font-size: 1.2rem;
            color: #777;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="header">
    <h1>📸 معرض منتجاتنا المميزة</h1>
    <p>تصفحي تشكيلتنا المتنوعة من أشهى الحلويات والمنتجات بالصور</p>
</div>

<div class="container">
    <a href="index.php" class="back-btn">⬅ العودة للرئيسية</a>

    <?php if (count($products) > 0): ?>
        <div class="gallery-grid">
            <?php foreach ($products as $p): ?>
                <div class="gallery-card">
                    <div class="image-container">
                        <span class="category-badge"><?php echo htmlspecialchars($p->category_name ?? 'غير مصنف'); ?></span>
                        <?php
                            // مسار الصورة الحقيقي أو الصورة البديلة عند غياب ملف الصورة
                            $product_image = (!empty($p->image) && file_exists($p->image))
                                ? $p->image
                                : 'uploads/default.svg';
                        ?>
                        <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($p->name); ?>">
                    </div>
                    <div class="card-details">
                        <h3><?php echo htmlspecialchars($p->name); ?></h3>
                        <div class="card-price">$<?php echo htmlspecialchars($p->price); ?></div>
                        <p class="card-desc"><?php echo htmlspecialchars($p->description); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            لا توجد صور منتجات مرفوعة حالياً في المعرض.
        </div>
    <?php endif; ?>
</div>

</body>
</html>
