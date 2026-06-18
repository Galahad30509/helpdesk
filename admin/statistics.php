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
// ดึงปีที่มีข้อมูลจาก repair_logs
$years = [];
$sql_years = "SELECT DISTINCT YEAR(repair_date) AS y FROM repair_logs WHERE status='approved' ORDER BY y DESC";
$result_years = $conn->query($sql_years);
if ($result_years && $result_years->num_rows > 0) {
    while ($row = $result_years->fetch_assoc()) {
        $years[] = $row['y'];
    }
}
$default_year = count($years) > 0 ? $years[0] : date('Y');

// กรองแผนก
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';

// ดึงข้อมูลรายเดือนทุกปี (เก็บเป็น array ของปี)
$labels_month_all = [];
$counts_month_all = [];
foreach ($years as $y) {
    $labels = [];
    $counts = [];
    for ($m = 1; $m <= 12; $m++) {
        $labels[] = sprintf('%04d-%02d', $y, $m);
        $counts[] = 0;
    }
    $sql = "SELECT DATE_FORMAT(r.repair_date, '%Y-%m') AS label, COUNT(*) AS count
            FROM repair_logs r
            LEFT JOIN durable_articles d ON r.durable_article_id = d.id
            LEFT JOIN user u ON d.user_id = u.id
            WHERE r.status='approved' AND YEAR(r.repair_date) = $y"
            . ($filter_department ? " AND u.department = '" . $conn->real_escape_string($filter_department) . "'" : "") .
            " GROUP BY label ORDER BY label";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $idx = array_search($row['label'], $labels);
            if ($idx !== false) $counts[$idx] = (int)$row['count'];
        }
    }
    $labels_month_all[$y] = $labels;
    $counts_month_all[$y] = $counts;
}

// ดึงข้อมูลรายสัปดาห์ (เก็บเป็น array [ปี][เดือน] => labels, counts)
$labels_week_all = [];
$counts_week_all = [];
foreach ($years as $y) {
    for ($m = 1; $m <= 12; $m++) {
        $labels = [];
        $counts = [];
        // หาวันแรกและวันสุดท้ายของเดือน
        $firstDay = date('Y-m-d', strtotime("$y-$m-01"));
        $lastDay = date('Y-m-t', strtotime($firstDay));
        // หาสัปดาห์แรกและสุดท้ายของเดือน (ISO-8601, week starts Monday)
        $firstWeek = (int)date('W', strtotime($firstDay));
        $lastWeek = (int)date('W', strtotime($lastDay));
        if ($m == 1 && $firstWeek > 50) $firstWeek = 1;
        if ($m == 12 && $lastWeek == 1) $lastWeek = (int)date('W', strtotime("$y-12-24"));
        for ($w = $firstWeek; $w <= $lastWeek; $w++) {
            $labels[] = sprintf('%04d-%02d-W%02d', $y, $m, $w);
            $counts[] = 0;
        }
        // Query หาจำนวนงานซ่อมแต่ละสัปดาห์ในเดือนนั้น
        $sql = "SELECT WEEK(r.repair_date, 1) AS w, COUNT(*) AS count
                FROM repair_logs r
                LEFT JOIN durable_articles d ON r.durable_article_id = d.id
                LEFT JOIN user u ON d.user_id = u.id
                WHERE r.status='approved' AND YEAR(r.repair_date) = $y AND MONTH(r.repair_date) = $m"
                . ($filter_department ? " AND u.department = '" . $conn->real_escape_string($filter_department) . "'" : "") .
                " GROUP BY w ORDER BY w";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $idx = array_search(sprintf('%04d-%02d-W%02d', $y, $m, $row['w']), $labels);
                if ($idx !== false) $counts[$idx] = (int)$row['count'];
            }
        }
        $labels_week_all[$y][$m] = $labels;
        $counts_week_all[$y][$m] = $counts;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติ</title>
    <link href="../bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/twbs/bootstrap-icons/font/bootstrap-icons.css">
        <link href="../assets/logo-mednu.png" rel="icon" type="image/png">
        <style>           
            .card {
                background: #FFFAA3;
                color: #222;
                border-radius: 16px;
                box-shadow: 0 2px 12px #1a237e1a;
                border: none;
            }
            h3, h4 {
                color: #1a237e !important;
            }
            .btn-primary, .btn-outline-primary {
                background-color: #FF9900;
                color: #fff;
                border: 1px solid #1a237e;
            }
            .btn-outline-primary {
                background: #fff;
                color: #1a237e;
                border: 1px solid #FF9900;
            }
            .form-select, .form-control, .border-secondary {
                border-color: #1a237e !important;
            }
            .table {
                background: #FFF7E6;
            }
            th {
                background: #FFD699;
                color: #1a237e;
                font-weight: bold;
            }
            td {
                background: #FFF7E6;
                color: #222;
            }
            #chart-container {
                background: #FFF7E6;
                border-radius: 12px;
                box-shadow: 0 2px 8px #1a237e1a;
                padding: 1.5rem;
                margin-bottom: 2rem;
            }
            .text-info {
                color: #1a237e !important;
            }
        </style>
