<?php
require_once __DIR__ . '/core/helpers.php'; auth_user();
$ticketId=$_POST['ticket_id']??''; if($ticketId==='') json_fail('ไม่พบ ticket_id'); if(empty($_FILES['file'])) json_fail('ไม่พบไฟล์');
$f=$_FILES['file']; if($f['error']!==UPLOAD_ERR_OK) json_fail('อัปโหลดไฟล์ไม่สำเร็จ');
$allow=['pdf','jpg','jpeg','png','gif','xlsx','xls','doc','docx','txt','zip']; $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION)); if(!in_array($ext,$allow)) json_fail('ชนิดไฟล์ไม่อนุญาต');
$dir=UPLOAD_DIR.'/tickets/'.$ticketId; if(!is_dir($dir)) @mkdir($dir,0775,true); $stored=date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext; $dest=$dir.'/'.$stored; if(!move_uploaded_file($f['tmp_name'],$dest)) json_fail('ย้ายไฟล์เข้า Server ไม่สำเร็จ');
$rel='mobile_api/uploads/tickets/'.$ticketId.'/'.$stored; $url=abs_url($rel);
$at=pick_table(['ticket_attachments','attachments','request_files','tb_ticket_file']); if(table_exists($at)){ $cols=[];$vals=[];$types='';$params=[]; foreach(['ticket_id'=>$ticketId,'request_id'=>$ticketId,'file_name'=>$stored,'original_name'=>$f['name'],'stored_name'=>$rel,'file_path'=>$rel,'path'=>$rel,'created_at'=>date('Y-m-d H:i:s')] as $c=>$v){ if(col_exists($at,$c)){ $cols[]="`$c`";$vals[]='?';$params[]=$v;$types.='s'; }} if($cols){ $sql="INSERT INTO `$at` (".implode(',',$cols).") VALUES (".implode(',',$vals).")"; $stmt=mysqli_prepare($conn,$sql); mysqli_stmt_bind_param($stmt,$types,...$params); @mysqli_stmt_execute($stmt); }}
json_ok(['file_name'=>$f['name'],'stored'=>$stored,'url'=>$url]);
