<?php
require_once __DIR__ . '/../../includes/smart_translation_engine.php';

$pdo = gdy_translation_db();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['enqueue_news_id'], $_POST['target_lang'])) {
            $jobId = gdy_translation_enqueue_news((int)$_POST['enqueue_news_id'], (string)$_POST['target_lang'], null);
            $message = 'Translation job queued: 
        } elseif (isset($_POST['approve_job_id'])) {
            $jobId = (int)$_POST['approve_job_id'];
            $pdo->prepare("UPDATE translation_suggestions SET is_approved=1, approved_at=NOW() WHERE job_id=?")->execute(array($jobId));
            $count = gdy_translation_apply_suggestion_set($jobId);
            $message = 'Approved and applied ' . $count . ' translated fields.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$jobs = $pdo->query("SELECT * FROM translation_jobs ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$newsItems = $pdo->query("SELECT id, title FROM news ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<title>Smart Translation Engine</title>
<style>
body{font-family:Arial, sans-serif;background:#f5f7fb;margin:0;padding:24px}
.wrap{max-width:1100px;margin:0 auto}
.card{background:#fff;border-radius:14px;padding:20px;margin-bottom:20px;box-shadow:0 8px 24px rgba(0,0,0,.06)}
h1,h2{margin-top:0}
input,select,button{padding:10px 12px;border:1px solid 
button{cursor:pointer}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid 
.ok{background:#eaf8ef;color:#166534;padding:10px 12px;border-radius:10px}
.err{background:#fff1f2;color:#9f1239;padding:10px 12px;border-radius:10px}
small{color:#64748b}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>🚀 Smart Translation Engine</h1>
    <p>حوّل المقالات إلى الإنجليزية والفرنسية عبر نظام مهام آمن مع مراجعة واعتماد قبل النشر.</p>
    <?php if ($message): ?><div class="ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  </div>

  <div class="card">
    <h2>إضافة مهمة ترجمة جديدة</h2>
    <form method="post">
      <label>المقال</label><br>
      <select name="enqueue_news_id" required>
        <?php foreach ($newsItems as $news): ?>
          <option value="<?php echo (int)$news['id']; ?>">#<?php echo (int)$news['id']; ?> — <?php echo htmlspecialchars($news['title']); ?></option>
        <?php endforeach; ?>
      </select>
      <select name="target_lang" required>
        <option value="en">English</option>
        <option value="fr">Français</option>
      </select>
      <button type="submit">إضافة للمراجعة</button>
    </form>
    <p><small>المعالجة الفعلية تتم عبر العامل: <code>/tools/process_translation_jobs.php?key=TRANSLATION_CRON_KEY</code></small></p>
  </div>

  <div class="card">
    <h2>آخر مهام الترجمة</h2>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>النوع</th>
          <th>العنصر</th>
          <th>اللغة</th>
          <th>الحالة</th>
          <th>الإجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($jobs as $job): ?>
          <tr>
            <td><?php echo (int)$job['id']; ?></td>
            <td><?php echo htmlspecialchars($job['entity_type']); ?></td>
            <td><?php echo (int)$job['entity_id']; ?></td>
            <td><?php echo htmlspecialchars($job['target_lang']); ?></td>
            <td><?php echo htmlspecialchars($job['status']); ?></td>
            <td>
              <?php if ($job['status'] === 'finished'): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="approve_job_id" value="<?php echo (int)$job['id']; ?>">
                  <button type="submit">اعتماد الكل وتطبيقها</button>
                </form>
              <?php else: ?>
                <small>بانتظار المعالجة</small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$jobs): ?>
          <tr><td colspan="6">لا توجد مهام ترجمة حتى الآن.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
