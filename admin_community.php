<?php
// admin_community.php
session_start();
require_once 'db.php';      // must define $pdo (PDO)
require_once 'admin_header.php';

function getSystemAdminUserId(PDO $pdo): int {
    $email = 'admin@system.local';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    $pwd = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (fullname, email, password, age, gender, height, weight, fitness_goal, calorie_target)
        VALUES ('Admin', ?, ?, 0, 'other', 0, 0, 'admin_posts', 0)
    ");
    $stmt->execute([$email, $pwd]);
    return (int)$pdo->lastInsertId();
}

function resolvePosterUserId(PDO $pdo): int {
    if (!empty($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];  // logged-in user
    }
    if (!empty($_SESSION['admin_id'])) {
        return getSystemAdminUserId($pdo); // fallback for admin
    }
    throw new RuntimeException('No valid session for posting.');
}

// flash
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Delete post
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_post'])) {
  $post_id = (int)($_POST['post_id'] ?? 0);
  try {
    $pdo->prepare("DELETE FROM comments WHERE post_id=?")->execute([$post_id]);
    $pdo->prepare("DELETE FROM community_posts WHERE post_id=?")->execute([$post_id]);
    $_SESSION['success'] = "Post deleted.";
  } catch(PDOException $e){ $_SESSION['error']="Error: ".$e->getMessage(); }
  header("Location: admin_community.php"); exit();
}

// Delete comment
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_comment'])) {
  $comment_id = (int)($_POST['comment_id'] ?? 0);
  try {
    $pdo->prepare("DELETE FROM comments WHERE comment_id=?")->execute([$comment_id]);
    $_SESSION['success'] = "Comment deleted.";
  } catch(PDOException $e){ $_SESSION['error']="Error: ".$e->getMessage(); }
  header("Location: admin_community.php"); exit();
}

// Add post (as admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post_admin'])) {
    $content = trim($_POST['post_content'] ?? '');
    if ($content === '') {
        $error = "Post content cannot be empty.";
    } else {
        try {
            // Use system admin user id instead of $_SESSION['admin_id']
            $posterUserId = getSystemAdminUserId($pdo);

            $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, content) VALUES (?, ?)");
            $stmt->execute([$posterUserId, $content]);

            $success = "Posted as Admin.";
        } catch (PDOException $e) {
            $error = "Failed to post: " . $e->getMessage();
        }
    } header("Location: admin_community.php"); exit();
}

// Add comment (as admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $content = trim($_POST['comment_content'] ?? '');

    if (!$post_id) {
        $error = "Invalid post id.";
    } elseif ($content === '') {
        $error = "Comment cannot be empty.";
    } else {
        try {
            $userId = resolvePosterUserId($pdo); // <-- works for both user & admin
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $userId, $content]);
            $success = "Comment added.";
        } catch (PDOException $e) {
            $error = "Failed to add comment: " . $e->getMessage();
        }
    } header("Location: admin_community.php"); exit();
}

