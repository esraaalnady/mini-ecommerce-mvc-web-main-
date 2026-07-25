<?php
/**
 * ============================================================================
 * admin_dashboard.php — الصفحة الرئيسية للوحة تحكم المسؤول
 * ============================================================================
 * ملاحظات:
 * 1) حماية الصفحة بدالة restrictToAdmin().
 * 2) حساب إحصائيات بسيطة من قاعدة البيانات (COUNT).
 * 3) عرض كروت أرقام + روابط سريعة لصفحات الإدارة.
 *
 * ملاحظة: $pdo يأتي من init.php عبر auth_check.php
 * ============================================================================
 */

require_once 'auth_check.php'; // يبدأ الجلسة ويحمّل الاتصال
restrictToAdmin(); // إذا لم يكن أدمن → تحويل إلى login.php

// قيم ابتدائية للإحصائيات
$countUsers = 0;
$countProducts = 0;
$countCategories = 0;

try {
    // fetchColumn() ترجع قيمة العمود الأول من أول صف (مناسب مع COUNT)
    $countUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $countProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $countCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
} catch (PDOException $e) {
    // لو فشل الاستعلام نترك الأرقام صفراً ونكمل عرض الصفحة
}

// هذا المتغير يستخدمه admin_sidebar.php لتظليل الرابط النشط
$admin_active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم الرئيسية 📊</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <style>
        .stat-card { border: none; border-radius: 12px; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once 'admin_sidebar.php'; // قائمة الإدارة المثبتة ?>

    <main class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <h2 class="fw-bold mb-0">مرحباً بكِ، <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'المسؤول'); ?> 👋</h2>
            <a href="index.php" class="btn btn-outline-dark">الموقع الرئيسي 🌐</a>
        </div>

        <!-- كروت الإحصائيات الديناميكية -->
        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="card stat-card bg-primary text-white shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center py-4">
                        <div>
                            <h5 class="card-title text-white-50">الأعضاء المسجلين</h5>
                            <h2 class="display-5 fw-bold"><?php echo (int)$countUsers; ?></h2>
                        </div>
                        <span style="font-size: 3.5rem; opacity: 0.8;">👥</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card stat-card bg-success text-white shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center py-4">
                        <div>
                            <h5 class="card-title text-white-50">المنتجات النشطة</h5>
                            <h2 class="display-5 fw-bold"><?php echo (int)$countProducts; ?></h2>
                        </div>
                        <span style="font-size: 3.5rem; opacity: 0.8;">🍰</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card stat-card bg-warning text-dark shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center py-4">
                        <div>
                            <h5 class="card-title text-dark text-opacity-50">الأقسام</h5>
                            <h2 class="display-5 fw-bold"><?php echo (int)$countCategories; ?></h2>
                        </div>
                        <span style="font-size: 3.5rem; opacity: 0.8;">📂</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم الوصول السريع للمهام الإدارية -->
        <div class="card mt-2 border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-3">⚡ لوحة التحكم السريع بالمهام البرمجية</h5>
            <p class="text-muted">يمكنك التحكم الفوري وعرض تفاصيل المشروع من خلال الأزرار التفاعلية التالية:</p>
            <div class="d-flex flex-wrap gap-3 mt-2">
                <a href="manage_products.php" class="btn btn-outline-success px-4 py-2">🍰 إضافة وتعديل المنتجات (CRUD)</a>
                <a href="manage_categories.php" class="btn btn-outline-warning px-4 py-2">📂 إدارة الأقسام</a>
                <a href="manage_users.php" class="btn btn-outline-primary px-4 py-2">👥 إدارة المستخدمين وصلاحياتهم</a>
                <a href="gallery.php" target="_blank" class="btn btn-outline-info px-4 py-2">🖨️ فتح المعرض ومعاينته للطباعة</a>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
