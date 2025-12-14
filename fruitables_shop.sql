-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 04, 2025 lúc 02:45 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `fruitables_shop`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `carts`
--

INSERT INTO `carts` (`id`, `customer_id`) VALUES
(7, NULL),
(8, NULL),
(6, 0),
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`) VALUES
(1, 1, 1, 3),
(2, 1, 4, 2),
(3, 1, 9, 1),
(6, 3, 2, 4),
(7, 3, 15, 1),
(8, 4, 7, 1),
(9, 4, 8, 2),
(17, 6, 36, 11),
(26, 6, 35, 7),
(27, 6, 31, 1),
(28, 6, 12, 1),
(30, 6, 32, 1),
(63, 2, 32, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Trái cây nội địa'),
(2, 'Trái cây nhập khẩu'),
(3, 'Trái cây theo mùa'),
(4, 'Trái cây hữu cơ'),
(5, 'Giỏ quà trái cây');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, 'Nguyễn Lan Anh', 'lananh95@gmail.com', 'Cho mình hỏi còn cherry Mỹ không ạ?', '2025-11-20 02:30:00'),
(2, 'Trần Văn Nam', 'namtran88@gmail.com', 'Mình muốn đặt giỏ quà 2 triệu, có thể mix thêm nho đen được không?', '2025-11-22 07:15:00'),
(3, 'Phạm Thị Mai', 'maipham@gmail.com', 'Shop có giao hàng trong ngày tại Hà Nội không ạ?', '2025-11-25 03:20:00'),
(4, 'Lê Minh Tuấn', 'tuanle92@yahoo.com', 'Mình bị giao thiếu 1kg cam, liên hệ hỗ trợ giúp mình với', '2025-11-27 09:45:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('fixed','percent') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `active`, `expires_at`) VALUES
(1, 'FRUIT2025', 'Giảm 10% cho đơn từ 500k', 'percent', 10.00, 1, '2025-12-31 23:59:59'),
(2, 'WELCOME50', 'Giảm 50k cho khách mới', 'fixed', 50000.00, 1, '2025-12-31 23:59:59'),
(3, 'FREESHIP', 'Miễn phí vận chuyển nội thành', 'fixed', 30000.00, 1, '2025-06-30 23:59:59'),
(4, 'SALE20', 'Giảm 20% toàn bộ sản phẩm nhập khẩu', 'percent', 20.00, 1, '2025-03-31 23:59:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `phone`, `address`) VALUES
(1, 2, '0901234567', '123 Đường Láng, Đống Đa, Hà Nội'),
(2, 3, '0912345678', '45 Nguyễn Trãi, Thanh Xuân, Hà Nội, VN'),
(3, 4, '0987654321', '78 Lê Văn Sỹ, Quận 3, TP.HCM'),
(4, 5, '0938123456', '56 Trần Phú, Hà Đông, Hà Nội'),
(5, 6, '0977888999', '12 Nguyễn Huệ, Quận 1, TP.HCM'),
(6, 7, '0823456789', '89 Hùng Vương, Hải Châu, Đà Nẵng'),
(7, 8, '0909876543', '25 Phạm Văn Đồng, Thủ Đức, TP.HCM');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('Cash','BankTransfer','MoMo','ZaloPay','VNPay') DEFAULT 'Cash',
  `status` enum('Processing','Shipped','Delivered','Cancelled') DEFAULT 'Processing',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `cart_id`, `subtotal`, `shipping_cost`, `total`, `payment_method`, `status`, `notes`, `created_at`) VALUES
(1, 1, 1, 378000.00, 30000.00, 408000.00, 'BankTransfer', 'Delivered', 'Giao giờ hành chính', '2025-11-15 01:20:00'),
(2, 2, 2, 490000.00, 0.00, 440000.00, 'Cash', 'Shipped', 'Dùng mã FREESHIP', '2025-11-18 07:30:00'),
(3, 3, 3, 980000.00, 30000.00, 1010000.00, 'MoMo', 'Delivered', NULL, '2025-11-20 02:15:00'),
(4, 4, 1, 1350000.00, 50000.00, 1300000.00, 'ZaloPay', 'Processing', 'Quà tặng sinh nhật', '2025-11-25 04:40:00'),
(5, 5, 2, 650000.00, 30000.00, 680000.00, 'Cash', 'Delivered', 'Giỏ quà cho mẹ', '2025-11-10 09:20:00'),
(6, 1, 3, 225000.00, 25000.00, 200000.00, 'Cash', 'Delivered', 'Dùng mã WELCOME50', '2025-11-05 03:10:00'),
(7, 6, 4, 540000.00, 30000.00, 570000.00, 'Cash', 'Cancelled', 'Khách hủy do hết hàng cherry', '2025-11-22 06:00:00'),
(8, 7, 5, 890000.00, 0.00, 890000.00, 'Cash', 'Shipped', NULL, '2025-11-28 10:55:00'),
(9, 2, 2, 550000.00, 30000.00, 580000.00, 'BankTransfer', 'Processing', '', '2025-11-30 10:50:36'),
(10, 2, 2, 3610000.00, 30000.00, 3640000.00, 'MoMo', 'Processing', '', '2025-11-30 10:52:28'),
(11, 2, 2, 2230000.00, 30000.00, 2260000.00, 'MoMo', 'Processing', '', '2025-12-02 08:33:58'),
(12, 2, 2, 1845000.00, 30000.00, 1875000.00, 'BankTransfer', 'Processing', '', '2025-12-02 10:11:50'),
(13, 2, 2, 2718000.00, 30000.00, 2748000.00, 'ZaloPay', 'Processing', '', '2025-12-03 03:28:00'),
(14, 2, 2, 890000.00, 30000.00, 920000.00, 'Cash', 'Processing', '', '2025-12-03 03:30:31'),
(15, 2, 2, 2330000.00, 30000.00, 2360000.00, 'Cash', 'Processing', '', '2025-12-04 11:50:47'),
(16, 2, 2, 135000.00, 30000.00, 165000.00, 'Cash', 'Shipped', '', '2025-12-04 11:55:01'),
(17, 2, 2, 0.00, 30000.00, 30000.00, 'Cash', 'Delivered', '', '2025-12-04 11:59:38'),
(18, 2, 2, 450000.00, 30000.00, 480000.00, 'Cash', 'Cancelled', '', '2025-12-04 12:01:43'),
(19, 2, 2, 0.00, 30000.00, 30000.00, 'Cash', 'Shipped', '', '2025-12-04 12:01:47'),
(20, 2, 2, 55000.00, 30000.00, 85000.00, 'Cash', 'Shipped', '', '2025-12-04 12:03:02'),
(21, 2, 2, 890000.00, 30000.00, 920000.00, 'Cash', 'Shipped', '', '2025-12-04 12:05:43'),
(22, 2, 2, 450000.00, 30000.00, 480000.00, 'Cash', 'Processing', '', '2025-12-04 12:06:32'),
(23, 2, 2, 45000.00, 30000.00, 75000.00, 'Cash', 'Processing', '', '2025-12-04 12:06:50'),
(24, 2, 2, 55000.00, 30000.00, 85000.00, 'Cash', 'Processing', '', '2025-12-04 12:08:32'),
(25, 2, 2, 55000.00, 30000.00, 85000.00, 'ZaloPay', 'Shipped', '', '2025-12-04 12:12:04'),
(26, 2, 2, 45000.00, 30000.00, 75000.00, 'ZaloPay', 'Shipped', '', '2025-12-04 12:13:14'),
(27, 2, 2, 55000.00, 30000.00, 85000.00, 'BankTransfer', 'Shipped', '', '2025-12-04 12:17:21'),
(28, 2, 2, 55000.00, 30000.00, 85000.00, 'Cash', 'Delivered', '', '2025-12-04 12:19:09'),
(29, 2, 2, 110000.00, 30000.00, 140000.00, 'BankTransfer', 'Shipped', '', '2025-12-04 12:30:36'),
(30, 2, 2, 450000.00, 30000.00, 480000.00, 'ZaloPay', 'Processing', '', '2025-12-04 12:49:34'),
(31, 2, 2, 55000.00, 30000.00, 85000.00, 'MoMo', 'Delivered', '', '2025-12-04 12:49:59'),
(32, 2, 2, 55000.00, 30000.00, 85000.00, 'BankTransfer', 'Cancelled', '', '2025-12-04 12:50:11'),
(33, 2, 2, 135000.00, 30000.00, 165000.00, 'BankTransfer', 'Shipped', '', '2025-12-04 12:50:29'),
(34, 2, 2, 55000.00, 30000.00, 85000.00, 'Cash', 'Cancelled', '', '2025-12-04 12:50:40'),
(35, 2, 2, 135000.00, 30000.00, 165000.00, 'Cash', 'Processing', '', '2025-12-04 12:50:51'),
(36, 2, 2, 0.00, 30000.00, 30000.00, 'ZaloPay', 'Processing', '', '2025-12-04 12:50:59'),
(37, 2, 2, 0.00, 30000.00, 30000.00, 'MoMo', 'Cancelled', '', '2025-12-04 12:51:02'),
(38, 2, 2, 55000.00, 30000.00, 85000.00, 'BankTransfer', 'Processing', '', '2025-12-04 12:55:38'),
(39, 2, 2, 450000.00, 30000.00, 480000.00, 'BankTransfer', 'Shipped', '', '2025-12-04 13:00:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 3, 25000.00),
(2, 1, 4, 2, 65000.00),
(3, 1, 9, 1, 380000.00),
(4, 2, 5, 5, 22000.00),
(5, 2, 12, 1, 220000.00),
(6, 3, 2, 4, 45000.00),
(7, 3, 15, 1, 1350000.00),
(8, 4, 16, 1, 1590000.00),
(9, 5, 17, 1, 650000.00),
(10, 6, 7, 1, 450000.00),
(11, 6, 3, 5, 28000.00),
(12, 8, 11, 2, 75000.00),
(13, 8, 14, 3, 42000.00),
(14, 8, 8, 1, 135000.00),
(15, 9, 5, 5, 22000.00),
(16, 9, 12, 2, 220000.00),
(17, 10, 1, 2, 25000.00),
(18, 10, 35, 4, 890000.00),
(19, 11, 35, 2, 890000.00),
(20, 11, 36, 1, 450000.00),
(21, 12, 36, 1, 450000.00),
(22, 12, 7, 1, 450000.00),
(23, 12, 35, 1, 890000.00),
(24, 12, 32, 1, 55000.00),
(25, 13, 30, 1, 48000.00),
(26, 13, 35, 3, 890000.00),
(27, 14, 35, 1, 890000.00),
(28, 15, 22, 1, 125000.00),
(29, 15, 29, 1, 45000.00),
(30, 15, 31, 2, 135000.00),
(31, 15, 32, 2, 55000.00),
(32, 15, 35, 2, 890000.00),
(33, 16, 31, 1, 135000.00),
(34, 18, 36, 1, 450000.00),
(35, 20, 32, 1, 55000.00),
(36, 21, 35, 1, 890000.00),
(37, 22, 36, 1, 450000.00),
(38, 23, 29, 1, 45000.00),
(39, 24, 32, 1, 55000.00),
(40, 25, 32, 1, 55000.00),
(41, 26, 29, 1, 45000.00),
(42, 27, 32, 1, 55000.00),
(43, 28, 32, 1, 55000.00),
(44, 29, 32, 2, 55000.00),
(45, 30, 36, 1, 450000.00),
(46, 31, 32, 1, 55000.00),
(47, 32, 32, 1, 55000.00),
(48, 33, 31, 1, 135000.00),
(49, 34, 32, 1, 55000.00),
(50, 35, 31, 1, 135000.00),
(51, 38, 32, 1, 55000.00),
(52, 39, 36, 1, 450000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'kg',
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 100,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `description`, `price`, `unit`, `stock`, `image`) VALUES
(1, 'Chuối sứ Đà Lạt', 1, 'Chuối sứ ngọt, thơm, ruột vàng óng', 25000.00, 'kg', 0, 'chuoi-su.jpg'),
(2, 'Bưởi da xanh Bến Tre', 1, 'Bưởi da xanh ruột hồng, ngọt thanh', 45000.00, 'kg', 150, 'buoi.png'),
(3, 'Cam sành Hàm Yên', 1, 'Cam sành ngọt tự nhiên, nhiều nước', 28000.00, 'kg', 300, 'cam.jpg'),
(4, 'Xoài cát Hòa Lộc', 1, 'Xoài cát ngọt lừ, thơm nồng', 65000.00, 'kg', 80, 'xoai.jpg'),
(5, 'Thanh long ruột trắng', 1, 'Thanh long Bình Thuận, giòn ngọt', 22000.00, 'kg', 395, 'thanh_long.jpg'),
(6, 'Dưa hấu không hạt', 3, 'Dưa hấu đỏ, ngọt mát mùa hè', 18000.00, 'kg', 250, 'dua_hau.jpg'),
(7, 'Cherry Mỹ đỏ', 2, 'Cherry nhập khẩu Mỹ size 9.5', 450000.00, 'kg', 49, 'cherry.jpg'),
(8, 'Táo Envy New Zealand', 2, 'Táo Envy giòn ngọt, vỏ bóng', 135000.00, 'kg', 120, 'tao.png'),
(9, 'Nho mẫu đơn Hàn Quốc', 2, 'Nho xanh không hạt, ngọt thanh', 380000.00, 'kg', 70, 'nho.png'),
(10, 'Lê Nam Phi', 2, 'Lê Nam Phi giòn, thơm', 95000.00, 'kg', 90, 'le.jpg'),
(11, 'Bơ 034 Lâm Đồng', 4, 'Bơ sáp 034 dẻo thơm, béo ngậy', 75000.00, 'kg', 180, 'bo.jpg'),
(12, 'Dâu tây Đà Lạt hữu cơ', 4, 'Dâu tây sạch, không thuốc trừ sâu', 220000.00, 'kg', 58, 'dau.jpg'),
(13, 'Hồng giòn Đà Lạt', 3, 'Hồng giòn ngọt, ăn sống hoặc làm sinh tố', 35000.00, 'kg', 140, 'hong.jpg'),
(14, 'Mít thái siêu sớm', 1, 'Mít thái múi to, ngọt đậm', 42000.00, 'kg', 100, 'mit.png'),
(15, 'Sầu riêng Ri6', 1, 'Sầu riêng Ri6 cơm vàng, hạt lép', 135000.00, 'kg', 45, 'sau_rieng.png'),
(16, 'Giỏ quà cao cấp 5kg', 5, 'Gồm cherry, nho mẫu đơn, táo Envy, bơ', 1590000.00, 'giỏ', 30, 'Gio-Qua-Trai-Cay.jpg'),
(17, 'Giỏ trái cây sức khỏe', 5, 'Cam, táo, bưởi, thanh long', 650000.00, 'giỏ', 50, 'gio_trai_cay.png'),
(18, 'Chôm chôm Thái', 1, 'Chôm chôm Thái đỏ tươi, ngọt thanh', 38000.00, 'kg', 120, 'chom_chom.jpg'),
(19, 'Nhãn lồng Hưng Yên', 3, 'Nhãn lồng Hưng Yên chính vụ, ngọt lịm', 55000.00, 'kg', 90, 'nhan.jpg'),
(20, 'Vải thiều Lục Ngạn', 3, 'Vải thiều Bắc Giang mùa 2025', 65000.00, 'kg', 80, 'vai.png'),
(21, 'Măng cụt Lái Thiêu', 1, 'Măng cụt ruột trắng, ngọt dịu', 85000.00, 'kg', 70, 'mang_cut.jpg'),
(22, 'Cam Cara Navel Mỹ', 2, 'Cam ruột đỏ nhập Mỹ, siêu ngọt', 125000.00, 'kg', 59, 'cam_vang.jpg'),
(23, 'Táo Juliet hữu cơ Pháp', 2, 'Táo hữu cơ Pháp, giòn tan', 195000.00, 'kg', 45, 'tao_phap.png'),
(24, 'Nho đen không hạt Úc', 2, 'Nho đen Úc size lớn, ngọt đậm', 420000.00, 'kg', 55, 'nho.jpg'),
(25, 'Kiwi vàng New Zealand', 2, 'Kiwi vàng ngọt, thơm', 145000.00, 'kg', 80, 'kiwi.png'),
(26, 'Dưa lưới Nhật Bản', 2, 'Dưa lưới thơm lừng, ruột cam', 180000.00, 'kg', 40, 'dua_luoi.jpg'),
(27, 'Ổi nữ hoàng', 1, 'Ổi không hạt, giòn ngọt', 32000.00, 'kg', 200, 'oi.jpg'),
(28, 'Dừa xiêm Bến Tre', 1, 'Dừa xiêm nước ngọt, thơm', 15000.00, 'trái', 300, 'dua.jpg'),
(29, 'Hồng xiêm Xuân Đỉnh', 1, 'Hồng xiêm chín cây, ngọt sắc', 45000.00, 'kg', 107, 'hong_xiem.jpg'),
(30, 'Na Thái', 1, 'Na bở Thái, múi to', 48000.00, 'kg', 129, 'na.png'),
(31, 'Lựu đỏ Ấn Độ', 2, 'Lựu đỏ hạt to, mọng nước', 135000.00, 'kg', 60, 'luu.jpg'),
(32, 'Chanh leo tím Đài Loan', 1, 'Chanh leo tím thơm nồng', 55000.00, 'kg', 136, 'chanh.png'),
(33, 'Giỏ quà Tết cao cấp 10kg', 5, 'Cherry, nho mẫu đơn, táo Envy, kiwi, bơ...', 3890000.00, 'giỏ', 15, 'gio_cao_cap.jpg'),
(34, 'Giỏ trái cây nhập khẩu 5kg', 5, 'Táo, cherry, kiwi, nho đen', 2190000.00, 'giỏ', 25, 'gio_nhap.jpg'),
(35, 'Giỏ biếu sức khỏe 3kg', 5, 'Cam, táo, bưởi, thanh long', 890000.00, 'kg', 26, 'gio_sk.jpg'),
(36, 'Set sinh tố 5 loại', 5, 'Chuối, xoài, dâu, bơ, thanh long', 450000.00, 'kg', 26, 'sinh_to.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review`
--

