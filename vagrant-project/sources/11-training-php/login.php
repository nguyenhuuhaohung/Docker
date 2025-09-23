<?php
// Start the session
session_start();

require_once 'models/UserModel.php';
$userModel = new UserModel();

if (!empty($_POST['submit'])) {
    $users = [
        'username' => $_POST['username'],
        'password' => $_POST['password']
    ];

    if ($user = $userModel->auth($users['username'], $users['password'])) {

        // Login successful
        $_SESSION['id'] = $user[0]['id'];
        $_SESSION['message'] = 'Login successful';

        // ==== Lưu log vào Redis ====
        try {
            $redis = new Redis();
            $redis->connect('redis', 6379); // dùng service name "redis"
            // thay bằng tên service trong docker-compose

            $logData = json_encode([
                'user_id' => $user[0]['id'],
                'username' => $users['username'],
                'time' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);

            $redis->lPush('user:logins', $logData);
        } catch (Exception $e) {
            error_log("Redis error: " . $e->getMessage());
        }

        // Redirect
        header('location: list_users.php');
        exit();
    } else {
        $_SESSION['message'] = 'Login failed';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>User form</title>
    <?php include 'views/meta.php' ?>
</head>

<body>
    <?php include 'views/header.php' ?>

    <div class="container">
        <div id="loginbox" style="margin-top:50px;" class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title">Login</div>
                    <div style="float:right; font-size: 80%; position: relative; top:-10px"><a href="#">Forgot
                            password?</a></div>
                </div>

                <div style="padding-top:30px" class="panel-body">
                    <form method="post" class="form-horizontal" role="form">

                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
                            <input id="login-username" type="text" class="form-control" name="username" value=""
                                placeholder="username or email">
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
                            <input id="login-password" type="password" class="form-control" name="password"
                                placeholder="password">
                        </div>

                        <div class="margin-bottom-25">
                            <input type="checkbox" tabindex="3" class="" name="remember" id="remember">
                            <label for="remember"> Remember Me</label>
                        </div>

                        <div class="margin-bottom-25 input-group">
                            <!-- Button -->
                            <div class="col-sm-12 controls">
                                <button type="submit" name="submit" value="submit"
                                    class="btn btn-primary">Submit</button>
                                <a id="btn-fblogin" href="#" class="btn btn-primary">Login with Facebook</a>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-12 control">
                                Don't have an account!
                                <a href="form_user.php">
                                    Sign Up Here
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>