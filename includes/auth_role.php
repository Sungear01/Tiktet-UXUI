<?php
/**
 * ตัวตัดสินว่าผู้ใช้อยู่โซนไหนของระบบ
 *
 *   true  -> ticket_head/  (หัวหน้า / ผู้จัดการ / Programmer)
 *   false -> ticket/       (พนักงานทั่วไป)
 *
 * login.php ใช้ฟังก์ชันนี้ตัดสินใจ redirect หลังล็อกอิน หน้าอื่นที่ต้องรู้ว่า
 * ผู้ใช้อยู่โซนไหน (เช่น Dashboard IT ที่เปิดได้ทั้งสองโซน) ต้องเรียกตัวเดียวกันนี้
 * ห้ามเขียนเงื่อนไขซ้ำที่อื่น ไม่งั้นวันที่แก้รายชื่อตำแหน่งจะแก้ไม่ครบทุกที่
 */
if (!function_exists('lh_is_head_role')) {
    function lh_is_head_role($position_text, $username): bool
    {
        $pos = (string)$position_text;
        $u   = strtolower((string)$username);
        return (
            strtolower($pos) === 'programmer' ||
            strpos($pos, 'ผู้จัดการ') === 0 ||
            strpos($pos, 'ผจก') === 0 ||
            stripos($pos, 'mgr') !== false ||
            stripos($pos, 'manager') !== false ||
            stripos($pos, 'managing') !== false ||
            stripos($pos, 'director') !== false ||
            in_array($u, ['pf', 'pa', 'pt', 'ka', 'tas'], true)
        );
    }
}
