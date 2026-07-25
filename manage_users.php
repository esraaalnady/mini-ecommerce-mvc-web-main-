<?php
/**
 * ============================================================================
 * manage_users.php — إدارة المستخدمين (عرض + حذف مع حماية)
 * ============================================================================
 * ملاحظات:
 * 1) حماية الصفحة: فقط admin يدخل هنا.
 * 2) الحذف يتم عبر ?delete_id=... مع تحقق من السيرفر.
 * 3) ممنوع حذف:
 *    - الحساب الحالي المسجّل دخوله
 *    - المسؤول الوحيد المتبقي في النظام
 * ============================================================================
 */

session_start();

// حماية الصفحة: يُسمح للمسؤول فقط
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// الاتصال بقاعدة البيانات
try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// معرف المستخدم الحالي من الجلسة (للمقارنة عند الحذف)
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// كم عدد حسابات الأدمن حالياً؟
$admin_count = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

/**
 * هل يُسمح بحذف هذا المستخدم؟
 * ترجع true إذا كان الحذف آمناً، و false إذا كان ممنوعاً
 */
function can_delete_user(object $user, int $current_user_id, int $admin_count): bool {
    // 1) لا تحذف حسابك وأنتِ داخله (حتى لا تخرجي من النظام بالخطأ)
    if ((int)$user->id === $current_user_id) {
        return false;
    }

    // 2) لا تحذف آخر أدمن (وإلا لن يستطيع أحد دخول لوحة التحكم)
    if (($user->role ?? '') === 'admin' && $admin_count <= 1) {
        return false;
    }

    return true;
}

// =========================
// تنفيذ الحذف عند وجود delete_id
// =========================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']); // تحويل آمن إلى رقم

    // نجلب المستخدم المستهدف أولاً قبل الحذف
    $stmt_target = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt_target->execute([':id' => $delete_id]);
    $target = $stmt_target->fetch();

    if (!$target) {
        $error = "المستخدم المراد حذفه غير موجود.";
    } elseif (!can_delete_user($target, $current_user_id, $admin_count)) {
        // رسالة واضحة حسب سبب المنع
        if ((int)$target->id === $current_user_id) {
            $error = "لا يمكنك حذف حسابك الحالي أثناء تسجيل الدخول.";
        } else {
            $error = "لا يمكن حذف المسؤول الوحيد في النظام.";
        }
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $delete_id]);
            // بعد النجاح نعيد تحميل الصفحة برسالة نجاح
            header("Location: manage_users.php?msg=deleted");
            exit();
        } catch (PDOException $e) {
            // قد يفشل الحذف إذا كان هناك قيد أجنبي (طلبات مرتبطة)
            $error = "لا يمكن حذف هذا المستخدم؛ ربما لديه طلبات مرتبطة في النظام.";
        }
    }
}

// جلب كل المستخدمين لعرضهم في الجدول
$stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();

// لتظليل رابط "المستخدمين" في القائمة الجانبية
$admin_active = 'users';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين والأعضاء 👥</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once 'admin_sidebar.php'; // قائمة الإدارة المثبتة ?>

    <main class="admin-content">
        <h2 class="fw-bold text-dark mb-4">👥 إدارة حسابات المستخدمين والزبائن</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                تم حذف حساب المستخدم بنجاح!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- جدول المستخدمين مع فرز وتصفية -->
        <div class="table-responsive shadow-sm rounded p-3">
            <div class="admin-table-toolbar">
                <div>
                    <label for="usersFilter">تصفية:</label>
                    <input type="search" id="usersFilter" placeholder="ابحث بالاسم أو البريد أو الصلاحية..." autocomplete="off">
                </div>
                <small class="text-muted">انقر على رأس العمود للفرز ▲▼</small>
            </div>

            <table id="usersTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th>المعرف (ID)</th>
                        <th>اسم المستخدم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الصلاحية</th>
                        <th data-no-sort>خيارات التحكم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php $allowed_delete = can_delete_user($user, $current_user_id, $admin_count); ?>
                        <tr>
                            <td class="fw-bold" data-order="<?php echo (int)$user->id; ?>">#<?php echo (int)$user->id; ?></td>
                            <!-- يستخدم حقل username لأن جدول users لا يحتوي على name -->
                            <td>
                                <?php echo htmlspecialchars($user->username ?? ''); ?>
                                <?php if ((int)$user->id === $current_user_id): ?>
                                    <span class="badge bg-info text-dark">أنتِ</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user->email ?? ''); ?></td>
                            <td data-order="<?php echo htmlspecialchars($user->role ?? ''); ?>">
                                <?php if ($user->role === 'admin'): ?>
                                    <span class="badge bg-danger">مسؤول (Admin)</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">زبون (User)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($allowed_delete): ?>
                                    <a href="manage_users.php?delete_id=<?php echo (int)$user->id; ?>"
                                       class="btn btn-sm btn-danger px-3 rounded-pill"
                                       onclick="return confirm('هل أنتِ متأكدة تماماً من رغبتك في حذف هذا الحساب نهائياً؟');">
                                        حذف الحساب 🗑️
                                    </a>
                                <?php else: ?>
                                    <?php if ((int)$user->id === $current_user_id): ?>
                                        <span class="text-muted small">لا يمكن حذف الحساب الحالي</span>
                                    <?php else: ?>
                                        <span class="text-muted small">لا يمكن حذف المسؤول الوحيد</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin-tables.js"></script>
</body>
</html>
