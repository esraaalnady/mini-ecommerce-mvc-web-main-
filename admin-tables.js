/**
 * ============================================================================
 * admin-tables.js — فرز وتصفية جداول لوحة الإدارة
 * ============================================================================
 * بدون مكتبات خارجية (يعمل حتى لو حُجب الـ CDN).
 *
 * ملاحظات:
 * 1) فرز الأعمدة: عند النقر على رأس العمود تُرتب صفوف tbody.
 * 2) التصفية: قراءة نص البحث وإخفاء الصفوف غير المطابقة.
 * 3) data-order: قيمة رقمية/نصية للفرز أدق من النص الظاهر.
 * 4) data-no-sort: لمنع فرز أعمدة مثل الأزرار أو الصور.
 * ============================================================================
 */
(function () {
    'use strict'; // يمنع بعض الأخطاء الشائعة في JavaScript

    /**
     * استخراج قيمة الفرز من الخلية
     * نفضّل data-order إن وُجد، وإلا نأخذ النص الظاهر
     */
    function getCellSortValue(cell) {
        if (!cell) return '';
        if (cell.dataset.order !== undefined && cell.dataset.order !== '') {
            return cell.dataset.order;
        }
        return (cell.textContent || '').trim();
    }

    /**
     * مقارنة قيمتين:
     * - إذا كانتا رقمين نقارن رقمياً
     * - وإلا نقارن نصياً مع دعم العربية (localeCompare)
     */
    function compareValues(a, b) {
        var numA = parseFloat(String(a).replace(/[^0-9.\-]/g, ''));
        var numB = parseFloat(String(b).replace(/[^0-9.\-]/g, ''));
        var aIsNum = !isNaN(numA) && String(a).search(/[0-9]/) !== -1;
        var bIsNum = !isNaN(numB) && String(b).search(/[0-9]/) !== -1;

        if (aIsNum && bIsNum) {
            return numA - numB;
        }

        return String(a).localeCompare(String(b), 'ar', {
            sensitivity: 'base',
            numeric: true
        });
    }

    /** إزالة علامات الفرز من كل رؤوس الأعمدة */
    function clearSortMarks(headers) {
        headers.forEach(function (th) {
            th.classList.remove('is-sorted-asc', 'is-sorted-desc');
            th.removeAttribute('aria-sort');
        });
    }

    /** تفعيل خاصية الفرز على جدول معيّن */
    function initSortableTable(table) {
        var tbody = table.tBodies[0];
        if (!tbody) return;

        var headers = Array.prototype.slice.call(
            table.tHead ? table.tHead.rows[0].cells : []
        );

        headers.forEach(function (th, colIndex) {
            // الأعمدة ذات data-no-sort لا تُفرز (مثل الصورة والإجراءات)
            if (th.hasAttribute('data-no-sort')) {
                th.classList.add('no-sort');
                return;
            }

            th.classList.add('is-sortable');
            th.setAttribute('role', 'button');
            th.setAttribute('tabindex', '0');
            th.title = 'انقر للفرز';

            function sortByColumn() {
                // إذا كان العمود تصاعدياً الآن، اجعله تنازلياً والعكس
                var ascending = !th.classList.contains('is-sorted-asc');

                // نتجاهل صف رسالة "لا توجد نتائج"
                var rows = Array.prototype.slice.call(tbody.rows).filter(function (row) {
                    return row.id.indexOf('-empty') === -1;
                });

                rows.sort(function (rowA, rowB) {
                    var valA = getCellSortValue(rowA.cells[colIndex]);
                    var valB = getCellSortValue(rowB.cells[colIndex]);
                    var result = compareValues(valA, valB);
                    return ascending ? result : -result;
                });

                clearSortMarks(headers);
                th.classList.add(ascending ? 'is-sorted-asc' : 'is-sorted-desc');
                th.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');

                // إعادة إدخال الصفوف بالترتيب الجديد داخل tbody
                rows.forEach(function (row) {
                    tbody.appendChild(row);
                });
            }

            th.addEventListener('click', sortByColumn);
            // دعم لوحة المفاتيح (Enter / Space)
            th.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sortByColumn();
                }
            });
        });
    }

    /** تفعيل البحث/التصفية على صفوف الجدول */
    function initTableFilter(table, input) {
        if (!table || !input) return;
        var tbody = table.tBodies[0];
        if (!tbody) return;

        input.addEventListener('input', function () {
            var query = (input.value || '').trim().toLowerCase();
            var rows = Array.prototype.slice.call(tbody.rows);
            var visible = 0;

            rows.forEach(function (row) {
                // نتخطى صف الرسالة الفارغة إن وُجد
                if (row.id && row.id.indexOf('-empty') !== -1) {
                    return;
                }
                var text = (row.textContent || '').toLowerCase();
                var show = !query || text.indexOf(query) !== -1;
                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            // إظهار رسالة عند عدم وجود نتائج مطابقة
            var emptyId = table.id + '-empty';
            var emptyRow = document.getElementById(emptyId);
            if (visible === 0) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.id = emptyId;
                    emptyRow.innerHTML =
                        '<td colspan="' + headersCount(table) +
                        '" class="text-center text-muted py-3">لا توجد نتائج مطابقة للبحث</td>';
                    tbody.appendChild(emptyRow);
                }
                emptyRow.style.display = '';
            } else if (emptyRow) {
                emptyRow.style.display = 'none';
            }
        });
    }

    /** عدد أعمدة الجدول (لاستخدامه في colspan) */
    function headersCount(table) {
        if (table.tHead && table.tHead.rows[0]) {
            return table.tHead.rows[0].cells.length;
        }
        return 1;
    }

    /** ربط الجدول مع مربع البحث الخاص به */
    function enhanceTable(tableId, filterInputId) {
        var table = document.getElementById(tableId);
        if (!table) return;
        table.classList.add('js-enhanced-table');
        initSortableTable(table);
        initTableFilter(table, document.getElementById(filterInputId));
    }

    // بعد تحميل الصفحة نفعل الجداول المعروفة في لوحة الإدارة
    document.addEventListener('DOMContentLoaded', function () {
        enhanceTable('productsTable', 'productsFilter');
        enhanceTable('usersTable', 'usersFilter');
        enhanceTable('categoriesTable', 'categoriesFilter');
    });
})();
