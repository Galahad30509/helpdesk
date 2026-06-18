<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// สมมติว่าเก็บชื่อผู้ใช้ไว้ใน $_SESSION['username']
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'ผู้ใช้';
?>
<nav class="navbar navbar-expand-lg mb-4 shadow-sm" 
     style="background: linear-gradient(90deg, #1565c0, #42a5f5);">
  <div class="container-fluid">
    <ul class="navbar-nav flex-row">
      <li class="nav-item me-3">
        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF'])=='receive_repair_requests.php') 
              ? 'active fw-bold text-light border-bottom border-2 border-light' 
              : 'text-white-50'; ?>"
           href="receive_repair_requests.php">
          รับเรื่องซ่อม
        </a>
      </li>
      <li class="nav-item me-3">
        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF'])=='repair_history.php') 
              ? 'active fw-bold text-light border-bottom border-2 border-light' 
              : 'text-white-50'; ?>" 
           href="repair_history.php">
          ประวัติการซ่อม
        </a>
      </li>
      <li class="nav-item me-3">
        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF'])=='statistics.php') 
              ? 'active fw-bold text-light border-bottom border-2 border-light' 
              : 'text-white-50'; ?>" 
           href="statistics.php">
          สถิติ
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF'])=='user_approval.php') 
              ? 'active fw-bold text-light border-bottom border-2 border-light' 
              : 'text-white-50'; ?>" 
           href="user_approval.php">
          อนุมัติผู้ใช้
        </a>
      </li>
    </ul>

    <form class="d-flex ms-auto align-items-center" action="../logout.php" method="post">
      <!-- กระดิ่ง -->
      <span class="me-3 text-warning" style="cursor:pointer;">
        <i id="bell-icon" class="bi bi-bell-fill bell-icon fs-5"></i>
      </span>
      <!-- แสดงชื่อผู้ใช้ -->    
      <button type="submit" class="btn btn-outline-light">ออกจากระบบ</button>
    </form>

    <!-- PNotify -->
    <script src="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.umd.min.js"></script>
    <link rel="stylesheet" 
          href="https://cdn.jsdelivr.net/npm/@pnotify/core@5.2.0/dist/PNotify.css" />

    <style>
    /* keyframes สำหรับกระดิ่ง */
    @keyframes bell-ring {
      0%   { transform: rotate(0); }
      15%  { transform: rotate(-15deg); }
      30%  { transform: rotate(15deg); }
      45%  { transform: rotate(-10deg); }
      60%  { transform: rotate(10deg); }
      75%  { transform: rotate(-5deg); }
      100% { transform: rotate(0); }
    }

    .bell-icon {
      display: inline-block;
      transform-origin: top center; /* หมุนจากจุดบน */
    }

    /* Hover แล้วสั่นต่อเนื่อง */
    .bell-icon:hover {
      animation: bell-ring 1s infinite;
    }
    </style>

    <?php
    // Query จำนวนรายการรอรับเรื่องซ่อม (pending)
    $pending_count = 0;
    $conn_navbar = new mysqli('localhost', 'root', '', 'repair_notification_system');
    if (!$conn_navbar->connect_error) {
        $result = $conn_navbar->query("SELECT COUNT(*) AS count FROM repair_logs WHERE status='pending'");
        if ($result && $row = $result->fetch_assoc()) {
            $pending_count = (int)$row['count'];
        }
    }
    ?>
    <script>
      function showStackBottomRight(type, text) {
        if (typeof window.stackBottomRight === 'undefined') {
          window.stackBottomRight = { dir1: 'up', dir2: 'left', firstpos1: 25, firstpos2: 25 };
        }
        const opts = {
          title: '',
          text: text || '',
          stack: window.stackBottomRight,
          delay: 2500,
          styling: 'brighttheme',
          addClass: 'pnotify-stack-bottomright',
        };
        switch (type) {
          case 'error': opts.title = 'เกิดข้อผิดพลาด'; opts.type = 'error'; break;
          case 'info': opts.title = 'แจ้งเตือน'; opts.type = 'info'; break;
          case 'success': opts.title = 'สำเร็จ'; opts.type = 'success'; break;
          default: opts.title = 'แจ้งเตือน'; opts.type = 'info';
        }
        if (window.PNotify && typeof window.PNotify.alert === 'function') {
          window.PNotify.alert(opts);
        } else {
          alert((opts.title ? opts.title + ': ' : '') + opts.text);
        }
      }

      document.addEventListener('DOMContentLoaded', function() {
        var bell = document.getElementById('bell-icon');
        if (bell) {
          bell.addEventListener('click', function() {
            showStackBottomRight('info', `รายการรอรับเรื่องซ่อม: <?php echo $pending_count; ?>`);
          });
        }
      });
    </script>
  </div>
</nav>
