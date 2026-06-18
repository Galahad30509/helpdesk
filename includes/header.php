<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$BASE             = $BASE             ?? '/webreport_repair';
$page_title       = $page_title       ?? 'ระบบแจ้งซ่อมครุภัณฑ์';
$brand_title      = $brand_title      ?? "ระบบแจ้งซ่อมครุภัณฑ์\nงานบริหารเทคโนโลยีสารสนเทศ";
$brand_href       = $brand_href       ?? $BASE . '/admin/receive_repair_requests.php';
$active_menu      = $active_menu      ?? '';
$header_mode      = $header_mode      ?? 'default'; // default|minimal
$header_cta_label = $header_cta_label ?? '';
$header_cta_href  = $header_cta_href  ?? '#';
$brand_clickable  = $brand_clickable  ?? true;
$logout_next      = $logout_next      ?? ($BASE . '/index.php');

/* ลิงก์หลักของเมนู */
$LINK_HOME        = $BASE . '/admin/receive_repair_requests.php';
$LINK_HISTORY     = $BASE . '/admin/repair_history.php';
$LINK_STATISTICS  = $BASE . '/admin/statistics.php';
$LINK_CONFIRM     = $BASE . '/admin/user_approval.php';
$LINK_ADMIN       = $BASE . '/admin/';

$user_display = 'admin'; // Default for admin users
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="icon" type="image/png" href="<?= $BASE ?>/logo-mednu.png">
  <link rel="stylesheet" href="<?= $BASE ?>/bootstrap-5.3.7-dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
  <style>
    .user-chip{ border:1px solid #ffd08a; background:#fffbe8; color:#784800; font-weight:600; padding:.35rem .65rem; border-radius:999px; }
    body { background: linear-gradient(90deg,#fffbe8 0%, #fff6e3 100%); font-family: 'Sarabun', sans-serif; }
    .navbar { background: linear-gradient(90deg,#ffac1c 60%,#fffbe8 100%); box-shadow: 0 2px 12px #ffb2401a; padding:.7rem 0; position:relative; z-index:1100; }
    .navbar .container-fluid { display:flex; align-items:center; }
    .navbar-brand { display:flex; align-items:center; gap:12px; font-weight:bold; color:#784800 !important; font-size:1.17rem; white-space:normal; line-height:1.2; }
    .logo-agency { width:44px; height:44px; }
    .navbar-nav .nav-link { color:#1a2438 !important; font-weight:500; font-size:1.05rem; margin-right:1.2rem; }
    .navbar-nav .nav-link.active { color:#e67c13 !important; }
    .logout-btn { display:inline-flex; align-items:center; white-space:nowrap; padding:.45rem 1rem; line-height:1.2; border-radius:2rem; font-weight:500; font-size:1rem; border-width:2px; }
  
    .custom-border th,
    .custom-border td {
      border: 4px solid orange !important;
    }
    .custom-border {
      border: 2px solid black !important;
    }
    .border-edit{
      border: 2px solid gray !important;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <?php
      $brand_inner = '
        <img src="'. $BASE .'/assets/logo-mednu.png" class="logo-agency" alt="Logo">
        ' . nl2br(htmlspecialchars($brand_title, ENT_QUOTES, 'UTF-8'));
    ?>

    <?php if ($brand_clickable && !empty($brand_href)): ?>
      <a class="navbar-brand" href="<?= htmlspecialchars($brand_href) ?>"><?= $brand_inner ?></a>
    <?php else: ?>
      <div class="navbar-brand pe-none" style="cursor:default;"><?= $brand_inner ?></div>
    <?php endif; ?>

    <?php if ($header_mode === 'default'): ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
              aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link <?= $active_menu==='home'?'active':'' ?>" href="<?= $LINK_HOME ?>"><h5>รับเรื่องซ่อม</h5></a></li>
          <li class="nav-item"><a class="nav-link <?= $active_menu==='history'?'active':'' ?>" href="<?= $LINK_HISTORY ?>"><h5>ประวัติการซ่อม</h5></a></li>
          <li class="nav-item"><a class="nav-link <?= $active_menu==='return'?'active':'' ?>" href="<?= $LINK_STATISTICS ?>"><h5>สถิติ</h5></a></li>
          <li class="nav-item"><a class="nav-link <?= $active_menu==='return'?'active':'' ?>" href="<?= $LINK_CONFIRM ?>"><h5>อนุมัติผู้ใช้</h5></a></li>
          <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li class="nav-item"><a class="nav-link <?= $active_menu==='admin-users'?'active':'' ?>" href="<?= $LINK_ADMIN ?>"><h5>จัดการผู้ใช้</h5></a></li>
          <?php endif; ?>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <?php if ($user_display): ?>
            <span class="user-chip">👤 <?= htmlspecialchars($user_display) ?></span>
          <?php endif; ?>
          <a href="<?= $BASE ?>/index.php?next=<?= urlencode($logout_next) ?>"
             class="btn btn-outline-danger logout-btn ms-lg-2"
             onclick="return confirm('ต้องการออกจากระบบหรือไม่?')">
            <img src="<?= $BASE ?>/assets/user-logout.png" alt="logout" style="width:22px; height:22px; vertical-align:middle; margin-right:6px;">
            ออกจากระบบ
          </a>
        </div>
      </div>

    <?php else: /* minimal */ ?>
      <div class="ms-auto">
        <?php if ($header_cta_label): ?>
          <a class="btn btn-primary" href="<?= htmlspecialchars($header_cta_href) ?>">
            <?= htmlspecialchars($header_cta_label) ?>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</nav>