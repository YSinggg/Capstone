<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    $content = htmlspecialchars($_POST['post_content'] ?? '');
    
    if (empty($content)) {
        $_SESSION['error'] = "Post content cannot be empty";
        header("Location: fitness_management.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user_id, $content]);
        $_SESSION['success'] = "Post added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding post: " . $e->getMessage();
    }
    header("Location: fitness_management.php");
    exit();
}

// Handle post like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post'])) {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    
    if ($post_id === false || $post_id === null) {
        $_SESSION['error'] = "Invalid post ID";
        header("Location: fitness_management.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE community_posts SET likes = likes + 1 WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $_SESSION['success'] = "Post liked!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error liking post: " . $e->getMessage();
    }
    header("Location: fitness_management.php");
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $content = htmlspecialchars($_POST['comment_content'] ?? '');
    
    if ($post_id === false || $post_id === null) {
        $_SESSION['error'] = "Invalid post ID";
        header("Location: fitness_management.php");
        exit();
    }
    
    if (empty($content)) {
        $_SESSION['error'] = "Comment cannot be empty";
        header("Location: fitness_management.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $content]);
        $_SESSION['success'] = "Comment added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding comment: " . $e->getMessage();
    }
    header("Location: fitness_management.php");
    exit();
}

header("Location: fitness_management.php");
exit();
?>