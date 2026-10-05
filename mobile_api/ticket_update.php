<?php
require_once __DIR__ . '/core/helpers.php'; auth_user(); $d=input_json(); $id=$d['id']??''; $status=$d['status']??''; if($id===''||$status==='') json_fail('ข้อมูลไม่ครบ');
$table=pick_table(['tickets','ticket','helpdesk_ticket','tb_ticket','requests']); if(!col_exists($table,'status')) json_fail('ตารางนี้ไม่มี status');
$stmt=mysqli_prepare($conn,"UPDATE `$table` SET status=? WHERE id=?"); mysqli_stmt_bind_param($stmt,'ss',$status,$id); if(!mysqli_stmt_execute($stmt)) json_fail(mysqli_error($conn),500); json_ok(['id'=>$id,'status'=>$status]);
