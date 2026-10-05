<?php
/**
 * ตั้งค่าความปลอดภัยของ session cookie แบบรวมศูนย์ - เรียกแทน session_start() ตรงๆ
 * ต้อง require ไฟล์นี้ "ก่อน" มีการ output หรือ session_start() อื่นใด เพราะ
 * session_set_cookie_params() ไม่มีผลถ้า session เริ่มไปแล้ว
 * ใช้: require_once __DIR__ . '/../../includes/session_bootstrap.php';
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true, // เปิดเฉพาะตอนเว็บย้ายไปใช้ HTTPS แล้ว - ถ้าเปิดตอนนี้ (ยังเป็น HTTP) เบราว์เซอร์จะไม่ส่ง cookie นี้เลย ทำให้ login ใช้งานไม่ได้
    ]);
    session_start();
}
