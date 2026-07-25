<?php
/**
 * ============================================================================
 * login.php — صفحة تسجيل الدخول
 * ============================================================================
 * مسار العمل:
 * 1) إذا كان المستخدم مسجلاً مسبقاً يتم تحويله مباشرة حسب صلاحيته.
 * 2) عند إرسال النموذج (POST) تُقرأ البيانات ويُتحقق منها.
 * 3) البحث عن المستخدم في قاعدة البيانات باستعلام مُحضَّر.
 * 4) مقارنة كلمة المرور ثم تخزين البيانات في الجلسة.
 * 5) توجيه الأدمن إلى لوحة التحكم، والمستخدم العادي إلى المتجر.
 * ============================================================================
 */

// بدء الجلسة لحفظ بيانات المستخدم بعد نجاح الدخول
session_start();

// إذا كان مسجلاً بالفعل لا نعرض له نموذج الدخول مرة أخرى
if (isset($_SESSION['user_name'])) {
    // الأدمن يذهب للوحة الإدارة
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        // المستخدم العادي يذهب للمتجر
        header("Location: index.php");
    }
    exit(); // إيقاف باقي الصفحة بعد التحويل
}

// محاولة الاتصال بقاعدة البيانات
try {
    // charset=utf8mb4 مهم للنصوص العربية
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    // أظهر أخطاء SQL كاستثناءات
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // اجلب الصفوف ككائنات
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    // نحفظ رسالة الخطأ لعرضها في الواجهة بدل إيقاف الصفحة فوراً
    $error_msg = "فشل الاتصال بقاعدة البيانات: " . $e->getMessage();
}

// معالجة النموذج فقط عند الإرسال بطريقة POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // trim يحذف الفراغات من بداية ونهاية النص
    $username_or_email = trim($_POST['username_or_email']);
    $password = trim($_POST['password']);

    // تحقق بسيط: لا نكمل إذا كان أحد الحقول فارغاً
    if (empty($username_or_email) || empty($password)) {
        $error_msg = "الرجاء تعبئة جميع الحقول المطلوبة.";
    } else {
        try {
            /**
             * استعلام مُحضَّر (Prepared Statement):
             * نبحث عن المستخدم باسم المستخدم أو البريد الإلكتروني.
             * استخدام :user يحمي من حقن SQL.
             */
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :user OR email = :user LIMIT 1");
            $stmt->execute([':user' => $username_or_email]);
            $user = $stmt->fetch(); // صف واحد أو false

            if ($user) {
                // قراءة الحقول من كائن المستخدم مع قيم افتراضية احتياطية
                $db_password = isset($user->password) ? $user->password : (isset($user->pass) ? $user->pass : null);
                $db_role = isset($user->role) ? $user->role : 'user';
                $db_name = isset($user->username) ? $user->username : 'مستخدم';

                /**
                 * التحقق من كلمة المرور:
                 * - password_verify: للطريقة الصحيحة (كلمة مرور مشفّرة)
                 * - المقارنة المباشرة: دعم قديم للبيانات غير المشفّرة (يفضّل الاعتماد على التشفير فقط)
                 */
                if ($db_password && ($password === $db_password || password_verify($password, $db_password))) {
                    // نجاح الدخول: نخزّن بيانات مهمة في الجلسة
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_name'] = $db_name;
                    $_SESSION['user_role'] = $db_role;

                    // التوجيه حسب الصلاحية
                    if ($db_role === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error_msg = "كلمة المرور غير صحيحة.";
                }
            } else {
                // لم يتم العثور على المستخدم
                $error_msg = "اسم المستخدم أو البريد الإلكتروني غير مسجل.";
            }
        } catch (PDOException $e) {
            $error_msg = "خطأ في الاستعلام: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول 🧁</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* تنسيق صفحة الدخول بشكل بطاقة في منتصف الشاشة */
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: white;
            max-width: 450px;
            width: 100%;
            padding: 40px 30px;
        }
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

<div class="login-card">
    <h3 class="text-center fw-bold text-dark mb-4">🔑 تسجيل الدخول</h3>

    <!-- عرض رسالة الخطأ إن وُجدت -->
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger text-center py-2 fs-6" role="alert">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <!-- نموذج الدخول: يُرسل لنفس الصفحة بطريقة POST -->
    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="username" class="form-label fw-bold text-secondary">اسم المستخدم أو البريد الإلكتروني:</label>
            <input type="text" name="username_or_email" id="username" class="form-control py-2" placeholder="أدخل اسم المستخدم" required>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label fw-bold text-secondary">كلمة المرور:</label>
            <input type="password" name="password" id="password" class="form-control py-2" placeholder="أدخل كلمة المرور" required>
        </div>

        <button type="submit" class="btn btn-gradient btn-lg w-100 rounded-pill mb-3">دخول 🚪</button>
    </form>

    <div class="text-center">
        <p class="text-muted small mb-0">ليس لديكِ حساب؟ <a href="register.php" class="text-primary fw-bold text-decoration-none">سجلي حساباً جديداً الآن</a></p>
        <hr class="my-3 text-muted">
        <a href="index.php" class="text-secondary small text-decoration-none">⬅ العودة للمتجر الرئيسي</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