CREATE TABLE `review` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(3) UNSIGNED DEFAULT 0,
  `message` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_visible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `review`
--

INSERT INTO `review` (`id`, `customer_id`, `product_id`, `rating`, `message`, `image`, `created_at`, `is_visible`) VALUES
(1, 1, 1, 5, 'Chuối ngon, ngọt, giao hàng nhanh!', 'chuoi.jpg', '2025-11-16 10:30:00', 1),
(2, 2, 5, 4, 'Thanh long ngọt nhưng có vài quả hơi nhỏ', 'thanh_long.jfif', '2025-11-19 14:20:00', 1),
(3, 3, 15, 5, 'Sầu riêng Ri6 đúng chuẩn, cơm dày, hạt lép', 'sau.png', '2025-11-21 08:45:00', 1),
(4, 4, 7, 5, 'Cherry Mỹ tươi, to, ngọt lịm, sẽ mua lại!', 'cherry.jpg', '2025-11-26 12:10:00', 1),
(5, 5, 12, 5, 'Dâu tây hữu cơ thơm ngon, sạch sẽ', 'dau.jpg', '2025-11-11 09:15:00', 1),
(6, 1, 4, 4, 'Xoài ngon nhưng hơi ít quả to', 'xoai.jpg', '2025-11-06 15:40:00', 1),
(7, 6, 9, 5, 'Nho mẫu đơn tuyệt vời, đáng giá từng đồng', 'nho.jpeg', '2025-11-23 18:20:00', 1),
(8, 7, 11, 5, 'Bơ 034 dẻo ngon, béo ngậy', 'bo.jfif', '2025-11-29 11:55:00', 1),
(9, 2, 15, 5, 'Sầu riêng Ri6 đúng chuẩn, cơm dày, hạt lép', 'sau.png', '2025-11-30 17:54:34', 1),
(10, 2, 15, 4, 'Sầu riêng Ri6 đúng chuẩn, cơm dày, hạt lép', 'sau.png', '2025-11-30 17:56:29', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin', 'admin@fruitables.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'nguyenvana', 'nguyenvana@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
(3, 'tranbichh', 'bichtran@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
(4, 'phamminh', 'phamminh92@yahoo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
(5, 'lethikim', 'kimle88@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
(6, 'hovanduc', 'duc.ho@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
(7, 'dangthuy', 'thuy.dang@hotmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
(8, 'vuminhanh', 'anhvu99@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carts_customer` (`customer_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `fk_cart_items_cart` (`cart_id`),
  ADD KEY `fk_cart_items_product` (`product_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_customer` (`customer_id`),
  ADD KEY `fk_orders_cart` (`cart_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Chỉ mục cho bảng `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_customer` (`customer_id`),
  ADD KEY `fk_review_product` (`product_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `review`
--
ALTER TABLE `review`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
