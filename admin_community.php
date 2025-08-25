<?php
// admin_community.php
session_start();
require_once 'db.php';      // must define $pdo (PDO)
require_once 'admin_header.php';

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
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_post'])) {
  $content = trim($_POST['post_content'] ?? '');
  if ($content !== '') {
    try {
      // use user_id=0 to mark admin authored; you can replace with a proper admin_posts table later
      $pdo->prepare("INSERT INTO community_posts (user_id, content) VALUES (?,?)")
          ->execute([0, "[ADMIN] ".$content]);
      $_SESSION['success'] = "Post published.";
    } catch(PDOException $e){ $_SESSION['error']="Error: ".$e->getMessage(); }
  }
  header("Location: admin_community.php"); exit();
}

// Add comment (as admin)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_comment'])) {
  $post_id = (int)($_POST['post_id'] ?? 0);
  $content = trim($_POST['comment_content'] ?? '');
  if ($post_id && $content!=='') {
    try {
      $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?,?,?)")
          ->execute([$post_id, 0, "[ADMIN] ".$content]);
      $_SESSION['success'] = "Comment added.";
    } catch(PDOException $e){ $_SESSION['error']="Error: ".$e->getMessage(); }
  }
  header("Location: admin_community.php"); exit();
}

// Fetch posts
$posts = $pdo->query(
  "SELECT cp.*, COALESCE(u.fullname,'Administrator') AS author_name
   FROM community_posts cp
   LEFT JOIN users u ON u.id = cp.user_id
   ORDER BY cp.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container">
  <?php if($success): ?><div class="card" style="background:#e9f7ef;color:#145a32;margin-bottom:14px;"><?=htmlspecialchars($success)?></div><?php endif; ?>
  <?php if($error): ?><div class="card" style="background:#fdecea;color:#7f1d1d;margin-bottom:14px;"><?=htmlspecialchars($error)?></div><?php endif; ?>

  <div class="card" style="margin-bottom:16px">
    <h2 style="margin:0 0 10px">Write a new post</h2>
    <form method="post">
      <textarea name="post_content" rows="3" style="width:100%;padding:10px;border-radius:10px;border:1px solid #cfd8e3"></textarea>
      <div style="margin-top:8px;display:flex;gap:8px">
        <button class="btn btn-blue" name="add_post" type="submit"><i class="fa-solid fa-bullhorn"></i> Post as Admin</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h2 style="margin-top:0">All Community Posts</h2>
    <?php if(!$posts): ?>
      <p style="margin:0">No posts yet.</p>
    <?php else: foreach($posts as $p): ?>
      <div style="border:1px solid #e5e9f2;border-radius:12px;padding:14px;margin:12px 0">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
          <div><strong><?=htmlspecialchars($p['author_name'])?></strong> ·
            <span style="color:#667;"><?=htmlspecialchars($p['created_at'])?></span>
          </div>
          <form method="post">
            <input type="hidden" name="post_id" value="<?=$p['post_id']?>">
            <button class="btn" style="background:#ef4444;color:#fff" name="delete_post" onclick="return confirm('Delete this post?')">
              <i class="fa-solid fa-trash"></i> Delete
            </button>
          </form>
        </div>
        <p style="margin:10px 0 6px"><?=nl2br(htmlspecialchars($p['content']))?></p>
        <div style="font-size:13px;color:#556">Likes: <?=$p['likes']?></div>

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
          <div style="margin-top:10px;padding-left:6px">
            <?php foreach($comments as $c): ?>
              <div style="border-left:3px solid #e5e9f2;margin:8px 0;padding:8px 10px;background:#fafbfd;border-radius:8px">
                <div style="display:flex;justify-content:space-between">
                  <div><strong><?=htmlspecialchars($c['author_name'])?></strong> ·
                    <span style="color:#667"><?=htmlspecialchars($c['created_at'])?></span>
                  </div>
                  <form method="post">
                    <input type="hidden" name="comment_id" value="<?=$c['comment_id']?>">
                    <button class="btn" style="background:#ef4444;color:#fff" name="delete_comment" onclick="return confirm('Delete this comment?')">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
                <div><?=nl2br(htmlspecialchars($c['content']))?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" style="margin-top:8px">
          <input type="hidden" name="post_id" value="<?=$p['post_id']?>">
          <textarea name="comment_content" rows="2" placeholder="Comment as Admin…" style="width:100%;padding:10px;border-radius:10px;border:1px solid #cfd8e3"></textarea>
          <div style="margin-top:6px">
            <button class="btn btn-blue" name="add_comment" type="submit"><i class="fa-solid fa-reply"></i> Add Comment</button>
          </div>
        </form>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

</body>
</html>
