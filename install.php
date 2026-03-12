<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('GDY_INSTALL_ROOT', __DIR__);
define('GDY_INSTALL_LOCK', GDY_INSTALL_ROOT . '/install.lock');
define('GDY_SCHEMA_FILE', GDY_INSTALL_ROOT . '/install/schema.sql');

const STEP_WELCOME = 1;
const STEP_REQUIREMENTS = 2;
const STEP_DATABASE = 3;
const STEP_SITE = 4;
const STEP_ADMIN = 5;
const STEP_INSTALL = 6;
const STEP_DONE = 7;

if (is_file(GDY_INSTALL_LOCK) && !isset($_GET['force'])) {
    http_response_code(403);
    exit('Installer is locked. Delete install.lock only if you intentionally want to reinstall.');
}

$_SESSION['gdy_installer'] = $_SESSION['gdy_installer'] ?? [
    'db' => [
        'host' => auto_detect_db_host(),
        'port' => auto_detect_db_port(),
        'name' => '',
        'user' => '',
        'pass' => '',
        'charset' => 'utf8mb4',
        'overwrite' => false,
        'tested' => false,
        'test_msg' => '',
        'tables_count' => 0,
    ],
    'site' => [
        'url' => auto_detect_site_url(),
        'name' => 'Godyar News Platform',
        'lang' => 'ar',
        'timezone' => 'Asia/Riyadh',
        'email' => '',
        'description' => 'Modern multilingual newsroom CMS',
    ],
    'admin' => [
        'username' => 'admin',
        'email' => '',
        'password' => '',
    ],
    'errors' => [],
    'log' => [],
];

$step = isset($_GET['step']) ? (int)$_GET['step'] : STEP_WELCOME;
if ($step < STEP_WELCOME || $step > STEP_DONE) {
    $step = STEP_WELCOME;
}

function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function add_error(string $msg): void { $_SESSION['gdy_installer']['errors'][] = $msg; }
function pop_errors(): array { $e = $_SESSION['gdy_installer']['errors'] ?? []; $_SESSION['gdy_installer']['errors'] = []; return $e; }
function log_line(string $msg): void { $_SESSION['gdy_installer']['log'][] = '['.date('H:i:s').'] '.$msg; }
function redirect_step(int $s): never { header('Location: ?step=' . $s); exit; }

