<?php
/**
 * <head> กลาง: ใช้แทนการก๊อป <link>/<script> ในทุกหน้า
 * ใช้:
 *   $pageTitle = 'งานเข้าใหม่';
 *   $assetBase = '../';   // path จากหน้านั้นไปถึง root (หน้าใน ticket/ = '../')
 *   include __DIR__ . '/../../includes/layout/head.php';
 *
 * เวอร์ชัน pin ไว้ที่นี่ที่เดียว - ห้ามเพิ่ม Bootstrap 4 หรือ Font Awesome ชุดที่ 2 ในหน้าใหม่
 */
require_once __DIR__ . '/../ui/helpers.php';
$pageTitle = $pageTitle ?? 'Landy Home Ticket';
$assetBase = $assetBase ?? '../';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | Landy Home Ticket</title>
<link rel="icon" href="<?= e($assetBase) ?>favicon.ico">

<!-- Font: โหลดครบ 4 น้ำหนัก ไม่ให้ browser สร้างตัวหนาปลอม -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Bootstrap 5.3 + Bootstrap Icons (ชุดไอคอนเดียวของระบบ) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- Design system (ลำดับสำคัญ: tokens ก่อน app) -->
<link href="<?= e($assetBase) ?>assets/css/tokens.css" rel="stylesheet">
<link href="<?= e($assetBase) ?>assets/css/bootstrap-bridge.css" rel="stylesheet">
<link href="<?= e($assetBase) ?>assets/css/app.css" rel="stylesheet">
