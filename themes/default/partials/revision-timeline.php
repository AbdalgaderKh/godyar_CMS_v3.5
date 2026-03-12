<?php if (empty($revisions)): return; endif; ?>
<section class="g-v4-card v4-revision-card">
    <h2>سجل المراجعات</h2>
    <ul class="v4-revision-list">
        <?php foreach ($revisions as $rev): ?>
            <li>
                <strong><?= e($rev['editor_name'] ?? 'system') ?></strong>
                <span><?= e($rev['created_at'] ?? '') ?></span>
                <?php if (!empty($rev['note'])): ?><p><?= e($rev['note']) ?></p><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
