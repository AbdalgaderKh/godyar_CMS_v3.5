
<?php
require_once dirname(__DIR__,2).'/includes/i18n.php';
GodyarI18n::init();
?>
<h2>Translation Test</h2>

<p><?=__('nav.home','Home')?></p>
<p><?=__('nav.login','Login')?></p>
<p><?=__('news.breaking','Breaking')?></p>

<p>Current language: <?=GodyarI18n::lang()?></p>
