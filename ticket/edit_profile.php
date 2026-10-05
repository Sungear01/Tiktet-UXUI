<?php
// บันทึกโปรไฟล์ของผู้ใช้ทั่วไป: ใช้ตัวบันทึกชุดเดียวกับหัวหน้าฝ่าย
// ได้หน้าแจ้งผลแบบใหม่ + prepared statement + ไม่เขียนทับ role (เดิมล้าง role เป็นค่าว่างทุกครั้งที่บันทึก)
// path ภายใน (../connect.php, profile_main.php, ../img/...) อ้างอิงจากโฟลเดอร์ ticket/ ซึ่งลึกเท่ากัน จึงใช้ได้ตรงกัน
require __DIR__ . '/../ticket_head/edit_profile.php';
