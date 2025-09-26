<?php
// Start the session
session_start();
require_once 'models/UserModel.php';
$userModel = new UserModel();

$user = NULL; // user data
$_id = NULL;

// Nếu có id thì là update
if (!empty($_GET['id'])) {
    $_id = $_GET['id'];
    $user = $userModel->findUserById($_id);
}

// Xử lý form submit
if (!empty($_POST['submit'])) {
    // Nếu có id thì update, không thì insert
    if (!empty($_POST['id'])) {
        $userModel->updateUser($_POST);
    } else {
        $userModel->insertUser($_POST);
    }
    header('location: list_users.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User form</title>
    <?php include 'views/meta.php' ?>
</head>
<body>
<?php include 'views/header.php'?>
<div class="container">
    <div class="alert alert-warning" role="alert">
        <?php echo !empty($_id) ? "Update User" : "Register User"; ?>
    </div>

    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $_id ?>">

        <div class="form-group">
            <label for="name">Username</label>
            <input class="form-control" name="name" placeholder="Username"
                   value="<?php echo !empty($user[0]['name']) ? $user[0]['name'] : '' ?>">
        </div>

        <div class="form-group">
            <label for="fullname">Fullname</label>
            <input class="form-control" name="fullname" placeholder="Fullname"
                   value="<?php echo !empty($user[0]['fullname']) ? $user[0]['fullname'] : '' ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Password">
        </div>

        <div class="form-group">
            <label for="type">Type</label>
            <select name="type" class="form-control">
                <option value="user" <?php if (!empty($user[0]['type']) && $user[0]['type'] == 'user') echo 'selected'; ?>>User</option>
                <option value="admin" <?php if (!empty($user[0]['type']) && $user[0]['type'] == 'admin') echo 'selected'; ?>>Admin</option>
            </select>
        </div>

        <button type="submit" name="submit" value="submit" class="btn btn-primary">
            <?php echo !empty($_id) ? "Update" : "Register"; ?>
        </button>
    </form>
</div>
</body>
</html>
