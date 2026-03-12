<?php

$title = 'Trending';
$period = isset($_GET['period']) ? $_GET['period'] : '24h';
?>
<div class="container py-4">
  <h1><?php echo htmlspecialchars($title); ?></h1>
  <p>Starter scaffold for trending news UX. Supported periods: 24h, 7d, 30d.</p>
  <ul>
    <li><a href="?period=24h">24h</a></li>
    <li><a href="?period=7d">7d</a></li>
    <li><a href="?period=30d">30d</a></li>
  </ul>
</div>
