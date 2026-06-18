<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'repair_notification_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$search_result = null;
$search_mode = '';
$success_message = '';
$error_message = '';
// ค้นหา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $cereal_number = $conn->real_escape_string($_POST['cereal_number']);
    $machine_number = $conn->real_escape_string($_POST['machine_number']);
    $sql = "SELECT * FROM durable_articles WHERE cereal_number = ? AND machine_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $cereal_number, $machine_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $search_result = $row;
        $search_mode = 'edit';
        // ดึงประวัติการซ่อมจาก repair_logs
        $sql_logs = "SELECT * FROM repair_logs WHERE durable_article_id = ? ORDER BY repair_date DESC, id DESC";
        $stmt_logs = $conn->prepare($sql_logs);
        $stmt_logs->bind_param('i', $row['id']);
        $stmt_logs->execute();
        $logs_result = $stmt_logs->get_result();
        $repair_logs = [];
        while ($log = $logs_result->fetch_assoc()) {
            $repair_logs[] = $log;
        }
        $stmt_logs->close();
    } else {
        $search_mode = 'add';
    }
    $stmt->close();
}
// เพิ่มเครื่องใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $cereal_number = $conn->real_escape_string($_POST['cereal_number']);
    $machine_number = $conn->real_escape_string($_POST['machine_number']);
    $repair_history = $conn->real_escape_string($_POST['repair_history']);
    $date = $conn->real_escape_string($_POST['date']);
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    // เพิ่ม durable_articles ก่อน
    if ($user_id) {
        $sql = "INSERT INTO durable_articles (cereal_number, machine_number, user_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $cereal_number, $machine_number, $user_id);
    } else {
        $sql = "INSERT INTO durable_articles (cereal_number, machine_number) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $cereal_number, $machine_number);
    }
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        $stmt->close();
        // เพิ่ม repair_logs
        $sql_log = "INSERT INTO repair_logs (durable_article_id, repair_history, repair_date, status) VALUES (?, ?, ?, 'pending')";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param('iss', $new_id, $repair_history, $date);
        if ($stmt_log->execute()) {
            $success_message = 'เพิ่มเครื่องใหม่สำเร็จ รอแอดมินตรวจสอบ';
        } else {
            $error_message = 'เกิดข้อผิดพลาด: ' . $conn->error;
        }
        $stmt_log->close();
    } else {
        $error_message = 'เกิดข้อผิดพลาด: ' . $conn->error;
        $stmt->close();
    }
}
// เพิ่มประวัติการซ่อมใหม่ (แก้ไขเครื่อง)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = intval($_POST['id']); // durable_article_id
    $new_repair_history = $conn->real_escape_string($_POST['repair_history']);
    $new_date = $conn->real_escape_string($_POST['date']);
    $sql_log = "INSERT INTO repair_logs (durable_article_id, repair_history, repair_date, status) VALUES (?, ?, ?, 'pending')";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param('iss', $id, $new_repair_history, $new_date);
    if ($stmt_log->execute()) {
        $success_message = 'เพิ่มประวัติการซ่อมสำเร็จ รอแอดมินตรวจสอบ';
    } else {
        $error_message = 'เกิดข้อผิดพลาด: ' . $conn->error;
    }
    $stmt_log->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
        <style>
            body {
                font-size: 1.25rem;
            }
            .card {
                font-size: 1.18rem;
            }
            h2, h3, h4 {
                font-size: 2.3rem !important;
            }
            .table {
                font-size: 1.15rem;
            }
            th, td {
                font-size: 1.5rem;
            }
        </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อม</title>
    <link href="../bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/logo-mednu.png" rel="icon" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body style="background: #f5f6fa;">
    <?php require_once(__DIR__ . '/../includes/headeruser.php'); ?>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card bg-light text-dark p-5 shadow-sm border-edit" style="border-radius: 16px; min-width:350px; max-width:500px; width:100%; border: 1px solid #e0e0e0;">
            <?php if ($success_message): ?>
                <div class="alert alert-success text-center"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <h2 class="text-center mb-4"><b>แจ้งซ่อมครุภัณฑ์</b></h2>
            <form method="post" class="mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label for="cereal_number" class="form-label">เลขซีเรียล</label>
                        <input type="text" class="form-control bg-white border-secondary" id="cereal_number" name="cereal_number" required value="<?php echo isset($_POST['cereal_number']) ? htmlspecialchars($_POST['cereal_number']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="machine_number" class="form-label">เลขเครื่อง</label>
                        <input type="text" class="form-control bg-white border-secondary" id="machine_number" name="machine_number" required value="<?php echo isset($_POST['machine_number']) ? htmlspecialchars($_POST['machine_number']) : ''; ?>">
                    </div>
                    <div class="col-12 text-center">
                        <button type="submit" name="search" class="btn btn-warning mt-2 px-4 shadow-sm">ค้นหา</button>
                    </div>
                </div>
            </form>
            <?php if ($search_mode === 'edit' && $search_result): ?>
                <div class="alert alert-success text-center">พบเครื่องในระบบ สามารถเพิ่มประวัติการซ่อมใหม่ได้</div>
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $search_result['id']; ?>">
                    <div class="mb-3">
                        <label for="repair_history" class="form-label">เพิ่มประวัติการซ่อม</label>
                        <textarea class="form-control bg-white border-secondary" id="repair_history" name="repair_history" rows="3" placeholder="กรอกประวัติการซ่อมใหม่"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">วันที่</label>
                        <div class="input-group">
                            <input type="date" class="form-control bg-white border-secondary" id="date" name="date" required>
                            <span class="input-group-text bg-white border-secondary"><i class="bi bi-calendar-event"></i></span>
                        </div>
                    </div>
                    <button type="submit" name="update" class="btn btn-primary w-100 shadow-sm">เพิ่มประวัติการซ่อม</button>
                </form>
                <?php if (!empty($repair_logs)): ?>
                <div class="mt-4">
                    <h5 class="text-info">ประวัติการซ่อมทั้งหมด</h5>
                    <ul class="list-group list-group-flush">
                        <?php
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
                        foreach ($repair_logs as $log): ?>
                        <li class="list-group-item border-0 bg-white shadow-sm mb-2 rounded-3 px-3 py-2">
                            <span class="fw-bold text-success">[<?php echo thai_date($log['repair_date']); ?>]</span>
                            <?php echo htmlspecialchars($log['repair_history']); ?>
                            <span class="badge bg-<?php echo ($log['status']==='approved')?'success':'secondary'; ?> ms-2">
                                <?php echo $log['status']; ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php elseif ($search_mode === 'add'): ?>
                <div class="alert alert-info text-center">ไม่พบเครื่องในระบบ สามารถเพิ่มเครื่องใหม่ได้</div>
                <form method="post">
                    <div class="mb-3">
                        <label for="cereal_number_add" class="form-label">เลขซีเรียล</label>
                        <input type="text" class="form-control bg-white border-secondary" id="cereal_number_add" name="cereal_number" value="<?php echo htmlspecialchars($_POST['cereal_number']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="machine_number_add" class="form-label">เลขเครื่อง</label>
                        <input type="text" class="form-control bg-white border-secondary" id="machine_number_add" name="machine_number" value="<?php echo htmlspecialchars($_POST['machine_number']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="repair_history_add" class="form-label">ประวัติการซ่อม</label>
                        <textarea class="form-control bg-white border-secondary" id="repair_history_add" name="repair_history" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="date_add" class="form-label">วันที่</label>
                        <div class="input-group">
                            <input type="date" class="form-control bg-white border-secondary" id="date_add" name="date" required>
                            <span class="input-group-text bg-white border-secondary"><i class="bi bi-calendar-event"></i></span>
                        </div>
                    </div>
                    <button type="submit" name="add" class="btn btn-success w-100 shadow-sm">เพิ่มเครื่องใหม่</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<script src="../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    flatpickr("#date", {
        locale: "th",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j F Y",
        allowInput: true
    });
    flatpickr("#date_add", {
        locale: "th",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j F Y",
        allowInput: true
    });
});
</script>
</body>
</html>
