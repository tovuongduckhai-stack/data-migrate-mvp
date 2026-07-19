<?php
$logFile = __DIR__ . '/data/clicks.log';
$clicks = [];
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $row = json_decode($line, true);
        if ($row) $clicks[] = $row;
    }
}
$clicks = array_reverse($clicks); // mới nhất lên đầu

$counts = [];
foreach ($clicks as $c) {
    $p = $c['plan'];
    $counts[$p] = ($counts[$p] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thống kê đăng ký</title>
<style>
  body { font-family: -apple-system, sans-serif; background: #0f1115; color: #e8eaed; padding: 40px 20px; }
  .wrap { max-width: 700px; margin: 0 auto; }
  h1 { font-size: 22px; }
  .summary { display: flex; gap: 12px; margin: 20px 0 30px; flex-wrap: wrap; }
  .box { background: #171a21; border: 1px solid #2a2e38; border-radius: 10px; padding: 16px 20px; }
  .box .n { font-size: 26px; font-weight: 700; color: #ff6b35; }
  .box .l { font-size: 13px; color: #9aa0ac; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th, td { border: 1px solid #2a2e38; padding: 8px 10px; text-align: left; }
  th { background: #1e222b; color: #9aa0ac; }
</style>
</head>
<body>
<div class="wrap">
  <h1>📊 Thống kê đăng ký gói</h1>
  <div class="summary">
    <div class="box"><div class="n"><?= count($clicks) ?></div><div class="l">Tổng lượt bấm</div></div>
    <?php foreach ($counts as $plan => $n): ?>
      <div class="box"><div class="n"><?= $n ?></div><div class="l"><?= htmlspecialchars(str_replace('_',' ', $plan)) ?></div></div>
    <?php endforeach; ?>
  </div>

  <table>
    <tr><th>Thời gian</th><th>Gói</th><th>Giá</th><th>IP</th></tr>
    <?php foreach ($clicks as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['time']) ?></td>
        <td><?= htmlspecialchars(str_replace('_',' ', $c['plan'])) ?></td>
        <td><?= number_format((float)$c['price']) ?>đ</td>
        <td><?= htmlspecialchars($c['ip']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($clicks)): ?>
      <tr><td colspan="4" style="text-align:center;color:#9aa0ac">Chưa có ai bấm đăng ký.</td></tr>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
