-- Seed / fix Arabic data with proper UTF-8 + product images
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- حذف البيانات القديمة ثم إعادة إدخالها بشكل نظيف
DELETE FROM products;
DELETE FROM categories;

-- الأقسام مع الإيموجي
INSERT INTO categories (id, name, emoji, description) VALUES
(1, 'حلويات', '🧁', 'كيك وحلويات'),
(2, 'مشروبات باردة', '🥤', 'عصائر ومشروبات منعشة'),
(3, 'مشروبات ساخنة', '☕', 'قهوة وشاي');

-- المنتجات مع مسارات الصور داخل مجلد uploads/
INSERT INTO products (category_id, name, description, price, quantity, image) VALUES
(1, 'كيكة شوكولاتة', 'كيكة منزلية غنية بالشوكولاتة', 12.50, 20, 'uploads/chocolate_cake.png'),
(1, 'كب كيك فانيلا', 'كب كيك طري بنكهة الفانيلا', 4.00, 40, 'uploads/vanilla_cupcake.png'),
(2, 'عصير برتقال', 'عصير طازج منعش', 3.50, 50, 'uploads/orange_juice.png'),
(2, 'موهيتو', 'مشروب بارد بالنعناع', 5.00, 30, 'uploads/mojito.png'),
(3, 'كابتشينو', 'قهوة إيطالية كلاسيكية', 4.50, 40, 'uploads/cappuccino.png'),
(3, 'شاي أخضر', 'شاي ساخن خفيف', 2.50, 60, 'uploads/green_tea.png');
