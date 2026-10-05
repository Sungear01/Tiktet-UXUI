<?php
// ใช้หน้าเดียวกับฝ่าย IT (ticket/sending_repair.php) แต่แสดงเมนูของ ticket_head
// สิทธิ์ตรวจในหน้านั้นเหมือนเดิม: ฝ่าย IT (dept = 18) หรือ Programmer
$repairNavDir = __DIR__;
$repairBase   = '../ticket/';
require __DIR__ . '/../ticket/sending_repair.php';
