<?php
/**
 * ============================================================================
 * edit_product.php — تعديل منتج موجود (عملية Update)
 * ============================================================================
 * مسار العمل:
 * 1) استقبال رقم المنتج من الرابط: edit_product.php?id=5
 * 2) جلب بيانات المنتج وملء النموذج.
 * 3) عند الحفظ (POST) تحديث الصف في جدول products.
 * 4) إذا رُفعت صورة جديدة تُستبدل القديمة ويُحذف الملف القديم.
 * 5) بعد النجاح العودة إلى manage_products.php?success=1
 * ============================================================================
 */

session_start();

// حماية: للأدمن فقط
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال: " . $e->getMessage());
}

$error = "";
$success = "";
$upload_dir = "uploads/";

// بدون id في الرابط لا نعرف أي منتج نعدّل
if (!isset($_GET['id'])) {
    header("Location: manage_products.php");
    exit();
}

$product_id = intval($_GET['id']); // تحويل آمن لرقم صحيح

// جلب بيانات المنتج لعرضها داخل حقول النموذج
$stmt_get = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt_get->execute([$product_id]);
$product = $stmt_get->fetch(PDO::FETCH_OBJ);

// منتج غير موجود → ارجع للقائمة
if (!$product) {
    header("Location: manage_products.php");
    exit();
}

// الأقسام للقائمة المنسدلة
$stmt_cats = $db->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt_cats->fetchAll(PDO::FETCH_OBJ);

// =========================
// حفظ التعديلات (Update)
// =========================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);

    // الصورة الحالية المحفوظة في حقل مخفي داخل النموذج
    $existing_image = $_POST['existing_image'];
    $image_name = $existing_image; // افتراضياً نحتفظ بنفس الصورة

    // إذا رفع المستخدم صورة جديدة نستبدل القديمة
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = time() . "_" . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp, $target_file)) {
            $image_name = $target_file;
            // حذف الصورة القديمة من القرص لتوفير المساحة
            if (!empty($existing_image) && file_exists($existing_image)) {
                unlink($existing_image);
            }
        }
    }

    // تحقق من الحقول الإجبارية
    if (empty($name) || empty($price) || empty($category_id)) {
        $error = "حقول الاسم، السعر، والقسم هي حقول إجبارية للتعديل.";
    } else {
        $stmt_update = $db->prepare(
            "UPDATE products
             SET name = ?, price = ?, description = ?, category_id = ?, image = ?
             WHERE id = ?"
        );
        if ($stmt_update->execute([$name, $price, $description, $category_id, $image_name, $product_id])) {
            header("Location: manage_products.php?success=1");
            exit();
        } else {
            $error = "فشل تعديل بيانات المنتج.";
        }
    }

    // لو فشل الحفظ نعيد جلب المنتج لعرض القيم الحالية في النموذج
    $stmt_get->execute([$product_id]);
    $product = $stmt_get->fetch(PDO::FETCH_OBJ);
}

// لتظليل رابط المنتجات أثناء التعديل
$admin_active = 'products';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل المنتج</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <style>
        .edit-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 2px solid #ffc107; max-width: 700px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea,
        .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-update { background-color: #ffc107; color: black; border: none; padding: 12px 20px; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold; width: 100%; }
        .btn-update:hover { background-color: #e0a800; }
    </style>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once 'admin_sidebar.php'; // قائمة الإدارة المثبتة ?>

    <main class="admin-content">
        <a href="manage_products.php" class="btn btn-secondary mb-3">⬅ إلغاء والعودة لإدارة المنتجات</a>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- نموذج تعديل بيانات المنتج -->
        <div class="edit-card">
            <h2 class="h4 mb-3">تعديل المنتج: <?php echo htmlspecialchars($product->name); ?> ✏️</h2>
            <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($product->image ?? ''); ?>">

                <div class="form-group">
                    <label>اسم المنتج:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product->name); ?>" required>
                </div>

                <div class="form-group">
                    <label>تعديل القسم (التصنيف):</label>
                    <select name="category_id" required>
                        <option value="">-- اختر القسم --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>" <?php echo ($cat->id == $product->category_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>السعر ($):</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product->price); ?>" required>
                </div>

                <div class="form-group">
                    <label>وصف المنتج:</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($product->description ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>صورة المنتج (اتركها فارغة للاحتفاظ بالصورة الحالية):</label>
                    <input type="file" name="image" accept="image/*">
                    <?php if (!empty($product->image)): ?>
                        <p class="small text-muted mt-2 mb-0">
                            الصورة الحالية:
                            <img src="<?php echo htmlspecialchars($product->image); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; vertical-align: middle; border-radius: 4px;">
                        </p>
                    <?php endif; ?>
                </div>

                <button type="submit" name="update_product" class="btn-update">حفظ وتحديث التغييرات</button>
            </form>
        </div>
    </main>
</div>

</body>
</html>
