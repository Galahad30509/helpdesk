<?php 
session_start(); 
$login_error = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {     
    $conn = new mysqli('localhost', 'root', '', 'repair_notification_system');     
    if ($conn->connect_error) {         
        die('Connection failed: ' . $conn->connect_error);     
    }     

    $username = $conn->real_escape_string($_POST['username']);     
    $password = $_POST['password'];      

    // ตรวจสอบ admin     
    $admin_user = '@min';     
    $admin_pass = '123';     
    if ($username === $admin_user && $password === $admin_pass) {         
        $_SESSION['admin'] = true;         
        header('Location: admin/receive_repair_requests.php');         
        exit();     
    }      

    // ตรวจสอบ user     
    $sql = "SELECT * FROM user WHERE user_name = ?";     
    $stmt = $conn->prepare($sql);     
    $stmt->bind_param('s', $username);     
    $stmt->execute();     
    $result = $stmt->get_result();     

    if ($row = $result->fetch_assoc()) {         
        // ✅ ปรับปรุง: ใช้ !isset() เช็กเผื่อไว้ก่อนเพื่อป้องกัน Warning: Undefined array key
        if (!isset($row['status']) || $row['status'] !== 'approved') {             
            $login_error = 'บัญชีนี้ยังไม่ได้รับการอนุมัติจากผู้ดูแลระบบ';         
        } elseif (password_verify($password, $row['password'])) {             
            $_SESSION['user_id'] = $row['id'];         
            $_SESSION['user_name'] = $row['user_name'];         
            $_SESSION['username'] = $row['user_name']; 
            header('Location: user/durable_articles.php');             
            exit();         
        } else {             
            $login_error = 'รหัสผ่านไม่ถูกต้อง';         
        }     
    } else {         
        $login_error = 'ไม่พบชื่อผู้ใช้';     
    }     

    $stmt->close();     
    $conn->close(); 
} 
?> 

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ - ระบบแจ้งซ่อม | คณะแพทยศาสตร์ ม.นเรศวร</title>
  <link href="bootstrap-5.3.7-dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="assets/logo-mednu.png" type="image/png">

  <style>
    body {
      background: linear-gradient(135deg, #fff3e0, #ffe0b2);
      font-family: "Prompt", sans-serif;
    }
    .navbar {
      background: linear-gradient(90deg, #ef6c00, #f57c00, #ffb74d);
    }
    .navbar-brand {
      color: #fff !important;
      font-weight: bold;
    }
    .btn-light {
      border-radius: 12px;
      font-weight: bold;
    }
    .card {
      border-radius: 20px;
      border: none;
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
      overflow: hidden;
    }
    .card-body {
      padding: 2rem;
    }
    h2 {
      color: #e65100;
      font-weight: bold;
    }
    .form-control {
      border-radius: 10px;
      padding: 12px;
      border: 1px solid #ffcc80;
    }
    .form-control:focus {
      border-color: #ff9800;
      box-shadow: 0 0 5px rgba(255,152,0,0.5);
    }
    .btn-primary {
      background: linear-gradient(90deg, #ef6c00, #f57c00);
      border: none;
      border-radius: 12px;
      padding: 12px;
      font-size: 16px;
      font-weight: bold;
    }
    .btn-primary:hover {
      background: linear-gradient(90deg, #e65100, #f57c00);
    }
    .hospital-side {
      background: url("assets/med.jpg") center/cover no-repeat;
      filter: brightness(0.95);
    }
    footer {
      text-align: center;
      margin-top: 40px;
      font-size: 14px;
      color: #e65100;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
      <img src="assets/logo-mednu.png" alt="Logo" style="width: 40px; height: 40px; margin-right: 10px;">
      <span class="navbar-brand"><h4>ระบบแจ้งซ่อมครุภัณฑ์</h4></span>
    </div>
    <a href="mix.php">
      <img src="assets/home(1).png" alt="หน้ารวมระบบ" style="width:28px; height:28px; vertical-align:middle;"> 
    </a>
  </div>
</nav>

  <div class="container my-5">
    <div class="card shadow-lg mx-auto" style="max-width: 950px;">
      <div class="row g-0">
        <div class="col-lg-6 hospital-side d-none d-lg-block"></div>
        <div class="col-lg-6 d-flex align-items-center">
          <div class="card-body">
            <h2 class="mb-4 text-center">เข้าสู่ระบบ</h2>
            <form action="#" method="post">
              <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" id="password" name="password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
              <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger mt-3 text-center"><?php echo $login_error; ?></div>
              <?php endif; ?>
              <div class="mt-3 text-center"> 
                <a href="register.php">สมัครสมาชิก</a>
              </div>  
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer>
    © 2025 คณะแพทยศาสตร์ มหาวิทยาลัยนเรศวร | ระบบแจ้งซ่อมครุภัณฑ์
  </footer>

  <script src="bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>