<?php
session_start();
require_once '../connect.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $position    = $_SESSION["position"] ?? '';
    $username    = strtolower($_SESSION["username"] ?? '');
    $description = $_SESSION["description"] ?? '';

    if (
        $position === "Programmer" ||
        in_array($username, ["pm","ka","pa","pf","pt","tas"]) ||
        substr($position, 0, 10) === "ผู้จัดการ" ||
        substr($username, 0, 3) === ".sh"
    ) {
        $de = '%';
    } else {
        $de = $description;
    }

    $data = [
      'index'=>0,'onprocess'=>0,'process'=>0,'allticketin'=>0,
      'onprocess_new'=>0,'process_new'=>0,'check'=>0,'allticket'=>0
    ];

    $sql = "SELECT status_request, COUNT(*) AS total 
            FROM ticket 
            WHERE recipient_department LIKE :de
            GROUP BY status_request";
    $stmt = $connect->prepare($sql);
    $stmt->execute([':de' => $de]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        switch ($row['status_request']) {
            case 'งานเข้าใหม่': $data['index'] = $row['total']; break;
            case 'รอดำเนินการ': $data['onprocess'] = $row['total']; break;
            case 'กำลังดำเนินการ': $data['process'] = $row['total']; break;
            // เพิ่ม mapping ให้ครบทุกสถานะของคุณ
        }
    }

    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['error'=>true, 'message'=>$e->getMessage()]);
}
