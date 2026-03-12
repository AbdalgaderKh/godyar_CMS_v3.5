
<?php
require_once dirname(__DIR__,2).'/includes/i18n.php';
GodyarI18n::init();
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Godyar i18n Test</title>
</head>
<body>
<h1>Translation Test</h1>
<p><?= __('nav.login','Login') ?></p>
<p><?= __('search.title','Search') ?></p>
<p><?= __('footer.about','About') ?></p>
</body>
</html>
