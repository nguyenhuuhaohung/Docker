cat > /var/www/html/search_vulnerable.php <<'PHP'
<?php
$dbHost = 'web-mysql';
$dbName = 'app_web1';
$dbUser = 'root';
$dbPass = 'root123';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die('DB connect error: ' . $e->getMessage());
}

$kw = isset($_GET['keyword']) ? $_GET['keyword'] : '';

$sql = "SELECT id, name, fullname, email, type FROM users";
if ($kw !== '') {
    $sql .= " WHERE name LIKE '%" . $kw . "%' OR fullname LIKE '%" . $kw . "%' OR email LIKE '%" . $kw . "%'";
}
$sql .= " ORDER BY id ASC";

$rows = [];
try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Vulnerable Search</title></head><body>
<h3>Vulnerable search (DEV only)</h3>
<p style="color:red">VULNERABLE: do not expose this on production.</p>

<form method="get">
  <input name="keyword" value="<?php echo htmlspecialchars($kw ?? '', ENT_QUOTES); ?>" placeholder="keyword">
  <button>Search</button>
</form>

<?php if (!empty($error)): ?>
  <div style="color:darkred">Error: <?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
<?php endif; ?>

<table border="1" cellpadding="6">
<tr><th>ID</th><th>Name</th><th>Fullname</th><th>Email</th><th>Type</th></tr>
<?php foreach ($rows as $r): ?>
  <tr>
    <td><?php echo (int)$r['id']; ?></td>
    <td><?php echo htmlspecialchars($r['name'], ENT_QUOTES); ?></td>
    <td><?php echo htmlspecialchars($r['fullname'], ENT_QUOTES); ?></td>
    <td><?php echo htmlspecialchars($r['email'], ENT_QUOTES); ?></td>
    <td><?php echo htmlspecialchars($r['type'], ENT_QUOTES); ?></td>
  </tr>
<?php endforeach; ?>
</table>
</body></html>
PHP
