<?php
session_start();
require 'db.php'; // PDO -> $pdo

// Only admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success = "";
$error = "";
$editUser = null;

/* ---------------------------
   DELETE USER (POST)
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                $success = "User #$id deleted successfully.";
            } else {
                $error = "No user was deleted (already removed or not found).";
            }
        } catch (PDOException $e) {
            $error = "Failed to delete user. If other data references this user, delete those first or use ON DELETE CASCADE.";
        }
    } else {
        $error = "Invalid user ID.";
    }
}

/* ---------------------------
   UPDATE USER (POST)
---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id       = (int)$_POST['id'];
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $age      = (int)$_POST['age'];
    $gender   = trim($_POST['gender']);
    $height   = (float)$_POST['height'];
    $weight   = (float)$_POST['weight'];
    $fitness_goal   = trim($_POST['fitness_goal']);
    $calorie_target = (int)$_POST['calorie_target'];

    $password = $_POST['password'] ?? '';
    $set = "fullname = ?, email = ?, age = ?, gender = ?, height = ?, weight = ?, fitness_goal = ?, calorie_target = ?";
    $params = [$fullname, $email, $age, $gender, $height, $weight, $fitness_goal, $calorie_target];

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $set .= ", password = ?";
        $params[] = $hashed;
    }
    $params[] = $id;

    $sql = "UPDATE users SET $set WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        $success = "User updated successfully!";
        if (isset($_GET['edit']) && (int)$_GET['edit'] === $id) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } else {
        $error = "Failed to update user.";
    }
}

/* ---------------------------
   LOAD USER FOR EDIT (GET)
---------------------------- */
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------------------------
   FETCH ALL USERS (ASC)
