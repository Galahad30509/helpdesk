<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli('localhost', 'root', '', 'repair_notification_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefix = $conn->real_escape_string($_POST['prefix']);
    $name = $conn->real_escape_string($_POST['name']);
    $department = $conn->real_escape_string($_POST['department']);
    $email = $conn->real_escape_string($_POST['email']);
    $user_name = $conn->real_escape_string($_POST['user_name']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');

    $fullname = $prefix . $name;

    // ตรวจสอบ user_name หรือ email ซ้ำ
    $check_sql = "SELECT id FROM user WHERE user_name = ? OR email = ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ss', $user_name, $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo '<div class="alert alert-danger text-center">ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้ไปแล้ว</div>';
    } else {
        $sql = "INSERT INTO user (name, department, email, user_name, password, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $fullname, $department, $email, $user_name, $password, $created_at);
        if ($stmt->execute()) {
            echo '<div class="alert alert-success text-center">สมัครสมาชิกสำเร็จ!</div>';
        } else {
            echo '<div class="alert alert-danger text-center">เกิดข้อผิดพลาด: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>สมัครสมาชิก - ระบบแจ้งซ่อม | คณะแพทยศาสตร์ ม.นเรศวร</title>
  <link href="bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/logo-mednu.png" rel="icon" type="image/png">
  <style>
    body {
      background: linear-gradient(135deg, #ff9800, #ffffff);
      font-family: "Prompt", sans-serif;
    }
    .navbar {
      background: linear-gradient(90deg, #ff9800, #ffcc80);
    }
    .navbar-brand {
      color: #fff !important;
      font-weight: bold;
    }
    .card {
      border-radius: 20px;
      border: none;
      overflow: hidden;
    }
    .card-header {
      font-size: 1.7rem;
      font-weight: bold;
      color: #ffffff;
      background: #ff9800;
    }
    .form-control, .form-select {
      border-radius: 10px;
      padding: 12px;
      border: 1px solid #ddd;
    }
    .btn-primary {
      background: #ff9800;
      border: none;
      border-radius: 12px;
      padding: 12px;
      font-size: 16px;
      font-weight: bold;
    }
    .btn-primary:hover {
      background: #e68900;
    }
    footer {
      text-align: center;
      margin-top: 40px;
      font-size: 14px;
      color: #666;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg mb-4">
    <div class="container-fluid justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <img src="assets/logo-mednu.png" alt="Logo" style="width: 40px; height: 40px; margin-right: 10px;">
        <span class="navbar-brand">ระบบแจ้งซ่อมครุภัณฑ์</span>
      </div>
      <a href="index.php" class="btn btn-warning">เข้าสู่ระบบ</a>
    </div>
  </nav>

  <!-- Register Card -->
  <div class="container min-vh-100 d-flex justify-content-center align-items-center">
    <div class="card shadow-lg" style="min-width:350px; max-width:450px; width:100%;">
      <div class="card-header text-center p-4">สมัครสมาชิก</div>
      <div class="p-4">
        <form action="register.php" method="post">
          <div class="mb-3">
            <label for="prefix" class="form-label">คำนำหน้า</label>
            <select class="form-select" id="prefix" name="prefix" required>
              <option value="">เลือกคำนำหน้า</option>
              <option value="นาย">นาย</option>
              <option value="นาง">นาง</option>
              <option value="นางสาว">นางสาว</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="name" class="form-label">ชื่อ-สกุล</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="department" class="form-label">แผนก</label>
            <select class="form-select" id="department" name="department" required>
              <option value="">เลือกแผนก</option>
              <option value="แผนกA">แผนกA</option>
              <option value="แผนกB">แผนกB</option>
              <option value="แผนกC">แผนกC</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">อีเมล</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="user_name" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" id="user_name" name="user_name" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">รหัสผ่าน</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">สมัครสมาชิก</button>
          <?php if (isset($stmt) && $stmt->error): ?>
            <div class="alert alert-danger mt-3 text-center">เกิดข้อผิดพลาด: <?php echo $stmt->error; ?></div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <footer>
    © 2025 คณะแพทยศาสตร์ มหาวิทยาลัยนเรศวร | ระบบแจ้งซ่อม
  </footer>

  <script src="bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
