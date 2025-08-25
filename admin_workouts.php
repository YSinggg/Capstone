<?php
// admin_workouts.php
session_start();

// Only admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php'; // must define $pdo (PDO)
if (!isset($pdo)) {
    die('DB connection not found. Ensure db.php defines $pdo (PDO).');
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}

// Flash helpers
function set_flash($type,$msg){ $_SESSION["flash_$type"] = $msg; }
function get_flash($type){
    if(!empty($_SESSION["flash_$type"])){ $m=$_SESSION["flash_$type"]; unset($_SESSION["flash_$type"]); return $m; }
    return null;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$tab     = $_GET['tab'] ?? 'plans'; // 'plans' or 'workouts'

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_flash('error', 'Invalid request (CSRF).');
        header('Location: admin_workouts.php');
        exit();
    }

    $action  = $_POST['action'] ?? '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    // Validate user exists for actions that target a user
    if (in_array($action, [
        'save_plan','delete_plan','delete_all_plans',
        'save_workout','delete_workout','delete_all_workouts'
    ]) && $user_id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetchColumn()) {
            set_flash('error', 'User not found.');
            header('Location: admin_workouts.php');
            exit();
        }
    }

    try {
        /* ===== Workout Plans actions ===== */
        if ($action === 'save_plan') {
            $plan_id         = $_POST['plan_id'] !== '' ? (int)$_POST['plan_id'] : null;
            $plan_date       = $_POST['plan_date'] ?? '';
            $morning_routine = $_POST['morning_routine'] ?? '';
            $evening_activity= $_POST['evening_activity'] ?? '';
            $is_completed    = isset($_POST['is_completed']) ? 1 : 0;

            if (!$plan_date) throw new Exception('Plan date is required.');

            if ($plan_id) {
                $stmt = $pdo->prepare(
                    "UPDATE workout_plans
                     SET plan_date=?, morning_routine=?, evening_activity=?, is_completed=?
                     WHERE plan_id=? AND user_id=?"
                );
                $stmt->execute([$plan_date,$morning_routine,$evening_activity,$is_completed,$plan_id,$user_id]);
                set_flash('success','Workout plan updated.');
            } else {
                // Upsert by date
                $check = $pdo->prepare("SELECT plan_id FROM workout_plans WHERE user_id=? AND plan_date=?");
                $check->execute([$user_id,$plan_date]);
                $existing = $check->fetchColumn();

                if ($existing) {
                    $stmt = $pdo->prepare(
                        "UPDATE workout_plans
                         SET morning_routine=?, evening_activity=?, is_completed=?
                         WHERE plan_id=? AND user_id=?"
                    );
                    $stmt->execute([$morning_routine,$evening_activity,$is_completed,$existing,$user_id]);
                    set_flash('success','Workout plan updated for that date.');
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO workout_plans (user_id,plan_date,morning_routine,evening_activity,is_completed)
                         VALUES (?,?,?,?,?)"
                    );
                    $stmt->execute([$user_id,$plan_date,$morning_routine,$evening_activity,$is_completed]);
                    set_flash('success','Workout plan created.');
                }
            }
            header('Location: admin_workouts.php?user_id='.$user_id.'&tab=plans');
            exit();
        }

        if ($action === 'delete_plan') {
            $plan_id = (int)($_POST['plan_id'] ?? 0);
            if ($plan_id <= 0) throw new Exception('Invalid plan ID.');
            $stmt = $pdo->prepare("DELETE FROM workout_plans WHERE plan_id=? AND user_id=?");
            $stmt->execute([$plan_id,$user_id]);
            set_flash('success','Workout plan deleted.');
            header('Location: admin_workouts.php?user_id='.$user_id.'&tab=plans');
            exit();
        }

        if ($action === 'delete_all_plans') {
            $stmt = $pdo->prepare("DELETE FROM workout_plans WHERE user_id=?");
            $stmt->execute([$user_id]);
            set_flash('success','All workout plans deleted for this user.');
            header('Location: admin_workouts.php?user_id='.$user_id.'&tab=plans');
            exit();
        }

        /* ===== Individual Workouts actions ===== */
        if ($action === 'save_workout') {
            $workout_id      = $_POST['workout_id'] !== '' ? (int)$_POST['workout_id'] : null;
            $workout_type    = $_POST['workout_type'] ?? '';
            $workout_date    = $_POST['workout_date'] ?? '';
            $duration        = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null;
            $calories        = ($_POST['calories_burned'] !== '') ? (int)$_POST['calories_burned'] : null;
            $notes           = $_POST['notes'] ?? '';

            if (!$workout_type) throw new Exception('Workout type is required.');
            if (!$workout_date) throw new Exception('Date is required.');
            if ($duration === null || $duration <= 0) throw new Exception('Duration must be a positive number.');

            if ($workout_id) {
                $stmt = $pdo->prepare(
                    "UPDATE workouts
                     SET workout_type=?, workout_date=?, duration_minutes=?, calories_burned=?, notes=?
                     WHERE workout_id=? AND user_id=?"
                );
                $stmt->execute([$workout_type,$workout_date,$duration,$calories,$notes,$workout_id,$user_id]);
                set_flash('success','Workout updated.');
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO workouts (user_id, workout_type, workout_date, duration_minutes, calories_burned, notes)
                     VALUES (?,?,?,?,?,?)"
                );
                $stmt->execute([$user_id,$workout_type,$workout_date,$duration,$calories,$notes]);
                set_flash('success','Workout added.');
            }
            header('Location: admin_workouts.php?user_id='.$user_id.'&tab=workouts');
            exit();
        }

        if ($action === 'delete_workout') {
            $workout_id = (int)($_POST['workout_id'] ?? 0);
            if ($workout_id <= 0) throw new Exception('Invalid workout ID.');
            $stmt = $pdo->prepare("DELETE FROM workouts WHERE workout_id=? AND user_id=?");
            $stmt->execute([$workout_id,$user_id]);
            set_flash('success','Workout deleted.');
            header('Location: admin_workouts.php?user_id='.$user_id.'&tab=workouts');
            exit();
        }

        if ($action === 'delete_all_workouts') {
            $stmt = $pdo->prepare("DELETE FROM workouts WHERE user_id=?");
            $stmt->execute([$user_id]);
            set_flash('success','All workouts deleted for this user.');
            header('Location: admin_workouts.php?user_id='.$user_id.'&tab=workouts');
            exit();
        }

    } catch (Exception $e) {
        set_flash('error', $e->getMessage());
        $suffix = $user_id ? ('?user_id='.$user_id.'&tab=' . ($tab ?? 'plans')) : '';
        header('Location: admin_workouts.php' . $suffix);
        exit();
    }
}

