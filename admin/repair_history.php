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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการซ่อม</title>
    <link href="../bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/twbs/bootstrap-icons/font/bootstrap-icons.css">
    <link href="../assets/logo-mednu.png" rel="icon" type="image/png">
</head>
<body style="background: #FFFAA3;">
    <?php require_once(__DIR__ . '/../includes/header.php'); ?>
    <div class="container  min-vh-100 d-flex justify-content-center align-items-center">
        <div class="card border-edit bg-light text-dark p-5" style="border-radius: 16px; min-width:350px; max-width:1100px; width:100%; font-size:1.15rem;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0" style="font-size:2rem;"><b>ประวัติการซ่อม</b></h2>
                <form method="get" class="d-flex align-items-center gap-2">
                    <label for="departmentFilter" class="form-label mb-0">แผนก</label>
                    <select id="departmentFilter" name="department" class="form-select w-auto border-secondary">
                        <option value="">ทุกแผนก</option>
                        <option value="แผนกA"<?php echo (isset($_GET['department'])&&$_GET['department']=='แผนกA')?' selected':''; ?>>แผนกA</option>
                        <option value="แผนกB"<?php echo (isset($_GET['department'])&&$_GET['department']=='แผนกB')?' selected':''; ?>>แผนกB</option>
                        <option value="แผนกC"<?php echo (isset($_GET['department'])&&$_GET['department']=='แผนกC')?' selected':''; ?>>แผนกC</option>
                    </select>
                    <input type="text" name="cereal_number" class="form-control border-secondary" placeholder="ค้นหา Serial" value="<?php echo isset($_GET['cereal_number']) ? htmlspecialchars($_GET['cereal_number']) : ''; ?>">
                    <input type="text" name="machine_number" class="form-control border-secondary" placeholder="ค้นหา Machine" value="<?php echo isset($_GET['machine_number']) ? htmlspecialchars($_GET['machine_number']) : ''; ?>">
                    <button type="submit" class="btn btn-warning">กรอง</button>
                </form>
            </div>
            <div style="overflow-x:auto;">
            <table class="table table-bordered custom-border align-middle text-center table-light" style="font-size:1.05rem; min-width:1000px;">
                <thead style="font-size:1.15rem;">
                    <tr>
                        <th style="min-width:120px;">ชื่อผู้ส่ง</th>
                        <th style="min-width:100px;">แผนก</th>
                        <th style="min-width:120px;">เลขซีเรียล</th>
                        <th style="min-width:120px;">เลขเครื่อง</th>
                        <th style="min-width:220px;">ประวัติการซ่อม</th>
                        <th style="min-width:120px;">วันที่ล่าสุด</th>
                    </tr>
                </thead>
                <tbody>
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
                // ดึง repair_logs ที่อนุมัติแล้ว พร้อม durable_articles/user
                $sql = "SELECT r.*, d.cereal_number, d.machine_number, d.created_at, u.name AS user_name, u.department
                        FROM repair_logs r
                        LEFT JOIN durable_articles d ON r.durable_article_id = d.id
                        LEFT JOIN user u ON d.user_id = u.id
                        WHERE r.status='approved'
                        ORDER BY d.cereal_number, d.machine_number, r.repair_date DESC, r.id DESC";
                $result = $conn->query($sql);
                $grouped = [];
                $filter_department = isset($_GET['department']) ? $_GET['department'] : '';
                $filter_cereal = isset($_GET['cereal_number']) ? trim($_GET['cereal_number']) : '';
                $filter_machine = isset($_GET['machine_number']) ? trim($_GET['machine_number']) : '';
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if ($filter_department && $row['department'] !== $filter_department) continue;
                        if ($filter_cereal && stripos($row['cereal_number'], $filter_cereal) === false) continue;
                        if ($filter_machine && stripos($row['machine_number'], $filter_machine) === false) continue;
                        $key = $row['cereal_number'] . '|' . $row['machine_number'];
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'user_name' => $row['user_name'],
                                'department' => $row['department'],
                                'cereal_number' => $row['cereal_number'],
                                'machine_number' => $row['machine_number'],
                                'created_at' => $row['created_at'],
                                'logs' => []
                            ];
                        }
                        $grouped[$key]['logs'][] = [
                            'repair_history' => $row['repair_history'],
                            'repair_date' => $row['repair_date']
                        ];
                    }
                    // เรียงตาม department
                    usort($grouped, function($a, $b) {
                        return strcmp($a['department'], $b['department']);
                    });
                    foreach ($grouped as $item) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($item['user_name'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($item['department'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($item['cereal_number']) . '</td>';
                        echo '<td>' . htmlspecialchars($item['machine_number']) . '</td>';
                        echo '<td style="white-space:pre-line;">';
                        if (!empty($item['logs'])) {
                            echo '<ul class="list-unstyled mb-0">';
                            foreach ($item['logs'] as $log) {
                                echo '<li>';
                                echo '<span class="fw-bold" style="color:#1a237e">[' . thai_date($log['repair_date']) . ']</span> ';
                                echo htmlspecialchars($log['repair_history']);
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '-';
                        }
                        echo '</td>';
                        // วันที่ล่าสุด
                        $latest_date = !empty($item['logs']) ? $item['logs'][0]['repair_date'] : '-';
                        echo '<td>' . ($latest_date !== '-' ? thai_date($latest_date) : '-') . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">ไม่มีข้อมูลที่ได้รับการอนุมัติ</td></tr>';
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
