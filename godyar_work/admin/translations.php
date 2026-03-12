<?php
require_once __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../includes/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) { gdy_session_start(); }

$pdo = function_exists('gdy_pdo_safe') ? gdy_pdo_safe() : null;
if (!$pdo) { http_response_code(500); echo "DB not available"; exit; }

$langs = array('ar'=>'العربية','en'=>'English','fr'=>'Français');
$tab = isset($_GET['tab']) ? (string)$_GET['tab'] : 'strings';

function gdy_h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? $_POST['action'] : '';
  if ($action === 'save_string') {
    $lang = isset($_POST['lang']) ? $_POST['lang'] : 'ar';
    $k = trim((string)($_POST['k'] ?? ''));
    $v = trim((string)($_POST['v'] ?? ''));
    if ($k !== '') {
      $st = $pdo->prepare("INSERT INTO i18n_strings (lang,k,v) VALUES (?,?,?) ON DUPLICATE KEY UPDATE v=VALUES(v), updated_at=CURRENT_TIMESTAMP");
      $st->execute(array($lang,$k,$v));
      $msg = "تم حفظ الترجمة.";
    }
    $tab = 'strings';
  }

  if ($action === 'save_field') {
    $scope = (string)($_POST['scope'] ?? '');
    $item_id = (int)($_POST['item_id'] ?? 0);
    $lang = (string)($_POST['lang'] ?? 'ar');
    $field = (string)($_POST['field'] ?? '');
    $value = (string)($_POST['value'] ?? '');
    if ($scope && $item_id && $field) {
      $st = $pdo->prepare("INSERT INTO i18n_fields (scope,item_id,lang,field,value) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=CURRENT_TIMESTAMP");
      $st->execute(array($scope,$item_id,$lang,$field,$value));
      $msg = "تم حفظ ترجمة المحتوى.";
    }
    $tab = 'content';
  }
}

?><!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<title>إدارة الترجمات — Godyar News Platform</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; background:#0b1220; color:#e9eefb; margin:0; padding:20px;}
    .card{background:#0f1a2e; border:1px solid rgba(255,255,255,.08); border-radius:14px; padding:16px; margin-bottom:14px;}
    a{color:#7ee3b5; text-decoration:none}
    .tabs a{display:inline-block; padding:8px 12px; border-radius:999px; margin-left:8px; background:rgba(255,255,255,.06)}
    .tabs a.active{background:rgba(126,227,181,.18)}
    input,select,textarea{width:100%; padding:10px 12px; border-radius:12px; border:1px solid rgba(255,255,255,.12); background:#0b1529; color:#e9eefb;}
    textarea{min-height:140px}
    button{padding:10px 14px; border-radius:12px; border:0; background:#19c37d; color:#062013; font-weight:700; cursor:pointer;}
    .grid{display:grid; grid-template-columns: 1fr 1fr; gap:12px;}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}
    .msg{padding:10px 12px; border-radius:12px; background:rgba(25,195,125,.12); border:1px solid rgba(25,195,125,.22); margin-bottom:12px;}
    small{opacity:.8}
  </style>
</head>
<body>

<h2 style="margin:0 0 10px;">إدارة الترجمات (I18n Pro)</h2>
<div class="tabs" style="margin-bottom:14px;">
  <a class="<?php echo $tab==='strings'?'active':''; ?>" href="?tab=strings">ترجمة واجهة (Strings)</a>
  <a class="<?php echo $tab==='content'?'active':''; ?>" href="?tab=content">ترجمة المحتوى (News/Categories)</a>
</div>

<?php if ($msg): ?><div class="msg"><?php echo gdy_h($msg); ?></div><?php endif; ?>

<?php if ($tab==='strings'): ?>
  <div class="card">
    <h3 style="margin-top:0;">إضافة/تعديل ترجمة نص</h3>
    <form method="post">
      <input type="hidden" name="action" value="save_string">
      <div class="grid">
        <div>
          <label>اللغة</label>
          <select name="lang">
            <?php foreach($langs as $code=>$label): ?>
              <option value="<?php echo gdy_h($code); ?>"><?php echo gdy_h($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>المفتاح (Key) <small>مثال: nav.home</small></label>
          <input name="k" placeholder="nav.home">
        </div>
      </div>
      <div style="margin-top:12px;">
        <label>النص المترجم</label>
        <textarea name="v" placeholder="الرئيسية / Home / Accueil"></textarea>
      </div>
      <div style="margin-top:12px;">
        <button type="submit">حفظ</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0;">أمثلة مفاتيح مقترحة</h3>
    <ul>
      <li>nav.home — الرئيسية</li>
      <li>nav.news — الأخبار</li>
      <li>nav.archive — الأرشيف</li>
      <li>nav.contact — تواصل</li>
      <li>search.placeholder — ابحث عن خبر أو موضوع</li>
      <li>breaking — عاجل</li>
    </ul>
  </div>

<?php else: ?>
  <?php
    $scope = isset($_GET['scope']) ? (string)$_GET['scope'] : 'news';
    $item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    $items = array();
    if ($scope === 'category') {
      $items = $pdo->query("SELECT id, name, slug FROM categories ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $items = $pdo->query("SELECT id, title, slug FROM news ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
      $scope = 'news';
    }

    $fields = ($scope === 'category') ? array('name','description') : array('title','excerpt','content');
  ?>
  <div class="card">
    <h3 style="margin-top:0;">اختيار المحتوى</h3>
    <div class="grid">
      <div>
        <label>النوع</label>
        <select onchange="location='?tab=content&scope='+this.value;">
          <option value="news" <?php echo $scope==='news'?'selected':''; ?>>الأخبار</option>
          <option value="category" <?php echo $scope==='category'?'selected':''; ?>>الأقسام</option>
        </select>
      </div>
      <div>
        <label>العنصر</label>
        <select onchange="location='?tab=content&scope=<?php echo gdy_h($scope); ?>&id='+this.value;">
          <option value="0">اختر...</option>
          <?php foreach($items as $it): $label = $scope==='category' ? $it['name'] : $it['title']; ?>
            <option value="<?php echo (int)$it['id']; ?>" <?php echo $item_id===(int)$it['id']?'selected':''; ?>>
              
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <?php if ($item_id): ?>
    <?php foreach($fields as $field): ?>
      <div class="card">
        <h3 style="margin-top:0;"><?php echo gdy_h($scope); ?> → <?php echo gdy_h($field); ?></h3>
        <form method="post">
          <input type="hidden" name="action" value="save_field">
          <input type="hidden" name="scope" value="<?php echo gdy_h($scope); ?>">
          <input type="hidden" name="item_id" value="<?php echo (int)$item_id; ?>">
          <div class="grid">
            <div>
              <label>اللغة</label>
              <select name="lang">
                <?php foreach($langs as $code=>$label): ?>
                  <option value="<?php echo gdy_h($code); ?>"><?php echo gdy_h($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>الحقل</label>
              <input name="field" value="<?php echo gdy_h($field); ?>" readonly>
            </div>
          </div>
          <div style="margin-top:12px;">
            <label>القيمة</label>
            <textarea name="value" placeholder="اكتب الترجمة هنا..."></textarea>
          </div>
          <div style="margin-top:12px;">
            <button type="submit">حفظ</button>
          </div>
        </form>
        <p style="margin:10px 0 0; opacity:.8;">
          ملاحظة: سيستخدم الموقع هذه الترجمة تلقائياً عبر <code>gdy_tr()</code> عندما تكون موجودة.
        </p>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="card"><p>اختر عنصرًا لبدء الترجمة.</p></div>
  <?php endif; ?>

<?php endif; ?>

</body>
</html>