include 'admin_header.php'; // your shared admin nav/header
?>
<style>
/* ========= Admin • Workouts – Responsive skin ========= */
:root{
  --bg: #f5f7fb;
  --card: #ffffff;
  --ink: #243244;
  --muted: #6c7a8a;

  --th-bg: #eef3f7;
  --th-ink:#1e2c3b;
  --row-border:#e7ecf1;
  --row-hover: rgba(74,143,231,.06);

  --primary:#4a8fe7;
  --primary-ink:#ffffff;
  --danger:#e04545;
  --danger-ink:#ffffff;

  --chip:#edf2f7;
  --chip-ink:#2b3a4a;

  --input-bg:#ffffff;
  --input-bd:#d7dee6;
  --input-ink:#1f2a36;
  --input-ph:#6f7f91;

  --shadow: 0 6px 24px rgba(0,0,0,.06);
}

.dark{
  --bg:#0e141a;
  --card:#0f1b24;
  --ink:#e6eef8;
  --muted:#a7b4c3;

  --th-bg: rgba(233,240,246,.16);
  --th-ink:#e6eef8;

  --row-border:#152635;
  --row-hover: rgba(74,143,231,.18);

  --chip:#132433;
  --chip-ink:#d8e6f5;

  --input-bg:#122233;
  --input-bd:#22384a;
  --input-ink:#e6eef8;
  --input-ph:#9fb2c5;

  --shadow: 0 10px 30px rgba(0,0,0,.35);
}

