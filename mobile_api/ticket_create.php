<?php
require_once __DIR__ . '/core/helpers.php'; $u=auth_user(); $d=input_json();
$title=trim($d['title']??''); $detail=trim($d['detail']??''); if($title===''||$detail==='') json_fail('กรอกเรื่องและรายละเอียด');
$table=pick_table(['tickets','ticket','helpdesk_ticket','tb_ticket','requests']);
$cols=[];$vals=[];$types='';$params=[];
foreach(['title'=>$title,'subject'=>$title,'detail'=>$detail,'description'=>$detail,'category'=>$d['category']??'','priority'=>$d['priority']??'normal','status'=>'pending','created_by'=>$u['id']??$u['username']??'','created_at'=>date('Y-m-d H:i:s')] as $c=>$v){ if(col_exists($table,$c)){ $cols[]="`$c`"; $vals[]='?'; $params[]=$v; $types.='s'; }}
if(!$cols) json_fail('ตาราง Ticket ยังไม่ตรงกับ API');
$sql="INSERT INTO `$table` (".implode(',',$cols).") VALUES (".implode(',',$vals).")"; $stmt=mysqli_prepare($conn,$sql); mysqli_stmt_bind_param($stmt,$types,...$params); if(!mysqli_stmt_execute($stmt)) json_fail(mysqli_error($conn),500); $id=mysqli_insert_id($conn); json_ok(['id'=>$id], ['id'=>$id,'message'=>'Created']);
