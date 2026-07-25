 
 
 //تنظيف المدخلات للحماية من ثغرات xss
 function  sanitize_input($data) {
    if (is_null($data)) return '' ;
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES,'utf-8');

}
// ادارة وتشفير كلمات المرور (جدول user)


تشفير كلمة المرور قبل حفظها في قاعدة البيانات//
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);

} 

//التحقق من كلمة المرور عند تسجيل الدخول 
function verify_password($password ,$hashed_password) {
    return password_verify($password, hashed_password);
}

//التحقق من صحة بيانات المستخدم (user validation)

//التحقق من البريد الالكتروني
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

//التحقق من اسم المستخدم
function validate_username($username) {
    //يجب ان يكون بين 3 الى 50 حرف ولا يحتوي رموز خاصة ضارة
    return preg_match('/^[a-zA-0-9_]{3,50}$/', $username);
}
    //التحقق من بيانات المنتجات والارقام(products &orders validation)
// التحقق من الاسعار
function validate_price($price) {
    return is_numeric($price) && $price >= 0;
}
 //التحقق من الكميات
 function validate_quantity($quantity) {
    return filter_var($quantity, FILTER_VALIDATE_INT) !== false && $quantity >=0;
 }  

 //التحقق من حالات الطلب 
 function validate_order_status($status){
    $allowed_statuses = ['pending', 'completed', 'cancelled'];
    return in_array($status, $allowed_statuses, true);
 }

