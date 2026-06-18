<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header('Location: ../index.php');
    exit();
}

// ถ้ายังไม่มีตัวแปรนี้ ให้แสดง welcome และตั้งค่า
$show_user_name_welcome = false; // ✅ กำหนดค่าเริ่มต้น
if (!isset($_SESSION['show_user_name_welcome'])) {
    $_SESSION['show_user_name_welcome'] = true;
    $show_user_name_welcome = true;
}

if (!isset($_SESSION['user_name'])) {
    header('Location: ../index.php');
    exit();
}

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'repair_notification_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// ดึง user_id จาก session
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

// ✅ ย้ายส่วนจัดการลบ log pending มาไว้ด้านบน (เพื่อให้โหลดหน้าเว็บได้ถูกต้องและปลอดภัย)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log_id'])) {
    $delete_id = intval($_POST['delete_log_id']);
    // ตรวจสอบว่า log นี้เป็นของ user นี้และเป็น pending
    $sql_check = "SELECT l.id FROM repair_logs l LEFT JOIN durable_articles d ON l.durable_article_id = d.id WHERE l.id=? AND l.status='pending' AND d.user_id=?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param('ii', $delete_id, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        $sql_del = "DELETE FROM repair_logs WHERE id=?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param('i', $delete_id);
        $stmt_del->execute();
        $stmt_del->close();
        
        // ใช้ Header Redirect ของ PHP แทนการใช้ JavaScript Reload
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $stmt_check->close();
        echo '<script>alert("ไม่สามารถลบได้"); window.location.href="'.$_SERVER['PHP_SELF'].'";</script>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
       
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ครุภัณฑ์</title>
    <link href="../bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/logo-mednu.png" rel="icon" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/@pnotify/core/dist/PNotify.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@pnotify/core/dist/BrightTheme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@pnotify/core/dist/PNotify.js"></script>
        <style>
                    th, td {
                        font-size: 1.5rem;
                    }
        </style>
</head>
<body style="background: #f5f6fa;">
    <?php require_once(__DIR__ . '/../includes/headeruser.php'); ?>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card  p-5  border-edit" style="border-radius: 16px; min-width:400px; max-width:1200px; width:100%; font-size:1.15rem;">
            <h2 class="text-center mb-4" style="font-size:2rem;"><b>ครุภัณฑ์ที่แจ้งซ่อม</b></h2>
            <div style="overflow-x:auto;">
            <table class="table table-bordered custom-border align-middle text-center" style="font-size:1.05rem; min-width:900px;">
                <thead>
                    <tr>
                        <th>เลขซีเรียล</th>
                        <th>เลขเครื่อง</th>
                        <th>ประวัติการซ่อม</th>
                        <th>วันที่</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (!function_exists('thai_date')) {
                    // ✅ เพิ่ม string type hint เพื่อแก้ Warning P1132 ของ Intelephense
                    function thai_date(string $date) {
                        $months = [
                            '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
                        ];
                        $ts = strtotime($date);
                        $day = date('j', $ts);
                        $month = $months[(int)date('n', $ts)];
                        $year = date('Y', $ts) + 543;
                        return "$day $month $year";
                    }
                }
                if ($user_id) {
    $sql = "SELECT * FROM durable_articles WHERE user_id=? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // ดึง repair_logs ทั้งหมดของเครื่องนี้
            $sql_logs = "SELECT * FROM repair_logs WHERE durable_article_id=? ORDER BY repair_date DESC, id DESC";
            $stmt_logs = $conn->prepare($sql_logs);
            $stmt_logs->bind_param('i', $row['id']);
            $stmt_logs->execute();
            $logs_result = $stmt_logs->get_result();
            $logs = [];
            $latest_date = '';
            $latest_status = '';
            if ($logs_result && $logs_result->num_rows > 0) {
                while ($log = $logs_result->fetch_assoc()) {
                    $logs[] = $log;
                }
                // ใช้รายการล่าสุดเป็นวันที่และสถานะ
                $latest_date = $logs[0]['repair_date'];
                $latest_status = $logs[0]['status'];
            }
            $stmt_logs->close();
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['cereal_number']) . '</td>';
            echo '<td>' . htmlspecialchars($row['machine_number']) . '</td>';
            echo '<td style="white-space:pre-line;">';
            if (!empty($logs)) {
                echo '<ul class="list-unstyled mb-0">';
                foreach ($logs as $log) {
                    echo '<li>';
                    echo '<span class="fw-bold text-success">[' . thai_date($log['repair_date']) . ']</span> ';
                    echo htmlspecialchars($log['repair_history']);
                    if ($log['status'] === 'approved') {
                        echo ' <span class="badge bg-success ms-2">อนุมัติ</span>';
                    } elseif ($log['status'] === 'pending') {
                        echo ' <span class="badge bg-warning text-dark ms-2">รอตรวจสอบ</span>';
                    } elseif ($log['status'] === 'rejected') {
                        echo ' <span class="badge bg-danger ms-2">ไม่อนุมัติ</span>';
                    } else {
                        echo ' <span class="badge bg-secondary ms-2">' . htmlspecialchars($log['status']) . '</span>';
                    }
                    if ($log['status'] === 'pending') {
                        echo ' <form method="post" action="" style="display:inline;" onsubmit="return confirm(\'ยืนยันการลบประวัติรอตรวจสอบนี้?\');">';
                        echo '<input type="hidden" name="delete_log_id" value="' . intval($log['id']) . '"><button type="submit" class="btn btn-sm btn-danger ms-1 py-0 px-2">ลบ</button>';
                        echo '</form>';
                    }
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '-';
            }
            echo '</td>';
            echo '<td>' . (isset($row['created_at']) ? thai_date($row['created_at']) : '-') . '</td>';
            if ($latest_status === 'approved') {
                echo '<td><span class="badge bg-success">อนุมัติ</span></td>';
            } elseif ($latest_status === 'pending') {
                echo '<td><span class="badge bg-warning text-dark">รอตรวจสอบ</span></td>';
            } elseif ($latest_status === 'rejected') {
                echo '<td><span class="badge bg-danger">ไม่อนุมัติ</span></td>';
            } else {
                echo '<td><span class="badge bg-secondary">-</span></td>';
            }
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">ยังไม่มีข้อมูลครุภัณฑ์</td></tr>';
    }
    $stmt->close();
                } else {
                    echo '<tr><td colspan="5">ไม่พบข้อมูลผู้ใช้</td></tr>';
                }
                ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
<script src="../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // ✅ Welcome user notification
    <?php if ($show_user_name_welcome): ?>
    PNotify.success({
      title: 'เข้าสู่ระบบแล้ว!',
      text: 'Welcome <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!',
      delay: 2000
    });
    <?php endif; ?>
});
</script>
</body>
</html>