/* Layout */
body{ background:var(--bg); color:var(--ink); }
.container{ max-width:min(1200px, 100vw - 32px); margin:24px auto; padding:0 16px; }
.card{
  background:var(--card); border-radius:16px; box-shadow:var(--shadow);
  padding:clamp(14px, 2.2vw, 20px); margin:18px 0; border:1px solid var(--row-border);
}

/* Page title row & tabs */
.page-title{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.tabs{ display:flex; gap:10px; flex-wrap:wrap; }
.tabs a{
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 14px; border-radius:12px; text-decoration:none;
  background:var(--chip); color:var(--chip-ink); font-weight:700; border:1px solid transparent;
}
.tabs a:hover{ filter:brightness(.98); }
.tabs a.active{ background:var(--primary); color:#fff; }

/* Table */
.table-wrap{ overflow-x:auto; border-radius:12px; }
table{ width:100%; border-collapse:collapse; min-width:760px; }
thead th{
  color:var(--th-ink);
  padding:14px 12px; text-align:left; font-weight:700;
  border-bottom:1px solid var(--row-border);
  background:var(--th-bg);
}
tbody td{ padding:14px 12px; border-bottom:1px solid var(--row-border); }
tbody tr:hover{ background:var(--row-hover); }
th.actions, td.actions{ white-space:nowrap; }

/* Chips / small labels */
.pill{
  display:inline-block; padding:6px 10px; border-radius:999px;
  background:var(--chip); color:var(--chip-ink); font-size:.85rem; font-weight:700;
}

/* Buttons */
.btn{
  display:inline-flex; align-items:center; gap:8px;
  padding:10px 14px; border-radius:12px; border:0; cursor:pointer;
  text-decoration:none; font-size:clamp(14px,2.2vw,16px);
}
.btn-blue{ background:var(--primary); color:var(--primary-ink); }
.btn-blue:hover{ filter:brightness(.96); }
.btn-danger{ background:var(--danger); color:var(--danger-ink); }
.btn-danger:hover{ filter:brightness(.96); }

.btn-stack{ display:flex; gap:8px; flex-wrap:wrap; }
@media (max-width:640px){
  .btn{ width:100%; justify-content:center; }
  .btn-stack .btn, .btn-stack form{ flex:1 1 100%; }
}

/* Forms (add / edit plan & workout) */
label{ display:block; margin:6px 0; font-weight:700; color:var(--ink) !important; }
input[type="text"],input[type="number"],input[type="date"],
textarea,select{
  width:95%; padding:12px 14px; border-radius:12px;
  border:1px solid var(--input-bd); background:var(--input-bg);
  color:var(--input-ink); outline:none; transition:.15s;
}
input::placeholder, textarea::placeholder{ color:var(--input-ph); }
input:focus, textarea:focus, select:focus{
  border-color:var(--primary); box-shadow:0 0 0 3px rgba(74,143,231,.18);
}

/* Modal (reusable) */
.modal{
  position:fixed; inset:0; display:none; place-items:center;
  background:rgba(0,0,0,.55); z-index:1000; padding:16px;
}
.modal.show{ display:grid; }
.modal-card{
  width:min(920px, 100%); background:var(--card); color:var(--ink);
  border-radius:16px; box-shadow:var(--shadow); padding:clamp(16px,2.2vw,22px);
  border:1px solid var(--row-border);
}
.modal-header{ display:flex; justify-content:space-between; align-items:center; gap:10px; }
.modal-title{ font-size:clamp(18px,2.6vw,22px); font-weight:800; }
.modal-actions{ display:flex; justify-content:flex-end; gap:8px; margin-top:14px; }
.form-grid{ display:grid; gap:12px; }
@media (min-width:760px){
  .form-grid.two-col{ grid-template-columns:1fr 1fr; }
  .form-grid.two-col .full{ grid-column:1 / -1; }
}

/* Alerts */
.alert{ padding:12px 14px; border-radius:10px; margin:12px 0; font-weight:600; }
.alert-ok{ background:#d4edda; color:#155724; border:1px solid #a6d6b0; }
.alert-bad{ background:#f8d7da; color:#721c24; border:1px solid #e3a5a8; }
.dark .alert-ok{ background:#0f2a22; color:#9af0c9; border-color:#1f5c4b; }
.dark .alert-bad{ background:#2a1212; color:#f2b5b5; border-color:#6f2a2a; }
</style>

<div class="container">
  <?php if ($msg = get_flash('success')): ?>
    <div class="alert alert-ok"><?= h($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = get_flash('error')): ?>
    <div class="alert alert-bad"><?= h($msg) ?></div>
  <?php endif; ?>

  <?php if ($user_id <= 0): ?>
    <div class="card">
      <h2 style="margin:0 0 12px;">Users · Manage Workout Plans & Workouts</h2>
      <?php
        $stmt = $pdo->query("SELECT id, fullname, email, created_at FROM users ORDER BY created_at ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      <?php if (!$users): ?>
        <p>No users found.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Created</th>
                <th class="actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= (int)$u['id'] ?></td>
                  <td><?= h($u['fullname'] ?? 'User') ?></td>
                  <td><?= h($u['email']) ?></td>
                  <td><?= h($u['created_at'] ?? '') ?></td>
                  <td class="actions">
                    <div class="btn-stack">
                      <a class="btn btn-blue" href="admin_workouts.php?user_id=<?= (int)$u['id'] ?>&tab=plans">Manage Plans</a>
                      <a class="btn btn-blue" href="admin_workouts.php?user_id=<?= (int)$u['id'] ?>&tab=workouts">Manage Workouts</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <?php
      $stmt = $pdo->prepare("SELECT id, fullname, email FROM users WHERE id=?");
      $stmt->execute([$user_id]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$user) { echo '<div class="card">User not found.</div>'; }
    ?>

    <div class="card" style="margin-bottom:16px;">
      <div class="page-title" style="justify-content:space-between;">
        <div>
          <h2 style="margin:0;">Manage <?= ($tab==='workouts'?'Workouts':'Workout Plans') ?> · <?= h($user['fullname']) ?> <small class="text-muted">(<?= h($user['email']) ?>)</small></h2>
          <div class="tabs" style="margin-top:10px;">
            <a href="admin_workouts.php?user_id=<?= (int)$user_id ?>&tab=plans" class="<?= $tab==='plans'?'active':'' ?>">Workout Plans</a>
            <a href="admin_workouts.php?user_id=<?= (int)$user_id ?>&tab=workouts" class="<?= $tab==='workouts'?'active':'' ?>">Workouts</a>
            <a href="admin_workouts.php" class="">← Back to Users</a>
          </div>
        </div>

        <div class="btn-stack">
          <?php if ($tab === 'plans'): ?>
            <button class="btn btn-blue" onclick="openPlanModal();">+ New Plan</button>
            <form method="POST" onsubmit="return confirm('Delete ALL workout plans for this user?');">
              <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
              <input type="hidden" name="action" value="delete_all_plans">
              <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
              <button class="btn btn-danger">Delete All Plans</button>
            </form>
          <?php else: ?>
            <button class="btn btn-blue" onclick="openWorkoutModal();">+ Add Workout</button>
            <form method="POST" onsubmit="return confirm('Delete ALL workouts for this user?');">
              <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
              <input type="hidden" name="action" value="delete_all_workouts">
              <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
              <button class="btn btn-danger">Delete All Workouts</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($tab === 'plans'): ?>
      <?php
        $stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE user_id=? ORDER BY plan_date ASC");
        $stmt->execute([$user_id]);
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <div class="card">
        <?php if (empty($plans)): ?>
          <p>No workout plans yet. Create one using the “New Plan” button.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Morning Routine</th>
                  <th>Evening Activity</th>
                  <th>Completed</th>
                  <th class="actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($plans as $p): ?>
                  <tr>
                    <td><?= h($p['plan_date']) ?></td>
                    <td style="white-space:pre-line;"><?= nl2br(h($p['morning_routine'])) ?></td>
                    <td style="white-space:pre-line;"><?= nl2br(h($p['evening_activity'])) ?></td>
                    <td><?= $p['is_completed'] ? 'Yes' : 'No' ?></td>
                    <td class="actions">
                      <div class="btn-stack">
                        <button
                          class="btn btn-blue"
                          onclick='openPlanModal({
                            plan_id: <?= (int)$p["plan_id"] ?>,
                            plan_date: "<?= h($p["plan_date"]) ?>",
                            morning_routine: <?= json_encode($p["morning_routine"]) ?>,
                            evening_activity: <?= json_encode($p["evening_activity"]) ?>,
                            is_completed: <?= (int)$p["is_completed"] ?>
                          })'
                        >Edit</button>

                        <form method="POST" onsubmit="return confirm('Delete this plan?');">
                          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                          <input type="hidden" name="action" value="delete_plan">
                          <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
                          <input type="hidden" name="plan_id" value="<?= (int)$p['plan_id'] ?>">
                          <button class="btn btn-danger">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Plan Modal -->
      <div id="planModal" class="modal" role="dialog" aria-modal="true">
        <div class="modal-card">
          <div class="modal-header">
            <h3 id="modalTitle" class="modal-title">New Workout Plan</h3>
            <button class="btn btn-outline" onclick="closePlanModal()">✕</button>
          </div>

          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="save_plan">
            <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
            <input type="hidden" name="plan_id" id="plan_id">

            <div class="form-grid two-col">
              <div>
                <label for="plan_date">Date</label>
                <input type="date" id="plan_date" name="plan_date" required>
              </div>

              <div class="full">
                <label for="morning_routine">Morning Routine</label>
                <textarea id="morning_routine" name="morning_routine" rows="4"></textarea>
              </div>

              <div class="full">
                <label for="evening_activity">Evening Activity</label>
                <textarea id="evening_activity" name="evening_activity" rows="4"></textarea>
              </div>

              <div class="full" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="is_completed" name="is_completed" value="1">
                <label for="is_completed" style="margin:0;">Mark as completed</label>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-outline" onclick="closePlanModal()">Cancel</button>
              <button type="submit" class="btn btn-blue">Save</button>
            </div>
          </form>
        </div>
      </div>

      <script>
        function openPlanModal(data){
          const modal = document.getElementById('planModal');
          const title = document.getElementById('modalTitle');
          const planId = document.getElementById('plan_id');
          const dateEl = document.getElementById('plan_date');
          const mr = document.getElementById('morning_routine');
          const ea = document.getElementById('evening_activity');
          const comp = document.getElementById('is_completed');

          if (data && data.plan_id) {
            title.textContent = 'Edit Workout Plan';
            planId.value = data.plan_id;
            dateEl.value = data.plan_date || '';
            mr.value = (data.morning_routine || '');
            ea.value = (data.evening_activity || '');
            comp.checked = !!data.is_completed;
          } else {
            title.textContent = 'New Workout Plan';
            planId.value = '';
            const t = new Date();
            dateEl.value = `${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,'0')}-${String(t.getDate()).padStart(2,'0')}`;
            mr.value = '';
            ea.value = '';
            comp.checked = false;
          }
          modal.classList.add('show');
        }
        function closePlanModal(){ document.getElementById('planModal').classList.remove('show'); }
        window.addEventListener('click', (e)=> {
            const m = document.getElementById('planModal');
            if (e.target === m) closePlanModal();
        });
      </script>

    <?php else: /* tab = workouts */ ?>
      <?php
        $stmt = $pdo->prepare("SELECT * FROM workouts WHERE user_id=? ORDER BY workout_date DESC, created_at ASC");
        $stmt->execute([$user_id]);
        $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <div class="card">
        <?php if (empty($workouts)): ?>
          <p>No workouts yet. Use “Add Workout”.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Duration (min)</th>
                  <th>Calories</th>
                  <th>Notes</th>
                  <th class="actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($workouts as $w): ?>
                  <tr>
                    <td><?= h($w['workout_date']) ?></td>
                    <td><?= h(ucfirst($w['workout_type'])) ?></td>
                    <td><?= (int)$w['duration_minutes'] ?></td>
                    <td><?= ($w['calories_burned'] !== null) ? (int)$w['calories_burned'] : '-' ?></td>
                    <td style="white-space:pre-line;"><?= nl2br(h($w['notes'] ?? '')) ?></td>
                    <td class="actions">
                      <div class="btn-stack">
                        <button
                          class="btn btn-blue"
                          onclick='openWorkoutModal({
                            workout_id: <?= (int)$w["workout_id"] ?>,
                            workout_type: <?= json_encode($w["workout_type"]) ?>,
                            workout_date: "<?= h($w["workout_date"]) ?>",
                            duration_minutes: <?= (int)$w["duration_minutes"] ?>,
                            calories_burned: <?= ($w["calories_burned"] !== null ? (int)$w["calories_burned"] : '""') ?>,
                            notes: <?= json_encode($w["notes"]) ?>
                          })'
                        >Edit</button>

                        <form method="POST" onsubmit="return confirm('Delete this workout?');">
                          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                          <input type="hidden" name="action" value="delete_workout">
                          <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
                          <input type="hidden" name="workout_id" value="<?= (int)$w['workout_id'] ?>">
                          <button class="btn btn-danger">Delete</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Workout Modal -->
      <div id="workoutModal" class="modal" role="dialog" aria-modal="true">
        <div class="modal-card">
          <div class="modal-header">
            <h3 id="wModalTitle" class="modal-title">Add Workout</h3>
            <button class="btn btn-outline" onclick="closeWorkoutModal()">✕</button>
          </div>

          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="save_workout">
            <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
            <input type="hidden" name="workout_id" id="workout_id">

            <div class="form-grid two-col">
              <div>
                <label>Workout Type</label>
                <select name="workout_type" id="workout_type" required>
                  <option value="">Select workout type</option>
                  <option value="strength">Strength</option>
                  <option value="cardio">Cardio</option>
                  <option value="hiit">HIIT</option>
                  <option value="yoga">Yoga/Pilates</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div>
                <label>Date</label>
                <input type="date" name="workout_date" id="workout_date" required>
              </div>

              <div>
                <label>Duration (minutes)</label>
                <input type="number" name="duration_minutes" id="duration_minutes" min="1" required>
              </div>

              <div>
                <label>Calories Burned (optional)</label>
                <input type="number" name="calories_burned" id="calories_burned" min="0">
              </div>

              <div class="full">
                <label>Notes (optional)</label>
                <textarea name="notes" id="notes" rows="3"></textarea>
              </div>
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-outline" onclick="closeWorkoutModal()">Cancel</button>
              <button type="submit" class="btn btn-blue">Save</button>
            </div>
          </form>
        </div>
      </div>

      <script>
        function openWorkoutModal(data){
          const modal = document.getElementById('workoutModal');
          const title = document.getElementById('wModalTitle');
          const wid   = document.getElementById('workout_id');
          const type  = document.getElementById('workout_type');
          const date  = document.getElementById('workout_date');
          const dur   = document.getElementById('duration_minutes');
          const cal   = document.getElementById('calories_burned');
          const notes = document.getElementById('notes');

          if (data && data.workout_id) {
            title.textContent = 'Edit Workout';
            wid.value = data.workout_id;
            type.value = (data.workout_type || '');
            date.value = (data.workout_date || '');
            dur.value  = (data.duration_minutes || '');
            cal.value  = (data.calories_burned !== "" ? data.calories_burned : '');
            notes.value= (data.notes || '');
          } else {
            title.textContent = 'Add Workout';
            wid.value = '';
            type.value = '';
            const t = new Date();
            date.value = `${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,'0')}-${String(t.getDate()).padStart(2,'0')}`;
            dur.value = '';
            cal.value = '';
            notes.value = '';
          }
          modal.classList.add('show');
        }
        function closeWorkoutModal(){ document.getElementById('workoutModal').classList.remove('show'); }
        window.addEventListener('click', (e)=> {
            const m = document.getElementById('workoutModal');
            if (e.target === m) closeWorkoutModal();
        });
      </script>
    <?php endif; ?>
  <?php endif; ?>
</div>
