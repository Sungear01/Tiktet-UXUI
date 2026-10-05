<?php
require_once __DIR__ . '/core/helpers.php'; auth_user();
$table=pick_table(['tickets','ticket','helpdesk_ticket','tb_ticket','requests']);
$status=$_GET['status']??''; $q=$_GET['q']??''; $where='1=1'; $params=[]; $types='';
if($status!=='' && col_exists($table,'status')){ $where.=' AND status=?'; $params[]=$status; $types.='s'; }
if($q!==''){ $like="%$q%"; $ors=[]; foreach(['title','subject','detail','description','ticket_no','id'] as $c){ if(col_exists($table,$c)) $ors[]="`$c` LIKE ?"; } if($ors){ $where.=' AND ('.implode(' OR ',$ors).')'; foreach($ors as $_){$params[]=$like;$types.='s';} } }
$sql="SELECT * FROM `$table` WHERE $where ORDER BY ".(col_exists($table,'created_at')?'created_at':'id')." DESC LIMIT 100";
$stmt=mysqli_prepare($conn,$sql); if($params) mysqli_stmt_bind_param($stmt,$types,...$params); mysqli_stmt_execute($stmt); $r=mysqli_stmt_get_result($stmt); $out=[];
while($row=mysqli_fetch_assoc($r)){ $out[]=['id'=>$row['id']??'', 'title'=>$row['title']??$row['subject']??$row['ticket_no']??('Ticket #'.($row['id']??'')), 'detail'=>$row['detail']??$row['description']??'', 'status'=>$row['status']??'', 'created_at'=>$row['created_at']??$row['date_create']??'']; }
json_ok($out);
