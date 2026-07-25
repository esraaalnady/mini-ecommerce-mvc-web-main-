<?php
/**
 * ============================================================================
 * manage_categories.php — إدارة الأقسام/التصنيفات + اختيار الإيموجي
 * ============================================================================
 * ملاحظات:
 * - CRUD كامل للأقسام في صفحة واحدة (إضافة/تعديل/حذف/عرض).
 * - إضافة عمود emoji تلقائياً إن لم يكن موجوداً (ALTER TABLE).
 * - تنظيف الإيموجي ليكون إيموجي واحد فقط (grapheme).
 * - عدد المنتجات في كل قسم عبر COUNT + GROUP BY.
 *
 * تحذير:
 * حذف قسم يحذف منتجاته أيضاً بسبب ON DELETE CASCADE في قاعدة البيانات.
 * ============================================================================
 */

session_start();

// حماية الصفحة: للمسؤولين فقط
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// الاتصال مع utf8mb4 لدعم العربية والإيموجي
try {
    $db = new PDO("mysql:host=localhost;dbname=ecommerce_db;charset=utf8mb4", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

/**
 * إذا كان عمود emoji غير موجود يُضاف مرة واحدة.
 * لو كان موجوداً سيظهر استثناء ويتم تجاهله.
 */
try {
    $db->exec("ALTER TABLE categories ADD COLUMN emoji VARCHAR(16) NULL DEFAULT '📂' AFTER name");
} catch (PDOException $e) {
    // العمود موجود مسبقاً — لا مشكلة
}

// قائمة إيموجي جاهزة للاختيار السريع في الواجهة
$emoji_options = [
    '📂', '🧁', '🍰', '🎂', '🍪', '🍩', '🍫', '🍬', '🍭', '🍮',
    '🥤', '🧋', '🧃', '🍹', '🍸', '☕', '🫖', '🍵', '❄️', '🔥',
    '🍓', '🍒', '🍇', '🍋', '🧡', '💛', '💚', '💙', '💜', '⭐',
];

$error = "";
$success = "";
$edit_category = null; // إذا كنا في وضع التعديل نخزن هنا بيانات القسم

// جلب قسم للتعديل عند وجود ?edit=ID
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt_edit = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt_edit->execute([$edit_id]);
    $edit_category = $stmt_edit->fetch();
    if (!$edit_category) {
        $error = "القسم المطلوب تعديله غير موجود.";
    }
}

/**
 * تنظيف قيمة الإيموجي القادمة من النموذج
 * يسمح بحرف/إيموجي واحد فقط (وحدة grapheme واحدة)
 */
function sanitize_category_emoji(?string $emoji, array $allowed): string {
    $emoji = trim((string)$emoji);
    if ($emoji === '') {
        return '📂';
    }

    // أخذ أول وحدة عرض فقط (إيموجي واحد حتى لو كان مكوّناً من عدة code points)
    if (function_exists('grapheme_substr')) {
        $emoji = grapheme_substr($emoji, 0, 1);
    } else {
        $emoji = mb_substr($emoji, 0, 1, 'UTF-8');
    }

    if ($emoji === '' || $emoji === false) {
        return '📂';
    }

    return $emoji;
}

// ===== إضافة قسم جديد =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $emoji = sanitize_category_emoji($_POST['emoji'] ?? '📂', $emoji_options);

    if ($name === '') {
        $error = "اسم القسم حقل إجباري.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name, emoji, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $emoji, $description]);
            $success = "تم إضافة القسم بنجاح!";
        } catch (PDOException $e) {
            // الاسم فريد في قاعدة البيانات
            $error = "تعذر إضافة القسم. ربما الاسم مستخدم مسبقاً.";
        }
    }
}

// ===== تحديث قسم موجود =====
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_category'])) {
    $id = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $emoji = sanitize_category_emoji($_POST['emoji'] ?? '📂', $emoji_options);

    if ($id <= 0 || $name === '') {
        $error = "اسم القسم حقل إجباري.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE categories SET name = ?, emoji = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $emoji, $description, $id]);
            header("Location: manage_categories.php?success=updated");
            exit();
        } catch (PDOException $e) {
            $error = "تعذر تحديث القسم. ربما الاسم مستخدم مسبقاً.";
            // إعادة تحميل بيانات القسم للنموذج
            $stmt_edit = $db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt_edit->execute([$id]);
            $edit_category = $stmt_edit->fetch();
        }
    }
}

