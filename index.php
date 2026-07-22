<?php
// ============================================================
// Data Migrate - chuyen doi du lieu khach hang giua cac nen tang
// MVP tho: upload CSV -> map cot -> tai CSV moi
// Log ghi thang vao Google Sheet qua Apps Script Web App
// ============================================================

define('SHEET_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbwb9OJfKg4-63zb1MGr7-Q4VFZ4RxDeztYFa1TprFREmCVtR5IKot8QGOoYs_aoxEWt/exec');

function log_to_sheet($type, $plan = '', $price = '') {
    $data = http_build_query([
        'type'  => $type,
        'plan'  => $plan,
        'price' => $price,
        'ip'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 5,
        ],
    ];
    $context = stream_context_create($opts);
    @file_get_contents(SHEET_WEBHOOK_URL, false, $context);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    log_to_sheet('visit');
}

function parse_csv_string($str) {
    $rows = [];
    $lines = explode("\n", str_replace("\r\n", "\n", trim($str)));
    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        $rows[] = str_getcsv($line);
    }
    return $rows;
}

$step = $_POST['step'] ?? 'upload';

if ($step === 'export' && isset($_POST['raw_csv'])) {
    $rawCsv = $_POST['raw_csv'];
    $rows = parse_csv_string($rawCsv);
    $header = array_shift($rows);

    $targetFields = $_POST['target_field'] ?? [];

    $newHeader = [];
    $colIndexes = [];
    foreach ($targetFields as $idx => $name) {
        $name = trim($name);
        if ($name === '') continue;
        $newHeader[] = $name;
        $colIndexes[] = (int)$idx;
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="converted-data.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, $newHeader);
    foreach ($rows as $row) {
        $newRow = [];
        foreach ($colIndexes as $idx) {
            $newRow[] = $row[$idx] ?? '';
        }
        fputcsv($out, $newRow);
    }
    fclose($out);
    exit;
}

$previewHeader = [];
$previewRows = [];
$rawCsv = '';
$error = '';

if ($step === 'mapping' && isset($_FILES['csv_file'])) {
    $content = file_get_contents($_FILES['csv_file']['tmp_name']);
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    $rows = parse_csv_string($content);
    if (count($rows) < 1) {
        $error = 'The CSV file is empty or could not be read.';
    } else {
        $previewHeader = $rows[0];
        $previewRows = array_slice($rows, 1, 5);
        $rawCsv = $content;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Migrate - Move Your Customer Data Between Platforms</title>
<style>
  :root {
    --bg: #0f1115;
    --card: #171a21;
    --border: #2a2e38;
    --text: #e8eaed;
    --muted: #9aa0ac;
    --accent: #ff6b35;
    --accent-hover: #ff8555;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--text);
    padding: 40px 20px;
  }
  .wrap { max-width: 720px; margin: 0 auto; }
  h1 { font-size: 26px; margin-bottom: 4px; }
  .sub { color: var(--muted); margin-bottom: 32px; font-size: 15px; }
  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 28px;
    margin-bottom: 20px;
  }
  label { display: block; font-size: 14px; color: var(--muted); margin-bottom: 8px; }
  input[type=file] {
    width: 100%;
    padding: 24px;
    border: 2px dashed var(--border);
    border-radius: 8px;
    background: transparent;
    color: var(--text);
    margin-bottom: 20px;
  }
  button {
    background: var(--accent);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
  }
  button:hover { background: var(--accent-hover); }
  table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
  th, td { border: 1px solid var(--border); padding: 8px 10px; text-align: left; }
  th { background: #1e222b; color: var(--muted); font-weight: 500; }
  td { color: var(--text); max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .map-row { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; padding: 10px; background: #1c1f27; border-radius: 8px; }
  .map-row .old-col { flex: 1; font-size: 13px; color: var(--muted); }
  .map-row .old-col b { color: var(--text); display: block; font-size: 14px; }
  .map-row input[type=text] { flex: 1; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border); background: #0f1115; color: var(--text); }
  .arrow { color: var(--accent); font-weight: bold; }
  .error { color: #ff6b6b; margin-bottom: 16px; }
  .hint { font-size: 13px; color: var(--muted); margin-top: 6px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>🔄 Data Migrate</h1>
  <p class="sub">Move your customer data from one platform to another — no code, no IT team needed.</p>

  <?php if ($step === 'upload' || $error): ?>
    <div class="card">
      <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="step" value="mapping">
        <label>Upload the CSV exported from your old platform (Shopify, Excel, CRM, POS...)</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit">Upload & preview</button>
      </form>
    </div>
  <?php elseif ($step === 'mapping' && !empty($previewHeader)): ?>
    <div class="card">
      <h3 style="margin-top:0">Data preview</h3>
      <div style="overflow-x:auto">
      <table>
        <tr><?php foreach ($previewHeader as $h): ?><th><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr>
        <?php foreach ($previewRows as $r): ?>
          <tr><?php foreach ($previewHeader as $i => $h): ?><td><?= htmlspecialchars($r[$i] ?? '') ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
      </table>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Rename columns for the new platform</h3>
      <p class="hint">Edit the column names on the right to match your new platform's format. Leave blank to drop a column.</p>
      <form method="post">
        <input type="hidden" name="step" value="export">
        <input type="hidden" name="raw_csv" value="<?= htmlspecialchars($rawCsv) ?>">
        <?php foreach ($previewHeader as $i => $h): ?>
          <div class="map-row">
            <div class="old-col">Old column<b><?= htmlspecialchars($h) ?></b></div>
            <span class="arrow">→</span>
            <input type="text" name="target_field[<?= $i ?>]" value="<?= htmlspecialchars($h) ?>" placeholder="New column name">
          </div>
        <?php endforeach; ?>
        <button type="submit">Download converted CSV</button>
      </form>
    </div>
  <?php endif; ?>

  <div class="card" id="pricing">
    <h3 style="margin-top:0">Pricing</h3>
    <p class="hint" style="margin-bottom:20px">Pick a plan — click to sign up, we'll reach out to activate it.</p>
    <div class="pricing-grid">
      <div class="plan">
        <div class="plan-name">Basic</div>
        <div class="plan-price">$9<span>/mo</span></div>
        <div class="plan-desc">Up to 5 file conversions/month</div>
        <button class="buy-btn" data-plan="Basic" data-price="9">Sign up</button>
      </div>
      <div class="plan featured">
        <div class="plan-name">Standard</div>
        <div class="plan-price">$29<span>/mo</span></div>
        <div class="plan-desc">Unlimited files + custom format mapping</div>
        <button class="buy-btn" data-plan="Standard" data-price="29">Sign up</button>
      </div>
      <div class="plan">
        <div class="plan-name">Enterprise</div>
        <div class="plan-price">Contact us</div>
        <div class="plan-desc">Custom integration + priority support</div>
        <button class="buy-btn" data-plan="Enterprise" data-price="0">Contact us</button>
      </div>
    </div>
    <p id="plan-msg" class="hint" style="display:none;margin-top:16px;color:var(--accent)"></p>
  </div>

  <p class="hint" style="text-align:center;margin-top:24px">Early prototype — files are processed on the fly and not stored.</p>
</div>

<style>
  .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  @media (max-width: 640px) { .pricing-grid { grid-template-columns: 1fr; } }
  .plan { border: 1px solid var(--border); border-radius: 10px; padding: 20px 16px; text-align: center; background: #1c1f27; }
  .plan.featured { border-color: var(--accent); background: #241b16; }
  .plan-name { font-size: 14px; color: var(--muted); margin-bottom: 8px; }
  .plan-price { font-size: 24px; font-weight: 700; margin-bottom: 10px; }
  .plan-price span { font-size: 13px; font-weight: 400; color: var(--muted); }
  .plan-desc { font-size: 13px; color: var(--muted); margin-bottom: 16px; min-height: 36px; }
  .buy-btn { width: 100%; }
</style>

<script>
document.querySelectorAll('.buy-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const plan = btn.dataset.plan;
    const price = btn.dataset.price;
    btn.disabled = true;
    btn.textContent = 'Sending...';
    fetch('log.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'plan=' + encodeURIComponent(plan) + '&price=' + encodeURIComponent(price)
    })
    .then(() => {
      const msg = document.getElementById('plan-msg');
      msg.style.display = 'block';
      msg.textContent = "✅ Got it! We'll reach out shortly to activate your " + plan + ' plan.';
      btn.textContent = 'Signed up';
    })
    .catch(() => {
      btn.disabled = false;
      btn.textContent = 'Sign up';
    });
  });
});
</script>
</body>
</html>
