<?php
/**
 * ============================================================================
 * register.php — إنشاء حساب مستخدم جديد
 * ============================================================================
 * مسار العمل:
 * 1) استقبال بيانات النموذج (اسم مستخدم + بريد + كلمة مرور).
 * 2) التحقق أن الحقول غير فارغة.
 * 3) التأكد أن الاسم/البريد غير مستخدمين من قبل.
 * 4) إدخال المستخدم الجديد بدور user (وليس admin).
 *
 * ملاحظة:
 * الأفضل تشفير كلمة المرور بـ password_hash قبل الحفظ.
 * حالياً المشروع يحفظها كما هي ويمكن تحسين ذلك لاحقاً.
 * ============================================================================
 */

session_start(); // الجلسة جاهزة لو أردنا تسجيل الدخول تلقائياً لاحقاً

// الاتصال بقاعدة البيانات
try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // بدون قاعدة بيانات لا يمكن إنشاء حساب
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// متغيرات لرسائل الواجهة
$error = "";
$success = "";

// معالجة النموذج عند الإرسال فقط
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // قراءة القيم من النموذج وتنظيف الفراغات
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // التحقق من تعبئة كل الحقول
    if (empty($username) || empty($email) || empty($password)) {
        $error = "الرجاء تعبئة جميع الحقول المطلوبة.";
    } else {
        /**
         * هل الحساب موجود مسبقاً؟
         * نبحث بنفس اسم المستخدم أو نفس البريد
         */
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user) {
            // لا نسمح بتكرار البيانات الفريدة
            $error = "اسم المستخدم أو البريد الإلكتروني مسجل بالفعل.";
        } else {
            /**
             * إدخال المستخدم الجديد
             * role = 'user' بشكل ثابت حتى لا يستطيع الزائر إنشاء حساب أدمن
             */
            $stmt_insert = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmt_insert->execute([$username, $email, $password])) {
                $success = "تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.";
            } else {
                $error = "حدث خطأ أثناء التسجيل، يرجى المحاولة لاحقاً.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنشاء حساب جديد</title>
    <style>
        /* بطاقة تسجيل بسيطة في منتصف الصفحة */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .register-container { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 24px; color: #333; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; color: #666; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .error-msg { color: red; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .success-msg { color: green; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .login-link { text-align: center; margin-top: 15px; }
        .login-link a { color: #007bff; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="register-container">
    <h2>إنشاء حساب جديد</h2>

    <!-- رسائل الخطأ والنجاح -->
    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success-msg"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- نموذج التسجيل -->
    <form action="register.php" method="POST">
        <div class="form-group">
            <label>اسم المستخدم:</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>البريد الإلكتروني:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>كلمة المرور:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">تسجيل الحساب</button>
    </form>

    <div class="login-link">
        <a href="login.php">لديك حساب بالفعل؟ سجل دخولك الآن</a>
    </div>
</div>

</body>
</html>
