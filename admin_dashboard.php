<?php
// admin_dashboard.php
session_start();
require_once 'db.php';          // must define $pdo (PDO)
require_once 'admin_header.php';// renders the header + opens <body>

// --- Fetch stats ---
$stats = [
  'users'        => 0,
  'posts'        => 0,
  'comments'     => 0,
  'likes'        => 0,
  'posts_today'  => 0,
  'posts_week'   => 0,
];

try {
  // total users
  $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

  // total posts & likes
  $row = $pdo->query("SELECT COUNT(*) AS c, COALESCE(SUM(likes),0) AS l FROM community_posts")->fetch(PDO::FETCH_ASSOC);
  $stats['posts'] = (int)$row['c'];
  $stats['likes'] = (int)$row['l'];

  // total comments
  $stats['comments'] = (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

  // posts today
  $stats['posts_today'] = (int)$pdo->query("SELECT COUNT(*) FROM community_posts WHERE DATE(created_at)=CURDATE()")->fetchColumn();

  // posts this ISO week
  $stats['posts_week'] = (int)$pdo->query("SELECT COUNT(*) FROM community_posts WHERE YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)")->fetchColumn();

  // recent posts
  $recentStmt = $pdo->query("
    SELECT cp.post_id, cp.content, cp.likes, cp.created_at,
           COALESCE(u.fullname,'Administrator') AS author_name
    FROM community_posts cp
    LEFT JOIN users u ON u.id = cp.user_id
    ORDER BY cp.created_at DESC
    LIMIT 6
  ");
  $recent_posts = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  $recent_posts = [];
}
?>

<style>
  /* ===== Responsive Admin Dashboard ===== */

  :root{
    --card-bg:#fff;
    --ink:#1f2a36;
    --muted:#6b7280;
    --primary:#4a8fe7;

    --shadow:0 6px 24px rgba(0,0,0,.06);
    --bd:#e8eef5;
    --radius:14px;

    /* spacing scale */
    --sp-2:  2px;
    --sp-4:  4px;
    --sp-6:  6px;
    --sp-8:  8px;
    --sp-10: 10px;
    --sp-12: 12px;
    --sp-14: 14px;
    --sp-16: 16px;
    --sp-18: 18px;
    --sp-20: 20px;
    --sp-24: 24px;
  }

  /* Dark mode (coexists with admin_header.php) */
  .dark {
    --card-bg:#0f1b24;
    --ink:#e6eef8;
    --muted:#9fb3c8;
    --bd:#193243;
    --shadow:0 10px 30px rgba(0,0,0,.35);
  }

  body { color:var(--ink); }

  .dash-wrap{
    max-width:min(1200px, 100vw - 32px);
    margin:var(--sp-24) auto;
    padding:0 var(--sp-20);
  }

  /* Cards grid */
  .grid{ display:grid; gap:var(--sp-16); }
  .grid.cards{
    /* auto-fit cards into the row responsively */
    grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
  }

  .card{
    background:var(--card-bg);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:clamp(14px, 2.2vw, 20px);
    border:1px solid var(--bd);
  }

  /* Metric tiles */
  .metric{
    display:flex; align-items:center; gap:var(--sp-12);
  }
  .metric i{
    font-size:clamp(18px, 2.2vw, 22px);
    color:var(--primary);
    flex:0 0 auto;
  }
  .metric .num{
    font-size:clamp(20px, 3.6vw, 28px);
    font-weight:800; line-height:1.15;
  }
  .metric .label{
    color:var(--muted);
    font-size:clamp(12px, 1.6vw, 13px);
    margin-top:var(--sp-2);
  }

  .muted{ color:var(--muted); }

  /* List of recent posts */
  .list{ display:flex; flex-direction:column; gap:var(--sp-12); margin-top:var(--sp-8); }
  .item{
    border:1px solid var(--bd);
    border-radius:12px;
    padding:var(--sp-12);
  }
  .item .top{
    display:flex; justify-content:space-between; gap:var(--sp-10); flex-wrap:wrap;
  }
  .item .author{ font-weight:600; font-size:clamp(14px, 2vw, 16px); }
  .item .when{ color:var(--muted); font-size:clamp(12px, 1.6vw, 13px); }
  .item .content{
    margin-top:var(--sp-6);
    white-space:pre-wrap;
    font-size:clamp(14px, 2vw, 16px);
  }

  /* Buttons */
  .btn{
    border:0; border-radius:10px;
    padding:clamp(8px, 1.6vw, 12px) clamp(12px, 2.2vw, 14px);
    font-weight:700; cursor:pointer; text-decoration:none;
    display:inline-flex; align-items:center; gap:var(--sp-8);
    font-size:clamp(14px, 2vw, 16px);
  }
  .btn i{ font-size:1em; }
  .btn-blue{ background:#4a8fe7; color:#fff; }
  .btn-green{ background:#44c767; color:#fff; }
  .btn-gray{ background:#e9eef5; color:var(--ink); }
  .dark .btn-gray{ background:#132534; color:var(--ink); }

  /* Two-column row (Recent + Quick) */
  .row{
    display:grid; gap:var(--sp-16);
    grid-template-columns:1.2fr .8fr;
    margin-top:var(--sp-18);
  }
  @media (max-width: 1000px){
    .row{ grid-template-columns:1fr; }
  }

  /* Header area within the card */
  .card-head{
    display:flex; justify-content:space-between; align-items:center;
    gap:var(--sp-12); flex-wrap:wrap;
  }
  .btn-bar{ display:flex; gap:var(--sp-8); flex-wrap:wrap; }

  /* Stack action buttons on very small devices for easy tapping */
  @media (max-width: 520px){
    .btn-bar .btn{
      width:100%; justify-content:center;
    }
  }

  /* Ensure long content never breaks the layout */
  .item, .card { overflow-wrap:anywhere; }

  /* Slight elevation hover for buttons (optional) */
  .btn:hover { filter:brightness(.98); }
</style>

<div class="dash-wrap">
  <div class="grid cards">
    <div class="card">
      <div class="metric">
        <i class="fa-solid fa-users"></i>
        <div>
          <div class="num"><?= number_format($stats['users']) ?></div>
          <div class="label">Total Users</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="metric">
        <i class="fa-solid fa-people-group"></i>
        <div>
          <div class="num"><?= number_format($stats['posts']) ?></div>
          <div class="label">Community Posts</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="metric">
        <i class="fa-solid fa-comments"></i>
        <div>
          <div class="num"><?= number_format($stats['comments']) ?></div>
          <div class="label">Comments</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="metric">
        <i class="fa-solid fa-heart"></i>
        <div>
          <div class="num"><?= number_format($stats['likes']) ?></div>
          <div class="label">Total Likes</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="metric">
        <i class="fa-solid fa-calendar-day"></i>
        <div>
          <div class="num"><?= number_format($stats['posts_today']) ?></div>
          <div class="label">Posts Today</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="metric">
        <i class="fa-solid fa-calendar-week"></i>
        <div>
          <div class="num"><?= number_format($stats['posts_week']) ?></div>
          <div class="label">Posts This Week</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="card">
      <div class="card-head">
        <h2 style="margin:0;font-size:clamp(18px,3vw,22px)">Recent Posts</h2>
        <div class="btn-bar">
          <a class="btn btn-blue" href="admin_community.php"><i class="fa-solid fa-people-group"></i> Manage Community</a>
          <a class="btn btn-green" href="admin_community.php#new-post"><i class="fa-solid fa-bullhorn"></i> New Admin Post</a>
        </div>
      </div>

      <?php if (empty($recent_posts)): ?>
        <p class="muted" style="margin:10px 0 0">No posts yet.</p>
      <?php else: ?>
        <div class="list">
          <?php foreach ($recent_posts as $p): ?>
            <div class="item">
              <div class="top">
                <div class="author"><?= htmlspecialchars($p['author_name']) ?></div>
                <div class="when"><?= htmlspecialchars(date('M j, Y g:i a', strtotime($p['created_at']))) ?></div>
              </div>
              <div class="content"><?= nl2br(htmlspecialchars($p['content'])) ?></div>
              <div class="muted" style="margin-top:var(--sp-6)"><i class="fa-solid fa-heart"></i> <?= (int)$p['likes'] ?> likes</div>
              <div style="margin-top:var(--sp-8)">
                <a class="btn btn-gray" href="admin_community.php?post=<?= (int)$p['post_id'] ?>">
                  <i class="fa-solid fa-pen-to-square"></i> Open in Community
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2 style="margin-top:0;font-size:clamp(18px,3vw,22px)">Quick Actions</h2>
      <div class="grid" style="grid-template-columns:1fr; gap:var(--sp-10)">
        <a class="btn btn-blue" href="admin_users.php"><i class="fa-solid fa-user-gear"></i> Manage Users</a>
        <a class="btn btn-blue" href="admin_community.php"><i class="fa-solid fa-people-group"></i> Review Posts & Comments</a>
        <a class="btn btn-blue" href="admin_workouts.php"><i class="fa-solid fa-dumbbell"></i> Manage Workouts</a>
        <a class="btn btn-blue" href="admin_nutrition.php"><i class="fa-solid fa-utensils"></i> Manage Nutrition</a>
        <a class="btn btn-blue" href="admin_settings.php"><i class="fa-solid fa-gear"></i> Admin Settings</a>
        <a class="btn btn-green" href="admin_community.php#new-post"><i class="fa-solid fa-bullhorn"></i> Post Announcement</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
