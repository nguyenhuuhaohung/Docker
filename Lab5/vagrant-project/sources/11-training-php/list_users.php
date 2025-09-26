<?php
// Start the session
session_start();

require_once 'models/UserModel.php';
$userModel = new UserModel();

// Nếu user đã login (session) -> lấy thông tin user hiện tại để lưu vào localStorage
$currentUser = null;
if (!empty($_SESSION['id'])) {
    // findUserById trả về mảng kết quả, giữ nguyên tùy model của bạn
    $found = $userModel->findUserById($_SESSION['id']);
    if (!empty($found) && isset($found[0])) {
        $currentUser = $found[0];
        // Nếu bạn muốn loại bỏ trường nhạy cảm trước khi gửi ra client:
        unset($currentUser['password']);
    }
}

$params = [];
if (!empty($_GET['keyword'])) {
    $params['keyword'] = $_GET['keyword'];
}

$users = $userModel->getUsers($params);
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
                        // Ghi object user vào localStorage (stringified)
                        const userObj = <?php echo json_encode($currentUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
                        localStorage.setItem('user', JSON.stringify(userObj));
                        // thêm vài key tiện dụng
                        if (userObj.id !== undefined) localStorage.setItem('user_id', String(userObj.id));
                        if (userObj.name !== undefined) localStorage.setItem('username', String(userObj.name));
                        // optional: lưu thời gian login
                        localStorage.setItem('login_time', new Date().toISOString());
                        console.log('LocalStorage: user saved', userObj);
                    <?php else: ?>
                        // Nếu chưa login thì xoá dữ liệu liên quan để tránh nhầm lẫn
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
                                <a href="delete_user.php?id=<?php echo urlencode($user['id']); ?>">
                                    <i class="fa fa-eraser" aria-hidden="true" title="Delete"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="alert alert-dark" role="alert">
                This is a dark alert—check it out!
            </div>
        <?php } ?>
    </div>
    <!-- BEGIN XSS DEMO (DEV ONLY) -->
    <hr>
    <div style="margin-top:24px;padding:16px;border:1px solid #e5e5e5;background:#fff;">
        <h4>XSS Demo (Dev only)</h4>
        <p style="color:#a00">Chỉ dùng trên môi trường local. Không dùng payload ăn cắp cookie / exfiltrate.</p>

        <!-- REFLECTED XSS -->
        <div style="margin-bottom:16px;">
            <h5>Reflected XSS (phản chiếu)</h5>
            <form method="get" action="">
                <input name="demo_q" style="width:60%"
                    placeholder="Nhập payload, ví dụ: &lt;script&gt;alert('XSS')&lt;/script&gt;"
                    value="<?php echo isset($_GET['demo_q']) ? htmlspecialchars($_GET['demo_q'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : ''; ?>">
                <button>Send</button>
            </form>

            <div style="margin-top:8px; padding:8px; border:1px dashed #ccc;">
                <strong>Reflected output:</strong>
                <div style="margin-top:6px;">
                    <?php
                    // VULNERABLE logic for demo only: phản chiếu trực tiếp (không escape)
                    if (isset($_GET['demo_q'])) {
                        // Warning: dòng dưới là intentionally vulnerable cho demo
                        echo $_GET['demo_q'];
                    } else {
                        echo '<em>(chưa có input)</em>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- STORED XSS (session-based, dev only) -->
        <div style="margin-bottom:16px;">
            <h5>Stored XSS (lưu trong session)</h5>

            <?php
            // session must be started earlier in your file (bạn đã session_start())
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xss_store_submit'])) {
                $name = $_POST['xss_name'] ?? 'anon';
                $comment = $_POST['xss_comment'] ?? '';

                // Lưu trực tiếp (vulnerable) vào session array - dev only
                if (!isset($_SESSION['xss_comments']))
                    $_SESSION['xss_comments'] = [];
                $_SESSION['xss_comments'][] = ['name' => $name, 'comment' => $comment, 'time' => date('c')];

                // redirect để tránh resubmit
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            }

            $stored = $_SESSION['xss_comments'] ?? [];
            ?>

            <form method="post" style="margin-bottom:8px;">
                Name: <input name="xss_name" style="width:150px;" value=""><br><br>
                Comment:<br>
                <textarea name="xss_comment" rows="4" cols="60"></textarea><br>
                <button name="xss_store_submit">Submit comment (stored)</button>
            </form>

            <div style="padding:8px; border:1px dashed #ccc;">
                <strong>Stored comments:</strong>
                <ul>
                    <?php foreach ($stored as $c): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($c['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                            <span style="color:#666"> @
                                <?php echo htmlspecialchars($c['time'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                            <div>
                                <?php
                                // VULNERABLE: in comment trực tiếp (intentionally vulnerable for demo)
                                echo $c['comment'];
                                ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- SAFE CHECK: show safe escaping example -->
        <div style="margin-top:12px;padding:8px;border-top:1px solid #eee;color:#333;">
            <strong>Safe example (how to fix)</strong>
            <pre
                style="background:#f7f7f7;padding:8px;margin-top:6px;">&lt;?php echo htmlspecialchars($user_input, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?&gt;</pre>
        </div>
    </div>
    <!-- END XSS DEMO -->

</body>

</html>