---------------------------- */
$stmt = $pdo->query("SELECT id, fullname, email, age, gender, height, weight, fitness_goal, calorie_target FROM users ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Header (your component)
include 'admin_header.php';
?>

<!-- Responsive page styles -->
<style>
  :root{
    --card:#ffffff;
    --ink:#0f172a;
    --muted:#6b7280;
    --th-bg:#f1f5f9;
    --th-ink:#0f172a;
    --row-bd:#e5e7eb;
    --shadow:0 6px 24px rgba(0,0,0,.06);
    --primary:#4a8fe7; --danger:#dc2626; --gray:#6b7280;
    --radius:14px;
  }
  .dark{
    --card:#0f1b24; --ink:#e6eef8; --muted:#9fb3c8;
    --th-bg:#0f1b24; --th-ink:#e6eef8; --row-bd:#193243;
    --shadow:0 10px 30px rgba(0,0,0,.35);
  }

  body{ color:var(--ink); }
  .container{ max-width:min(1200px, 100vw - 32px); margin:24px auto; padding:0 20px; }
  .card{ background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow); padding:clamp(14px,2.2vw,20px); border:1px solid var(--row-bd); }
  h1{ margin:8px 0 16px; font-size:clamp(20px,3.6vw,28px); }
  h2{ margin:0 0 12px; font-size:clamp(16px,2.6vw,20px); }

  /* table */
  .table-wrap{ overflow-x:auto; border-radius:12px; }
  table{ width:100%; border-collapse:collapse; min-width:780px; }
  thead th{ background:var(--th-bg); color:var(--th-ink); font-weight:700; padding:14px 10px; border-bottom:1px solid var(--row-bd); text-align:left; }
  tbody td{ padding:14px 10px; border-bottom:1px solid var(--row-bd); }
  tbody tr:hover{ background:rgba(74,143,231,.06); }
  th.actions, td.actions{ white-space:nowrap; }

  /* buttons */
  .btn{ display:inline-flex; align-items:center; gap:8px; padding:10px 12px; border-radius:10px; border:0; cursor:pointer; font-weight:700; color:#fff; text-decoration:none; font-size:clamp(14px,2vw,16px); }
  .btn-blue{ background:var(--primary); }
  .btn-red{ background:var(--danger); }
  .btn-gray{ background:var(--gray); color:#fff; }
  .btn + .btn{ margin-left:8px; }
  .inline-form{ display:inline; margin:0; }

  /* form */
  label{ display:block; font-weight:700; margin-top:10px; }
  input[type="text"], input[type="email"], input[type="number"], input[type="password"], select{
      width:95%; padding:12px 14px; border:1px solid var(--row-bd); border-radius:12px; background:#fff; color:#111827; outline:none;
  }
  .dark input[type="text"], .dark input[type="email"], .dark input[type="number"], .dark input[type="password"], .dark select{
      background:#0f1b24; border-color:#29475b; color:#e6eef8;
  }
  .actions{ display:flex; gap:10px; margin-top:12px; flex-wrap:wrap; }

  /* alerts */
  .alert{ padding:12px 14px; border-radius:10px; margin:12px 0; font-weight:600; }
  .ok{ background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
  .bad{ background:#fef2f2; color:#7f1d1d; border:1px solid #fecaca; }
  .dark .ok{ background:#0f2a22; color:#9af0c9; border-color:#1f5c4b; }
  .dark .bad{ background:#2a1212; color:#f2b5b5; border-color:#6f2a2a; }

  /* responsive layout tweaks */
  @media (max-width: 920px){
    /* actions column stays readable */
    th:last-child{ width:180px; }
  }
  @media (max-width: 640px){
    .btn{ width:100%; justify-content:center; }
    .inline-form{ display:block; margin-top:8px; }
  }
</style>

<div class="container">
  <h1><i class="fa-solid fa-user"></i> Manage Users</h1>

  <?php if(!empty($success)): ?>
    <div class="alert ok"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if(!empty($error)): ?>
    <div class="alert bad"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- Users Table -->
  <div class="card">
    <h2>Available Accounts</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Height (cm)</th>
            <th>Weight (kg)</th>
            <th>Goal</th>
            <th>Calories</th>
            <th class="actions" style="width:220px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo (int)$u['id']; ?></td>
              <td><?php echo htmlspecialchars($u['fullname']); ?></td>
              <td><?php echo htmlspecialchars($u['email']); ?></td>
              <td><?php echo (int)$u['age']; ?></td>
              <td><?php echo htmlspecialchars($u['gender']); ?></td>
              <td><?php echo (float)$u['height']; ?></td>
              <td><?php echo (float)$u['weight']; ?></td>
              <td><?php echo htmlspecialchars($u['fitness_goal']); ?></td>
              <td><?php echo (int)$u['calorie_target']; ?></td>
              <td class="actions">
                <a class="btn btn-blue" href="admin_users.php?edit=<?php echo (int)$u['id']; ?>">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </a>

                <!-- Delete via POST -->
                <form class="inline-form delete-form" method="post" action="admin_users.php" onsubmit="return confirmDelete(<?php echo (int)$u['id']; ?>)">
                  <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                  <button type="submit" name="delete_user" class="btn btn-red">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($users)): ?>
            <tr><td colspan="10">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit Form -->
  <?php if ($editUser): ?>
    <div class="card">
      <h2>Edit User: <?php echo htmlspecialchars($editUser['fullname']); ?></h2>
      <form method="post" autocomplete="off">
        <input type="hidden" name="id" value="<?php echo (int)$editUser['id']; ?>">

        <label>Full Name</label>
        <input type="text" name="fullname" value="<?php echo htmlspecialchars($editUser['fullname']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>

        <label>Age</label>
        <input type="number" name="age" min="1" max="120" value="<?php echo (int)$editUser['age']; ?>" required>

        <label>Gender</label>
        <select name="gender" required>
          <option value="male"   <?php if($editUser['gender']==='male')   echo 'selected'; ?>>Male</option>
          <option value="female" <?php if($editUser['gender']==='female') echo 'selected'; ?>>Female</option>
          <option value="other"  <?php if($editUser['gender']==='other')  echo 'selected'; ?>>Other</option>
        </select>

        <label>Height (cm)</label>
        <input type="number" step="0.1" name="height" value="<?php echo (float)$editUser['height']; ?>" required>

        <label>Weight (kg)</label>
        <input type="number" step="0.1" name="weight" value="<?php echo (float)$editUser['weight']; ?>" required>

        <label>Fitness Goal</label>
        <input type="text" name="fitness_goal" value="<?php echo htmlspecialchars($editUser['fitness_goal']); ?>" required>

        <label>Calorie Target</label>
        <input type="number" name="calorie_target" value="<?php echo (int)$editUser['calorie_target']; ?>" required>

        <label>New Password (leave blank to keep current)</label>
        <input type="password" name="password" placeholder="Enter new password (optional)">

        <div class="actions">
          <button class="btn btn-blue" type="submit" name="update_user">
            <i class="fa-solid fa-save"></i> Save Changes
          </button>
          <a class="btn btn-gray" href="admin_users.php">
            <i class="fa-solid fa-xmark"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  <?php endif; ?>
</div>

<script>
  function confirmDelete(id) {
    return confirm('Are you sure you want to delete user #' + id + '? This action cannot be undone.');
  }
</script>

</body>
</html>
