<?php
// Include the router
require_once __DIR__ . '/router.php';

// Home page
$router->get('/', function() {
    require_once __DIR__ . '/index.php';
});

// Authentication routes
$router->get('/dang-nhap', function() {
    require_once __DIR__ . '/login.php';
});

$router->post('/dang-nhap', function() {
    require_once __DIR__ . '/login.php';
});

$router->get('/dang-ky', function() {
    require_once __DIR__ . '/register.php';
});

$router->post('/dang-ky', function() {
    require_once __DIR__ . '/register.php';
});

$router->get('/dang-xuat', function() {
    require_once __DIR__ . '/logout.php';
});

$router->get('/quen-mat-khau', function() {
    require_once __DIR__ . '/forgot.php';
});

$router->post('/quen-mat-khau', function() {
    require_once __DIR__ . '/forgot.php';
});

// Shop routes
$router->get('/cua-hang', function() {
    require_once __DIR__ . '/shop.php';
});

$router->get('/danh-muc/([0-9]+)/([a-zA-Z0-9-]*)', function($id, $slug) {
    $_GET['category_id'] = $id;
    require_once __DIR__ . '/shop.php';
});

$router->get('/san-pham/([0-9]+)/([a-zA-Z0-9-]*)', function($id, $slug) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/shop-detail.php';
});

// Cart routes
$router->get('/gio-hang', function() {
    require_once __DIR__ . '/cart.php';
});

$router->post('/them-vao-gio-hang', function() {
    require_once __DIR__ . '/add_to_cart.php';
});

$router->post('/cap-nhat-gio-hang', function() {
    require_once __DIR__ . '/update_cart.php';
});

$router->get('/xoa-gio-hang/([0-9]+)', function($item_id) {
    $_GET['remove_item'] = $item_id;
    require_once __DIR__ . '/remove_from_cart.php';
});

// Checkout routes
$router->get('/thanh-toan', function() {
    require_once __DIR__ . '/checkout.php';
});

$router->post('/dat-hang', function() {
    require_once __DIR__ . '/process_checkout.php';
});

// User account routes
$router->get('/tai-khoan', function() {
    require_once __DIR__ . '/user.php';
});

$router->post('/cap-nhat-tai-khoan', function() {
    require_once __DIR__ . '/update_account.php';
});

$router->get('/doi-mat-khau', function() {
    require_once __DIR__ . '/change_password.php';
});

$router->post('/doi-mat-khau', function() {
    require_once __DIR__ . '/change_password.php';
});

$router->get('/lich-su-mua-hang', function() {
    require_once __DIR__ . '/order_history.php';
});

$router->get('/don-hang/([0-9]+)', function($order_id) {
    $_GET['id'] = $order_id;
    require_once __DIR__ . '/order_detail.php';
});

// Contact page
$router->get('/lien-he', function() {
    require_once __DIR__ . '/contact.php';
});

$router->post('/lien-he', function() {
    require_once __DIR__ . '/contact.php';
});

// Admin routes
$router->get('/admin', function() {
    require_once __DIR__ . '/admin/dashboard.php';
});

$router->get('/admin/danh-muc', function() {
    require_once __DIR__ . '/admin/category-management.php';
});

$router->get('/admin/danh-muc/them', function() {
    require_once __DIR__ . '/admin/category-form.php';
});

$router->get('/admin/danh-muc/sua/([0-9]+)', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/admin/category-form.php';
});

$router->post('/admin/danh-muc/luu', function() {
    require_once __DIR__ . '/admin/save-category.php';
});

$router->get('/admin/danh-muc/xoa/([0-9]+)', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/admin/delete-category.php';
});

$router->get('/admin/san-pham', function() {
    require_once __DIR__ . '/admin/product-management.php';
});

$router->get('/admin/san-pham/them', function() {
    require_once __DIR__ . '/admin/product-form.php';
});

$router->get('/admin/san-pham/sua/([0-9]+)', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/admin/product-form.php';
});

$router->post('/admin/san-pham/luu', function() {
    require_once __DIR__ . '/admin/save-product.php';
});

$router->get('/admin/san-pham/xoa/([0-9]+)', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/admin/delete-product.php';
});

$router->get('/admin/don-hang', function() {
    require_once __DIR__ . '/admin/order-management.php';
});

$router->get('/admin/don-hang/chi-tiet/([0-9]+)', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/admin/order-detail.php';
});

$router->post('/admin/don-hang/cap-nhat-trang-thai', function() {
    require_once __DIR__ . '/admin/update-order-status.php';
});

$router->get('/admin/danh-gia', function() {
    require_once __DIR__ . '/admin/review-management.php';
});

$router->get('/admin/danh-gia/xoa/([0-9]+)', function($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/admin/delete-review.php';
});

// API routes
$router->post('/api/cart/update', function() {
    require_once __DIR__ . '/api/update_cart.php';
});

$router->post('/api/check-email', function() {
    require_once __DIR__ . '/api/check_email.php';
});

// 404 Not Found
$router->notFound(function() {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
});

// Run the router
$router->run();