</head>
<body style="background: #FFFAA3;">
    <?php require_once(__DIR__ . '/../includes/header.php'); ?>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">
    <div class="card p-5 border-edit"  style="border-radius: 16px; min-width:350px; max-width:900px; width:100%;">
            <div class="d-flex  flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h2 class="mb-0" style="font-size:2rem;"><b>สถิติการซ่อม</b></h2>
                <form method="get" class="d-flex gap-2 align-items-center">
                    <select id="chartType" name="chartType" class="form-select w-auto border-secondary">
                        <option value="month"<?php echo (isset($_GET['chartType'])&&$_GET['chartType']=='month')?' selected':''; ?>>รายเดือน</option>
                        <option value="week"<?php echo (isset($_GET['chartType'])&&$_GET['chartType']=='week')?' selected':''; ?>>รายสัปดาห์</option>
                    </select>
                    <select id="yearSelect" name="year" class="form-select w-auto border-secondary" style="display:none;"></select>
                    <select id="departmentSelect" name="department" class="form-select w-auto border-secondary">
                        <option value="">ทุกแผนก</option>
                        <option value="แผนกA"<?php echo ($filter_department=='แผนกA')?' selected':''; ?>>แผนกA</option>
                        <option value="แผนกB"<?php echo ($filter_department=='แผนกB')?' selected':''; ?>>แผนกB</option>
                        <option value="แผนกC"<?php echo ($filter_department=='แผนกC')?' selected':''; ?>>แผนกC</option>
                    </select>
                    <button type="submit" class="btn" style="background:#FF9900; color:#000;">กรอง</button>
                </form>
                <button id="toggleChartType" type="button" class="btn" style="background:#1a237e; color:#fff; border:1px solid #fff;" class="ms-2">เปลี่ยนเป็นกราฟเส้น</button>
            </div>
            <div id="chart-container" style="background:#fff; border-radius:8px; padding:12px;">
                <canvas id="repairChart" style="background:#fff; border-radius:8px;"></canvas>
            </div>
            <div id="totalCount" class="mt-4 text-center fs-5 fw-bold text-info"></div>
        </div>
    </div>
<script src="../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.monthLabelsAll = <?php echo json_encode($labels_month_all); ?>;
window.monthCountsAll = <?php echo json_encode($counts_month_all); ?>;
window.years = <?php echo json_encode($years); ?>;
window.selectedYear = <?php echo json_encode($default_year); ?>;
window.labelsWeekAll = <?php echo json_encode($labels_week_all); ?>;
window.countsWeekAll = <?php echo json_encode($counts_week_all); ?>;
window.selectedChartType = <?php echo json_encode(isset($_GET['chartType']) ? $_GET['chartType'] : 'month'); ?>;
window.chartDisplayType = 'bar'; // default
</script>
<style>
/* Force Chart.js text to black for all chart elements */
#chart-container, #chart-container * {
    color: #222 !important;
    fill: #222 !important;
}
</style>
<script src="../assets/js/statistics-chart.js"></script>
</body>
</html>