// Fetch posts
$posts = $pdo->query(
  "SELECT cp.*, COALESCE(u.fullname,'Administrator') AS author_name
   FROM community_posts cp
   LEFT JOIN users u ON u.id = cp.user_id
   ORDER BY cp.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  /* ========= Layout & tokens ========= */
  .container{
    max-width:min(1200px, 100% - 32px);
    margin:24px auto;
    padding:0 16px;
  }
  .card{
    background:#fff;
    border-radius:14px;
    box-shadow:0 6px 24px rgba(0,0,0,.06);
    padding:clamp(14px,2.2vw,20px);
    margin-bottom:18px;
    border:1px solid #e7ecf1;
  }
  h1{ margin:8px 0 16px; font-size:clamp(20px,3.4vw,24px); }
  h2{ margin:0 0 12px; font-size:clamp(18px,2.8vw,20px); }
  h3{ margin:0 0 10px; font-size:clamp(16px,2.4vw,18px); }
  .muted{ color:#667; }

  /* Dark mode (relies on .dark on <body> from admin_header.php) */
  .dark .card{ background:#0f1b24; color:#e6eef8; border:1px solid #193243; }
  .dark .muted{ color:#9fb3c8; }

  /* Buttons (assumes .btn / .btn-blue exist from header; add safe defaults) */
  .btn{ display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:10px; border:0; cursor:pointer; font-weight:700; text-decoration:none; }
  .btn-blue{ background:#4a8fe7; color:#fff; }
  .dark .btn-outline{ border-color:#29475b; color:#e6eef8; }
  .btn-danger{ background:#ef4444; color:#fff; }
  .btn-row{ display:flex; gap:8px; flex-wrap:wrap; }
  @media (max-width:640px){
    .btn, .btn-row .btn, .btn-row form{ width:100%; justify-content:center; }
  }

  /* Forms */
  textarea, input[type="text"], input[type="email"], input[type="number"], input[type="time"], select{
    width:95% !important;               /* override inline widths like 95% */
    padding:12px 14px;
    border-radius:10px;
    border:1px solid #cfd8e3;
    background:#fff;
    color:#111827;
  }
  textarea{ resize:vertical; }
  .dark textarea, .dark input, .dark select{
    background:#0f1b24; border-color:#29475b; color:#e6eef8;
  }

  /* Flash messages */
  .flash{ padding:12px 14px; border-radius:10px; margin-bottom:14px; font-weight:600; }
  .flash-ok{ background:#e9f7ef; color:#145a32; border:1px solid #b7ead0; }
  .flash-bad{ background:#fdecea; color:#7f1d1d; border:1px solid #f5c2c2; }
  .dark .flash-ok{ background:#0f2a22; color:#9af0c9; border-color:#1f5c4b; }
  .dark .flash-bad{ background:#2a1212; color:#f2b5b5; border-color:#6f2a2a; }

  /* New Post composer */
  .composer-actions{ margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }

  /* Post list */
  .post{
    border:1px solid #e5e9f2;
    border-radius:12px;
    padding:14px;
    margin:12px 0;
    background:#fff;
  }
  .dark .post{ background:#0f1b24; border-color:#193243; }
  .post-header{
    display:flex;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
    align-items:flex-start;
  }
  .post-meta{ display:flex; gap:8px; flex-wrap:wrap; }
  .post-author{ font-weight:700; }
  .post-when{ color:#667; }
  .dark .post-when{ color:#9fb3c8; }
  .post-content{ margin:10px 0 6px; white-space:pre-wrap; }
  .post-likes{ font-size:13px; color:#556; }
  .dark .post-likes{ color:#9fb3c8; }

  /* Comments */
  .comments{ margin-top:10px; display:flex; flex-direction:column; gap:8px; }
  .comment{
    padding:10px 12px;
    border-radius:10px;
    background:#f7f9fc;
    border:1px solid #e6edf5;
  }
  .dark .comment{ background:#112233; border-color:#193243; }
  .comment-top{
    display:flex; justify-content:space-between; align-items:flex-start; gap:8px; flex-wrap:wrap;
  }
  .comment-meta{ display:flex; gap:8px; flex-wrap:wrap; }
  .comment-author{ font-weight:600; }
  .comment-when{ color:#667; }
  .dark .comment-when{ color:#9fb3c8; }
  .comment-body{ margin-top:6px; white-space:pre-wrap; }

  /* Add comment form */
  .add-comment{ margin-top:10px; }
  .add-comment .btn{ margin-top:6px; }

  /* Responsive typography spacing */
  .stack{ display:flex; flex-direction:column; gap:10px; }
</style>

<div class="container">
  <h1><i class="fa-solid fa-people-group"></i> Admin · Community</h1>

  <?php if($success): ?>
    <div class="flash flash-ok"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
  <?php if($error): ?>
    <div class="flash flash-bad"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>

  <!-- Composer -->
  <div class="card" style="margin-bottom:16px">
    <h2 style="margin:0 0 10px">Write a new post</h2>
    <form method="post" class="stack">
        <textarea name="post_content" rows="3" placeholder="Share an update with the community…"></textarea>
        <div class="composer-actions">
          <button type="submit" name="add_post_admin" class="btn btn-blue">
            <i class="fa-solid fa-user-shield"></i> Post as Admin
          </button>
        </div>
    </form>
  </div>

  <!-- Posts -->
  <div class="card">
    <h2 style="margin-top:0">All Community Posts</h2>
    <?php if(!$posts): ?>
      <p class="muted" style="margin:0">No posts yet.</p>
    <?php else: foreach($posts as $p): ?>
      <div class="post">
        <div class="post-header">
          <div class="post-meta">
            <div class="post-author"><?=htmlspecialchars($p['author_name'])?></div>
            <div class="post-when"><?=htmlspecialchars($p['created_at'])?></div>
          </div>
          <form method="post" class="btn-row">
            <input type="hidden" name="post_id" value="<?=$p['post_id']?>">
            <button class="btn btn-danger" name="delete_post" onclick="return confirm('Delete this post?')">
              <i class="fa-solid fa-trash"></i> Delete
            </button>
          </form>
        </div>

        <div class="post-content"><?=nl2br(htmlspecialchars($p['content']))?></div>
        <div class="post-likes">Likes: <?=$p['likes']?></div>

        <?php
          $stmt = $pdo->prepare(
            "SELECT c.*, COALESCE(u.fullname,'Administrator') AS author_name
             FROM comments c LEFT JOIN users u ON u.id=c.user_id
             WHERE c.post_id=? ORDER BY c.created_at ASC"
          );
          $stmt->execute([$p['post_id']]);
          $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if($comments): ?>
          <div class="comments">
            <?php foreach($comments as $c): ?>
              <div class="comment">
                <div class="comment-top">
                  <div class="comment-meta">
                    <div class="comment-author"><?=htmlspecialchars($c['author_name'])?></div>
                    <div class="comment-when"><?=htmlspecialchars($c['created_at'])?></div>
                  </div>
                  <form method="post" class="btn-row">
                    <input type="hidden" name="comment_id" value="<?=$c['comment_id']?>">
                    <button class="btn btn-danger" name="delete_comment" title="Delete comment" onclick="return confirm('Delete this comment?')">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
                <div class="comment-body"><?=nl2br(htmlspecialchars($c['content']))?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="add-comment stack">
          <input type="hidden" name="post_id" value="<?=$p['post_id']?>">
          <textarea name="comment_content" rows="2" placeholder="Comment as Admin…"></textarea>
          <div class="btn-row">
            <button class="btn btn-blue" name="add_comment" type="submit">
              <i class="fa-solid fa-reply"></i> Add Comment
            </button>
          </div>
        </form>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

</body>
</html>
