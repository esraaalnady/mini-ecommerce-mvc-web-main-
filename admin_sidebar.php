<?php
/**
 * ============================================================================
 * admin_sidebar.php — القائمة الجانبية المشتركة للوحة الإدارة
 * ============================================================================
 * ملاحظات:
 * - ملف مشترك حتى لا تتكرر قائمة الروابط في كل صفحة إدارة.
 * - أي تعديل على القائمة يظهر مباشرة في كل الصفحات.
 *
 * كيف يُحدَّد الرابط النشط؟
 * - الصفحة ترسل $admin_active قبل استدعاء هذا الملف
 *   مثل: $admin_active = 'products';
 * - أو يُعتمد على اسم الملف الحالي عبر basename($_SERVER['PHP_SELF'])
 *
 * ملاحظة تصميم:
 * القائمة مثبتة (sticky) عبر CSS في ملف admin.css
 * ============================================================================
 */

// إذا لم تحدد الصفحة قيمة $admin_active نستخدم نصاً فارغاً
$admin_active = $admin_active ?? '';

// اسم ملف الصفحة الحالية مثل: manage_products.php
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!-- الشريط الجانبي الثابت للإدارة -->
<aside class="admin-sidebar">
    <div class="admin-sidebar-inner">
        <h4 class="admin-brand">لوحة الإدارة</h4>

        <nav class="admin-nav">
            <!-- الرابط الرئيسي للوحة التحكم -->
            <a href="admin_dashboard.php"
               class="<?php echo ($admin_active === 'dashboard' || $current_page === 'admin_dashboard.php') ? 'active' : ''; ?>">
                📊 الرئيسية
            </a>

            <!-- إدارة المنتجات (Create/Read/Update/Delete) -->
            <a href="manage_products.php"
               class="<?php echo ($admin_active === 'products' || $current_page === 'manage_products.php' || $current_page === 'edit_product.php') ? 'active' : ''; ?>">
                🍰 إدارة المنتجات
            </a>

            <!-- إدارة الأقسام/التصنيفات -->
            <a href="manage_categories.php"
               class="<?php echo ($admin_active === 'categories' || $current_page === 'manage_categories.php') ? 'active' : ''; ?>">
                📂 إدارة الأقسام
            </a>

            <!-- إدارة الحسابات والصلاحيات -->
            <a href="manage_users.php"
               class="<?php echo ($admin_active === 'users' || $current_page === 'manage_users.php') ? 'active' : ''; ?>">
                👥 إدارة المستخدمين
            </a>

            <!-- فتح المعرض في تبويب جديد للمعاينة/الطباعة -->
            <a href="gallery.php" target="_blank" class="admin-link-accent">
                📸 معرض الصور والطباعة 🖨️
            </a>

            <hr class="admin-divider">

            <!-- العودة للمتجر العام -->
            <a href="index.php">🌐 الموقع الرئيسي</a>

            <!-- إنهاء جلسة المسؤول -->
            <a href="logout.php" class="admin-link-danger">🚪 تسجيل الخروج</a>
        </nav>
    </div>
</aside>
