
<?php

$url = "../../tools/translation_scanner.php";
$data = @file_get_contents($url);
$items = json_decode($data,true);
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<title>Translation Coverage</title>
<style>
body{font-family:Arial;margin:40px;background:#f5f5f5}
.card{background:white;padding:20px;border-radius:8px;margin-bottom:20px}
.file{font-weight:bold;color:#333}
.line{color:#666;font-size:13px}
</style>
</head>
<body>

<h1>Missing Translation Strings</h1>

<?php if(!$items){ ?>
<div class="card">No missing strings detected 🎉</div>
<?php } else { ?>
<?php foreach($items as $file=>$rows){ ?>
<div class="card">
<div class="file"><?php echo $file ?></div>
<?php foreach($rows as $r){ ?>
<div class="line">Line <?php echo $r['line']?> — <?php echo htmlentities($r['text']) ?></div>
<?php } ?>
</div>
<?php } ?>
<?php } ?>

</body>
</html>
