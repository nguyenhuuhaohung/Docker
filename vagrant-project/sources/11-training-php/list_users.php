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
    <?php include 'views/header.php'?>
    <div class="container">

        <!-- Script để set localStorage nếu user đã login -->
        <script>
        (function(){
            try {
                <?php if ($currentUser): ?>
                    // Ghi object user vào localStorage (stringified)
                    const userObj = <?php echo json_encode($currentUser, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
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
            } catch(e) {
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
</body>
</html>
