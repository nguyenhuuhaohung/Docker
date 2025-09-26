<?php
// list_users.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once 'models/UserModel.php';
$userModel = new UserModel();

// Nếu user đã login -> lấy thông tin user hiện tại để lưu vào localStorage
$currentUser = null;
if (!empty($_SESSION['id'])) {
  $found = $userModel->findUserById($_SESSION['id']);
  if (!empty($found) && isset($found[0])) {
    $currentUser = $found[0];
    unset($currentUser['password']);
  }
}

$params = [];
if (!empty($_GET['keyword'])) {
  $params['keyword'] = $_GET['keyword'];
}

$users = $userModel->getUsers($params);

// Tạo CSRF token nếu chưa có
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <title>Home</title>
  <?php include 'views/meta.php' ?>
</head>

<body>
  <?php include 'views/header.php' ?>
  <div class="container">

    <!-- Script để set localStorage nếu user đã login -->
    <script>
      (function () {
        try {
          <?php if ($currentUser): ?>
            const userObj = <?php echo json_encode($currentUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
            localStorage.setItem('user', JSON.stringify(userObj));
            if (userObj.id !== undefined) localStorage.setItem('user_id', String(userObj.id));
            if (userObj.name !== undefined) localStorage.setItem('username', String(userObj.name));
            localStorage.setItem('login_time', new Date().toISOString());
            console.log('LocalStorage: user saved', userObj);
          <?php else: ?>
            localStorage.removeItem('user');
            localStorage.removeItem('user_id');
            localStorage.removeItem('username');
            localStorage.removeItem('login_time');
            console.log('LocalStorage: cleared user keys');
          <?php endif; ?>
        } catch (e) {
          console.warn('LocalStorage error:', e);
        }
      })();
    </script>

    <?php if (!empty($users)) { ?>
      <div class="alert alert-warning" role="alert">
        List of users! <br>
        Hacker: http://php.local/list_users.php?keyword=ASDF%25%22%3BTRUNCATE+banks%3B%23%23
      </div>
      <table class="table table-striped">
        <thead>
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Username</th>
            <th scope="col">Fullname</th>
            <th scope="col">Type</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user) { ?>
            <tr>
              <th scope="row"><?php echo htmlspecialchars($user['id']); ?></th>
              <td><?php echo htmlspecialchars($user['name']); ?></td>
              <td><?php echo htmlspecialchars($user['fullname']); ?></td>
              <td><?php echo htmlspecialchars($user['type']); ?></td>
              <td>
                <a href="form_user.php?id=<?php echo urlencode($user['id']); ?>">
                  <i class="fa fa-pencil-square-o" aria-hidden="true" title="Update"></i>
                </a>
                <a href="view_user.php?id=<?php echo urlencode($user['id']); ?>">
                  <i class="fa fa-eye" aria-hidden="true" title="View"></i>
                </a>

                <!-- Delete: dùng form POST + CSRF -->
                <form method="POST" action="delete_user.php" style="display:inline"
                  onsubmit="return confirm('Bạn có chắc muốn xóa user này?');">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <button type="submit" class="btn btn-link" style="padding:0; border:none; background:none;">
                    <i class="fa fa-eraser" aria-hidden="true" title="Delete"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>

      <!-- CSRF quick-test panel (chèn vào list_users.php) -->
      <div class="card" style="margin-top:30px; padding:16px; border:1px solid #eee; background:#fafafa;">
        <h4>CSRF Demo (test)</h4>
        <p style="color:#555; margin-bottom:8px;">
          Chọn user ID (dùng user test), rồi thử gửi request <strong>không có token</strong> (mô phỏng attacker) hoặc
          <strong>có token</strong> (request hợp lệ).
          <br><strong>Không chạy trên production.</strong>
        </p>

        <label for="test-user-id">User ID to delete:</label>
        <select id="test-user-id" style="margin:6px 0 12px 0;">
          <?php foreach ($users as $u): ?>
            <option value="<?php echo htmlspecialchars($u['id']); ?>">
              <?php echo htmlspecialchars($u['id'] . ' — ' . $u['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <div style="margin-bottom:12px;">
          <button id="btn-csrf-attack" class="btn btn-warning" style="margin-right:8px;">
            Simulate CSRF attack (no token)
          </button>
          <button id="btn-valid-delete" class="btn btn-danger" style="margin-right:8px;">
            Simulate valid delete (with token)
          </button>
          <button id="btn-refresh" class="btn btn-secondary">Refresh list</button>
        </div>

        <div id="csrf-test-log"
          style="white-space:pre-wrap; background:#fff; padding:10px; border:1px solid #ddd; min-height:60px;"></div>

        <!-- CSRF token from server (session) -->
        <script>
          // token được in bởi PHP (đã có $csrf trong file)
          const CSRF_TOKEN = "<?php echo htmlspecialchars($csrf); ?>";

          (function () {
            const logEl = document.getElementById('csrf-test-log');
            const select = document.getElementById('test-user-id');
            const btnAttack = document.getElementById('btn-csrf-attack');
            const btnValid = document.getElementById('btn-valid-delete');
            const btnRefresh = document.getElementById('btn-refresh');

            function appendLog(txt) {
              const t = new Date().toISOString() + ' — ' + txt + '\n';
              logEl.textContent = t + logEl.textContent;
            }

            // Simulate attacker: POST without csrf_token
            btnAttack.addEventListener('click', async function () {
              const id = select.value;
              if (!confirm('Thực sự muốn thử attack (no-token) trên user id=' + id + ' ?')) return;
              appendLog('Sending attack request (no token) for id=' + id);
              try {
                const form = new URLSearchParams();
                form.append('id', id);
                // deliberate: do NOT include csrf_token
                const r = await fetch('delete_user.php', {
                  method: 'POST',
                  body: form,
                  credentials: 'include', // send cookies
                  headers: { 'Accept': 'text/html' }
                });
                appendLog('Response status: ' + r.status + ' ' + r.statusText);
                const text = await r.text();
                appendLog('Response snippet: ' + text.substring(0, 300).replace(/\s+/g, ' ').trim());
              } catch (e) {
                appendLog('Fetch error: ' + e.message);
              }
            });

            // Simulate valid: POST with csrf_token from page
            btnValid.addEventListener('click', async function () {
              const id = select.value;
              if (!confirm('Thực sự muốn gửi request hợp lệ (with-token) xóa user id=' + id + ' ?\n(Thao tác sẽ thực thi nếu bạn có quyền)')) return;
              appendLog('Sending valid delete request (with token) for id=' + id);
              try {
                const form = new URLSearchParams();
                form.append('id', id);
                form.append('csrf_token', CSRF_TOKEN);
                const r = await fetch('delete_user.php', {
                  method: 'POST',
                  body: form,
                  credentials: 'include',
                  headers: { 'Accept': 'text/html' }
                });
                appendLog('Response status: ' + r.status + ' ' + r.statusText);
                const text = await r.text();
                appendLog('Response snippet: ' + text.substring(0, 300).replace(/\s+/g, ' ').trim());
              } catch (e) {
                appendLog('Fetch error: ' + e.message);
              }
            });

            // Refresh list (just reload page)
            btnRefresh.addEventListener('click', function () { location.reload(); });

          })();
        </script>
      <?php } else { ?>
        <div class="alert alert-dark" role="alert">
          This is a dark alert—check it out!
        </div>
      <?php } ?>
    </div>
</body>

</html>