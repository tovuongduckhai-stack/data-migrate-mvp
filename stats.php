<?php
function readLogLines($file) {
    if (!file_exists($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $rows = [];
    foreach ($lines as $line) {
        $row = json_decode($line, true);
        if ($row) $rows[] = $row;
    }
    return $rows;
}

$visits = readLogLines(__DIR__ . '/data/visits.log');
$clicks = readLogLines(__DIR__ . '/data/clicks.log');
$clicks = array_reverse($clicks);

$totalVisits = count($visits);
$totalClicks = count($clicks);
$conversion = $totalVisits > 0 ? round($totalClicks / $totalVisits * 100, 1) : 0;

$counts = [];
foreach ($clicks as $c) {
    $p = $c['plan'];
    $counts[$p] = ($counts[$p] ?? 0) + 1;
}
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
    <?php foreach ($counts as $plan => $n): ?>
      <div class="box"><div class="n"><?= $n ?></div><div class="l"><?= htmlspecialchars($plan) ?></div></div>
    <?php endforeach; ?>
  </div>

  <h2>Recent signups</h2>
  <table>
    <tr><th>Time</th><th>Plan</th><th>Price</th><th>IP</th></tr>
    <?php foreach ($clicks as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['time']) ?></td>
        <td><?= htmlspecialchars($c['plan']) ?></td>
        <td>$<?= htmlspecialchars($c['price']) ?></td>
        <td><?= htmlspecialchars($c['ip']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($clicks)): ?>
      <tr><td colspan="4" style="text-align:center;color:#9aa0ac">No signups yet.</td></tr>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
