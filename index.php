<?php
/**
 * ============================================================================
 * index.php — الصفحة الرئيسية للمتجر
 * ============================================================================
 * ملاحظات:
 * - بدء الجلسة لمعرفة هل المستخدم مسجل دخول أم لا.
 * - الاتصال بقاعدة البيانات وجلب الأقسام والمنتجات.
 * - التصفية حسب القسم عبر معامل الرابط category_id.
 * - عرض المنتجات في واجهة HTML مع حماية htmlspecialchars.
 * ============================================================================
 */

// بدء الجلسة لقراءة بيانات المستخدم وعدد عناصر السلة
session_start();

// =========================
// الاتصال بقاعدة البيانات
// =========================
try {
    // charset=utf8mb4 ضروري لعرض العربية والإيموجي بشكل صحيح
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // أخطاء SQL تظهر كاستثناءات
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ); // النتائج ككائنات
} catch (PDOException $e) {
    // بدون قاعدة بيانات لا يمكن عرض المتجر
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// =========================
// 1) جلب الأقسام لشريط التصفية
// =========================
$categories = [];
try {
    // ORDER BY id DESC = الأحدث أولاً
    $categories = $db->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    // لو فشل الاستعلام نكمل الصفحة بقائمة أقسام فارغة
    $categories = [];
}

// =========================
// 2) جلب المنتجات (مع أو بدون فلتر قسم)
// =========================
// نقرأ رقم القسم من الرابط مثل: index.php?category_id=2
// intval يحول القيمة إلى رقم صحيح للحماية
$category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_filter > 0) {
    /**
     * تصفية حسب قسم محدد
     * نستخدم LEFT JOIN لجلب اسم القسم مع كل منتج
     * والاستعلام المُحضَّر يحمي من SQL Injection
     */
    $stmt = $db->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.category_id = :cat_id
         ORDER BY p.id DESC"
    );
    $stmt->execute([':cat_id' => $category_filter]);
} else {
    // بدون فلتر: عرض كل المنتجات
    $stmt = $db->query(
        "SELECT p.*, c.name AS category_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         ORDER BY p.id DESC"
    );
}

// تحويل نتيجة الاستعلام إلى مصفوفة منتجات
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر الحلويات والمشروبات الفاخرة 🍰🥤</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #fdfaf6; /* لون خلفية ناعم ومريح للعين يناسب طابع الحلويات */
        }
        .hero-section {
            background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .category-btn {
            border-radius: 25px;
            padding: 8px 20px;
            margin: 5px;
            transition: 0.3s;
            font-weight: 500;
        }
        /* لمسة فنية: تأثير الزوم الناعم والظل الوردي عند التمرير على كروت المنتجات */
        .product-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            background: white;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(214, 51, 132, 0.15);
        }
        .product-card img {
            height: 230px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.5s ease;
        }
        .product-card:hover img {
            transform: scale(1.06);
        }
        .card-title {
            font-weight: bold;
            color: #4a4a4a;
        }
        .price-tag {
            font-size: 1.25rem;
            color: #d63384;
            font-weight: bold;
        }
        /* لمسة فنية: تدرج لوني رائع ومميز لأزرار الشراء */
        .btn-gradient {
            background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%);
            color: white;
            border: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #ff7eb3 0%, #ff758c 100%);
            transform: scale(1.02);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 117, 140, 0.3);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">🧁 متجر شادية للحلويات</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active fw-bold" href="index.php">الرئيسية</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link fw-bold text-info" href="cold_drinks.php">🥤 مشروبات باردة</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-warning" href="hot_drinks.php">☕ مشروبات ساخنة</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link fw-bold text-primary" href="gallery.php">📸 معرض الصور والطباعة</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link fw-bold text-danger" href="cart.php">🛒 السلة 
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo count($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <div class="d-flex gap-2 ms-lg-3 align-items-center">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <span class="navbar-text me-2 fw-bold text-dark">مرحباً، <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</span>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin_dashboard.php" class="btn btn-sm btn-outline-danger">لوحة التحكم</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-sm btn-dark">خروج</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm btn-outline-primary">دخول</a>
                    <a href="register.php" class="btn btn-sm btn-primary">تسجيل جديد</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<?php if (isset($_GET['cart_msg']) && $_GET['cart_msg'] === 'added'): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            🎉 تم إضافة المنتج إلى السلة بنجاح! <a href="cart.php" class="fw-bold text-success text-decoration-underline">عرض السلة من هنا 🛒</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<header class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">أهلاً بكِ في عالم الحلويات والمشروبات الفاخرة 🧁🥤</h1>
        <p class="lead mb-4">اكتشفي تشكيلة واسعة ولذيذة من الكعك المنزلي، المشروبات الباردة المنعشة، والقهوة الدافئة!</p>
        <a href="gallery.php" class="btn btn-light btn-lg text-primary fw-bold px-4 shadow">📸 تصفح المعرض والطباعة</a>
    </div>
</header>

<div class="container my-5">
    
    <div class="text-center mb-5">
        <h4 class="fw-bold mb-3">📂 تصفح حسب الأقسام:</h4>
        <a href="index.php" class="btn category-btn <?php echo $category_filter === 0 ? 'btn-primary' : 'btn-outline-primary'; ?>">الكل</a>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?category_id=<?php echo $cat->id; ?>" 
               class="btn category-btn <?php echo $category_filter === $cat->id ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <?php echo htmlspecialchars(($cat->emoji ?? '') . ' ' . $cat->name); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <h3 class="fw-bold mb-4 text-dark text-center">🛒 قائمة حلوياتنا ومشروباتنا المميزة</h3>
    
    <div class="row">
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $p): ?>
                <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                    <div class="card product-card h-100">
                        <?php
                            // عرض صورة المنتج إن وُجدت، وإلا نعرض الصورة البديلة الافتراضية
                            $product_image = (!empty($p->image) && file_exists($p->image))
                                ? $p->image
                                : 'uploads/default.svg';
                        ?>
                        <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($p->name); ?>">
                        <div class="card-body d-flex flex-column text-center">
                            <span class="badge bg-secondary mb-2 align-self-center"><?php echo htmlspecialchars($p->category_name ?? 'عام'); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($p->name); ?></h5>
                            <p class="card-text text-muted small flex-grow-1"><?php echo htmlspecialchars($p->description); ?></p>
                            <div class="price-tag mb-3">$<?php echo htmlspecialchars($p->price); ?></div>
                            
                            <a href="cart.php?action=add&id=<?php echo $p->id; ?>" class="btn btn-gradient w-100 rounded-pill">أضف للسلة 🛒</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-warning py-4">
                    لا توجد منتجات معروضة حالياً في هذا القسم.
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<footer class="bg-white border-top text-muted text-center py-4 mt-5">
    <p class="mb-0">جميع الحقوق محفوظة © متجر شادية للحلويات والمشروبات 2026</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>