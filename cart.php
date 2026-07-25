<?php
/**
 * ============================================================================
 * cart.php — سلة التسوق باستخدام الجلسة (Session Cart)
 * ============================================================================
 * ملاحظات:
 * - السلة تُحفظ مؤقتاً داخل $_SESSION وليس في قاعدة البيانات.
 * - كل عنصر في السلة يحتوي: id, name, price, image, qty.
 * - العمليات عبر الرابط:
 *   ?action=add&id=5     إضافة منتج
 *   ?action=remove&id=5  حذف منتج
 *   ?action=clear        تفريغ السلة
 * ============================================================================
 */

session_start(); // ضروري لقراءة/كتابة السلة

// الاتصال بقاعدة البيانات لجلب بيانات المنتج عند الإضافة
try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// إذا لم توجد سلة بعد، ننشئها كمصفوفة فارغة
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// قراءة العملية ورقم المنتج من الرابط (Query String)
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0; // intval يحول أي قيمة لرقم صحيح آمن

// =========================
// 1) إضافة منتج إلى السلة
// =========================
if ($action === 'add' && $id > 0) {
    // نجلب المنتج من قاعدة البيانات للتأكد أنه موجود
    $stmt = $db->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        if (isset($_SESSION['cart'][$id])) {
            // المنتج موجود مسبقاً → نزيد الكمية فقط
            $_SESSION['cart'][$id]['qty'] += 1;
        } else {
            // منتج جديد في السلة → نضيفه بكمية 1
            $_SESSION['cart'][$id] = [
                'id' => (int)$product->id,
                'name' => $product->name,
                'price' => (float)$product->price,
                'image' => $product->image,
                'qty' => 1,
            ];
        }
    }

    // بعد الإضافة نرجع للمتجر مع رسالة نجاح
    header('Location: index.php?cart_msg=added');
    exit();
}

// =========================
// 2) حذف منتج واحد من السلة
// =========================
if ($action === 'remove' && $id > 0) {
    unset($_SESSION['cart'][$id]); // حذف العنصر من المصفوفة
    header('Location: cart.php');
    exit();
}

// =========================
// 3) تفريغ السلة بالكامل
// =========================
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit();
}

// حساب الإجمالي = مجموع (السعر × الكمية) لكل العناصر
$total = 0.0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['qty'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سلة التسوق</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { font-family: 'Tajawal', sans-serif; background: #fdfaf6; }</style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🧁 متجر شادية</a>
        <a href="index.php" class="btn btn-outline-primary btn-sm">متابعة التسوق</a>
    </div>
</nav>
<div class="container py-4">
    <h2 class="fw-bold mb-4">🛒 سلة التسوق</h2>

    <!-- إذا كانت السلة فارغة نعرض تنبيهاً -->
    <?php if (count($_SESSION['cart']) === 0): ?>
        <div class="alert alert-warning text-center">السلة فارغة حالياً.</div>
    <?php else: ?>
        <!-- جدول محتويات السلة -->
        <div class="table-responsive bg-white rounded shadow-sm p-3">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>السعر</th>
                        <th>الكمية</th>
                        <th>الإجمالي</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <!-- htmlspecialchars تمنع XSS عند عرض النصوص -->
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo (int)$item['qty']; ?></td>
                            <td>$<?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-danger"
                                   href="cart.php?action=remove&id=<?php echo (int)$item['id']; ?>">
                                    حذف
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <h4 class="mb-0">الإجمالي: $<?php echo number_format($total, 2); ?></h4>
            <div class="d-flex gap-2">
                <a href="cart.php?action=clear" class="btn btn-outline-secondary">تفريغ السلة</a>
                <!-- زر إتمام الطلب موجود شكلياً ولم يُربط بعد بجدول orders -->
                <button class="btn btn-success" disabled title="Checkout not implemented yet">إتمام الطلب</button>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
