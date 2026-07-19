<?php
require_once __DIR__ . '/db.php';
turso_ensure_table();

$visitRows = turso_query_rows("SELECT COUNT(*) as n FROM events WHERE type = 'visit'");
$totalVisits = (int)($visitRows[0]['n'] ?? 0);

$clickRows = turso_query_rows("SELECT COUNT(*) as n FROM events WHERE type = 'click'");
$totalClicks = (int)($clickRows[0]['n'] ?? 0);

$conversion = $totalVisits > 0 ? round($totalClicks / $totalVisits * 100, 1) : 0;

$planCounts = turso_query_rows("SELECT plan, COUNT(*) as n FROM events WHERE type = 'click' GROUP BY plan");

$recentClicks = turso_query_rows("SELECT plan, price, ip, time FROM events WHERE type = 'click' ORDER BY id DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stats - Data Migrate</title>
<style>
  body { font-family: -apple-system, sans-serif; background: #0f1115; color: #e8eaed; padding: 40px 20px; }
  .wrap { max-width: 760px; margin: 0 auto; }
  h1 { font-size: 22px; }
  .summary { display: flex; gap: 12px; margin: 20px 0 30px; flex-wrap: wrap; }
  .box { background: #171a21; border: 1px solid #2a2e38; border-radius: 10px; padding: 16px 20px; min-width: 120px; }
  .box .n { font-size: 26px; font-weight: 700; color: #ff6b35; }
  .box .l { font-size: 13px; color: #9aa0ac; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th, td { border: 1px solid #2a2e38; padding: 8px 10px; text-align: left; }
  th { background: #1e222b; color: #9aa0ac; }
  h2 { font-size: 16px; margin-top: 30px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>📊 Data Migrate - Stats</h1>
  <div class="summary">
    <div class="box"><div class="n"><?= $totalVisits ?></div><div class="l">Total visits</div></div>
    <div class="box"><div class="n"><?= $totalClicks ?></div><div class="l">Total signups</div></div>
    <div class="box"><div class="n"><?= $conversion ?>%</div><div class="l">Conversion rate</div></div>
    <?php foreach ($planCounts as $p): ?>
      <div class="box"><div class="n"><?= htmlspecialchars($p['n']) ?></div><div class="l"><?= htmlspecialchars($p['plan']) ?></div></div>
    <?php endforeach; ?>
  </div>

  <h2>Recent signups</h2>
  <table>
    <tr><th>Time</th><th>Plan</th><th>Price</th><th>IP</th></tr>
    <?php foreach ($recentClicks as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['time']) ?></td>
        <td><?= htmlspecialchars($c['plan']) ?></td>
        <td>$<?= htmlspecialchars($c['price']) ?></td>
        <td><?= htmlspecialchars($c['ip']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($recentClicks)): ?>
      <tr><td colspan="4" style="text-align:center;color:#9aa0ac">No signups yet.</td></tr>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
