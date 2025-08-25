<?php
// admin_header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Only allow admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kurus+ Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    :root{
      --primary:#4a8fe7; --secondary:#44c767; --dark:#1f2a36; --darker:#16202a;
      --light:#f7f9fc; --text:#2c3e50; --white:#fff;
    }
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:var(--light);color:var(--text);}
    header{position:sticky;top:0;z-index:1000;background:linear-gradient(135deg,var(--dark),var(--darker));color:#fff;box-shadow:0 2px 10px rgba(0,0,0,.15)}
    .nav{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:14px;padding:12px 20px}
    .logo{display:flex;align-items:center;font-weight:700;font-size:20px;letter-spacing:.3px}
    .logo i{color:var(--secondary);margin-right:10px}
    .links{display:flex;gap:10px;flex:1}
    .links a{color:#cfe2ff;text-decoration:none;padding:10px 14px;border-radius:10px;display:flex;align-items:center;gap:8px;transition:.2s}
    .links a:hover{background:#203042;color:#fff}
    .links a.active{background:var(--secondary);color:#fff}
    .right{display:flex;gap:10px}
    .btn{border:0;border-radius:10px;padding:8px 12px;font-weight:600;cursor:pointer}
    .btn-blue{background:var(--primary);color:#fff}
    .btn-blue:hover{filter:brightness(.95)}
    .btn-outline{background:transparent;color:#fff;border:1px solid #4b5a67}
    .container{max-width:1200px;margin:24px auto;padding:0 20px}
    .card{background:#fff;border-radius:14px;box-shadow:0 6px 24px rgba(0,0,0,.06);padding:20px}
    /* Dark mode */
    .dark body{background:#0e141a;color:#e6eef8}
    .dark .card{background:#0f1b24;border:1px solid #193243}
    .dark header{background:linear-gradient(135deg,#0f1b24,#0b131a)}
    .dark .links a{color:#a9c3ff}
    .dark .links a:hover{background:#122231}
    .dark .btn-outline{border-color:#29475b}
  </style>
  <script>
    // dark mode toggle keeps preference
    function toggleDark(){ 
      const d = document.documentElement;
      const on = d.classList.toggle('dark');
      localStorage.setItem('adminDark', on ? '1' : '0');
    }
    document.addEventListener('DOMContentLoaded',()=>{
      if(localStorage.getItem('adminDark')==='1'){
        document.documentElement.classList.add('dark');
      }
    });
  </script>
</head>
<body>
<header>
  <nav class="nav">
    <div class="logo"><i class="fa-solid fa-dumbbell"></i> Kurus+ Admin</div>
    <div class="links">
      <a href="admin_dashboard.php" class="<?= $current==='admin_dashboard.php'?'active':'' ?>"><i class="fa-solid fa-gauge"></i>Dashboard</a>
      <a href="admin_community.php" class="<?= $current==='admin_community.php'?'active':'' ?>"><i class="fa-solid fa-people-group"></i>Community</a>
      <a href="admin_users.php" class="<?= $current==='admin_users.php'?'active':'' ?>"><i class="fa-solid fa-user"></i>Users</a>
      <a href="admin_workouts.php" class="<?= $current==='admin_workouts.php'?'active':'' ?>"><i class="fa-solid fa-dumbbell"></i>Workouts</a>
      <a href="admin_nutrition.php" class="<?= $current==='admin_nutrition.php'?'active':'' ?>"><i class="fa-solid fa-utensils"></i>Nutrition</a>
      <a href="admin_settings.php" class="<?= $current==='admin_settings.php'?'active':'' ?>"><i class="fa-solid fa-gear"></i>Settings</a>
    </div>
    <div class="right">
      <button class="btn btn-outline" onclick="toggleDark()"><i class="fa-solid fa-circle-half-stroke"></i></button>
      <a class="btn btn-blue" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </nav>
</header>