function auto_detect_db_host(): string {
    foreach ([getenv('DB_HOST') ?: null, $_ENV['DB_HOST'] ?? null, 'localhost', '127.0.0.1'] as $v) {
        if (is_string($v) && trim($v) !== '') return trim($v);
    }
    return 'localhost';
}
function auto_detect_db_port(): string {
    foreach ([getenv('DB_PORT') ?: null, $_ENV['DB_PORT'] ?? null, '3306'] as $v) {
        if (is_string($v) && trim($v) !== '') return trim($v);
    }
    return '3306';
}
function auto_detect_site_url(): string {
    $https = $_SERVER['HTTPS'] ?? '';
    $scheme = ($https && strtolower((string)$https) !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/install.php');
    $dir = rtrim(dirname($script), '/.');
    return $scheme . '://' . $host . ($dir && $dir !== '/' ? $dir : '');
}

function requirements(): array {
    $items = [];
    $items[] = ['PHP >= 8.1', PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>=')];
    foreach (['pdo', 'pdo_mysql', 'mbstring', 'json'] as $ext) {
        $items[] = ["Extension: {$ext}", extension_loaded($ext) ? 'Loaded' : 'Missing', extension_loaded($ext)];
    }
    foreach (['uploads','cache','logs','tmp','backup','storage','install'] as $dir) {
        $path = GDY_INSTALL_ROOT . '/' . $dir;
        if (!is_dir($path)) @mkdir($path, 0755, true);
        $items[] = ["Writable: /{$dir}", is_writable($path) ? 'Yes' : 'No', is_writable($path)];
    }
    $items[] = ['Writable: project root (.env)', is_writable(GDY_INSTALL_ROOT) ? 'Yes' : 'No', is_writable(GDY_INSTALL_ROOT)];
    $items[] = ['File: install/schema.sql', is_file(GDY_SCHEMA_FILE) ? 'Found' : 'Missing', is_file(GDY_SCHEMA_FILE)];
    return $items;
}

function pdo_connect(array $db, bool $withDb = true): PDO {
    $host = trim((string)($db['host'] ?? 'localhost'));
    $port = trim((string)($db['port'] ?? '3306'));
    $name = trim((string)($db['name'] ?? ''));
    $charset = trim((string)($db['charset'] ?? 'utf8mb4')) ?: 'utf8mb4';
    $dsn = "mysql:host={$host};port={$port};charset={$charset}";
    if ($withDb && $name !== '') $dsn .= ";dbname={$name}";
    return new PDO($dsn, (string)($db['user'] ?? ''), (string)($db['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function test_database_connection(array $db): array {
    try {
        $server = pdo_connect($db, false);
        $server->query('SELECT 1');
        $name = trim((string)$db['name']);
        if ($name === '') {
            return ['ok'=>false,'message'=>'Database name is required.','tables_count'=>0];
        }
        try {
            $pdoDb = pdo_connect($db, true);
        } catch (Throwable $e) {
            return ['ok'=>false,'message'=>'Cannot access selected database. Create it in hosting panel and assign this user to it. Details: '.$e->getMessage(),'tables_count'=>0];
        }
        $count = (int)$pdoDb->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type='BASE TABLE'")->fetchColumn();
        return ['ok'=>true,'message'=>"Database connection successful. Existing tables: {$count}",'tables_count'=>$count];
    } catch (Throwable $e) {
        return ['ok'=>false,'message'=>'Database connection failed: '.$e->getMessage(),'tables_count'=>0];
    }
}

function drop_existing_objects(PDO $pdo): void {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $pdo->exec('DROP TABLE IF EXISTS `'.str_replace('`','``',(string)$t).'`');
    }
    $views = $pdo->query("SELECT table_name FROM information_schema.views WHERE table_schema = DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($views as $v) {
        $pdo->exec('DROP VIEW IF EXISTS `'.str_replace('`','``',(string)$v).'`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function execute_sql_file(PDO $pdo, string $path): void {
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) throw new RuntimeException('Cannot read schema.sql');
    $delimiter = ';';
    $buffer = '';
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#')) continue;
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trim, $m)) {
            $delimiter = trim($m[1]);
            continue;
        }
        $buffer .= $line . "\n";
        if ($delimiter !== '' && str_ends_with(rtrim($trim), $delimiter)) {
            $statement = rtrim($buffer);
            $statement = preg_replace('/' . preg_quote($delimiter, '/') . '\\s*$/', '', $statement);
            $statement = trim((string)$statement);
            if ($statement !== '') $pdo->exec($statement);
            $buffer = '';
        }
    }
    $tail = trim($buffer);
    if ($tail !== '') $pdo->exec($tail);
}

function create_env_file(array $db, array $site): void {
    $path = GDY_INSTALL_ROOT . '/.env';
    $key = bin2hex(random_bytes(32));
    $lines = [
        'APP_ENV=production',
        'APP_DEBUG=false',
        'APP_URL=' . ($site['url'] ?: 'https://example.com'),
        'TIMEZONE=' . ($site['timezone'] ?: 'Asia/Riyadh'),
        'DB_DRIVER=mysql',
        'DB_HOST=' . $db['host'],
        'DB_PORT=' . ($db['port'] ?: '3306'),
        'DB_NAME=' . $db['name'],
        'DB_USER=' . $db['user'],
        'DB_PASS=' . $db['pass'],
        'DB_CHARSET=' . ($db['charset'] ?: 'utf8mb4'),
        'ENCRYPTION_KEY=' . $key,
        'SITE_LANG_DEFAULT=' . ($site['lang'] ?: 'ar'),
        'GDY_INSTALLED=1',
    ];
    file_put_contents($path, implode("\n", $lines) . "\n");
}

function seed_basic_settings(PDO $pdo, array $site): void {
    $settings = [
        'site_name' => $site['name'] ?: 'Godyar News Platform',
        'site_url' => $site['url'] ?: '',
        'site_lang' => $site['lang'] ?: 'ar',
        'site_timezone' => $site['timezone'] ?: 'Asia/Riyadh',
        'site_email' => $site['email'] ?: '',
        'site_description' => $site['description'] ?: '',
        'site_version' => 'v3.5.0-stable',
        'site_installed_at' => date('Y-m-d H:i:s'),
        'front_preset' => 'blue',
    ];
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($settings as $k => $v) $stmt->execute([$k, (string)$v]);
}

function create_admin_user(PDO $pdo, array $admin): void {
    $username = trim((string)$admin['username']);
    $email = trim((string)$admin['email']);
    $password = (string)$admin['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->execute([$username, $email]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $up = $pdo->prepare("UPDATE users SET name=?, email=?, password_hash=?, password=NULL, role='admin', is_admin=1, status='active' WHERE id=?");
        $up->execute([$username, $email, $hash, $id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO users (name, username, email, password_hash, password, role, is_admin, status, created_at) VALUES (?, ?, ?, ?, NULL, 'admin', 1, 'active', NOW())");
        $ins->execute([$username, $username, $email, $hash]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data =& $_SESSION['gdy_installer'];
    $action = (string)($_POST['_action'] ?? 'save');

    if ($step === STEP_WELCOME) redirect_step(STEP_REQUIREMENTS);

    if ($step === STEP_REQUIREMENTS) {
        $failed = array_filter(requirements(), fn($row) => !$row[2]);
        if ($failed) {
            foreach ($failed as $f) add_error('Requirement failed: '.$f[0]);
        } else {
            redirect_step(STEP_DATABASE);
        }
    }

    if ($step === STEP_DATABASE) {
        $data['db']['host'] = trim((string)($_POST['db_host'] ?? 'localhost'));
        $data['db']['port'] = trim((string)($_POST['db_port'] ?? '3306'));
        $data['db']['name'] = trim((string)($_POST['db_name'] ?? ''));
        $data['db']['user'] = trim((string)($_POST['db_user'] ?? ''));
        $data['db']['pass'] = (string)($_POST['db_pass'] ?? '');
        $data['db']['charset'] = trim((string)($_POST['db_charset'] ?? 'utf8mb4')) ?: 'utf8mb4';
        $data['db']['overwrite'] = !empty($_POST['db_overwrite']);
        $data['db']['tested'] = false;
        $data['db']['test_msg'] = '';
        $data['db']['tables_count'] = 0;
        if ($data['db']['name'] === '' || $data['db']['user'] === '') {
            add_error('Database name and user are required.');
        } else {
            $result = test_database_connection($data['db']);
            $data['db']['tested'] = (bool)$result['ok'];
            $data['db']['test_msg'] = (string)$result['message'];
            $data['db']['tables_count'] = (int)($result['tables_count'] ?? 0);
            if (!$result['ok']) {
                add_error($result['message']);
            } elseif ($data['db']['tables_count'] > 0 && !$data['db']['overwrite']) {
                add_error("Database is not empty ({$data['db']['tables_count']} tables). Enable overwrite or choose an empty database.");
            } elseif ($action === 'save_db') {
                redirect_step(STEP_SITE);
            }
        }
    }

    if ($step === STEP_SITE) {
        $data['site']['name'] = trim((string)($_POST['site_name'] ?? 'Godyar News Platform'));
        $data['site']['url'] = trim((string)($_POST['site_url'] ?? ''));
        $data['site']['lang'] = trim((string)($_POST['site_lang'] ?? 'ar'));
        $data['site']['timezone'] = trim((string)($_POST['site_timezone'] ?? 'Asia/Riyadh'));
        $data['site']['email'] = trim((string)($_POST['site_email'] ?? ''));
        $data['site']['description'] = trim((string)($_POST['site_description'] ?? ''));
        if ($data['site']['url'] === '') add_error('Site URL is required.');
        if ($data['site']['email'] !== '' && !filter_var($data['site']['email'], FILTER_VALIDATE_EMAIL)) add_error('Site email is invalid.');
        if (!($_SESSION['gdy_installer']['errors'] ?? [])) redirect_step(STEP_ADMIN);
    }

    if ($step === STEP_ADMIN) {
        $data['admin']['username'] = trim((string)($_POST['admin_user'] ?? 'admin'));
        $data['admin']['email'] = trim((string)($_POST['admin_email'] ?? ''));
        $data['admin']['password'] = (string)($_POST['admin_pass'] ?? '');
        $confirm = (string)($_POST['admin_pass_confirm'] ?? '');
        if (strlen($data['admin']['username']) < 3) add_error('Admin username must be at least 3 characters.');
        if (!filter_var($data['admin']['email'], FILTER_VALIDATE_EMAIL)) add_error('Valid admin email is required.');
        if (strlen($data['admin']['password']) < 8) add_error('Admin password must be at least 8 characters.');
        if ($data['admin']['password'] !== $confirm) add_error('Passwords do not match.');
        if (!($_SESSION['gdy_installer']['errors'] ?? [])) redirect_step(STEP_INSTALL);
    }

    if ($step === STEP_INSTALL) {
        $data['errors'] = [];
        $data['log'] = [];
        try {
            log_line('Connecting to selected database');
            $pdo = pdo_connect($data['db'], true);
            if (!empty($data['db']['overwrite'])) {
                log_line('Overwrite enabled: removing existing tables and views');
                drop_existing_objects($pdo);
            }
            log_line('Importing install/schema.sql');
            execute_sql_file($pdo, GDY_SCHEMA_FILE);
            log_line('Seeding basic settings');
            seed_basic_settings($pdo, $data['site']);
            log_line('Creating admin account');
            create_admin_user($pdo, $data['admin']);
            log_line('Writing .env file');
            create_env_file($data['db'], $data['site']);
            log_line('Creating installer lock');
            file_put_contents(GDY_INSTALL_LOCK, date('c'));
            log_line('Installation completed successfully');
            redirect_step(STEP_DONE);
        } catch (Throwable $e) {
            add_error('Installation failed: '.$e->getMessage());
            log_line('ERROR: '.$e->getMessage());
        }
    }
}

$errors = pop_errors();
$data = $_SESSION['gdy_installer'];
$steps = [STEP_WELCOME=>'الترحيب', STEP_REQUIREMENTS=>'المتطلبات', STEP_DATABASE=>'قاعدة البيانات', STEP_SITE=>'الموقع', STEP_ADMIN=>'المدير', STEP_INSTALL=>'التثبيت', STEP_DONE=>'الانتهاء'];
$progressMap = [STEP_WELCOME=>10, STEP_REQUIREMENTS=>25, STEP_DATABASE=>40, STEP_SITE=>60, STEP_ADMIN=>78, STEP_INSTALL=>92, STEP_DONE=>100];
$progress = $progressMap[$step] ?? 0;
?><!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Godyar News Platform - Installer Pro</title>
<style>
:root{--bg:#081120;--card:#0f172a;--line:#22304a;--muted:#94a3b8;--text:#e2e8f0;--primary:#4f46e5;--primary2:#8b5cf6;--ok:#10b981;--bad:#ef4444;}
*{box-sizing:border-box}body{margin:0;background:linear-gradient(180deg,#050b16,#0a1322);color:var(--text);font-family:Tahoma,Arial,sans-serif;padding:28px}.wrap{max-width:1040px;margin:0 auto}.card{background:var(--card);border:1px solid var(--line);border-radius:22px;overflow:hidden;box-shadow:0 35px 90px rgba(0,0,0,.45)}.header{padding:28px 30px;background:linear-gradient(135deg,var(--primary),var(--primary2));display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}.header h1{margin:0;font-size:34px;line-height:1.2}.header small{display:block;opacity:.95;margin-top:8px;font-size:18px}.version{display:inline-block;padding:10px 18px;border-radius:999px;background:#1e293b;color:#fff;font-weight:800;font-size:18px}.body{padding:24px 28px 30px}.muted{color:var(--muted)}input,select,textarea{width:100%;padding:14px 16px;border-radius:14px;border:1px solid 
</style>
</head>
<body><div class="wrap"><div class="card"><div class="header"><div><h1>🚀 مُثبّت Godyar News Platform</h1><small>نسخة نظيفة جاهزة للتثبيت مع فحص اتصال قاعدة البيانات واستيراد آمن</small></div><div class="version">v3.5.0-stable</div></div><div class="body"><div class="muted">الخطوة الحالية: <strong><?php echo h($steps[$step] ?? 'Installer'); ?></strong></div><div class="progress"><div class="bar"><div class="fill"></div></div><div class="steps"><?php foreach ($steps as $idx=>$label): ?><div class="step-pill <?php echo $idx === $step ? 'active' : ''; ?>"><?php echo h($label); ?></div><?php endforeach; ?></div></div><?php if ($errors): ?><div class="err"><strong>حدثت أخطاء:</strong><ul><?php foreach ($errors as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($step === STEP_WELCOME): ?>
<p>سيقوم هذا المثبّت باستيراد بنية قاعدة البيانات النظيفة، إنشاء ملف <code>.env</code>، وإعداد حساب المدير.</p><div class="help">مهم: استخدم قاعدة بيانات فارغة أو فعّل خيار الكتابة فوق الجداول الحالية إذا كنت تعيد التثبيت.</div><form method="post" class="btns"><button class="btn">ابدأ التثبيت</button></form>
<?php elseif ($step === STEP_REQUIREMENTS): $req=requirements(); $all=true; ?>
<table class="table"><thead><tr><th>المتطلب</th><th>الحالة</th></tr></thead><tbody><?php foreach ($req as $r): $ok=(bool)$r[2]; $all=$all&&$ok; ?><tr><td><?php echo h($r[0]); ?> <span class="muted">(<?php echo h($r[1]); ?>)</span></td><td><?php echo $ok?'<span class="pass">✓</span>':'<span class="fail">✗</span>'; ?></td></tr><?php endforeach; ?></tbody></table><form method="post" class="btns"><button class="btn" <?php echo $all ? '' : 'disabled'; ?>>متابعة</button></form>
<?php elseif ($step === STEP_DATABASE): ?>
<form method="post"><div class="grid"><div><label>DB Host</label><input name="db_host" value="<?php echo h($data['db']['host']); ?>" required></div><div><label>DB Port</label><input name="db_port" value="<?php echo h($data['db']['port']); ?>" required></div></div><label>DB Name</label><input name="db_name" value="<?php echo h($data['db']['name']); ?>" required><div class="grid"><div><label>DB User</label><input name="db_user" value="<?php echo h($data['db']['user']); ?>" required></div><div><label>DB Password</label><input type="password" name="db_pass" value=""></div></div><label>Charset</label><select name="db_charset"><option value="utf8mb4" <?php echo $data['db']['charset']==='utf8mb4'?'selected':''; ?>>utf8mb4</option><option value="utf8" <?php echo $data['db']['charset']==='utf8'?'selected':''; ?>>utf8</option></select><div class="help"><label class="inline" style="margin:0;cursor:pointer;"><input type="checkbox" name="db_overwrite" value="1" <?php echo !empty($data['db']['overwrite'])?'checked':''; ?>><span><strong>Overwrite existing tables</strong> — يحذف الجداول الحالية ثم يعيد إنشاءها</span></label></div><?php if (!empty($data['db']['test_msg'])): ?><div class="<?php echo !empty($data['db']['tested']) ? 'okbox' : 'err'; ?>"><?php echo h($data['db']['test_msg']); ?></div><?php endif; ?><div class="btns"><button class="btn secondary" type="submit" name="_action" value="test_db">Test Connection</button><button class="btn ok" type="submit" name="_action" value="save_db">حفظ والمتابعة</button></div></form>
<?php elseif ($step === STEP_SITE): ?>
<form method="post"><label>اسم الموقع</label><input name="site_name" value="<?php echo h($data['site']['name']); ?>" required><label>رابط الموقع (URL)</label><input name="site_url" value="<?php echo h($data['site']['url']); ?>" required><div class="grid"><div><label>اللغة الافتراضية</label><select name="site_lang"><option value="ar" <?php echo $data['site']['lang']==='ar'?'selected':''; ?>>العربية</option><option value="en" <?php echo $data['site']['lang']==='en'?'selected':''; ?>>English</option><option value="fr" <?php echo $data['site']['lang']==='fr'?'selected':''; ?>>Français</option></select></div><div><label>المنطقة الزمنية</label><input name="site_timezone" value="<?php echo h($data['site']['timezone']); ?>"></div></div><label>بريد الموقع</label><input name="site_email" value="<?php echo h($data['site']['email']); ?>" placeholder="admin@example.com"><label>وصف الموقع</label><textarea name="site_description" rows="3"><?php echo h($data['site']['description']); ?></textarea><div class="btns"><button class="btn ok">متابعة</button></div></form>
<?php elseif ($step === STEP_ADMIN): ?>
<form method="post"><div class="grid"><div><label>اسم المدير</label><input name="admin_user" value="<?php echo h($data['admin']['username']); ?>" required></div><div><label>البريد الإلكتروني</label><input type="email" name="admin_email" value="<?php echo h($data['admin']['email']); ?>" required></div></div><div class="grid"><div><label>كلمة المرور</label><input type="password" name="admin_pass" required></div><div><label>تأكيد كلمة المرور</label><input type="password" name="admin_pass_confirm" required></div></div><div class="btns"><button class="btn ok">متابعة للتثبيت</button></div></form>
<?php elseif ($step === STEP_INSTALL): ?>
<p>سيتم الآن استيراد <code>install/schema.sql</code> ثم إعداد الموقع وإنشاء حساب المدير.</p><form method="post" class="btns"><button class="btn ok">تنفيذ التثبيت الآن</button></form><?php if (!empty($data['log'])): ?><div class="log" style="margin-top:14px"><?php foreach ($data['log'] as $l) echo h($l)."\n"; ?></div><?php endif; ?>
<?php elseif ($step === STEP_DONE): ?>
<div class="okbox">✅ تم التثبيت بنجاح. احذف <code>install.php</code> وملف <code>install.lock</code> بعد التأكد من عمل الموقع.</div><?php if (!empty($data['log'])): ?><div class="log" style="margin-top:14px"><?php foreach ($data['log'] as $l) echo h($l)."\n"; ?></div><?php endif; ?><div class="btns"><a class="btn" href="<?php echo h($data['site']['url'] ?: '/'); ?>">فتح الموقع</a><a class="btn ok" href="<?php echo h(rtrim($data['site']['url'] ?: '', '/') . '/admin'); ?>">لوحة التحكم</a></div>
<?php endif; ?>
</div></div></div></body></html>
