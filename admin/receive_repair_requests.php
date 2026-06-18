<?php
session_start();

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['admin'])) {
    header('Location: ../index.php');
    exit();
}

// แสดง welcome แค่ครั้งเดียว
$show_admin_welcome = false;
if (!isset($_SESSION['show_admin_welcome'])) {
    $_SESSION['show_admin_welcome'] = true;
    $show_admin_welcome = true;
} elseif ($_SESSION['show_admin_welcome']) {
    $show_admin_welcome = true;
    $_SESSION['show_admin_welcome'] = false;
}

// DB connect
$conn = new mysqli('localhost', 'root', '', 'repair_notification_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// โหลด PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php');
require_once(__DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php');
require_once(__DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php');

// ตัวแปรสำหรับแจ้งผล
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $id = intval($_POST['id']);
        $sql = "UPDATE repair_logs SET status='approved' WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $success_message = 'อนุมัติข้อมูลเรียบร้อย';
        } else {
            $error_message = 'เกิดข้อผิดพลาด: ' . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $id = intval($_POST['id']);

        // หาว่า repair_log นี้ผูกกับ durable_article_id อะไร
        $sql_get = "SELECT durable_article_id FROM repair_logs WHERE id=?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param('i', $id);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        $durable_id = null;
        if ($row = $result_get->fetch_assoc()) {
            $durable_id = intval($row['durable_article_id']);
        }
        $stmt_get->close();

        // ลบ repair_log
        $sql = "DELETE FROM repair_logs WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $ok1 = $stmt->execute();
        $stmt->close();

        // ถ้าไม่มี repair_logs อื่นที่ผูกกับ durable_id นี้ → ลบ durable_articles ด้วย
        if ($durable_id) {
            $sql_check = "SELECT COUNT(*) AS cnt FROM repair_logs WHERE durable_article_id=?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param('i', $durable_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $cnt = 0;
            if ($row = $result_check->fetch_assoc()) {
                $cnt = intval($row['cnt']);
            }
            $stmt_check->close();

            if ($cnt === 0) {
                $sql_del = "DELETE FROM durable_articles WHERE id=?";
                $stmt_del = $conn->prepare($sql_del);
                $stmt_del->bind_param('i', $durable_id);
                $stmt_del->execute();
                $stmt_del->close();
            }
        }

        if ($ok1) {
            $success_message = 'ลบข้อมูลเรียบร้อย';
        } else {
            $error_message = 'เกิดข้อผิดพลาด: ' . $conn->error;
        }

        // ส่งอีเมลแจ้งผู้ใช้ว่าโดนปฏิเสธ
        if ($durable_id) {
            $sql_email = "SELECT u.email FROM durable_articles d 
                          LEFT JOIN user u ON d.user_id = u.id 
                          WHERE d.id=?";
            $stmt_email = $conn->prepare($sql_email);
            $stmt_email->bind_param('i', $durable_id);
            $stmt_email->execute();
            $result_email = $stmt_email->get_result();

            if ($row_email = $result_email->fetch_assoc()) {
                $user_email = $row_email['email'];

                if ($user_email) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'northnorth7404@gmail.com';
                        $mail->Password = 'abcd efgh ijkl mnop'; // <<< ใส่ App Password ตรงนี้
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->CharSet = 'UTF-8';

                        $mail->setFrom('northnorth7404@gmail.com', 'Admin');
                        $mail->addAddress($user_email);

                        $mail->isHTML(true);
                        $mail->Subject = 'แจ้งผลการรับซ่อม';
                        $mail->Body = 'ขอแจ้งว่า รายการแจ้งซ่อมของคุณถูก <b>ปฏิเสธ</b> และถูกลบออกจากระบบแล้ว';
                        $mail->AltBody = 'ขอแจ้งว่า รายการแจ้งซ่อมของคุณถูกปฏิเสธและถูกลบออกจากระบบแล้ว';

                        $mail->send();
                    } catch (Exception $e) {
                        $error_message .= " | ส่งอีเมลล้มเหลว: {$mail->ErrorInfo}";
                    }
                }
            }
            $stmt_email->close();
        }
    }
} elseif (isset($_GET['countOnly'])) {
    $result = $conn->query("SELECT COUNT(*) AS count FROM repair_logs WHERE status='pending'");
    $row = $result->fetch_assoc();
    echo json_encode(['count' => $row['count']]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <!-- PNotify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/BrightTheme.css">
    <link rel="stylesheet" href="../vendor/twbs/bootstrap-icons/font/bootstrap-icons.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รับเรื่องซ่อม</title>
    <link href="../bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/logo-mednu.png" rel="icon" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">    
</head>
<body style="background: #FFFAA3;">
    <?php require_once(__DIR__ . '/../includes/header.php'); ?>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card bg-light border-edit text-dark p-5" style="border-radius: 16px; min-width:350px; max-width:1100px; width:100%; font-size:1.25rem;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0" style="font-size:2.2rem;"><b>รับเรื่องซ่อม</b></h2>
                <form method="get" class="d-flex align-items-center gap-2">
                    <input type="text" name="cereal_number" class="form-control border-secondary" placeholder="ค้นหา Serial" value="<?php echo isset($_GET['cereal_number']) ? htmlspecialchars($_GET['cereal_number']) : ''; ?>">
                    <input type="text" name="machine_number" class="form-control border-secondary" placeholder="ค้นหา Machine" value="<?php echo isset($_GET['machine_number']) ? htmlspecialchars($_GET['machine_number']) : ''; ?>">
                    <div class="input-group">
                        <input type="date" name="date_start" class="form-control border-secondary" value="<?php echo isset($_GET['date_start']) ? htmlspecialchars($_GET['date_start']) : ''; ?>">
                        <span class="input-group-text border-secondary"><i class="bi bi-calendar-event"></i></span>
                    </div>
                    <span class="text-light">ถึง</span>
                    <div class="input-group">
                        <input type="date" name="date_end" class="form-control border-secondary" value="<?php echo isset($_GET['date_end']) ? htmlspecialchars($_GET['date_end']) : ''; ?>">
                        <span class="input-group-text border-secondary"><i class="bi bi-calendar-event"></i></span>
                    </div>
                    <button type="submit" class="btn btn-warning">ค้นหา</button>
                    <a href="receive_repair_requests.php" class="btn btn-outline-secondary" title="ล้างช่องค้นหา">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </form>
            </div>
            <?php if ($success_message): ?>
                <div class="alert alert-success text-center" style="font-size:1.1rem;"> <?php echo $success_message; ?> </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center" style="font-size:1.1rem;"> <?php echo $error_message; ?> </div>
            <?php endif; ?>
            <div style="overflow-x:auto;">
            <table class="table table-bordered custom-border align-middle text-center table-light" style="font-size:1.1rem; min-width:1000px;">
                <thead style="font-size:1.15rem;">
                    <tr>
                        <th style="min-width:120px;">ชื่อผู้ส่ง</th>
                        <th style="min-width:100px;">แผนก</th>
                        <th style="min-width:120px;">เลขซีเรียล</th>
                        <th style="min-width:120px;">เลขเครื่อง</th>
                        <th style="min-width:220px;">ประวัติการซ่อม</th>
                        <th style="min-width:120px;">วันที่</th>
                        <th style="min-width:120px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // query รายการที่ pending
                $filter_cereal = isset($_GET['cereal_number']) ? trim($_GET['cereal_number']) : '';
                $filter_machine = isset($_GET['machine_number']) ? trim($_GET['machine_number']) : '';
                $filter_date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
                $filter_date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';
                $sql = "SELECT r.*, d.cereal_number, d.machine_number, d.created_at, u.name AS user_name, u.department
                        FROM repair_logs r
                        LEFT JOIN durable_articles d ON r.durable_article_id = d.id
                        LEFT JOIN user u ON d.user_id = u.id
                        WHERE r.status='pending'";
                if ($filter_cereal) {
                    $sql .= " AND d.cereal_number LIKE '%" . $conn->real_escape_string($filter_cereal) . "%'";
                }
                if ($filter_machine) {
                    $sql .= " AND d.machine_number LIKE '%" . $conn->real_escape_string($filter_machine) . "%'";
                }
                if ($filter_date_start) {
                    $sql .= " AND r.repair_date >= '" . $conn->real_escape_string($filter_date_start) . "'";
                }
                if ($filter_date_end) {
                    $sql .= " AND r.repair_date <= '" . $conn->real_escape_string($filter_date_end) . "'";
                }
                $sql .= " ORDER BY r.id DESC";
                $result = $conn->query($sql);

                if (!function_exists('thai_date')) {
                    function thai_date($date) {
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

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['user_name'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['department'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['cereal_number']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['machine_number']) . '</td>';
                        echo '<td style="white-space:pre-line;">' . nl2br(htmlspecialchars($row['repair_history'])) . '</td>';
                        echo '<td>' . thai_date($row['repair_date']) . '</td>';
                        echo '<td>';
                        echo '<form method="post" class="d-flex flex-column gap-2" style="display:inline-block; min-width:110px;">';
                        echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                        echo '<button type="submit" name="approve" class="btn" style="background-color:#1E3A8A; color:white; font-weight:bold;">อนุมัติ</button>';
                        echo '<button type="submit" name="reject" class="btn" style="background-color:#E5E7EB; color:#222; font-weight:bold;">ปฏิเสธ</button>';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7">ไม่มีรายการรออนุมัติ</td></tr>';
                }
                ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
<script src="../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<!-- PNotify JS -->
<script src="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotifyMobile.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    flatpickr("input[type=date]", {
        locale: "th",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j F Y",
        allowInput: true
    });

    // Welcome admin notification
    <?php if ($show_admin_welcome): ?>
    PNotify.success({
      title: 'เข้าสู่ระบบแล้ว!',
      text: 'Welcome admin!',
      delay: 2000
    });
    <?php endif; ?>
});
</script>
</body>
</html>