// ===== حذف قسم =====
// ملاحظة: حذف القسم يحذف المنتجات المرتبطة به بسبب ON DELETE CASCADE
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: manage_categories.php?success=deleted");
        exit();
    } catch (PDOException $e) {
        $error = "فشل حذف القسم.";
    }
}

// رسائل النجاح القادمة من إعادة التوجيه
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'updated') {
        $success = "تم تحديث القسم بنجاح!";
    } elseif ($_GET['success'] === 'deleted') {
        $success = "تم حذف القسم بنجاح!";
    }
}

// جلب الأقسام مع عدد المنتجات التابعة لكل قسم
$categories = $db->query(
    "SELECT c.*, COUNT(p.id) AS products_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.id DESC"
)->fetchAll();

// تمييز رابط الأقسام في القائمة الجانبية
$admin_active = 'categories';

/**
 * عرض مكوّن اختيار الإيموجي داخل النموذج
 */
function render_emoji_selector(array $emoji_options, string $selected = '📂'): void {
    $selected = $selected !== '' ? $selected : '📂';
    ?>
    <div class="form-group">
        <label>إيموجي القسم:</label>
        <div class="emoji-selector">
            <div class="emoji-preview" id="emojiPreview"><?php echo htmlspecialchars($selected); ?></div>
            <input type="hidden" name="emoji" id="emojiInput" value="<?php echo htmlspecialchars($selected); ?>">
            <input type="text" id="emojiCustom" class="emoji-custom"
                   placeholder="إيموجي واحد"
                   value="<?php echo htmlspecialchars($selected); ?>"
                   inputmode="text"
                   autocomplete="off">
            <div class="emoji-grid" id="emojiGrid" role="listbox" aria-label="اختيار إيموجي">
                <?php foreach ($emoji_options as $option): ?>
                    <button type="button"
                            class="emoji-option <?php echo $option === $selected ? 'active' : ''; ?>"
                            data-emoji="<?php echo htmlspecialchars($option); ?>"
                            aria-label="اختيار <?php echo htmlspecialchars($option); ?>">
                        <?php echo htmlspecialchars($option); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <small class="text-muted d-block mt-2">يمكن اختيار إيموجي واحد فقط من الشبكة أو باللصق.</small>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأقسام 📂</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <style>
        .admin-panel-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-save {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-save:hover { background-color: #0b5ed7; color: white; }
        .btn-update {
            background-color: #ffc107;
            color: #000;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .edit-btn {
            background-color: #ffc107;
            color: black;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-left: 5px;
            font-weight: bold;
            display: inline-block;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            display: inline-block;
        }

        /* أنماط منتقي الإيموجي */
        .emoji-selector {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            background: #fafafa;
        }
        .emoji-preview {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .emoji-custom {
            max-width: 220px;
            margin-bottom: 12px !important;
            text-align: center;
            font-size: 1.2rem;
        }
        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(44px, 1fr));
            gap: 8px;
            max-width: 520px;
        }
        .emoji-option {
            width: 44px;
            height: 44px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            font-size: 1.25rem;
            cursor: pointer;
            transition: 0.15s ease;
            line-height: 1;
        }
        .emoji-option:hover {
            border-color: #0d6efd;
            transform: translateY(-1px);
        }
        .emoji-option.active {
            border-color: #0d6efd;
            background: #e7f1ff;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
        }
        .table-emoji {
            font-size: 1.4rem;
        }
    </style>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php require_once 'admin_sidebar.php'; // قائمة الإدارة المثبتة ?>

    <main class="admin-content">
        <h2 class="fw-bold mb-4">📂 إدارة الأقسام (التصنيفات)</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- نموذج إضافة أو تعديل قسم -->
        <div class="admin-panel-card">
            <?php if ($edit_category): ?>
                <h3 class="h4 mb-3">تعديل القسم: <?php echo htmlspecialchars(($edit_category->emoji ?? '📂') . ' ' . $edit_category->name); ?> ✏️</h3>
                <form action="manage_categories.php?edit=<?php echo (int)$edit_category->id; ?>" method="POST">
                    <input type="hidden" name="category_id" value="<?php echo (int)$edit_category->id; ?>">
                    <?php render_emoji_selector($emoji_options, $edit_category->emoji ?? '📂'); ?>
                    <div class="form-group">
                        <label>اسم القسم:</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_category->name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>وصف القسم:</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($edit_category->description ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_category" class="btn-update">حفظ التعديلات</button>
                    <a href="manage_categories.php" class="btn btn-secondary ms-2">إلغاء</a>
                </form>
            <?php else: ?>
                <h3 class="h4 mb-3">إضافة قسم جديد ➕</h3>
                <form action="manage_categories.php" method="POST">
                    <?php render_emoji_selector($emoji_options, '📂'); ?>
                    <div class="form-group">
                        <label>اسم القسم:</label>
                        <input type="text" name="name" placeholder="مثال: حلويات" required>
                    </div>
                    <div class="form-group">
                        <label>وصف القسم:</label>
                        <textarea name="description" rows="3" placeholder="وصف اختياري للقسم"></textarea>
                    </div>
                    <button type="submit" name="add_category" class="btn-save">حفظ وإضافة القسم</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- جدول الأقسام مع فرز وتصفية -->
        <div class="admin-panel-card">
            <h3 class="h4 mb-3">الأقسام الحالية</h3>

            <div class="admin-table-toolbar">
                <div>
                    <label for="categoriesFilter">تصفية:</label>
                    <input type="search" id="categoriesFilter" placeholder="ابحث باسم القسم أو الوصف..." autocomplete="off">
                </div>
                <small class="text-muted">انقر على رأس العمود للفرز ▲▼</small>
            </div>

            <div class="table-responsive">
                <table id="categoriesTable" class="table table-bordered align-middle bg-white mb-0 w-100">
                    <thead class="table-light">
                        <tr>
                            <th>المعرف</th>
                            <th>الإيموجي</th>
                            <th>اسم القسم</th>
                            <th>الوصف</th>
                            <th>عدد المنتجات</th>
                            <th data-no-sort>التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td data-order="<?php echo (int)$cat->id; ?>">#<?php echo (int)$cat->id; ?></td>
                                <td class="table-emoji"><?php echo htmlspecialchars($cat->emoji ?? '📂'); ?></td>
                                <td><?php echo htmlspecialchars($cat->name); ?></td>
                                <td><?php echo htmlspecialchars($cat->description ?? ''); ?></td>
                                <td data-order="<?php echo (int)$cat->products_count; ?>">
                                    <span class="badge bg-secondary"><?php echo (int)$cat->products_count; ?></span>
                                </td>
                                <td>
                                    <a href="manage_categories.php?edit=<?php echo (int)$cat->id; ?>" class="edit-btn">تعديل ✏️</a>
                                    <a href="manage_categories.php?delete=<?php echo (int)$cat->id; ?>"
                                       class="delete-btn"
                                       onclick="return confirm('تحذير: حذف القسم سيحذف أيضاً كل المنتجات التابعة له. هل تريد المتابعة؟');">
                                        حذف 🗑️
                                    </a>
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
<script>
// تفعيل منتقي الإيموجي: إيموجي/حرف واحد فقط
(function () {
    var input = document.getElementById('emojiInput');
    var preview = document.getElementById('emojiPreview');
    var custom = document.getElementById('emojiCustom');
    var grid = document.getElementById('emojiGrid');
    if (!input || !preview || !custom || !grid) return;

    // استخراج أول وحدة grapheme فقط (إيموجي واحد)
    function firstGrapheme(value) {
        var text = String(value || '').trim();
        if (!text) return '';

        if (typeof Intl !== 'undefined' && Intl.Segmenter) {
            var segmenter = new Intl.Segmenter('ar', { granularity: 'grapheme' });
            for (var segment of segmenter.segment(text)) {
                return segment.segment;
            }
        }

        // احتياطي: أول عنصر من Array.from (يفصل حسب code points)
        var parts = Array.from(text);
        return parts.length ? parts[0] : '';
    }

    function setEmoji(value) {
        var emoji = firstGrapheme(value) || '📂';
        input.value = emoji;
        preview.textContent = emoji;
        custom.value = emoji;

        grid.querySelectorAll('.emoji-option').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-emoji') === emoji);
        });
    }

    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('.emoji-option');
        if (!btn) return;
        setEmoji(btn.getAttribute('data-emoji'));
    });

    custom.addEventListener('input', function () {
        setEmoji(custom.value);
    });

    custom.addEventListener('paste', function (e) {
        e.preventDefault();
        var pasted = (e.clipboardData || window.clipboardData).getData('text');
        setEmoji(pasted);
    });
})();
</script>
</body>
</html>
