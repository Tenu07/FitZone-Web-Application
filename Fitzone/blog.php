<?php
require 'config.php';

$stmt = $pdo->query("SELECT posts.*, users.name as author 
                    FROM posts 
                    JOIN users ON posts.author_id = users.id 
                    ORDER BY created_at DESC LIMIT 10");
$posts = $stmt->fetchAll();
?>

<!-- Display Blog Posts -->
<div class="blog-posts">
    <?php foreach ($posts as $post): ?>
    <article class="post">
        <h3><?= htmlspecialchars($post['title']) ?></h3>
        <div class="meta">
            <span class="category"><?= ucfirst(str_replace('_', ' ', $post['category'])) ?></span>
            <span class="author">By <?= htmlspecialchars($post['author']) ?></span>
        </div>
        <div class="content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
    </article>
    <?php endforeach; ?>
</div>