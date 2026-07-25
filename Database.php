<?php
/**
 * ============================================================================
 * Database.php — كلاس الاتصال بقاعدة البيانات
 * ============================================================================
 * ملاحظات:
 * - بيانات الاتصال مجمّعة في كلاس واحد بدل تكرارها في كل صفحة.
 * - PDO يدعم الاستعلامات المُحضَّرة (Prepared Statements) ويقلل خطر SQL Injection.
 * - charset=utf8mb4 مطلوب لدعم العربية والإيموجي بشكل صحيح.
 * ============================================================================
 */

class Database {
    // عنوان السيرفر المحلي (في XAMPP / التطوير المحلي غالباً localhost)
    private $host = "localhost";

    // اسم قاعدة البيانات المستخدمة في المشروع
    private $db_name = "ecommerce_db";

    // اسم مستخدم MySQL (الافتراضي في XAMPP هو root)
    private $username = "root";

    // كلمة مرور MySQL (الافتراضي في XAMPP فارغة)
    private $password = "";

    // متغير لحفظ كائن الاتصال PDO بعد إنشائه
    private $conn;

    /**
     * دالة الاتصال بقاعدة البيانات
     * تُرجع كائن PDO جاهزاً للاستخدام، أو null عند الفشل
     */
    public function connect() {
        // نبدأ دائماً بدون اتصال سابق
        $this->conn = null;

        try {
            // بناء جملة DSN: تحدد نوع القاعدة + الهوست + الاسم + الترميز
            // utf8mb4 = يدعم كل حروف اليونيكود تقريباً (عربي + إيموجي)
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );

            // عند حدوث خطأ SQL ارمه كاستثناء (Exception) بدل تجاهله بصمت
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // اجلب النتائج ككائنات (object) مثل: $row->name بدل $row['name']
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        } catch (PDOException $exception) {
            // عند فشل الاتصال نعرض رسالة الخطأ أثناء التطوير
            echo "خطأ في الاتصال بقاعدة البيانات: " . $exception->getMessage();
        }

        // نرجع كائن الاتصال ليستخدمه باقي المشروع
        return $this->conn;
    }
}
