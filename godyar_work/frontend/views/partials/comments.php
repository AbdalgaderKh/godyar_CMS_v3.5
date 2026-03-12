<section class="article-comments">

<h3>التعليقات</h3>

<form method="post" class="article-comment-form">

<label for="comment_author">الاسم</label>
<input id="comment_author" name="author" required>

<label for="comment_body">تعليقك</label>
<textarea id="comment_body" name="comment" rows="4" required></textarea>

<button type="submit">نشر التعليق</button>

</form>

<?php if(!empty($comments)): ?>

<?php foreach($comments as $comment): ?>

<div class="article-comment-card">

<strong>
<?= htmlspecialchars($comment['author'] ?? 'زائر') ?>
</strong>

<p>
<?= nl2br(htmlspecialchars($comment['comment'] ?? '')) ?>
</p>

</div>

<?php endforeach; ?>

<?php endif; ?>

</section>