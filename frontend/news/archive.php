<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$to = base_url('/archive');
if (headers_sent() === false) {
    header('Location: ' . $to, true, 301);
    exit;
}

?><!doctype html>
<meta charset = "utf-8">
<meta http-equiv = "refresh" content = "0;url=<?php echo htmlspecialchars($to, ENT_QUOTES, 'UTF-8'); ?>">
<a href = "<?php echo htmlspecialchars($to, ENT_QUOTES, 'UTF-8'); ?>">Continue</a>
