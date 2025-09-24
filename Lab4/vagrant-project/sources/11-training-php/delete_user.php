<?php
/*
  FILE: delete_user.php
  - Chỉ chấp nhận POST
  - Kiểm tra session (đã login) và quyền
  - Kiểm tra CSRF bằng hash_equals
  - Xoá token (one-time) sau khi dùng
*/

// delete_user.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once 'models/UserModel.php';
$userModel = new UserModel();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Kiểm tra login / quyền
if (empty($_SESSION['id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Kiểm tra CSRF token
$posted = $_POST['csrf_token'] ?? '';
$sessToken = $_SESSION['csrf_token'] ?? '';
if (!hash_equals($sessToken, $posted)) {
    http_response_code(403);
    $_SESSION['message'] = 'CSRF token không hợp lệ!';
    header('Location: list_users.php');
    exit();
}

// One-time token: unset sau khi dùng
unset($_SESSION['csrf_token']);

// Lấy id và validate
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    $_SESSION['message'] = 'ID không hợp lệ';
    header('Location: list_users.php');
    exit();
}

// Kiểm tra quyền chi tiết: ví dụ chỉ admin được xóa
$currentUser = $userModel->findUserById($_SESSION['id']);
if (empty($currentUser) || ($currentUser[0]['type'] ?? '') !== 'admin') {
    $_SESSION['message'] = 'Bạn không có quyền xóa user';
    header('Location: list_users.php');
    exit();
}

// Thực hiện xoá thông qua model (model phải xử lý SQL an toàn)
$deleted = $userModel->deleteUserById($id);

if ($deleted) {
    $_SESSION['message'] = 'Xóa user thành công';
} else {
    $_SESSION['message'] = 'Xóa user thất bại';
}

header('Location: list_users.php');
exit();
?>
