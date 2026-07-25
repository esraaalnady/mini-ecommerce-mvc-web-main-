<?php
/**
 * ============================================================================
 * manage_products.php — إدارة المنتجات (CRUD جزئي)
 * ============================================================================
 * العمليات في هذه الصفحة:
 * - Create: إضافة منتج جديد (+ رفع صورة اختيارياً)
 * - Read  : عرض كل المنتجات في جدول
 * - Delete: حذف منتج وصورته من السيرفر
 * - Update: يتم في صفحة منفصلة edit_product.php
 *
 * ملاحظات عن رفع الملفات:
 * الملف يُرفع أولاً إلى مجلد uploads/
 * ثم يُحفظ مسار الصورة داخل عمود image في جدول products
 * ============================================================================
 */

session_start();

// حماية: للأدمن فقط
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// الاتصال بقاعدة البيانات (utf8mb4 لدعم العربية)
try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال: " . $e->getMessage());
}

$error = "";
$success = "";

// رسالة نجاح عند الرجوع من صفحة التعديل: manage_products.php?success=1
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "تم تحديث بيانات المنتج بنجاح!";
}

// مجلد حفظ الصور المرفوعة
$upload_dir = "uploads/";

// جلب الأقسام لتعبئة القائمة المنسدلة في نموذج الإضافة
$stmt_cats = $db->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt_cats->fetchAll(PDO::FETCH_OBJ);

// =========================
// Create: إضافة منتج جديد
// =========================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    // قراءة حقول النموذج
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $image_name = ""; // مسار الصورة النهائي (أو فارغ)

    // رفع الصورة إن أرسل المستخدم ملفاً صالحاً
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file_tmp = $_FILES['image']['tmp_name']; // المسار المؤقت للملف
        // نضيف الوقت لاسم الملف لتفادي تكرار الأسماء
        $file_name = time() . "_" . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;

        // نقل الملف من المجلد المؤقت إلى uploads/
        if (move_uploaded_file($file_tmp, $target_file)) {
            $image_name = $target_file; // نحفظ المسار في قاعدة البيانات
        }
    }

    // التحقق من الحقول الإجبارية
    if (empty($name) || empty($price) || empty($category_id)) {
        $error = "اسم المنتج، السعر، واختيار القسم هي حقول إجبارية.";
    } else {
        // إدخال المنتج باستعلام مُحضَّر
        $stmt = $db->prepare(
            "INSERT INTO products (name, price, description, category_id, image)
             VALUES (?, ?, ?, ?, ?)"
        );
        if ($stmt->execute([$name, $price, $description, $category_id, $image_name])) {
            $success = "تم إضافة المنتج بنجاح!";
        } else {
            $error = "حدث خطأ أثناء إضافة المنتج.";
        }
    }
}

// =========================
// Delete: حذف منتج
// =========================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // أولاً: نجلب مسار الصورة لحذف الملف من القرص
    $stmt_img = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt_img->execute([$id]);
    $prod = $stmt_img->fetch(PDO::FETCH_OBJ);
    if ($prod && !empty($prod->image) && file_exists($prod->image)) {
        unlink($prod->image); // حذف ملف الصورة من السيرفر
    }

    // ثانياً: حذف صف المنتج من قاعدة البيانات
    $stmt_del = $db->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt_del->execute([$id])) {
        $success = "تم حذف المنتج بنجاح!";
    } else {
        $error = "فشل حذف المنتج.";
    }
}

// =========================
// Read: عرض المنتجات
// =========================
$stmt_products = $db->query(
    "SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY p.id DESC"
);
$products = $stmt_products->fetchAll(PDO::FETCH_OBJ);

// لتظليل رابط المنتجات في القائمة الجانبية
$admin_active = 'products';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <style>
        .admin-panel-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea,
        .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-add { background-color: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn-add:hover { background-color: #218838; color: white; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .edit-btn { background-color: #ffc107; color: black; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; margin-left: 5px; font-weight: bold; display: inline-block; }
        .delete-btn { background-color: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-block; }
    </style>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once 'admin_sidebar.php'; // قائمة الإدارة المثبتة ?>

    <main class="admin-content">
        <h2 class="fw-bold mb-4">🍰 إدارة المنتجات</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- نموذج إضافة منتج جديد -->
        <div class="admin-panel-card">
            <h3 class="h4 mb-3">إضافة منتج جديد للمتجر 🛒</h3>
            <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>اسم المنتج:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>اختر القسم المصنف (التصنيف):</label>
                    <select name="category_id" required>
                        <option value="">-- اختر القسم المناسب للمنتج --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>"><?php echo htmlspecialchars($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>سعر المنتج ($):</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group">
                    <label>وصف المنتج:</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>صورة المنتج:</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <button type="submit" name="add_product" class="btn-add">حفظ وإضافة المنتج</button>
            </form>
        </div>

        <!-- جدول المنتجات الحالية مع فرز وتصفية -->
        <div class="admin-panel-card">
            <h3 class="h4 mb-3">المنتجات المعروضة حالياً وإدارتها</h3>

            <!-- شريط البحث/التصفية -->
            <div class="admin-table-toolbar">
                <div>
                    <label for="productsFilter">تصفية:</label>
                    <input type="search" id="productsFilter" placeholder="ابحث بالاسم أو القسم أو السعر..." autocomplete="off">
                </div>
                <small class="text-muted">انقر على رأس العمود للفرز ▲▼</small>
            </div>

            <div class="table-responsive">
                <table id="productsTable" class="table table-bordered align-middle bg-white mb-0 w-100">
                    <thead class="table-light">
                        <tr>
                            <th data-no-sort>الصورة</th>
                            <th>اسم المنتج</th>
                            <th>القسم</th>
                            <th>السعر</th>
                            <th>الوصف</th>
                            <th data-no-sort>التحكم بالمنتج</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php
                                    // عرض الصورة أو نص بديل إذا لم تُرفع صورة
                                    $img = (!empty($p->image) && file_exists($p->image)) ? $p->image : 'uploads/default.svg';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($img); ?>" class="product-img" alt="">
                                </td>
                                <td><?php echo htmlspecialchars($p->name); ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($p->category_name ?? 'غير مصنف'); ?></span></td>
                                <td data-order="<?php echo htmlspecialchars($p->price); ?>">$<?php echo htmlspecialchars($p->price); ?></td>
                                <td><?php echo htmlspecialchars($p->description); ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $p->id; ?>" class="edit-btn">تعديل ✏️</a>
                                    <a href="manage_products.php?delete=<?php echo $p->id; ?>" class="delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">حذف 🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="admin-tables.js"></script>
</body>
</html>
