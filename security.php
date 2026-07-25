<?php
/**
 * ============================================================================
 * security.php — دوال الأمان والتحقق من صحة البيانات
 * ============================================================================
 * ملاحظات:
 * 1) تنظيف المدخلات لمنع XSS (حقن سكربتات في الصفحة).
 * 2) تشفير كلمات المرور وعدم حفظها كنص واضح.
 * 3) التحقق من شكل البيانات قبل إدخالها لقاعدة البيانات.
 * ============================================================================
 */

/**
 * تنظيف النص قبل عرضه في HTML
 * - trim: حذف الفراغات الزائدة من البداية والنهاية
 * - stripslashes: إزالة الشرطات المائلة الزائدة إن وُجدت
 * - htmlspecialchars: تحويل رموز HTML الخطرة إلى نص آمن
 */
function sanitize_input($data) {
    if (is_null($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'utf-8');
}

/**
 * تشفير كلمة المرور قبل حفظها في قاعدة البيانات
 * PASSWORD_DEFAULT يختار خوارزمية قوية مدعومة من PHP
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * التحقق من كلمة المرور عند تسجيل الدخول
 * تقارن النص الذي أدخله المستخدم مع الهاش المخزَّن
 */
function verify_password($password, $hashed_password) {
    return password_verify($password, $hashed_password);
}

/**
 * التحقق من صحة البريد الإلكتروني
 * تُرجع البريد إن كان صالحاً، أو false إن كان غير صالح
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * التحقق من اسم المستخدم:
 * حروف إنجليزية/أرقام/شرطة سفلية فقط، وطول بين 3 و 50
 */
function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

/**
 * التحقق من السعر: يجب أن يكون رقماً وغير سالب
 */
function validate_price($price) {
    return is_numeric($price) && $price >= 0;
}

/**
 * التحقق من الكمية: يجب أن تكون عدداً صحيحاً >= 0
 */
function validate_quantity($quantity) {
    return filter_var($quantity, FILTER_VALIDATE_INT) !== false && $quantity >= 0;
}

/**
 * التحقق من حالة الطلب:
 * نسمح فقط بالقيم المعرفة مسبقاً (White List) لمنع قيم غريبة
 */
function validate_order_status($status) {
    $allowed_statuses = ['pending', 'completed', 'cancelled'];
    // المعامل true يجعل المقارنة صارمة (نوع + قيمة)
    return in_array($status, $allowed_statuses, true);
}
