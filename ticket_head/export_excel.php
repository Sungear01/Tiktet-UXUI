 <?php
                                                require '../connect.php';

                                                header("Content-Type: application/vnd.ms-excel");
                                                header("Content-Disposition: attachment; filename=report_ticket_" . date("Ymd_His") . ".xls");

                                                // รับค่าจาก query string
                                                $search_number = $_GET['search_number'] ?? '';
                                                $search_box = $_GET['search_box'] ?? '';
                                                $search_name = $_GET['search_name'] ?? '';
                                                $search_status = $_GET['search_status'] ?? '';

                                                // SQL ดึงข้อมูลเหมือนกับหน้า AJAX
                                                $query = "SELECT 
                                                    ticket.ticket_id,
                                                    ticket.date_ticket,
                                                    ticket.request_subject,
                                                    U1.fullname AS request_name,
                                                    UD.department_name AS user_department_name,
                                                    U.fullname AS recipient_name,
                                                    RD.department_name AS recipient_department_name,
                                                    ticket.user_appove,
                                                    tbl_status.name_status
                                                FROM ticket
                                                LEFT JOIN department AS UD ON UD.id = ticket.user_department
                                                LEFT JOIN department AS RD ON RD.id = ticket.recipient_department
                                                LEFT JOIN tbl_status ON tbl_status.id_status = ticket.status_request
                                                LEFT JOIN user AS U ON U.user_id = ticket.recipient_request
                                                LEFT JOIN user AS U1 ON U1.user_id = ticket.user_request
                                                WHERE 1=1";

                                                if (!empty($search_number)) {
                                                    $query .= " AND ticket.ticket_id LIKE '%$search_number%'";
                                                }
                                                if (!empty($search_name)) {
                                                    $query .= " AND U.fullname LIKE '%$search_name%'";
                                                }
                                                if (!empty($search_box)) {
                                                    $query .= " AND UD.department_name LIKE '%$search_box%'";
                                                }
                                                if (!empty($search_status)) {
                                                    $query .= " AND tbl_status.name_status LIKE '%$search_status%'";
                                                }

                                                $query .= " GROUP BY ticket_id ORDER BY ticket_id DESC";

                                                $stmt = $conn1->prepare($query);
                                                $stmt->execute();
                                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                // สร้าง HTML Table ที่ Excel อ่านได้
                                                echo '<table border="1">';
                                                echo '<tr>
                                                    <th>Ticket ID</th>
                                                    <th>วันที่ Request</th>
                                                    <th>ชื่อผู้ Request</th>
                                                    <th>ฝ่ายผู้ Request</th>
                                                    <th>เรื่อง Request</th>
                                                    <th>ชื่อผู้รับ</th>
                                                    <th>ฝ่ายผู้รับ</th>
                                                    <th>ผู้อนุมัติ</th>
                                                    <th>สถานะงาน</th>
                                                </tr>';

                                                foreach ($rows as $r) {
                                                    echo '<tr>
                                                        <td>' . $r['ticket_id'] . '</td>
                                                        <td>' . $r['date_ticket'] . '</td>
                                                        <td>' . $r['request_name'] . '</td>
                                                        <td>' . $r['user_department_name'] . '</td>
                                                        <td>' . $r['request_subject'] . '</td>
                                                        <td>' . $r['recipient_name'] . '</td>
                                                        <td>' . $r['recipient_department_name'] . '</td>
                                                        <td>' . $r['user_appove'] . '</td>
                                                        <td>' . $r['name_status'] . '</td>
                                                    </tr>';
                                                }

                                                echo '</table>';
                                                exit;
                                                ?>
