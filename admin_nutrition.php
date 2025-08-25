<?php
session_start();
require 'db.php'; // PDO -> $pdo

// Only admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$success = "";
$error   = "";

// CSRF (simple)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Helper: fetch user row
function getUser(PDO $pdo, int $id) {
    $st = $pdo->prepare("SELECT id, fullname, email, fitness_goal, calorie_target FROM users WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

// Helper: fetch meal plans
function getPlans(PDO $pdo) {
    $st = $pdo->query("SELECT id, plan_name, goal, calories, protein, carbs, fats FROM meal_plans ORDER BY goal DESC, plan_name ASC");
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// POST actions when a user is selected
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $uid = (int)$_POST['user_id'];

        // Update targets (goal + calories)
        if (isset($_POST['update_targets'])) {
            $goal = trim($_POST['fitness_goal'] ?? 'maintenance');
            $cal  = (int)($_POST['calorie_target'] ?? 2000);
            $st = $pdo->prepare("UPDATE users SET fitness_goal = ?, calorie_target = ?, updated_at = NOW() WHERE id = ?");
            if ($st->execute([$goal, $cal, $uid])) $success = "Targets updated."; else $error = "Failed to update targets.";
        }

        // Apply plan (sets calorie_target to plan's calories)
        if (isset($_POST['apply_plan'])) {
            $planId = (int)($_POST['plan_id'] ?? 0);
            $st = $pdo->prepare("SELECT calories FROM meal_plans WHERE id = ?");
            $st->execute([$planId]);
            $plan = $st->fetch(PDO::FETCH_ASSOC);
            if ($plan) {
                $st = $pdo->prepare("UPDATE users SET calorie_target = ?, updated_at = NOW() WHERE id = ?");
                if ($st->execute([(int)$plan['calories'], $uid])) $success = "Plan applied (calorie target updated).";
                else $error = "Failed to apply plan.";
            } else {
                $error = "Plan not found.";
            }
        }

        // Add meal
        if (isset($_POST['add_meal'])) {
            $date   = date('Y-m-d');
            $type   = trim($_POST['meal_type'] ?? 'breakfast');
            $name   = trim($_POST['meal_name'] ?? '');
            $time   = trim($_POST['meal_time'] ?? date('H:i'));
            $cal    = (int)($_POST['calories'] ?? 0);
            $prot   = (float)($_POST['protein'] ?? 0);
            $carb   = (float)($_POST['carbs'] ?? 0);
            $fat    = (float)($_POST['fats'] ?? 0);
            $serve  = trim($_POST['serving_size'] ?? '');
            $notes  = trim($_POST['notes'] ?? '');

            $st = $pdo->prepare("INSERT INTO meals
                (user_id, date, meal_type, meal_name, meal_time, calories, protein, carbs, fats, serving_size, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($st->execute([$uid, $date, $type, $name, $time, $cal, $prot, $carb, $fat, $serve, $notes])) $success = "Meal added.";
            else $error = "Failed to add meal.";
        }

        // Update meal
        if (isset($_POST['update_meal'])) {
            $mid   = (int)$_POST['meal_id'];
            $type  = trim($_POST['meal_type'] ?? 'breakfast');
            $name  = trim($_POST['meal_name'] ?? '');
            $time  = trim($_POST['meal_time'] ?? date('H:i'));
            $cal   = (int)($_POST['calories'] ?? 0);
            $prot  = (float)($_POST['protein'] ?? 0);
            $carb  = (float)($_POST['carbs'] ?? 0);
            $fat   = (float)($_POST['fats'] ?? 0);
            $serve = trim($_POST['serving_size'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            // ensure meal belongs to user
            $chk = $pdo->prepare("SELECT id FROM meals WHERE id = ? AND user_id = ?");
            $chk->execute([$mid, $uid]);
            if ($chk->fetch()) {
                $st = $pdo->prepare("UPDATE meals SET meal_type=?, meal_name=?, meal_time=?, calories=?, protein=?, carbs=?, fats=?, serving_size=?, notes=? WHERE id = ?");
                if ($st->execute([$type, $name, $time, $cal, $prot, $carb, $fat, $serve, $notes, $mid])) $success = "Meal updated.";
                else $error = "Failed to update meal.";
            } else {
                $error = "Meal not found for this user.";
            }
        }

        // Delete single meal
        if (isset($_POST['delete_meal'])) {
            $mid = (int)$_POST['meal_id'];
            $st  = $pdo->prepare("DELETE FROM meals WHERE id = ? AND user_id = ?");
            if ($st->execute([$mid, $uid])) $success = "Meal deleted."; else $error = "Delete failed.";
        }

        // Delete all today's meals
        if (isset($_POST['delete_today'])) {
            $today = date('Y-m-d');
            $st = $pdo->prepare("DELETE FROM meals WHERE user_id = ? AND date = ?");
            if ($st->execute([$uid, $today])) $success = "All today's meals deleted.";
            else $error = "Failed to delete today's meals.";
        }

        // Delete ALL meals for this user (whole nutrition plan history)
        if (isset($_POST['delete_all_meals'])) {
            $st = $pdo->prepare("DELETE FROM meals WHERE user_id = ?");
            if ($st->execute([$uid])) $success = "All meals for this user deleted.";
            else $error = "Failed to delete meals.";
        }
    }
}

// If managing a specific user
$manageId = isset($_GET['user']) ? (int)$_GET['user'] : (int)($_POST['user_id'] ?? 0);
$managedUser = $manageId ? getUser($pdo, $manageId) : null;

if ($managedUser) {
    // Fetch today’s meals for that user
    $today = date('Y-m-d');
    $st = $pdo->prepare("SELECT * FROM meals WHERE user_id = ? AND date = ? ORDER BY meal_time ASC, id ASC");
    $st->execute([$manageId, $today]);
    $meals = $st->fetchAll(PDO::FETCH_ASSOC);

    $plans = getPlans($pdo);
}

// Fetch all users for the first screen
$users = $pdo->query("SELECT id, fullname, email FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Include your reusable admin header
include 'admin_header.php';
?>

<style>
  /* Container & Cards */
  .container { max-width:min(1200px, 100% - 32px); margin:24px auto; padding:0 16px; }
  .card { background:#fff; border-radius:14px; box-shadow:0 6px 24px rgba(0,0,0,.06); padding:clamp(16px,2.2vw,20px); margin-bottom:18px; border:1px solid #e7ecf1; }
  h1 { margin:8px 0 16px; font-size:clamp(20px, 3.4vw, 24px); }
  h2 { margin:0 0 12px; font-size:clamp(18px, 2.8vw, 20px); }
  h3 { margin:0 0 10px; font-size:clamp(16px, 2.4vw, 18px); }

  /* Dark mode */
  .dark .card{background:#0f1b24;color:#e6eef8;border:1px solid #193243}
  .dark table thead th{background:#132233; color:#e6eef8; border-bottom-color:#203042;}
  .dark td{border-bottom-color:#203042}

  /* Table */
  .table-wrap{ overflow-x:auto; border-radius:12px; }
  table{ width:100%; border-collapse:collapse; min-width:760px; }
  th,td{ padding:12px 10px; border-bottom:1px solid #e5e7eb; text-align:left }
  table thead th{ background:#eef3f8; font-weight:700; }
  tbody tr:hover{ background:rgba(74,143,231,.06); }

  /* Buttons */
  .btn { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:10px; border:0; cursor:pointer; font-weight:700; color:#fff; text-decoration:none; font-size:clamp(14px,2.2vw,16px); }
  .btn-blue { background:#4a8fe7; }
  .btn-red { background:#dc2626; }
  .btn-gray { background:#6b7280; color:#fff !important; }
  .actions { display:flex; gap:10px; margin-top:12px; flex-wrap:wrap; }
  .btn-stack{ display:flex; gap:8px; flex-wrap:wrap; }
  @media (max-width:640px){
    .btn, .actions .btn, .btn-stack .btn, .actions form, .btn-stack form { width:100%; justify-content:center; }
  }

  /* Forms */
  label { display:block; font-weight:600; margin-top:8px; }
  input[type="text"], input[type="email"], input[type="number"], input[type="time"], select, textarea {
      width:95%; padding:12px 14px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; color:#111827;
  }
  textarea{ resize:vertical; }
  .row2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .row1 { display:grid; grid-template-columns:1fr; gap:10px; }
  @media (max-width:900px){ .row2{ grid-template-columns:1fr; } }

  /* Alerts */
  .alert { padding:12px 14px; border-radius:10px; margin:12px 0; font-weight:600; }
  .ok { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
  .bad { background:#fef2f2; color:#7f1d1d; border:1px solid #fecaca; }
  .dark .ok{ background:#0f2a22; color:#9af0c9; border-color:#1f5c4b; }
  .dark .bad{ background:#2a1212; color:#f2b5b5; border-color:#6f2a2a; }
</style>

<div class="container">
  <h1><i class="fa-solid fa-utensils"></i> Admin · Nutrition</h1>

  <?php if(!empty($success)): ?>
    <div class="alert ok"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if(!empty($error)): ?>
    <div class="alert bad"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php if (!$managedUser): ?>
    <!-- First interface: list users -->
    <div class="card">
      <h2>Available Accounts</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>ID</th><th>Full Name</th><th>Email</th><th style="width:180px;">Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?php echo (int)$u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                  <div class="btn-stack">
                    <a class="btn btn-blue" href="admin_nutrition.php?user=<?php echo (int)$u['id']; ?>">
                      <i class="fa-solid fa-pen-to-square"></i> Manage
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
              <tr><td colspan="4">No users found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php else: ?>
    <!-- Manage specific user -->
    <div class="card">
      <div class="actions" style="justify-content:space-between;">
        <h2>Managing: <?php echo htmlspecialchars($managedUser['fullname']); ?> (ID #<?php echo $managedUser['id']; ?>)</h2>
        <div class="btn-stack">
          <a class="btn btn-gray" href="admin_nutrition.php"><i class="fa-solid fa-arrow-left"></i> Back to users</a>
        </div>
      </div>

      <div class="row2">
        <!-- Targets -->
        <form method="post" class="card" style="margin:0;">
          <h3>Targets</h3>
          <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
          <input type="hidden" name="user_id" value="<?php echo (int)$managedUser['id']; ?>">
          <label>Fitness Goal</label>
          <select name="fitness_goal" required>
            <?php
              $goals = ['weight_loss'=>'Weight Loss','muscle_gain'=>'Muscle Gain','endurance'=>'Endurance','maintenance'=>'Maintenance'];
              $cur = $managedUser['fitness_goal'] ?? 'maintenance';
              foreach ($goals as $k=>$v) {
                  $sel = $k === $cur ? 'selected' : '';
                  echo "<option value=\"$k\" $sel>$v</option>";
              }
            ?>
          </select>
          <label>Calorie Target</label>
          <input type="number" name="calorie_target" min="1000" max="6000" value="<?php echo (int)($managedUser['calorie_target'] ?? 2000); ?>" required>
          <div class="actions">
            <button class="btn btn-blue" type="submit" name="update_targets"><i class="fa-solid fa-save"></i> Save Targets</button>
          </div>
        </form>

        <!-- Apply Plan -->
        <form method="post" class="card" style="margin:0;">
          <h3>Apply Meal Plan</h3>
          <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
          <input type="hidden" name="user_id" value="<?php echo (int)$managedUser['id']; ?>">
          <label>Plan</label>
          <select name="plan_id" required>
            <option value="">Choose a plan</option>
            <?php foreach ($plans as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>">
                <?php echo htmlspecialchars($p['plan_name']); ?> (<?php echo htmlspecialchars($p['goal']); ?>) — <?php echo (int)$p['calories']; ?> cal
              </option>
            <?php endforeach; ?>
          </select>
          <div class="actions">
            <button class="btn btn-blue" type="submit" name="apply_plan"><i class="fa-solid fa-bolt"></i> Apply Plan</button>
          </div>
          <p style="margin:.5rem 0 0; color:#6b7280;">This sets the user's calorie target to the plan's calories.</p>
        </form>
      </div>
    </div>

    <!-- Today's meals -->
    <div class="card">
      <div class="actions" style="justify-content:space-between;">
        <h2>Today's Meals (<?php echo date('Y-m-d'); ?>)</h2>
        <div class="btn-stack">
          <form class="inline-form" method="post" onsubmit="return confirm('Delete all meals for today?');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="user_id" value="<?php echo (int)$managedUser['id']; ?>">
            <button class="btn btn-red" type="submit" name="delete_today"><i class="fa-solid fa-trash"></i> Delete Today</button>
          </form>
          <form class="inline-form" method="post" onsubmit="return confirm('Delete ALL meals for this user? This cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="user_id" value="<?php echo (int)$managedUser['id']; ?>">
            <button class="btn btn-red" type="submit" name="delete_all_meals"><i class="fa-solid fa-skull-crossbones"></i> Delete ALL</button>
          </form>
        </div>
      </div>

      <?php if (empty($meals)): ?>
        <p>No meals logged today.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Meal</th><th>Time</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fats</th><th>Serving</th><th>Notes</th><th style="width:220px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($meals as $m): ?>
                <tr>
                  <td><?php echo htmlspecialchars(ucfirst($m['meal_type']).' — '.$m['meal_name']); ?></td>
                  <td><?php echo htmlspecialchars(substr($m['meal_time'],0,5)); ?></td>
                  <td><?php echo (int)$m['calories']; ?></td>
                  <td><?php echo (float)$m['protein']; ?> g</td>
                  <td><?php echo (float)$m['carbs']; ?> g</td>
                  <td><?php echo (float)$m['fats']; ?> g</td>
                  <td><?php echo htmlspecialchars($m['serving_size'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($m['notes'] ?? ''); ?></td>
                  <td>
                    <!-- Edit form in-row -->
                    <details>
                      <summary class="btn btn-gray" style="display:inline-flex;">Edit</summary>
                      <form method="post" class="card" style="margin-top:8px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="user_id" value="<?php echo (int)$managedUser['id']; ?>">
                        <input type="hidden" name="meal_id" value="<?php echo (int)$m['id']; ?>">

                        <label>Meal Type</label>
                        <select name="meal_type">
                          <?php
                            $types = ['breakfast','lunch','dinner','snack'];
                            foreach ($types as $t) {
                                $sel = $t === $m['meal_type'] ? 'selected' : '';
                                echo "<option value=\"$t\" $sel>".ucfirst($t)."</option>";
                            }
                          ?>
                        </select>

                        <label>Meal Name</label>
                        <input type="text" name="meal_name" value="<?php echo htmlspecialchars($m['meal_name']); ?>" required>

                        <label>Time</label>
                        <input type="time" name="meal_time" value="<?php echo htmlspecialchars(substr($m['meal_time'],0,5)); ?>" required>

                        <div class="row2">
                          <div>
                            <label>Calories</label>
                            <input type="number" name="calories" value="<?php echo (int)$m['calories']; ?>" required>
                          </div>
                          <div>
                            <label>Protein (g)</label>
                            <input type="number" step="0.1" name="protein" value="<?php echo (float)$m['protein']; ?>" required>
                          </div>
                          <div>
                            <label>Carbs (g)</label>
                            <input type="number" step="0.1" name="carbs" value="<?php echo (float)$m['carbs']; ?>" required>
                          </div>
                          <div>
                            <label>Fats (g)</label>
                            <input type="number" step="0.1" name="fats" value="<?php echo (float)$m['fats']; ?>" required>
                          </div>
                        </div>

                        <label>Serving Size</label>
                        <input type="text" name="serving_size" value="<?php echo htmlspecialchars($m['serving_size'] ?? ''); ?>">

                        <label>Notes</label>
                        <textarea name="notes" rows="2"><?php echo htmlspecialchars($m['notes'] ?? ''); ?></textarea>

                        <div class="actions">
                          <button class="btn btn-blue" type="submit" name="update_meal"><i class="fa-solid fa-save"></i> Save</button>
                        </div>
                      </form>
                    </details>

                    <!-- Delete single -->
                    <form class="inline-form" method="post" onsubmit="return confirm('Delete this meal?');">
                      <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                      <input type="hidden" name="user_id" value="<?php echo (int)$managedUser['id']; ?>">
                      <input type="hidden" name="meal_id" value="<?php echo (int)$m['id']; ?>">
                      <button class="btn btn-red" type="submit" name="delete_meal"><i class="fa-solid fa-trash"></i> Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Add meal -->
    <div class="card">
      <h2>Add Meal</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
        <input type="hidden" name="user_id" value="<?php echo (int)$managedUser['id']; ?>">

        <label>Meal Type</label>
        <select name="meal_type">
          <option value="breakfast">Breakfast</option>
          <option value="lunch">Lunch</option>
          <option value="dinner">Dinner</option>
          <option value="snack">Snack</option>
        </select>

        <label>Meal Name</label>
        <input type="text" name="meal_name" placeholder="e.g. Chicken salad" required>

        <div class="row2">
          <div>
            <label>Time</label>
            <input type="time" name="meal_time" value="<?php echo date('H:i'); ?>" required>
          </div>
          <div>
            <label>Calories</label>
            <input type="number" name="calories" required>
          </div>
          <div>
            <label>Protein (g)</label>
            <input type="number" step="0.1" name="protein" required>
          </div>
          <div>
            <label>Carbs (g)</label>
            <input type="number" step="0.1" name="carbs" required>
          </div>
          <div>
            <label>Fats (g)</label>
            <input type="number" step="0.1" name="fats" required>
          </div>
        </div>

        <label>Serving Size (optional)</label>
        <input type="text" name="serving_size" placeholder="e.g. 1 cup">

        <label>Notes (optional)</label>
        <textarea name="notes" rows="2" placeholder="Any notes"></textarea>

        <div class="actions">
          <button class="btn btn-blue" type="submit" name="add_meal"><i class="fa-solid fa-plus"></i> Add Meal</button>
        </div>
      </form>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
