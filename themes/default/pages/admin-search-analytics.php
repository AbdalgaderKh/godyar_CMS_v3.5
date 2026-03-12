<section class="g-v4-wrap g-v4-section-head">
    <div>
        <h1>تحليلات البحث</h1>
        <p>قياس الكلمات الأكثر بحثًا والكلمات التي لا تُرجع نتائج لمساعدتك في تطوير المحتوى.</p>
    </div>
</section>

<section class="g-v4-wrap g-v4-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
    <article class="g-v4-card g-v4-admin-card">
        <h3>الأكثر بحثًا</h3>
        <div class="g-v4-admin-list">
            <?php foreach (($summary['top'] ?? []) as $row): ?>
                <div class="g-v4-admin-list-row">
                    <strong><?= e((string)($row['query'] ?? '')) ?></strong>
                    <span><?= (int)($row['count'] ?? 0) ?> مرة</span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($summary['top'])): ?>
                <p class="g-v4-muted">لا توجد بيانات بحث حتى الآن.</p>
            <?php endif; ?>
        </div>
    </article>

    <article class="g-v4-card g-v4-admin-card">
        <h3>بدون نتائج</h3>
        <div class="g-v4-admin-list">
            <?php foreach (($summary['no_results'] ?? []) as $row): ?>
                <div class="g-v4-admin-list-row">
                    <strong><?= e((string)($row['query'] ?? '')) ?></strong>
                    <span><?= (int)($row['count'] ?? 0) ?> مرة</span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($summary['no_results'])): ?>
                <p class="g-v4-muted">ممتاز، لا توجد كلمات كثيرة بدون نتائج.</p>
            <?php endif; ?>
        </div>
    </article>

    <article class="g-v4-card g-v4-admin-card">
        <h3>الأكثر بحثًا اليوم</h3>
        <div class="g-v4-admin-list">
            <?php foreach (($summary['today'] ?? []) as $row): ?>
                <div class="g-v4-admin-list-row">
                    <strong><?= e((string)($row['query'] ?? '')) ?></strong>
                    <span><?= (int)($row['count'] ?? 0) ?> مرة</span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($summary['today'])): ?>
                <p class="g-v4-muted">لا توجد عمليات بحث اليوم.</p>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="g-v4-wrap g-v4-card g-v4-admin-card">
    <h3>آخر عمليات البحث</h3>
    <div class="g-v4-admin-table-wrap">
        <table class="g-v4-admin-table">
            <thead>
                <tr>
                    <th>الكلمة</th>
                    <th>النتائج</th>
                    <th>اللغة</th>
                    <th>الوقت</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($summary['recent'] ?? []) as $row): ?>
                    <tr>
                        <td><?= e((string)($row['query'] ?? '')) ?></td>
                        <td><?= (int)($row['results'] ?? 0) ?></td>
                        <td><?= e((string)($row['locale'] ?? 'ar')) ?></td>
                        <td><?= e((string)($row['created_at'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($summary['recent'])): ?>
                    <tr><td colspan="4" class="g-v4-muted">لا توجد بيانات متاحة.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
