<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: ../index.php');
    exit();
}
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'repair_notification_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$success_message = '';
$error_message = '';
// อนุมัติ/ปฏิเสธ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    if (isset($_POST['approve'])) {
        $sql = "UPDATE user SET status='approved' WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $success_message = 'อนุมัติผู้ใช้เรียบร้อย';
        } else {
            $error_message = 'เกิดข้อผิดพลาด: ' . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $sql = "DELETE FROM user WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $success_message = 'ลบผู้ใช้เรียบร้อย';
        } else {
            $error_message = 'เกิดข้อผิดพลาด: ' . $conn->error;
        }
        $stmt->close();
    }
}
// ...existing code...
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติผู้ใช้</title>
    <link href="../bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/twbs/bootstrap-icons/font/bootstrap-icons.css">
    <link href="../assets/logo-mednu.png" rel="icon" type="image/png">
</head>
<body style="background: #FFFAA3;">
   <?php require_once(__DIR__ . '/../includes/header.php'); ?>
    <?php
    $sql = "SELECT * FROM user WHERE status='pending' ORDER BY id DESC";
    $result = $conn->query($sql);
    ?>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card p-5 border-edit" style="border-radius: 16px; min-width:350px; max-width:900px; width:100%; font-size:1.15rem;">
            <h2 class="text-center mb-4 " style="font-size:2rem;"><b>อนุมัติผู้ใช้ใหม่</b></h2>
            <?php if ($success_message): ?>
                <div class="alert alert-success text-center" style="font-size:1.1rem;"> <?php echo $success_message; ?> </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center" style="font-size:1.1rem;"> <?php echo $error_message; ?> </div>
            <?php endif; ?>
            <div style="overflow-x:auto;">
            <table class="table table-bordered custom-border align-middle text-center" style="font-size:1.05rem; min-width:700px;">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>แผนก</th>
                        <th>อีเมล</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . (isset($row['user_name']) ? htmlspecialchars($row['user_name']) : '-') . '</td>';
                        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                        echo '<td>';
                        echo '<form method="post" style="display:inline-block;">';
                        echo '<input type="hidden" name="user_id" value="' . $row['id'] . '">';
                        echo '<button type="submit" name="approve" class="btn" style="background-color:#1E3A8A; color:white; btn-sm me-1">อนุมัติ</button>';
                        echo '<button type="submit" name="reject" class="btn btn-warning btn-sm">ปฏิเสธ</button>';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">ไม่มีผู้ใช้รออนุมัติ</td></tr>';
                }
                ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
<script src="../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
