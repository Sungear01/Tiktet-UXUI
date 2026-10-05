<?php
/**
 * UI helpers กลางของ Landy Home Ticket
 * ใช้: require_once __DIR__ . '/../../includes/ui/helpers.php';
 */

if (!function_exists('e')) {
    /** Escape ข้อความก่อนแสดงผลใน HTML (กัน XSS) - ใช้กับข้อมูลจาก DB / ผู้ใช้ทุกครั้ง */
    function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lh_status_meta')) {
    /**
     * ข้อมูลการแสดงผลของสถานะ: [modifier CSS, ไอคอน, ป้ายที่แสดง]
     * key = name_status จากตาราง tbl_status
     */
    function lh_status_meta(string $name): array
    {
        // สีเดิมของระบบ (ดู tokens.css) + ไอคอนช่วยแยกสถานะโดยไม่ต้องพึ่งสีอย่างเดียว
        $map = [
            'งานเข้าใหม่'        => ['new',       'bi-inbox',           'งานใหม่'],
            'งานใหม่'            => ['new',       'bi-inbox',           'งานใหม่'],
            'รอดำเนินการรับงาน'  => ['new',       'bi-inbox',           'รอดำเนินการรับงาน'],
            'รออนุมัติ'          => ['approve',   'bi-hourglass-split', 'รออนุมัติ'],
            'รออนุมัติส่งงาน'    => ['approve',   'bi-hourglass-split', 'รออนุมัติส่งงาน'],
            'รออนุมัติรับงาน'    => ['approvein', 'bi-hourglass-split', 'รออนุมัติรับงาน'],
            'รอดำเนินการ'        => ['waiting',   'bi-clock',           'รอดำเนินการ'],
            'กำลังดำเนินการ'     => ['progress',  'bi-gear',            'กำลังดำเนินการ'],
            'สำเร็จ'             => ['done',      'bi-check-circle',    'สำเร็จ'],
            'สำเร็จ (รอปิดงาน)'  => ['done',      'bi-check-circle',    'สำเร็จ (รอปิดงาน)'],
            'ปิดงาน'             => ['closed',    'bi-archive',         'ปิดงาน'],
            'ไม่อนุมัติ'         => ['reject',    'bi-x-circle',        'ไม่อนุมัติ'],
            'ไม่อนุมัติรับงาน'   => ['reject',    'bi-x-circle',        'ไม่อนุมัติรับงาน'],
            'ไม่อนุมัติส่งงาน'   => ['reject',    'bi-x-circle',        'ไม่อนุมัติส่งงาน'],
            'ปฏิเสธไม่รับงาน'    => ['reject',    'bi-x-circle',        'ปฏิเสธไม่รับงาน'],
            'ยกเลิกงาน'          => ['reject',    'bi-slash-circle',    'ยกเลิกงาน'],
        ];
        $name = trim($name);
        return $map[$name] ?? ['other', 'bi-question-circle', $name !== '' ? $name : 'ไม่ทราบสถานะ'];
    }
}

if (!function_exists('status_badge')) {
    /** ป้ายสถานะ: แทน if/else + สี inline ที่กระจายอยู่ใน data/*.php */
    function status_badge(?string $name): string
    {
        [$mod, $icon, $label] = lh_status_meta((string)$name);
        return '<span class="lh-status lh-status--' . $mod . '">'
             . '<i class="bi ' . $icon . '" aria-hidden="true"></i>' . e($label)
             . '</span>';
    }
}

if (!function_exists('count_pill')) {
    /** ตัวเลขนับงานในเมนู: ซ่อนเมื่อเป็น 0, สีแดงเฉพาะเมื่อ $urgent = true */
    function count_pill($count, bool $urgent = false, string $label = 'รายการ'): string
    {
        $n = (int)$count;
        if ($n <= 0) return '';
        $cls = 'lh-count' . ($urgent ? ' lh-count--urgent' : '');
        return '<span class="' . $cls . '" aria-label="' . $n . ' ' . e($label) . '">' . $n . '</span>';
    }
}

if (!function_exists('pagination_nav')) {
    /**
     * แถบเปลี่ยนหน้าแบบไทย (ก่อนหน้า/เลขหน้า/ถัดไป) ใช้ร่วมกับ JS เดิมที่ฟังคลิกบน
     * data-page_number (ตัว $linkClass ให้ตรงกับ selector ที่หน้านั้นผูก event ไว้ เช่น
     * 'page-link' ธรรมดา หรือ 'page-link-approve' เมื่อหน้าเดียวมีหลายตารางแยกกัน)
     */
    function pagination_nav(int $page, int $totalLinks, string $linkClass = 'page-link', string $ariaLabel = 'เปลี่ยนหน้ารายการ'): string
    {
        if ($totalLinks <= 1) return '';
        if ($totalLinks <= 7) {
            $pages = range(1, $totalLinks);
        } else {
            $lo = max(2, $page - 1);
            $hi = min($totalLinks - 1, $page + 1);
            if ($page <= 3) { $lo = 2; $hi = 4; }
            if ($page >= $totalLinks - 2) { $lo = $totalLinks - 3; $hi = $totalLinks - 1; }
            $pages = [1];
            if ($lo > 2) $pages[] = '…';
            for ($p = $lo; $p <= $hi; $p++) $pages[] = $p;
            if ($hi < $totalLinks - 1) $pages[] = '…';
            $pages[] = $totalLinks;
        }

        $cls = e($linkClass);
        $html = '<nav class="mt-3" aria-label="' . e($ariaLabel) . '"><ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">';

        $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">'
            . '<a class="page-link ' . $cls . '" href="#" aria-label="หน้าก่อนหน้า" '
            . ($page > 1 ? 'data-page_number="' . ($page - 1) . '"' : 'tabindex="-1" aria-disabled="true"')
            . '><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">ก่อนหน้า</span></a></li>';

        foreach ($pages as $p) {
            if ($p === '…') {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            } elseif ($p === $page) {
                $html .= '<li class="page-item active"><span class="page-link" aria-current="page">' . $p . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link ' . $cls . '" href="#" data-page_number="' . $p . '" aria-label="หน้า ' . $p . '">' . $p . '</a></li>';
            }
        }

        $html .= '<li class="page-item ' . ($page >= $totalLinks ? 'disabled' : '') . '">'
            . '<a class="page-link ' . $cls . '" href="#" aria-label="หน้าถัดไป" '
            . ($page < $totalLinks ? 'data-page_number="' . ($page + 1) . '"' : 'tabindex="-1" aria-disabled="true"')
            . '><span class="d-none d-sm-inline me-1">ถัดไป</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>';

        $html .= '</ul></nav>';
        return $html;
    }
}

if (!function_exists('empty_state')) {
    /** ข้อความเมื่อไม่มีข้อมูล (แทน "ไม่มีรายการ" / "ไม่พบรายการ" ที่ไม่ตรงกัน) */
    function empty_state(bool $filtered = false): string
    {
        $title = $filtered ? 'ไม่พบใบงานที่ตรงกับการค้นหา' : 'ยังไม่มีใบงานในหมวดนี้';
        $hint  = $filtered ? 'ลองลบคำค้นหรือเปลี่ยนตัวกรอง' : 'เมื่อมีงานเข้ามา จะแสดงที่นี่';
        $icon  = $filtered ? 'bi-search' : 'bi-inbox';
        return '<div class="lh-empty" role="status">'
             . '<i class="bi ' . $icon . '" aria-hidden="true"></i>'
             . '<div class="lh-empty-title">' . e($title) . '</div>'
             . '<div>' . e($hint) . '</div></div>';
    }
}
