cat > /var/www/html/search_safe.php <<'PHP'
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
$rows = [];

if ($kw !== '') {
    $sql = "SELECT id, name, fullname, email, type FROM users WHERE name LIKE :kw OR fullname LIKE :kw OR email LIKE :kw ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $like = "%{$kw}%";
    $stmt->bindParam(':kw', $like, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT id, name, fullname, email, type FROM users ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Safe Search</title></head><body>
<h3>Safe search (prepared statements)</h3>
<form method="get">
  <input name="keyword" value="<?php echo htmlspecialchars($kw ?? '', ENT_QUOTES); ?>" placeholder="keyword">
  <button>Search</button>
</form>

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
