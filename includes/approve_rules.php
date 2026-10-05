<?php
/**
 * กติกาสิทธิ์ "อนุมัติ / ไม่อนุมัติ" ใบงาน (หน้า ticket_head/onprocess.php)
 * ใช้ทั้งตอนแสดงปุ่ม (ticket_head/data/appove.php) และตอนกดจริง (ticket_head/disappwork_ticket.php)
 * → กติกาอยู่ที่เดียว ปุ่มที่เห็นกับสิทธิ์ที่ server ตรวจจะตรงกันเสมอ
 */

// ===== Role helpers (จากข้อความ) =====
function normalizeText($s)
{
    $s = trim((string)$s);
    $s = preg_replace('/[\s\p{Z}\x{200B}-\x{200D}]/u', '', $s);
    $s = str_replace(['(', ')', '（', '）', '.', '•', '-', '_', '[', ']', '{', '}', '‒', '–', '—', '―', '、', '。'], '', $s);
    return mb_strtolower($s, 'UTF-8');
}

// เป็นโปรแกรมเมอร์ไหม (รองรับ Programmer/โปรแกรมเมอร์/Developer/Dev)
function isProgrammer($position)
{
    $t = normalizeText($position);
    return (
        mb_stripos($t, 'programmer') !== false ||
        mb_stripos($t, 'โปรแกรมเมอร์') !== false ||
        mb_stripos($t, 'developer') !== false ||
        mb_stripos($t, 'dev') !== false
    );
}

// map ตำแหน่ง → แท็กบทบาท: DIR = ผอ./รองผอ., MGR = ผจก./รองผจก./หัวหน้าแผนก
function roleTagFromPosition($position)
{
    $t = normalizeText($position);
    $t = normalizeText($position);
    if (mb_stripos($t, 'ผู้อำนวยการ') !== false || mb_stripos($t, 'ผอ') !== false || mb_stripos($t, 'รองผู้อำนวยการ') !== false || mb_stripos($t, 'dir') !== false || mb_stripos($t, 'director') !== false) return 'DIR';
    if (mb_stripos($t, 'ผู้จัดการ') !== false || mb_stripos($t, 'ผจก') !== false || mb_stripos($t, 'รองผู้จัดการ') !== false || mb_stripos($t, 'หัวหน้าแผนก') !== false || mb_stripos($t, 'mgr') !== false || mb_stripos($t, 'manager') !== false) return 'MGR';
    return null;
}

// บทบาทที่ระบบต้องการ (มักเก็บใน recipient_appove/user_appove เป็นต้น)
function roleTagFromRequired($text)
{
    return roleTagFromPosition($text);
}



function canApprove($username, $position, $myDeptId, $needRoleText, $recipientDeptId)
{
    $u       = strtolower(trim((string)$username));
    $posTag  = roleTagFromPosition($position);     // 'DIR' | 'MGR' | null
    $needTag = roleTagFromRequired($needRoleText); // 'DIR' | 'MGR' | null

    // 1) Programmer → ได้หมด
    if (isProgrammer($position)) return true;

    // 2) เคสพิเศษ
    if ($u === 'tas') {
        if ($needTag === 'DIR') return true;                                 // ผอ. → ได้ทุกฝ่าย
        if ($needTag === 'MGR') return in_array((int)$recipientDeptId, [10, 11, 12, 13, 14, 15, 18, 21, 22, 20], true);
        return false;
    }

    if ($u === 'ko') {
        if ($needTag === 'DIR') return in_array((int)$recipientDeptId, [17, 19], true);
    }

    if ($u === 'pak') return ((int)$recipientDeptId === 17) && in_array($needTag, ['DIR', 'MGR'], true);
    if (in_array($u, ['pa', 'pf', 'pt', 'ka'], true)) return ($needTag === 'DIR');
    if (in_array($u, ['as'], true)) return ($needTag === 'MGR');

    // 3) กติกาทั่วไป: ต้องเป็นหัวหน้าของ "ฝ่ายผู้รับงาน"
    $isMyDept = ((string)$recipientDeptId === (string)$myDeptId);

    if (!$isMyDept || $posTag === null) return false;

    // ถ้าไม่กำหนดบทบาท → อนุโลม MGR/DIR
    if ($needTag === null) return true;

    // ต้องการตรงตำแหน่งเท่านั้น
    if ($needTag === 'MGR') return ($posTag === 'MGR');
    if ($needTag === 'DIR') return ($posTag === 'DIR');

    return false;
}
