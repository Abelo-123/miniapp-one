-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 09, 2026 at 04:14 PM
-- Server version: 10.6.27-MariaDB
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paxyocom_paxyov3`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_recommended_services`
--

CREATE TABLE `admin_recommended_services` (
  `service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_recommended_services`
--

INSERT INTO `admin_recommended_services` (`service_id`) VALUES
(1001),
(1002);

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, '123456789', 'Notification', 'bnbn', 'info', 1, '2026-04-06 11:24:29'),
(2, '123456789', 'New Message', 'You have a new message from support', 'chat', 1, '2026-04-07 00:03:13'),
(3, '123456789', 'New Message', 'You have a new message from support', 'chat', 1, '2026-04-07 00:06:27'),
(4, '123456789', 'Notification', 'nm', 'info', 1, '2026-04-07 00:08:11'),
(5, '5928771903', 'New Message', 'You have a new message from support', 'chat', 0, '2026-04-07 14:51:58'),
(6, '5928771903', 'Referral Bonus', 'You received 20 ETB bonus for using a referral code!', 'success', 0, '2026-06-03 10:37:37'),
(7, '111', 'Referral Success', 'User Abel signed up using your code!', 'success', 0, '2026-06-03 10:37:37'),
(8, '7159821786', 'Referral Bonus', 'You received 20 ETB bonus for using a referral code!', 'success', 0, '2026-06-03 11:10:51'),
(9, '5928771903', 'Referral Success', 'User Yohannes signed up using your code!', 'success', 0, '2026-06-03 11:10:51'),
(10, '8300384390', 'Referral Bonus', 'You received 20 ETB bonus for using a referral code!', 'success', 0, '2026-06-03 11:29:39'),
(11, '5928771903', 'Referral Success', 'User Isu signed up using your code!', 'success', 0, '2026-06-03 11:29:39'),
(12, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 12:36:09'),
(13, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 12:41:36'),
(14, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 12:52:32'),
(15, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 12:54:35'),
(16, '111', 'Referral Commission', 'You earned 1.40 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 13:12:55'),
(17, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 13:36:29'),
(18, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 13:54:39'),
(19, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-05 14:13:29'),
(20, '111', 'Referral Commission', 'You earned 0.70 ETB (7%) from your referred friend\'s deposit!', 'success', 0, '2026-06-06 10:28:09');

-- --------------------------------------------------------

--
-- Table structure for table `auth`
--

CREATE TABLE `auth` (
  `tg_id` bigint(20) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `photo_url` text DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `is_blocked` tinyint(1) DEFAULT 0,
  `auth_provider` varchar(50) DEFAULT 'telegram',
  `last_login` datetime DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_deposit` datetime DEFAULT NULL,
  `last_order` datetime DEFAULT NULL,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone_verified` tinyint(1) DEFAULT 0,
  `referral_code` varchar(50) DEFAULT NULL,
  `referred_by` bigint(20) DEFAULT NULL,
  `refers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`refers`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth`
--

INSERT INTO `auth` (`tg_id`, `username`, `google_id`, `email`, `first_name`, `last_name`, `photo_url`, `balance`, `is_blocked`, `auth_provider`, `last_login`, `last_seen`, `created_at`, `last_deposit`, `last_order`, `total_spent`, `phone_number`, `phone_verified`, `referral_code`, `referred_by`, `refers`) VALUES
(0, 'local_user', NULL, NULL, 'Local', 'User', '', 16.11, 0, 'telegram', '2026-06-08 09:35:12', NULL, '2026-04-15 04:37:08', NULL, NULL, 0.00, NULL, 0, 'REFD8F5E70', NULL, NULL),
(111, NULL, NULL, NULL, 'Test', 'User', '', 506.85, 0, 'telegram', '2026-02-23 14:46:13', '2026-02-19 22:44:40', '2026-02-19 18:03:37', NULL, NULL, 0.00, NULL, 0, 'REF65DFDF111', NULL, NULL),
(999, NULL, NULL, NULL, 'Test', 'User', '', 0.00, 0, 'telegram', '2026-02-23 14:22:43', NULL, '2026-02-23 14:22:43', NULL, NULL, 0.00, NULL, 0, 'REF0015EF999', NULL, NULL),
(999999, NULL, NULL, NULL, 'John', 'Doe', '', 10.00, 0, 'telegram', '2026-02-23 14:28:03', NULL, '2026-02-23 14:28:03', NULL, NULL, 0.00, NULL, 0, 'REFED4548999', NULL, NULL),
(123456789, 'demouser', NULL, NULL, 'Demo', 'User', '', 6247.11, 0, 'telegram', '2026-06-05 11:17:33', NULL, '2026-02-21 19:12:57', NULL, NULL, 0.00, '251993960702', 1, 'REF5CD3D8789', NULL, NULL),
(348956182, 'AnteDop', NULL, NULL, 'ANTENEH', 'GETACHEW', 'https://t.me/i/userpic/320/gZ9f07ZTP-SawWXLmyR6pCJdt4hN0anzEML9LnbRYQM.svg', 0.00, 0, 'telegram', '2026-05-19 12:24:34', NULL, '2026-05-19 12:24:34', NULL, NULL, 0.00, NULL, 0, 'REFFA801E182', NULL, NULL),
(466701947, 'Nowisatime_29', NULL, NULL, 'ጊዜው አሁን ነው።', 'User', 'https://t.me/i/userpic/320/Hw-S3PzWi_m2xSls9hw5ghGbOhl8hfFEWHO4tib4eoM.svg', 0.00, 0, 'telegram', '2026-05-07 16:14:19', NULL, '2026-05-07 16:14:19', NULL, NULL, 0.00, NULL, 0, 'REFC29CEB947', NULL, NULL),
(557497105, 'zekilike', NULL, NULL, 'Z E K I L I K E', 'User', 'https://t.me/i/userpic/320/Z3XKdp4EsRSBQD1QMq1Jy7OOMOZfLz3dOX8L6UoZ9BA.svg', 0.00, 0, 'telegram', '2026-06-05 22:04:30', NULL, '2026-05-19 06:02:27', NULL, NULL, 0.00, NULL, 0, 'REFE130CD105', NULL, NULL),
(779060335, 'Paxyo', NULL, NULL, 'Paxyo', 'User', 'https://t.me/i/userpic/320/5v4C9fGJ6Cx4OHmmGf9y7LoklHAJDK772I0ca5-lRBA.svg', 8557.87, 0, 'telegram', '2026-06-07 08:20:35', NULL, '2026-04-07 17:08:50', NULL, NULL, 0.00, '251993960702', 1, 'REFFB8ADA335', NULL, NULL),
(933955823, 'parallaxx_eff', NULL, NULL, '₱₳Ɍ₳ƉØӾ', 'User', 'https://t.me/i/userpic/320/UKD-H24LiG7Y4o9aYqOc6U7ikuNgc4fC7gjyRfbuDtw.svg', 0.00, 0, 'telegram', '2026-06-06 04:53:09', NULL, '2026-05-16 20:31:21', NULL, NULL, 0.00, NULL, 0, 'REF2EC739823', NULL, NULL),
(999999999, 'testuser', NULL, NULL, NULL, NULL, NULL, 9999.99, 0, 'telegram', NULL, NULL, '2026-03-29 22:40:53', NULL, NULL, 0.00, NULL, 0, 'REFC1F89A999', NULL, NULL),
(1894231439, 'Mr_Ebek', NULL, NULL, 'M𝐫 𝑬𝒃𝒆𝒌¹³', 'User', 'https://t.me/i/userpic/320/5IZ1R56oBPwwzjZ-oTfkSd6h6_liT_mkbJq1UNbWJho.svg', 0.00, 0, 'telegram', '2026-06-02 13:50:23', NULL, '2026-05-11 19:32:53', NULL, NULL, 0.00, NULL, 0, 'REFD0E10A439', NULL, NULL),
(1961928800, 'Mickey_s_i', NULL, NULL, '●▬▬๑۩Mickey۩๑▬▬●', 'User', 'https://t.me/i/userpic/320/hu4yWITSGJIE2MN1quAPEu6Gm2otM92osiL_VDSbLo8.svg', 7.21, 0, 'telegram', '2026-05-23 14:33:18', NULL, '2026-05-10 00:33:29', NULL, NULL, 0.00, NULL, 0, 'REF7B6299800', NULL, NULL),
(2030466394, 'Amanuniguss', NULL, NULL, 'አማኑኤል', 'ንጉሴ', 'https://t.me/i/userpic/320/Ta9txrkgjmdigzVzBTZmiLRACoE5o_41PEaR9G2GxdU.svg', 15.49, 0, 'telegram', '2026-05-27 15:29:42', NULL, '2026-05-06 17:50:31', NULL, NULL, 0.00, NULL, 0, 'REF5472E2394', NULL, NULL),
(2047670227, 'Boyikaw', NULL, NULL, 'بويكاو ۞ BOYIKAW', 'User', 'https://t.me/i/userpic/320/Fav0A0WT0e_7fxA_EyiotajpUECu9CtmKYvb1D8QBTU.svg', 48.18, 0, 'telegram', '2026-05-31 07:08:51', NULL, '2026-05-15 06:26:34', NULL, NULL, 0.00, NULL, 0, 'REFCEAE1A227', NULL, NULL),
(5065467140, 'Yo_3x_zidan1', NULL, NULL, 'زيدان🥤', 'User', 'https://t.me/i/userpic/320/duwuVJq564VfIYr7Rg01yAzBhCmazHwmrnFtQ5mRj-hQLuMwWSBC-Ar_VDVQtjDM.svg', 4.16, 0, 'telegram', '2026-05-11 19:24:45', NULL, '2026-05-11 18:57:31', NULL, NULL, 0.00, NULL, 0, 'REF0A334D140', NULL, NULL),
(5687828785, 'kev_mila', NULL, NULL, 'MILLION', 'User', 'https://t.me/i/userpic/320/F2DaTsc4AOG8sx7QiyVhv_fi4t33zGfm8x0PM8OqApjJRm7pwkIdRVHADA3WWytu.svg', 0.00, 0, 'telegram', '2026-06-01 22:11:30', NULL, '2026-06-01 22:11:30', NULL, NULL, 0.00, NULL, 0, 'REF4EFC20785', NULL, NULL),
(5826257535, 'framevideoproduction', NULL, NULL, 'FRAME VIDEO PRODUCTION', 'User', 'https://t.me/i/userpic/320/qRWm0urOBGHUevzE8xkLthGGkKf_ZFIiDfuZrEkOem5J8ZG037zQz1uVvlRm4Ajd.svg', 20.72, 0, 'telegram', '2026-06-05 22:07:43', NULL, '2026-05-17 22:09:30', NULL, NULL, 0.00, NULL, 0, 'REF4B1323535', NULL, NULL),
(5878415243, 'Mr_Kiyar', NULL, NULL, 'ᴋɪʏᴀʀ𓄂꯭꯭꯭꯭', 'User', 'https://t.me/i/userpic/320/DrAzDgRxO5GIiUuNiZR4Kg0RMou11R1VQY9H3ZDntdojvplFNzhDcNQ-ByhmwOfW.svg', 0.00, 0, 'telegram', '2026-05-14 04:20:50', NULL, '2026-05-14 04:20:50', NULL, NULL, 0.00, NULL, 0, 'REF48EA86243', NULL, NULL),
(5928771903, 'sonthefather', NULL, NULL, 'Abel', '🕊️', 'https://t.me/i/userpic/320/ZDGQ8WfSgSY9HE2Jtay-1LeLDFq-OgeBJ2kQ-n8m11F3kdAoyrVzMEaiWP6P571U.svg', 317.46, 0, 'telegram', '2026-06-06 19:57:52', NULL, '2026-04-07 13:42:04', NULL, NULL, 0.00, NULL, 0, 'REFC024BD903', 111, '[\"7159821786\",\"8300384390\"]'),
(6128857120, 'NNaaaaaaas', NULL, NULL, 'Nas', 'User', 'https://t.me/i/userpic/320/Tve177NMFIPDnxx5TMoCbkBGRWQntsN4G03GiapzNg7YMdqsHH_JKN394uZdXhJ0.svg', 62.80, 0, 'telegram', '2026-05-20 10:06:56', NULL, '2026-05-16 13:55:49', NULL, NULL, 0.00, NULL, 0, 'REFE14D01120', NULL, NULL),
(6187538792, 'Jibghi', NULL, NULL, 'Biruk', 'User', 'https://t.me/i/userpic/320/KJXyO_pjKJVvNXBk-0gOYdUA6MXnsx6I2Gz-EKboyDcAkHdDksvFbXBhThQt7gRH.svg', 0.00, 0, 'telegram', '2026-05-17 13:35:58', NULL, '2026-05-17 13:35:58', NULL, NULL, 0.00, NULL, 0, 'REF01C47C792', NULL, NULL),
(6195785370, 'tselubenteselam', NULL, NULL, 'アケ', 'ake', 'https://t.me/i/userpic/320/q9N229OZ83Kodm_Xg8BtInaQ9WwWzwuG4LCQRR9GWYZWxlpPJWAYc7uH6tts8rYO.svg', 1.25, 0, 'telegram', '2026-06-05 17:39:29', NULL, '2026-06-05 16:31:20', NULL, NULL, 0.00, NULL, 0, 'REF6738A7370', NULL, NULL),
(6488487323, 'lij_Da_boss', NULL, NULL, '[ɖαաʊɖ🇵🇸™]', '🦅', 'https://t.me/i/userpic/320/RV5afpuUSlutgKr6NUuVrO99PE00L5qm9N1fNKzryE0WBiLFTP8If_gwiCcjpHwL.svg', 0.00, 0, 'telegram', '2026-05-23 13:56:31', NULL, '2026-05-23 13:56:31', NULL, NULL, 0.00, NULL, 0, 'REF0C7F7F323', NULL, NULL),
(6528707984, 'Bidjw', NULL, NULL, 'Yohannes', 'User', 'https://t.me/i/userpic/320/eRxI2zbdsuVtzHDWckcuIMskMSewW-BLV95RQp6d9eM4F9TXPSxBGHA8sKqSByic.svg', 0.00, 0, 'telegram', '2026-06-06 17:56:56', NULL, '2026-04-11 06:19:03', NULL, NULL, 0.00, NULL, 0, 'REF93356C984', NULL, NULL),
(6581678657, 'babure_21', NULL, NULL, 'ባቡሬ', 'User', 'https://t.me/i/userpic/320/V-dENAqhCI4GqFE9iH2mu4fyLA9sKqunUwOijKVLLMO7sl96VCdE_LKvHYKjE-xv.svg', 36.20, 0, 'telegram', '2026-05-18 10:36:59', NULL, '2026-05-18 09:59:26', NULL, NULL, 0.00, NULL, 0, 'REF587F9D657', NULL, NULL),
(6655261307, 'wizsami0', NULL, NULL, 'Sam online shopping', 'User', 'https://t.me/i/userpic/320/mXLJnRDQM6t9OILTJIS9EPy509jnN0eSYw4YLkLYZF0TbLAgoYVozxGHwhT94Rd2.svg', 8.00, 0, 'telegram', '2026-05-18 10:27:08', NULL, '2026-05-18 10:27:08', NULL, NULL, 0.00, NULL, 0, 'REF7CAD23307', NULL, NULL),
(6912633019, '', NULL, NULL, 'Zylo', '', 'https://t.me/i/userpic/320/mOsYO5AAYLsxEqFANySahRWfoshqXfqMvyzGXy8kLpSbskBuvU08k7FPZQAt2DcS.svg', 0.00, 0, 'telegram', '2026-04-13 18:37:10', NULL, '2026-04-13 17:07:42', NULL, NULL, 0.00, NULL, 0, 'REFC997A9019', NULL, NULL),
(7048311314, 'BigmiBoss', NULL, NULL, '😜😜😜', 'User', 'https://t.me/i/userpic/320/pn9m8fUjDclb8kRvxnhdNs24SB3GH-wpnbgYL6s5qb0CxVeLXYpviwRPt4P9ilyh.svg', 0.00, 0, 'telegram', '2026-05-27 21:29:58', NULL, '2026-05-21 17:39:09', NULL, NULL, 0.00, NULL, 0, 'REF7A3AC9314', NULL, NULL),
(7114779745, 'Yoha2234', NULL, NULL, 'Yohannes', 'User', 'https://t.me/i/userpic/320/Mj8SZmqbGd4F1Ct3P-37GIqLsvzl9X-6UHc2RTkU6ukV-vzS9DPGSxQKNvoH5U0g.svg', 0.00, 0, 'telegram', '2026-05-19 05:22:41', NULL, '2026-04-10 15:10:47', NULL, NULL, 0.00, NULL, 0, 'REFE02287745', NULL, NULL),
(7115890811, 'Icy_shalom', NULL, NULL, 'S-h-a', 'User', 'https://t.me/i/userpic/320/ovumPqRbFyg0B1kf-GGAiJRSBezFjrVSm8U-_76TsI192XIKQ1QlRgXsh5V2be5c.svg', 8.20, 0, 'telegram', '2026-05-25 05:02:16', NULL, '2026-05-08 08:38:47', NULL, NULL, 0.00, NULL, 0, 'REF0167DD811', NULL, NULL),
(7159821786, 'yoha394', NULL, NULL, 'Yohannes', 'User', 'https://t.me/i/userpic/320/Q2xlW1iUb3dwl75-bfr6iuIyouB0WSaOrUyqE-JU08clhEceHrXvcUKqGyWZHJl2.svg', 20.00, 0, 'telegram', '2026-06-03 11:23:08', NULL, '2026-04-07 13:15:42', NULL, NULL, 0.00, NULL, 0, 'REF1BEAD5786', 5928771903, NULL),
(7360255928, 'Henok_king15', NULL, NULL, '𝐑𝐎𝐏', 'User', 'https://t.me/i/userpic/320/zyPnYu_tNovR4ijqhTgYTRadA07sP0Quk4LGyeYeYk8i1SBHvCFVgYw5kDsZFN9Y.svg', 76.00, 0, 'telegram', '2026-06-06 23:43:21', NULL, '2026-05-27 20:26:02', NULL, NULL, 0.00, NULL, 0, 'REFEAF1F2928', NULL, NULL),
(7460427736, 'big_smokee2', NULL, NULL, 'Şąmǐ🕷️', 'User', 'https://t.me/i/userpic/320/Aww6UXl9GeSrh4yEw5gdW5mmKoI5yEsm9RJwIqmzAoxgpjdM_rGBDNNNv6hjHiwo.svg', 0.00, 0, 'telegram', '2026-05-30 21:17:10', NULL, '2026-05-30 21:17:10', NULL, NULL, 0.00, NULL, 0, 'REFD1EED8736', NULL, NULL),
(7573961936, 'Egziabheryeamr', NULL, NULL, 'Ake', 'User', 'https://t.me/i/userpic/320/DftMQpQKB4g14X-bM6GUF1kkjWkMW2Zrc6QKSbcouD4hfiXDNwSQieMoel4Tp6p0.svg', 0.00, 0, 'telegram', '2026-06-05 16:28:51', NULL, '2026-06-05 16:24:39', NULL, NULL, 0.00, NULL, 0, 'REF6470E4936', NULL, NULL),
(7684231888, 'Abini06', NULL, NULL, '.', 'User', 'https://t.me/i/userpic/320/oDmnxdEWdEV3svwawISPvc0ApbT1J4AOagxTsIsp6LTMNtV69SsmNL_8TapDdGIm.svg', 0.00, 0, 'telegram', '2026-05-07 12:57:51', NULL, '2026-05-07 12:11:05', NULL, NULL, 0.00, NULL, 0, 'REF170D3A888', NULL, NULL),
(7868106291, 'local_user', NULL, NULL, 'Ⓢⓐⓜ🔹', 'User', 'https://t.me/i/userpic/320/_tjCkwNLIpJ2x0PNiLXE4Z70oJSzNcPiJZrCrS5QSYy_tji1iH7kktGJFWgEA1gK.svg', 0.00, 0, 'telegram', '2026-06-04 06:40:21', NULL, '2026-06-04 06:40:21', NULL, NULL, 0.00, NULL, 0, 'REFDE59A6291', NULL, NULL),
(7884055903, 'KIDUSPHONE', NULL, NULL, 'KIDUS 𖣂', 'User', 'https://t.me/i/userpic/320/vLontTY-TU1Lb1KOH8UhLBtBVB1nDklMDpZhE6sDN4uAmPQ9t2ZSEgZO8EO3sjpk.svg', 0.00, 0, 'telegram', '2026-05-23 16:28:58', NULL, '2026-05-23 16:28:58', NULL, NULL, 0.00, NULL, 0, 'REFEDD786903', NULL, NULL),
(7999410461, 'yuye84', NULL, NULL, 'The Samurai', 'User', 'https://t.me/i/userpic/320/eaj5u3_SB678ax60LXt2nruJ7Xcc4pkaRKVHSyfBj0cXTQFOJBHkBXUklRcQcVEO.svg', 20.02, 0, 'telegram', '2026-06-07 05:44:33', NULL, '2026-06-03 09:04:47', NULL, NULL, 0.00, NULL, 0, 'REFC2006D461', NULL, NULL),
(8126556091, 'Unknownversion78', NULL, NULL, '🖤🤍', 'User', 'https://t.me/i/userpic/320/jusxfE5L4gIGOAQyD6pkCiRyFWKa3bs_HzA2gYBM8xRVjwoykxqMOmuUWKf4F4ff.svg', 9.95, 0, 'telegram', '2026-05-31 13:34:45', NULL, '2026-05-16 11:26:44', NULL, NULL, 0.00, NULL, 0, 'REF2C5274091', NULL, NULL),
(8175487874, 'ESM5291', NULL, NULL, 'ESM 1🇵🇸', 'User', 'https://t.me/i/userpic/320/K7uhfcfmDpgXTGi_vBGRxEi1YaBX37cXjImmfPVrhSs_6x0GNgJaD6yOQyXw1ynh.svg', 0.00, 0, 'telegram', '2026-05-21 16:58:27', NULL, '2026-05-10 13:22:40', NULL, NULL, 0.00, NULL, 0, 'REF2F98EA874', NULL, NULL),
(8300384390, 'local_user', NULL, NULL, 'Isu', 'User', 'https://t.me/i/userpic/320/mIPl-Y4UrUyb12XUoTon2dm5mx1DqzS-3uZTtK3QOXnV_jTNcbkZ_JedLyYYSffE.svg', 20.00, 0, 'telegram', '2026-06-03 11:29:47', NULL, '2026-06-03 11:24:08', NULL, NULL, 0.00, NULL, 0, 'REFCF03AE390', 5928771903, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `tx_ref` varchar(100) NOT NULL,
  `chapa_tx_ref` varchar(100) DEFAULT NULL,
  `checkout_url` varchar(500) DEFAULT NULL,
  `reference_id` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `chapa_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`chapa_response`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposits`
--

INSERT INTO `deposits` (`id`, `user_id`, `amount`, `tx_ref`, `chapa_tx_ref`, `checkout_url`, `reference_id`, `status`, `chapa_response`, `created_at`, `completed_at`) VALUES
(1, 111, 500.00, 'test-ref-001', NULL, NULL, 'test-ref-001', 'completed', NULL, '2026-02-19 18:03:37', NULL),
(2, 111, 25.00, 'test-ref-1771513521', NULL, NULL, 'test-ref-1771513521', 'completed', NULL, '2026-02-19 18:05:21', NULL),
(3, 111, 25.00, 'test-ref-1771530279', NULL, NULL, 'test-ref-1771530279', 'completed', NULL, '2026-02-19 22:44:39', NULL),
(4, 123456789, 100.00, 'chapa_1771865624_4236', NULL, NULL, 'chapa_1771865624_4236', 'completed', NULL, '2026-02-23 19:53:45', NULL),
(5, 123456789, 100.00, 'chapa_1771867511_123456789', NULL, NULL, 'chapa_1771867511_123456789', 'completed', NULL, '2026-02-23 20:25:14', NULL),
(6, 123456789, 1000.00, 'chapa_1772443282_123456789', NULL, NULL, 'chapa_1772443282_123456789', 'completed', NULL, '2026-03-02 12:21:24', NULL),
(7, 123456789, 100.00, 'chapa_1772443265_123456789', NULL, NULL, 'chapa_1772443265_123456789', 'completed', NULL, '2026-03-02 12:21:36', NULL),
(8, 123456789, 10.00, 'DEP-123456789-1773072952611-54edc6e7', NULL, NULL, '', 'pending', NULL, '2026-03-09 19:15:52', NULL),
(9, 123456789, 10.00, 'DEP-123456789-1773072977-30d533d3', NULL, NULL, '', 'pending', NULL, '2026-03-09 19:16:17', NULL),
(10, 123456789, 10.00, 'DEP-123456789-1773073314181-36332dc7', NULL, NULL, '', 'pending', NULL, '2026-03-09 19:21:54', NULL),
(11, 123456789, 10.00, 'DEP-123456789-1773073328-e39daee9', NULL, NULL, '', 'pending', NULL, '2026-03-09 19:22:08', NULL),
(12, 123456789, 10.00, 'DEP-123456789-1773074453403-65c9cdd7', NULL, NULL, '', 'pending', NULL, '2026-03-09 19:40:53', NULL),
(13, 123456789, 10.00, 'DEP-123456789-1773074466-8cbb37fd', NULL, 'https://checkout.chapa.co/checkout/payment/McRIn8aVnCX8uCawXte2Kajtq2gvKrSxpXHzcxGzllm3r', '', 'pending', NULL, '2026-03-09 19:41:06', NULL),
(14, 123456789, 10.00, 'DEP-123456789-1773076241124-6a7a6f49', NULL, NULL, '', 'pending', NULL, '2026-03-09 20:10:41', NULL),
(15, 123456789, 10.00, 'DEP-123456789-1773076264462-4eadbf2a', NULL, NULL, '', 'pending', NULL, '2026-03-09 20:11:04', NULL),
(16, 123456789, 10.00, 'DEP-123456789-1773076386024-9bc02d08', NULL, NULL, '', 'pending', NULL, '2026-03-09 20:13:06', NULL),
(17, 123456789, 10.00, 'DEP-123456789-1773076990-5249e68b', NULL, 'https://checkout.chapa.co/checkout/payment/bGIZFGtaIXykbbwvQfl5gZfrwHUnM1rrh8H5EbobNV3V0', '', 'pending', NULL, '2026-03-09 20:23:10', NULL),
(18, 123456789, 10.00, 'DEP-123456789-1773079485-1f68913c', NULL, 'https://checkout.chapa.co/checkout/payment/xjFMp39wP2I4MopbnyXC5ct6RTPa1byiMwsjCWWsOTlp6', '', 'pending', NULL, '2026-03-09 21:04:45', NULL),
(19, 123456789, 10.00, 'DEP-123456789-1773218615-06e15d66', NULL, 'https://checkout.chapa.co/checkout/payment/93awrfbqZXm45i1FmULRWZLJQiejjGB9WbbUs0IZzEkDn', '', 'pending', NULL, '2026-03-11 11:43:35', NULL),
(20, 123456789, 10.00, 'DEP-123456789-1773222868787-46cdc901', NULL, 'https://checkout.chapa.co/checkout/payment/wDZ1W0z4eMWcj9MhAqT5qFSUJxGGgevyU7r3PDTTwlBvn', '', 'pending', NULL, '2026-03-11 12:54:28', NULL),
(21, 123456789, 10.00, 'DEP-123456789-1773222964152-f2d29bbc', NULL, 'https://checkout.chapa.co/checkout/payment/eHdZVoCwLjVxxdaOKqR2MR3TbgIKZfCZf2q4MSQZmFWZb', '', 'pending', NULL, '2026-03-11 12:56:04', NULL),
(22, 123456789, 10.00, 'DEP-123456789-1773223811807-5e1a6903', NULL, 'https://checkout.chapa.co/checkout/payment/Gk6DRzJHOT2kNVgA6D55azLciL1aKAiqNCBTLWYKUscEv', '', 'pending', NULL, '2026-03-11 13:10:11', NULL),
(23, 123456789, 10.00, 'DEP-123456789-1773225252269-7896', NULL, NULL, '', 'pending', NULL, '2026-03-11 13:34:12', NULL),
(24, 123456789, 10.00, 'DEP-123456789-1773225303856-6203', NULL, NULL, '', 'pending', NULL, '2026-03-11 13:35:03', NULL),
(25, 123456789, 21.00, 'DEP-123456789-1773225346973-9109', NULL, NULL, '', 'success', NULL, '2026-03-11 13:35:46', '2026-03-11 13:51:51'),
(26, 123456789, 10.00, 'DEP-123456789-1773226722648-723', NULL, NULL, '', 'pending', NULL, '2026-03-11 13:58:42', NULL),
(27, 123456789, 10.00, 'DEP-123456789-1773226755926-6563', NULL, NULL, '', 'pending', NULL, '2026-03-11 13:59:15', NULL),
(28, 123456789, 10.00, 'DEP-123456789-1773227750194-8484', 'CHIfjYlLbSwQ0', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHIfjYlLbSwQ0\",\"tx_ref\":\"DEP-123456789-1773227750194-8484\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-11T11:16:01.000000Z\",\"updated_at\":\"2026-03-11T11:16:08.000000Z\"}}', '2026-03-11 14:15:50', '2026-03-11 14:16:20'),
(29, 123456789, 23.00, 'DEP-123456789-1773227791388-1998', 'CHCp115pyBotm', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":23,\"charge\":0.58,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHCp115pyBotm\",\"tx_ref\":\"DEP-123456789-1773227791388-1998\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-11T11:16:40.000000Z\",\"updated_at\":\"2026-03-11T11:16:50.000000Z\"}}', '2026-03-11 14:16:31', '2026-03-11 14:16:55'),
(30, 123456789, 28.00, 'DEP-123456789-1773227824482-506', 'CHQriI0HuQvgj', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":28,\"charge\":0.7,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHQriI0HuQvgj\",\"tx_ref\":\"DEP-123456789-1773227824482-506\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-11T11:17:12.000000Z\",\"updated_at\":\"2026-03-11T11:17:20.000000Z\"}}', '2026-03-11 14:17:04', '2026-03-11 14:17:25'),
(31, 123456789, 10.00, 'DEP-123456789-1773228605001-8546', NULL, NULL, '', 'pending', NULL, '2026-03-11 14:30:05', NULL),
(32, 123456789, 10.00, 'DEP-123456789-1773228740052-616', NULL, NULL, '', 'pending', NULL, '2026-03-11 14:32:20', NULL),
(33, 123456789, 10.00, 'DEP-123456789-1773228968226-440', 'CHjlCXxMJNrhI', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHjlCXxMJNrhI\",\"tx_ref\":\"DEP-123456789-1773228968226-440\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-11T11:36:19.000000Z\",\"updated_at\":\"2026-03-11T11:36:29.000000Z\"}}', '2026-03-11 14:36:08', '2026-03-11 14:36:35'),
(34, 123456789, 11.00, 'DEP-123456789-1773229013747-2727', NULL, NULL, '', 'pending', NULL, '2026-03-11 14:36:53', NULL),
(35, 123456789, 10.00, 'DEP-123456789-1773229448315-8045', 'CHJld1Fb5U3U6', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHJld1Fb5U3U6\",\"tx_ref\":\"DEP-123456789-1773229448315-8045\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-11T11:44:18.000000Z\",\"updated_at\":\"2026-03-11T11:44:28.000000Z\"}}', '2026-03-11 14:44:08', '2026-03-11 14:44:54'),
(36, 123456789, 27.00, 'DEP-123456789-1773229505969-8057', 'CHJLnxlzyh6Tt', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":27,\"charge\":0.68,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHJLnxlzyh6Tt\",\"tx_ref\":\"DEP-123456789-1773229505969-8057\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-11T11:45:15.000000Z\",\"updated_at\":\"2026-03-11T11:45:24.000000Z\"}}', '2026-03-11 14:45:05', '2026-03-11 14:45:32'),
(37, 123456789, 10.00, 'DEP-0-1774432402083-4345', NULL, NULL, '', 'pending', NULL, '2026-03-25 12:53:22', NULL),
(38, 123456789, 10.00, 'DEP-0-1774432454897-9766', 'CHi37Xwv5kvmS', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHi37Xwv5kvmS\",\"tx_ref\":\"DEP-0-1774432454897-9766\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-25T09:54:21.000000Z\",\"updated_at\":\"2026-03-25T09:54:32.000000Z\"}}', '2026-03-25 12:54:14', '2026-03-25 12:54:42'),
(39, 123456789, 10.00, 'DEP-123456789-1774442764324-4591', NULL, NULL, '', 'pending', NULL, '2026-03-25 15:46:04', NULL),
(40, 123456789, 10.00, 'DEP-123456789-1774823250443-6523', 'CHijaFRuT7r9M', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHijaFRuT7r9M\",\"tx_ref\":\"DEP-123456789-1774823250443-6523\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-29T22:27:42.000000Z\",\"updated_at\":\"2026-03-29T22:27:55.000000Z\"}}', '2026-03-30 01:27:30', '2026-03-30 01:28:04'),
(41, 123456789, 20.00, 'DEP-123456789-1774823296021-5963', 'CHz908yXDJu1B', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":20,\"charge\":0.5,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHz908yXDJu1B\",\"tx_ref\":\"DEP-123456789-1774823296021-5963\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-03-29T22:28:24.000000Z\",\"updated_at\":\"2026-03-29T22:28:34.000000Z\"}}', '2026-03-30 01:28:16', '2026-03-30 01:28:48'),
(42, 123456789, 10.00, 'DEP-123456789-1774823338631-9918', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:28:58', NULL),
(43, 123456789, 10.00, 'DEP-123456789-1774823611403-8519', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:33:31', NULL),
(44, 123456789, 10.00, 'DEP-123456789-1774823901475-7655', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:38:21', NULL),
(45, 123456789, 10.00, 'DEP-123456789-1774824508570-712', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:48:28', NULL),
(46, 123456789, 10.00, 'DEP-123456789-1774824610661-8639', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:50:10', NULL),
(47, 123456789, 10000.00, 'DEP-123456789-1774824652899-5507', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:50:52', NULL),
(48, 123456789, 10000.00, 'DEP-123456789-1774824666172-2893', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:51:06', NULL),
(49, 123456789, 10.00, 'DEP-123456789-1774824959331-8424', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:55:59', NULL),
(50, 123456789, 10000.00, 'DEP-123456789-1774824968276-6384', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:56:08', NULL),
(51, 123456789, 10000.00, 'DEP-123456789-1774824995273-3765', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:56:35', NULL),
(52, 123456789, 10.00, 'DEP-123456789-1774825039484-9887', NULL, NULL, '', 'pending', NULL, '2026-03-30 01:57:19', NULL),
(53, 123456789, 10.00, 'DEP-123456789-1774832597371-2669', NULL, NULL, '', 'pending', NULL, '2026-03-30 04:03:17', NULL),
(54, 123456789, 10.00, 'DEP-123456789-1774833001883-7504', NULL, NULL, '', 'pending', NULL, '2026-03-30 04:10:01', NULL),
(55, 123456789, 10.00, 'DEP-123456789-1775226750471-9640', NULL, NULL, '', 'pending', NULL, '2026-04-03 17:32:30', NULL),
(56, 123456789, 10.00, 'DEP-123456789-1775318135464-2377', NULL, NULL, '', 'pending', NULL, '2026-04-04 18:55:35', NULL),
(57, 123456789, 10.00, 'DEP-123456789-1775318549242-9967', NULL, NULL, '', 'pending', NULL, '2026-04-04 19:02:29', NULL),
(58, 123456789, 10.00, 'DEP-123456789-1775562228063-9508', NULL, NULL, '', 'pending', NULL, '2026-04-07 14:43:48', NULL),
(59, 123456789, 10.00, 'DEP-123456789-1775564200015-9560', NULL, NULL, '', 'pending', NULL, '2026-04-07 15:16:40', NULL),
(61, 5928771903, 10.00, 'DEP-5928771903-1775569435116-8339', NULL, NULL, '', 'pending', NULL, '2026-04-07 13:43:55', NULL),
(62, 779060335, 1000.00, 'DEP-779060335-1775581951167-7445', NULL, NULL, '', 'pending', NULL, '2026-04-07 17:12:31', NULL),
(63, 779060335, 10.00, 'DEP-779060335-1775637120322-5311', NULL, NULL, '', 'pending', NULL, '2026-04-08 08:32:00', NULL),
(64, 5928771903, 10.00, 'DEP-5928771903-1775749083285-8432', NULL, NULL, '', 'pending', NULL, '2026-04-09 15:38:03', NULL),
(65, 5928771903, 10.00, 'DEP-5928771903-1775749142527-4034', NULL, NULL, '', 'pending', NULL, '2026-04-09 15:39:03', NULL),
(66, 5928771903, 10.00, 'DEP-5928771903-1775750006779-7213', NULL, NULL, '', 'pending', NULL, '2026-04-09 15:53:27', NULL),
(67, 5928771903, 10.00, 'DEP-5928771903-1775757940950-1429', NULL, NULL, '', 'pending', NULL, '2026-04-09 18:05:41', NULL),
(68, 5928771903, 10.00, 'DEP-5928771903-1775762678674-2044', NULL, NULL, '', 'pending', NULL, '2026-04-09 19:24:39', NULL),
(69, 779060335, 10.00, 'DEP-779060335-1775804837685-7996', NULL, NULL, '', 'pending', NULL, '2026-04-10 07:07:18', NULL),
(70, 779060335, 100.00, 'DEP-779060335-1775804906200-2551', NULL, NULL, '', 'pending', NULL, '2026-04-10 07:08:27', NULL),
(71, 779060335, 100.00, 'DEP-779060335-1775826528099-1861', NULL, NULL, '', 'pending', NULL, '2026-04-10 13:08:49', NULL),
(72, 779060335, 100.00, 'DEP-779060335-1775826549766-2119', NULL, NULL, '', 'pending', NULL, '2026-04-10 13:09:10', NULL),
(73, 779060335, 340.00, 'DEP-779060335-1775827680353-4902', NULL, NULL, '', 'pending', NULL, '2026-04-10 13:28:01', NULL),
(74, 5928771903, 100.00, 'DEP-5928771903-1775843697768-236', NULL, NULL, '', 'pending', NULL, '2026-04-10 17:54:58', NULL),
(75, 5928771903, 10.00, 'DEP-5928771903-1775853882910-2190', NULL, NULL, '', 'pending', NULL, '2026-04-10 20:44:43', NULL),
(76, 779060335, 1000.00, 'DEP-779060335-1776008974201-2262', NULL, NULL, '', 'pending', NULL, '2026-04-12 15:49:34', NULL),
(77, 6912633019, 10.00, 'DEP-6912633019-1776100075525-5004', NULL, NULL, '', 'pending', NULL, '2026-04-13 17:07:56', NULL),
(78, 6912633019, 10.00, 'DEP-6912633019-1776100200257-5884', NULL, NULL, '', 'pending', NULL, '2026-04-13 17:10:01', NULL),
(79, 6912633019, 10.00, 'DEP-6912633019-1776105436519-2535', NULL, NULL, '', 'pending', NULL, '2026-04-13 18:37:17', NULL),
(80, 5928771903, 10.00, 'DEP-5928771903-1776105553373-1400', NULL, NULL, '', 'pending', NULL, '2026-04-13 18:39:14', NULL),
(81, 5928771903, 10.00, 'DEP-5928771903-1776105676608-3014', NULL, NULL, '', 'pending', NULL, '2026-04-13 18:41:17', NULL),
(82, 5928771903, 10.00, 'DEP-5928771903-1776113619639-88', NULL, NULL, '', 'pending', NULL, '2026-04-13 20:53:40', NULL),
(83, 5928771903, 100.00, 'DEP-5928771903-1776113721090-629', NULL, NULL, '', 'pending', NULL, '2026-04-13 20:55:21', NULL),
(84, 5928771903, 10.00, 'DEP-5928771903-1776115156172-1964', NULL, NULL, '', 'pending', NULL, '2026-04-13 21:19:16', NULL),
(85, 5928771903, 10.00, 'DEP-5928771903-1776115240776-4915', NULL, NULL, '', 'pending', NULL, '2026-04-13 21:20:41', NULL),
(86, 5928771903, 100.00, 'DEP-5928771903-1776115340854-965', NULL, NULL, '', 'pending', NULL, '2026-04-13 21:22:21', NULL),
(87, 0, 10.00, 'DEP-unauth_local_user-1776227857407-9734', NULL, NULL, '', 'failed', NULL, '2026-04-15 04:37:37', NULL),
(88, 0, 10.00, 'DEP-unauth_local_user-1776227909937-4550', 'CHANrax7efVGV', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHANrax7efVGV\",\"tx_ref\":\"DEP-unauth_local_user-1776227909937-4550\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-04-15T04:38:39.000000Z\",\"updated_at\":\"2026-04-15T04:38:49.000000Z\"}}', '2026-04-15 04:38:30', '2026-04-15 04:39:02'),
(89, 0, 10.00, 'DEP-unauth_local_user-1776228122856-6234', NULL, NULL, '', 'pending', NULL, '2026-04-15 04:42:03', NULL),
(90, 5928771903, 100.00, 'DEP-5928771903-1776229614430-8227', NULL, NULL, '', 'pending', NULL, '2026-04-15 05:06:54', NULL),
(91, 779060335, 10.00, 'DEP-779060335-1776273584628-1534', 'CHTS6tGmE4feQ', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHTS6tGmE4feQ\",\"tx_ref\":\"DEP-779060335-1776273584628-1534\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-04-15T17:19:59.000000Z\",\"updated_at\":\"2026-04-15T17:20:11.000000Z\"}}', '2026-04-15 17:19:45', '2026-04-15 17:20:38'),
(92, 5928771903, 11660.00, 'DEP-5928771903-1776338733999-8219', NULL, NULL, '', 'pending', NULL, '2026-04-16 11:25:34', NULL),
(93, 5928771903, 30.00, 'DEP-5928771903-1776753347395-7233', NULL, NULL, '', 'pending', NULL, '2026-04-21 06:35:47', NULL),
(94, 779060335, 10.00, 'DEP-779060335-1776753441922-565', 'CHF5IIBKnJl8Z', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHF5IIBKnJl8Z\",\"tx_ref\":\"DEP-779060335-1776753441922-565\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-04-21T06:37:34.000000Z\",\"updated_at\":\"2026-04-21T06:37:47.000000Z\"}}', '2026-04-21 06:37:22', '2026-04-21 06:37:53'),
(95, 779060335, 10.00, 'DEP-779060335-1776753491163-5346', NULL, NULL, '', 'failed', NULL, '2026-04-21 06:38:11', NULL),
(96, 5928771903, 10.00, 'DEP-5928771903-1776753877906-4379', NULL, NULL, '', 'pending', NULL, '2026-04-21 06:44:38', NULL),
(97, 5928771903, 10.00, 'DEP-5928771903-1776753964967-7770', NULL, NULL, '', 'pending', NULL, '2026-04-21 06:46:05', NULL),
(98, 5928771903, 10.00, 'DEP-5928771903-1776754012684-3463', NULL, NULL, '', 'pending', NULL, '2026-04-21 06:46:53', NULL),
(99, 6528707984, 1000.00, 'DEP-6528707984-1777569473571-6993', NULL, NULL, '', 'failed', NULL, '2026-04-30 17:17:53', NULL),
(100, 6528707984, 1000.00, 'DEP-6528707984-1777569514694-6349', NULL, NULL, '', 'failed', NULL, '2026-04-30 17:18:35', NULL),
(101, 6528707984, 1000.00, 'DEP-6528707984-1777569553460-9354', NULL, NULL, '', 'failed', NULL, '2026-04-30 17:19:14', NULL),
(102, 6528707984, 1000.00, 'DEP-6528707984-1777569594452-2875', NULL, NULL, '', 'failed', NULL, '2026-04-30 17:19:54', NULL),
(103, 6528707984, 11000.00, 'DEP-6528707984-1777569641033-1025', NULL, NULL, '', 'failed', NULL, '2026-04-30 17:20:41', NULL),
(104, 6528707984, 9000.00, 'DEP-6528707984-1777569666543-5510', NULL, NULL, '', 'failed', NULL, '2026-04-30 17:21:06', NULL),
(105, 5928771903, 10.00, 'DEP-5928771903-1777897022371-1371', NULL, NULL, '', 'pending', NULL, '2026-05-04 12:17:03', NULL),
(106, 5928771903, 10.00, 'DEP-5928771903-1777897150191-1768', NULL, NULL, '', 'pending', NULL, '2026-05-04 12:19:10', NULL),
(107, 5928771903, 10.00, 'DEP-5928771903-1777898057386-7697', NULL, NULL, '', 'pending', NULL, '2026-05-04 12:34:18', NULL),
(108, 5928771903, 10.00, 'DEP-5928771903-1777898163106-536', NULL, NULL, '', 'pending', NULL, '2026-05-04 12:36:03', NULL),
(109, 5928771903, 10.00, 'DEP-5928771903-1777898244861-4972', NULL, NULL, '', 'pending', NULL, '2026-05-04 12:37:25', NULL),
(110, 2030466394, 63.00, 'DEP-2030466394-1778068660544-1108', 'CHc9xBFru2yEv', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Amanuel nigussie\",\"last_name\":\"Customer\",\"email\":\"client@vamos.com\",\"phone_number\":\"251926937509\",\"currency\":\"ETB\",\"amount\":63,\"charge\":1.58,\"mode\":\"live\",\"method\":\"cbebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHc9xBFru2yEv\",\"tx_ref\":\"DEP-2030466394-1778068660544-1108\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-06T17:50:56.000000Z\",\"updated_at\":\"2026-05-06T17:51:06.000000Z\"}}', '2026-05-06 17:50:44', '2026-05-06 17:51:17'),
(111, 7115890811, 15.00, 'DEP-7115890811-1778229565398-6515', 'CHvrXm0FX7GOG', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":null,\"last_name\":null,\"email\":null,\"phone_number\":\"251989774595\",\"currency\":\"ETB\",\"amount\":15,\"charge\":0.26,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHvrXm0FX7GOG\",\"tx_ref\":\"DEP-7115890811-1778229565398-6515\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-08T08:39:34.000000Z\",\"updated_at\":\"2026-05-08T08:39:44.000000Z\"}}', '2026-05-08 08:39:25', '2026-05-08 08:39:48'),
(112, 2030466394, 40.00, 'DEP-2030466394-1778229958771-5718', 'CHiWL4kfbPGTO', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Amanuel nigussie\",\"last_name\":\"Customer\",\"email\":\"client@vamos.com\",\"phone_number\":\"251926937509\",\"currency\":\"ETB\",\"amount\":40,\"charge\":1,\"mode\":\"live\",\"method\":\"cbebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHiWL4kfbPGTO\",\"tx_ref\":\"DEP-2030466394-1778229958771-5718\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-08T14:39:16.000000Z\",\"updated_at\":\"2026-05-08T14:39:32.000000Z\"}}', '2026-05-08 14:39:00', '2026-05-08 14:39:47'),
(113, 5928771903, 10.00, 'DEP-5928771903-1778314022856-1643', NULL, NULL, '', 'pending', NULL, '2026-05-09 08:07:03', NULL),
(114, 1961928800, 50.00, 'DEP-1961928800-1778373287274-8802', NULL, NULL, '', 'failed', NULL, '2026-05-10 00:34:47', NULL),
(115, 1961928800, 50.00, 'DEP-1961928800-1778373347772-7571', NULL, NULL, '', 'failed', NULL, '2026-05-10 00:35:48', NULL),
(116, 1961928800, 50.00, 'DEP-1961928800-1778373369491-4513', 'CHTsoqOkDehgP', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":null,\"last_name\":null,\"email\":null,\"phone_number\":\"251703268212\",\"currency\":\"ETB\",\"amount\":50,\"charge\":1.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHTsoqOkDehgP\",\"tx_ref\":\"DEP-1961928800-1778373369491-4513\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-10T00:36:31.000000Z\",\"updated_at\":\"2026-05-10T00:36:53.000000Z\"}}', '2026-05-10 00:36:09', '2026-05-10 00:36:55'),
(117, 1961928800, 200.00, 'DEP-1961928800-1778459890392-6948', 'CHfbiL5oGxyto', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Mickey\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251912268212\",\"currency\":\"ETB\",\"amount\":200,\"charge\":3.45,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHfbiL5oGxyto\",\"tx_ref\":\"DEP-1961928800-1778459890392-6948\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-11T00:38:24.000000Z\",\"updated_at\":\"2026-05-11T00:38:41.000000Z\"}}', '2026-05-11 00:38:11', '2026-05-11 00:38:46'),
(118, 5065467140, 40.00, 'DEP-5065467140-1778525984642-2810', 'CHm23VzUy5YxD', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Zidni\",\"last_name\":\"-\",\"email\":\"payment@waliya.com\",\"phone_number\":\"251961540100\",\"currency\":\"ETB\",\"amount\":40,\"charge\":0.69,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHm23VzUy5YxD\",\"tx_ref\":\"DEP-5065467140-1778525984642-2810\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-11T18:59:53.000000Z\",\"updated_at\":\"2026-05-11T19:00:11.000000Z\"}}', '2026-05-11 18:59:46', '2026-05-11 19:00:14'),
(119, 5065467140, 20.00, 'DEP-5065467140-1778527490776-7028', 'CHXNT7KxhwpWI', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Zidni\",\"last_name\":\"-\",\"email\":\"payment@waliya.com\",\"phone_number\":\"251961540100\",\"currency\":\"ETB\",\"amount\":20,\"charge\":0.35,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHXNT7KxhwpWI\",\"tx_ref\":\"DEP-5065467140-1778527490776-7028\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-11T19:25:02.000000Z\",\"updated_at\":\"2026-05-11T19:25:11.000000Z\"}}', '2026-05-11 19:24:52', '2026-05-11 19:25:18'),
(120, 1961928800, 280.00, 'DEP-1961928800-1778543107427-2180', NULL, NULL, '', 'failed', NULL, '2026-05-11 23:45:08', NULL),
(121, 1961928800, 270.00, 'DEP-1961928800-1778543162902-9061', 'CH7V3ulFHpXsA', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Mickey\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251912268212\",\"currency\":\"ETB\",\"amount\":270,\"charge\":4.66,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CH7V3ulFHpXsA\",\"tx_ref\":\"DEP-1961928800-1778543162902-9061\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-11T23:46:10.000000Z\",\"updated_at\":\"2026-05-11T23:46:23.000000Z\"}}', '2026-05-11 23:46:03', '2026-05-11 23:46:28'),
(122, 2030466394, 20.00, 'DEP-2030466394-1778631114307-7830', NULL, NULL, '', 'failed', NULL, '2026-05-13 06:04:51', NULL),
(123, 2030466394, 20.00, 'DEP-2030466394-1778631171881-9616', NULL, NULL, '', 'pending', NULL, '2026-05-13 06:06:12', NULL),
(124, 2030466394, 20.00, 'DEP-2030466394-1778631181938-6657', NULL, NULL, '', 'pending', NULL, '2026-05-13 06:06:12', NULL),
(125, 2030466394, 20.00, 'DEP-2030466394-1778631191918-884', 'CHUjXychhjhmB', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Amanuel nigussie\",\"last_name\":\"Customer\",\"email\":\"client@vamos.com\",\"phone_number\":\"251926937509\",\"currency\":\"ETB\",\"amount\":20,\"charge\":0.35,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHUjXychhjhmB\",\"tx_ref\":\"DEP-2030466394-1778631191918-884\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-13T06:06:26.000000Z\",\"updated_at\":\"2026-05-13T06:06:38.000000Z\"}}', '2026-05-13 06:06:12', '2026-05-13 06:06:47'),
(126, 2047670227, 70.00, 'DEP-2047670227-1778826567795-3428', 'CHJTd08g3LuZc', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Mohammed\",\"last_name\":\"Abdu Mohammed\",\"email\":null,\"phone_number\":\"251934551738\",\"currency\":\"ETB\",\"amount\":70,\"charge\":1.21,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHJTd08g3LuZc\",\"tx_ref\":\"DEP-2047670227-1778826567795-3428\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-15T06:29:38.000000Z\",\"updated_at\":\"2026-05-15T06:29:49.000000Z\"}}', '2026-05-15 06:29:29', '2026-05-15 06:29:52'),
(127, 8126556091, 10.00, 'DEP-8126556091-1778930818821-2423', 'CH5OOPmSFBOHd', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251984210648\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.17,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CH5OOPmSFBOHd\",\"tx_ref\":\"DEP-8126556091-1778930818821-2423\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-16T11:27:14.000000Z\",\"updated_at\":\"2026-05-16T11:27:23.000000Z\"}}', '2026-05-16 11:27:01', '2026-05-16 11:27:31'),
(128, 8126556091, 10.00, 'DEP-8126556091-1778932380548-3562', 'CHAEKJCNUlIE1', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251984210648\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.17,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHAEKJCNUlIE1\",\"tx_ref\":\"DEP-8126556091-1778932380548-3562\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-16T11:53:21.000000Z\",\"updated_at\":\"2026-05-16T11:53:31.000000Z\"}}', '2026-05-16 11:53:03', '2026-05-16 11:53:44'),
(129, 6128857120, 500.00, 'DEP-6128857120-1778945461571-2444', NULL, NULL, '', 'failed', NULL, '2026-05-16 15:31:03', NULL),
(130, 6128857120, 300.00, 'DEP-6128857120-1778945527187-8688', 'CH3R69dFanVOy', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Samuel\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251901250406\",\"currency\":\"ETB\",\"amount\":300,\"charge\":5.18,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CH3R69dFanVOy\",\"tx_ref\":\"DEP-6128857120-1778945527187-8688\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-16T15:32:15.000000Z\",\"updated_at\":\"2026-05-16T15:32:23.000000Z\"}}', '2026-05-16 15:32:08', '2026-05-16 15:32:29'),
(131, 6128857120, 200.00, 'DEP-6128857120-1778956642992-1326', 'CHrcEGLPMkhvu', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Samuel\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251901250406\",\"currency\":\"ETB\",\"amount\":200,\"charge\":3.45,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHrcEGLPMkhvu\",\"tx_ref\":\"DEP-6128857120-1778956642992-1326\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-16T18:37:38.000000Z\",\"updated_at\":\"2026-05-16T18:37:50.000000Z\"}}', '2026-05-16 18:37:24', '2026-05-16 18:38:00'),
(132, 5826257535, 50.00, 'DEP-5826257535-1779034173205-7245', NULL, NULL, '', 'pending', NULL, '2026-05-17 22:09:54', NULL),
(133, 5826257535, 10.00, 'DEP-5826257535-1779034349144-7955', NULL, NULL, '', 'pending', NULL, '2026-05-17 22:12:50', NULL),
(134, 5826257535, 10.00, 'DEP-5826257535-1779053081119-5898', 'CHXOZMFq7v08W', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":null,\"last_name\":null,\"email\":null,\"phone_number\":\"251964875380\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.17,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHXOZMFq7v08W\",\"tx_ref\":\"DEP-5826257535-1779053081119-5898\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-18T03:25:11.000000Z\",\"updated_at\":\"2026-05-18T03:25:28.000000Z\"}}', '2026-05-18 03:25:02', '2026-05-18 03:25:48'),
(135, 6581678657, 40.00, 'DEP-6581678657-1779098408913-9611', 'CHKfYKPyCXW7O', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":null,\"last_name\":null,\"email\":null,\"phone_number\":\"251917005690\",\"currency\":\"ETB\",\"amount\":40,\"charge\":0.69,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHKfYKPyCXW7O\",\"tx_ref\":\"DEP-6581678657-1779098408913-9611\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-18T10:00:16.000000Z\",\"updated_at\":\"2026-05-18T10:00:29.000000Z\"}}', '2026-05-18 10:00:09', '2026-05-18 10:00:34'),
(136, 6581678657, 40.00, 'DEP-6581678657-1779098808746-5827', 'CHpWmzJ5AKpzk', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":null,\"last_name\":null,\"email\":null,\"phone_number\":\"251917005690\",\"currency\":\"ETB\",\"amount\":40,\"charge\":0.69,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHpWmzJ5AKpzk\",\"tx_ref\":\"DEP-6581678657-1779098808746-5827\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-18T10:06:56.000000Z\",\"updated_at\":\"2026-05-18T10:07:08.000000Z\"}}', '2026-05-18 10:06:49', '2026-05-18 10:07:13'),
(137, 6655261307, 20.00, 'DEP-6655261307-1779100038037-1684', 'CHA2hhEIqlUmY', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":null,\"last_name\":null,\"email\":null,\"phone_number\":\"251963515789\",\"currency\":\"ETB\",\"amount\":20,\"charge\":0.35,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHA2hhEIqlUmY\",\"tx_ref\":\"DEP-6655261307-1779100038037-1684\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-18T10:27:32.000000Z\",\"updated_at\":\"2026-05-18T10:27:49.000000Z\"}}', '2026-05-18 10:27:18', '2026-05-18 10:28:50'),
(138, 8126556091, 10.00, 'DEP-8126556091-1779147712723-5896', 'CHuhTL8vpeltA', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251984210648\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.17,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHuhTL8vpeltA\",\"tx_ref\":\"DEP-8126556091-1779147712723-5896\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-18T23:42:05.000000Z\",\"updated_at\":\"2026-05-18T23:42:18.000000Z\"}}', '2026-05-18 23:41:54', '2026-05-18 23:42:36'),
(139, 557497105, 10.00, 'DEP-557497105-1779148943598-3665', NULL, NULL, '', 'failed', NULL, '2026-05-19 06:02:45', NULL),
(140, 557497105, 10.00, 'DEP-557497105-1779148973600-7242', NULL, NULL, '', 'pending', NULL, '2026-05-19 06:03:15', NULL),
(141, 557497105, 10.00, 'DEP-557497105-1779161254717-6025', NULL, NULL, '', 'pending', NULL, '2026-05-19 09:27:56', NULL),
(142, 557497105, 10.00, 'DEP-557497105-1779161288343-6424', NULL, NULL, '', 'pending', NULL, '2026-05-19 09:30:10', NULL),
(143, 557497105, 100.00, 'DEP-557497105-1779161403019-1710', NULL, NULL, '', 'pending', NULL, '2026-05-19 09:30:32', NULL),
(144, 348956182, 10.00, 'DEP-348956182-1779172088966-2715', NULL, NULL, '', 'failed', NULL, '2026-05-19 12:26:51', NULL),
(145, 6128857120, 100.00, 'DEP-6128857120-1779267296661-1285', NULL, NULL, '', 'failed', NULL, '2026-05-20 08:54:57', NULL),
(146, 6128857120, 100.00, 'DEP-6128857120-1779267330488-7342', NULL, NULL, '', 'failed', NULL, '2026-05-20 08:55:31', NULL),
(147, 6128857120, 150.00, 'DEP-6128857120-1779267471855-4237', NULL, NULL, '', 'failed', NULL, '2026-05-20 08:57:53', NULL),
(148, 6128857120, 150.00, 'DEP-6128857120-1779267913929-8296', NULL, NULL, '', 'failed', NULL, '2026-05-20 09:05:15', NULL),
(149, 6128857120, 100.00, 'DEP-6128857120-1779267987736-4093', NULL, NULL, '', 'failed', NULL, '2026-05-20 09:06:29', NULL),
(150, 779060335, 10.00, 'DEP-779060335-1779269172043-7599', 'CHWz8YYZ4ZY7b', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"Abate\",\"email\":\"mulukenzewude736@gmail.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHWz8YYZ4ZY7b\",\"tx_ref\":\"DEP-779060335-1779269172043-7599\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-20T09:26:26.000000Z\",\"updated_at\":\"2026-05-20T09:26:54.000000Z\"}}', '2026-05-20 09:26:12', '2026-05-20 09:26:55'),
(151, 6128857120, 150.00, 'DEP-6128857120-1779270189869-2834', NULL, NULL, '', 'failed', NULL, '2026-05-20 09:43:11', NULL),
(152, 6128857120, 100.00, 'DEP-6128857120-1779270265215-2747', NULL, NULL, '', 'failed', NULL, '2026-05-20 09:44:26', NULL),
(153, 6128857120, 110.00, 'DEP-6128857120-1779270337037-9674', NULL, NULL, '', 'failed', NULL, '2026-05-20 09:45:38', NULL),
(154, 6128857120, 110.00, 'DEP-6128857120-1779270728765-1454', NULL, NULL, '', 'pending', NULL, '2026-05-20 09:52:10', NULL),
(155, 6128857120, 20.00, 'DEP-6128857120-1779270788844-1518', NULL, NULL, '', 'failed', NULL, '2026-05-20 09:53:10', NULL),
(156, 1961928800, 50.00, 'DEP-1961928800-1779314894814-7892', 'CHSQfsbUhTjG0', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Mickey\",\"last_name\":\"\",\"email\":\"\",\"phone_number\":\"251912268212\",\"currency\":\"ETB\",\"amount\":50,\"charge\":0.86,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHSQfsbUhTjG0\",\"tx_ref\":\"DEP-1961928800-1779314894814-7892\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-20T22:08:24.000000Z\",\"updated_at\":\"2026-05-20T22:08:32.000000Z\"}}', '2026-05-20 22:08:15', '2026-05-20 22:08:49'),
(157, 7048311314, 10.00, 'DEP-7048311314-1779385162704-5944', NULL, NULL, '', 'pending', NULL, '2026-05-21 17:39:23', NULL),
(158, 779060335, 1000.00, 'DEP-779060335-1779525094858-7159', NULL, NULL, '', 'pending', NULL, '2026-05-23 08:31:35', NULL),
(159, 5826257535, 10.00, 'DEP-5826257535-1779771125101-3899', NULL, NULL, '', 'pending', NULL, '2026-05-26 10:52:26', NULL),
(160, 557497105, 10.00, 'DEP-557497105-1779771204330-5762', NULL, NULL, '', 'failed', NULL, '2026-05-26 10:53:45', NULL),
(161, 2030466394, 40.00, 'DEP-2030466394-1779784851390-7103', 'CHRwJCn5zYSRR', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Amanuel nigussie\",\"last_name\":\"Customer\",\"email\":\"client@vamos.com\",\"phone_number\":\"251926937509\",\"currency\":\"ETB\",\"amount\":40,\"charge\":1,\"mode\":\"live\",\"method\":\"cbebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHRwJCn5zYSRR\",\"tx_ref\":\"DEP-2030466394-1779784851390-7103\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-26T14:33:46.000000Z\",\"updated_at\":\"2026-05-26T14:34:00.000000Z\"}}', '2026-05-26 14:33:32', '2026-05-26 14:34:06'),
(162, 557497105, 110.00, 'DEP-557497105-1779855707762-6043', NULL, NULL, '', 'failed', NULL, '2026-05-27 10:22:09', NULL),
(163, 557497105, 110.00, 'DEP-557497105-1779855829516-901', NULL, NULL, '', 'pending', NULL, '2026-05-27 10:24:10', NULL),
(164, 2030466394, 20.00, 'DEP-2030466394-1779874388898-1567', 'CHLpE98JyQBHL', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Amanuel nigussie\",\"last_name\":\"Customer\",\"email\":\"client@vamos.com\",\"phone_number\":\"251926937509\",\"currency\":\"ETB\",\"amount\":20,\"charge\":0.5,\"mode\":\"live\",\"method\":\"cbebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHLpE98JyQBHL\",\"tx_ref\":\"DEP-2030466394-1779874388898-1567\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-27T15:27:06.000000Z\",\"updated_at\":\"2026-05-27T15:27:15.000000Z\"}}', '2026-05-27 15:25:49', '2026-05-27 15:28:17'),
(165, 7360255928, 100.00, 'DEP-7360255928-1779913724719-8968', NULL, NULL, '', 'failed', NULL, '2026-05-27 20:28:45', NULL),
(166, 7360255928, 100.00, 'DEP-7360255928-1779913780815-7190', NULL, NULL, '', 'failed', NULL, '2026-05-27 20:29:41', NULL),
(167, 7360255928, 100.00, 'DEP-7360255928-1779913794376-5350', 'CHlqcD1vWjQzJ', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":null,\"last_name\":null,\"email\":null,\"phone_number\":\"251989146464\",\"currency\":\"ETB\",\"amount\":100,\"charge\":1.73,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHlqcD1vWjQzJ\",\"tx_ref\":\"DEP-7360255928-1779913794376-5350\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-05-27T20:30:12.000000Z\",\"updated_at\":\"2026-05-27T20:30:22.000000Z\"}}', '2026-05-27 20:29:55', '2026-05-27 20:30:26'),
(168, 7048311314, 10.00, 'DEP-7048311314-1779917340311-8461', NULL, NULL, '', 'pending', NULL, '2026-05-27 21:29:00', NULL),
(169, 123456789, 10.00, 'DEP-123456789-1780320032226-1726', NULL, NULL, '', 'pending', NULL, '2026-06-01 13:20:32', NULL),
(170, 7999410461, 50.00, 'DEP-7999410461-1780477557858-1878', 'CHzBLG0HiF3rV', NULL, '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abebe\",\"last_name\":\"Kebede\",\"email\":\"7999410461@t.me\",\"phone_number\":\"251990552803\",\"currency\":\"ETB\",\"amount\":50,\"charge\":0.86,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"Direct Charges\",\"status\":\"success\",\"reference\":\"CHzBLG0HiF3rV\",\"tx_ref\":\"DEP-7999410461-1780477557858-1878\",\"customization\":{\"title\":null,\"description\":null,\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-03T09:06:06.000000Z\",\"updated_at\":\"2026-06-03T09:06:14.000000Z\"}}', '2026-06-03 09:05:58', '2026-06-03 09:06:26'),
(171, 7159821786, 10.00, 'DEP-7159821786-1780485074069-771', NULL, NULL, '', 'pending', NULL, '2026-06-03 11:11:14', NULL),
(172, 5928771903, 10.00, 'DEP-5928771903-1780439110896-8122', NULL, NULL, '', 'pending', NULL, '2026-06-03 11:21:07', NULL),
(173, 7159821786, 10.00, 'DEP-7159821786-1780485793673-8111', NULL, NULL, '', 'pending', NULL, '2026-06-03 11:23:14', NULL),
(174, 8300384390, 10.00, 'DEP-8300384390-1780486474874-1202', NULL, NULL, '', 'pending', NULL, '2026-06-03 11:34:34', NULL),
(175, 5928771903, 10.00, 'DEP-5928771903-1780440066638-1391', NULL, NULL, '', 'pending', NULL, '2026-06-03 11:37:03', NULL),
(176, 5928771903, 10.00, 'DEP-5928771903-1780441157381-1400', NULL, NULL, '', 'pending', NULL, '2026-06-03 11:55:14', NULL),
(177, 5928771903, 10.00, 'DEP-5928771903-1780441194176-6920', NULL, NULL, '', 'pending', NULL, '2026-06-03 11:55:50', NULL),
(178, 5928771903, 10.00, 'DEP-5928771903-1780489116758-5733', NULL, NULL, '', 'pending', NULL, '2026-06-03 12:18:37', NULL),
(179, 8300384390, 10.00, 'DEP-8300384390-1780489215632-2531', NULL, NULL, '', 'pending', NULL, '2026-06-03 12:20:14', NULL),
(180, 5928771903, 10.00, 'DEP-5928771903-1780489865435-6693', NULL, NULL, '', 'pending', NULL, '2026-06-03 12:31:06', NULL),
(181, 5928771903, 10.00, 'DEP-5928771903-1780489908695-303', NULL, NULL, '', 'pending', NULL, '2026-06-03 12:31:49', NULL),
(182, 5928771903, 10.00, 'DEP-5928771903-1780494176535-8506', NULL, NULL, '', 'pending', NULL, '2026-06-03 13:42:56', NULL),
(183, 5928771903, 10.00, 'DEP-5928771903-1780494311800-6452', NULL, NULL, '', 'pending', NULL, '2026-06-03 13:45:13', NULL),
(184, 5928771903, 10.00, 'DEP-5928771903-1780494476957-624', NULL, NULL, '', 'pending', NULL, '2026-06-03 13:47:57', NULL),
(185, 5928771903, 10.00, 'DEP-5928771903-1780495992431-8479', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:13:12', NULL),
(186, 5928771903, 10.00, 'DEP-5928771903-1780496018658-3635', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:13:46', NULL),
(187, 5928771903, 10.00, 'DEP-5928771903-1780496440897-3528', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:20:41', NULL),
(188, 5928771903, 10.00, 'DEP-5928771903-1780496809325-6813', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:26:49', NULL),
(189, 5928771903, 10.00, 'DEP-5928771903-1780496896490-69', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:28:16', NULL),
(190, 5928771903, 10.00, 'DEP-5928771903-1780496991498-3232', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:29:51', NULL),
(191, 5928771903, 10.00, 'DEP-5928771903-1780497027914-5757', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:30:28', NULL),
(192, 5928771903, 10.00, 'DEP-5928771903-1780497601553-5280', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:40:02', NULL),
(193, 5928771903, 10.00, 'DEP-5928771903-1780497687118-9688', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:41:27', NULL),
(194, 5928771903, 10.00, 'DEP-5928771903-1780497761366-8569', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:42:42', NULL),
(195, 5928771903, 10.00, 'DEP-5928771903-1780498741150-1199', NULL, NULL, '', 'pending', NULL, '2026-06-03 14:59:01', NULL),
(196, 5928771903, 10.00, 'DEP-5928771903-1780498830926-3124', NULL, NULL, '', 'pending', NULL, '2026-06-03 15:00:31', NULL),
(197, 5928771903, 10.00, 'DEP-5928771903-1780498924889-6146', NULL, NULL, '', 'pending', NULL, '2026-06-03 15:02:05', NULL),
(198, 5928771903, 10.00, 'DEP-5928771903-1780499787692-5270', NULL, NULL, '', 'pending', NULL, '2026-06-03 15:16:27', NULL),
(199, 7999410461, 50.00, 'DEP-7999410461-1780513389493-5363', NULL, NULL, '', 'pending', NULL, '2026-06-03 19:03:10', NULL),
(200, 7999410461, 50.00, 'DEP-7999410461-1780513422479-3056', NULL, NULL, '', 'pending', NULL, '2026-06-03 19:03:43', NULL),
(201, 7999410461, 100.00, 'DEP-7999410461-1780550452877-6168', NULL, NULL, '', 'pending', NULL, '2026-06-04 05:20:53', NULL),
(202, 7999410461, 10.00, 'DEP-7999410461-1780550593932-7735', NULL, NULL, '', 'pending', NULL, '2026-06-04 05:23:14', NULL),
(203, 7999410461, 10.00, 'DEP-7999410461-1780550624284-3501', NULL, NULL, '', 'pending', NULL, '2026-06-04 05:23:44', NULL),
(204, 7999410461, 10.00, 'DEP-7999410461-1780550988046-703', NULL, NULL, '', 'pending', NULL, '2026-06-04 05:29:48', NULL),
(205, 7999410461, 10.00, 'DEP-7999410461-1780587912786-3953', NULL, NULL, '', 'pending', NULL, '2026-06-04 15:45:13', NULL),
(206, 7999410461, 10.00, 'DEP-7999410461-1780587951702-2715', NULL, NULL, '', 'pending', NULL, '2026-06-04 15:45:52', NULL),
(207, 7999410461, 10.00, 'DEP-7999410461-1780587959888-835', NULL, NULL, '', 'pending', NULL, '2026-06-04 15:46:00', NULL),
(208, 7999410461, 10.00, 'DEP-7999410461-1780588203370-2267', NULL, NULL, '', 'pending', NULL, '2026-06-04 15:50:03', NULL),
(209, 5928771903, 10.00, 'DEP-5928771903-1780648859661-6035', NULL, NULL, '', 'pending', NULL, '2026-06-05 08:41:00', NULL),
(210, 5928771903, 10.00, 'DEP-5928771903-1780649396037-3085', NULL, NULL, '', 'pending', NULL, '2026-06-05 08:49:59', NULL),
(211, 5928771903, 10.00, 'DEP-5928771903-1780652913199-5036', NULL, NULL, '', 'pending', NULL, '2026-06-05 09:48:33', NULL),
(212, 5928771903, 10.00, 'DEP-5928771903-1780652967764-2973', NULL, NULL, '', 'pending', NULL, '2026-06-05 09:49:29', NULL),
(213, 5928771903, 10.00, 'DEP-5928771903-1780653018150-2772', NULL, NULL, '', 'pending', NULL, '2026-06-05 09:50:18', NULL),
(214, 5928771903, 20.00, 'DEP-5928771903-1780653097139-8794', NULL, NULL, '', 'pending', NULL, '2026-06-05 09:51:37', NULL);
INSERT INTO `deposits` (`id`, `user_id`, `amount`, `tx_ref`, `chapa_tx_ref`, `checkout_url`, `reference_id`, `status`, `chapa_response`, `created_at`, `completed_at`) VALUES
(215, 5928771903, 10.00, 'DEP-5928771903-1780654124210-2306', NULL, NULL, '', 'pending', NULL, '2026-06-05 10:08:45', NULL),
(216, 5928771903, 10.00, 'DEP-5928771903-1780654182333-5486', NULL, NULL, '', 'pending', NULL, '2026-06-05 10:09:43', NULL),
(217, 5928771903, 10.00, 'DEP-5928771903-1780654399766-2090', NULL, NULL, '', 'pending', NULL, '2026-06-05 10:13:20', NULL),
(218, 5928771903, 10.00, 'DEP-5928771903-1780654601319-4700', NULL, NULL, '', 'pending', NULL, '2026-06-05 10:16:42', NULL),
(219, 5928771903, 10.00, 'DEP-5928771903-1780654720833-2967', NULL, NULL, '', 'pending', NULL, '2026-06-05 10:18:42', NULL),
(220, 123456789, 110.00, 'DEP-123456789-1780657305343-6c03e8c4', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:01:45', NULL),
(221, 123456789, 110.00, 'DEP-123456789-1780657308423-5da0a408', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:01:48', NULL),
(222, 123456789, 10.00, 'DEP-123456789-1780657675095-3ac04834', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:07:55', NULL),
(223, 123456789, 10.00, 'DEP-123456789-1780657681439-23566106', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:08:01', NULL),
(224, 123456789, 10.00, 'DEP-123456789-1780657704684-a5d12866', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:08:24', NULL),
(225, 123456789, 10.00, 'DEP-123456789-1780657849021-18add59f', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:10:49', NULL),
(226, 123456789, 10.00, 'DEP-123456789-1780658014127-e5585402', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:13:34', NULL),
(227, 123456789, 10.00, 'DEP-123456789-1780658045461-157932cd', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:14:05', NULL),
(228, 123456789, 10.00, 'DEP-123456789-1780658268901-12a99986', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:17:48', NULL),
(229, 123456789, 10.00, 'DEP-123456789-1780658271627-eb58005c', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:17:51', NULL),
(230, 5928771903, 10.00, 'DEP-5928771903-1780659779073-6771da33', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:42:59', NULL),
(231, 5928771903, 10.00, 'DEP-5928771903-1780659781175-32d1ef86', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:43:01', NULL),
(232, 5928771903, 10.00, 'DEP-5928771903-1780659786197-fd181b9a', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:43:06', NULL),
(233, 5928771903, 10.00, 'DEP-5928771903-1780659787520-9605c855', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:43:07', NULL),
(234, 5928771903, 10.00, 'DEP-5928771903-1780659788588-e3096f49', NULL, NULL, '', 'pending', NULL, '2026-06-05 11:43:08', NULL),
(235, 5928771903, 10.00, 'DEP-5928771903-1780659859360-d031e995', NULL, 'https://checkout.chapa.co/checkout/payment/UD9Heu734JgOpchsiU5qsLj2lW3w05pUrJaym9tD8pFFN', '', 'pending', NULL, '2026-06-05 11:44:19', NULL),
(236, 5928771903, 10.00, 'DEP-5928771903-1780660045857-eca15ec9', NULL, 'https://checkout.chapa.co/checkout/payment/Y7vxf6bdapCT2FqzLkbTxmkhJ3l3wIYF80QNOUf8fjSmn', '', 'pending', NULL, '2026-06-05 11:47:25', NULL),
(237, 5928771903, 10.00, 'DEP-5928771903-1780660873656-6e6875af', NULL, 'https://checkout.chapa.co/checkout/payment/OGNfDLKeMnmoLza3wI6k2LBlNOWhJIk5A6Y5rmddGADD4', '', 'pending', NULL, '2026-06-05 12:01:13', NULL),
(238, 5928771903, 10.00, 'DEP-5928771903-1780661132041-43a675f2', NULL, 'https://checkout.chapa.co/checkout/payment/MesUapeUKEdHHgglKba44m6fhs2yOnlf2rSwKgLM3k0gD', '', 'pending', NULL, '2026-06-05 12:05:32', NULL),
(239, 5928771903, 10.00, 'DEP-5928771903-1780661469314-3f1fb9df', NULL, 'https://checkout.chapa.co/checkout/payment/Po8kapSHvALNoAM36gPRcmYVhGlX2ZOZF0Ua1KggLwaBs', '', 'pending', NULL, '2026-06-05 12:11:09', NULL),
(240, 5928771903, 20.00, 'DEP-5928771903-1780662098899-8a205d09', NULL, 'https://checkout.chapa.co/checkout/payment/ZqkHpZdBUSgIJdh2klGh05tEJp1rzucRZCrJbnUixzFrd', '', 'pending', NULL, '2026-06-05 12:21:38', NULL),
(241, 5928771903, 20.00, 'DEP-5928771903-1780662185641-bc17f9a1', NULL, 'https://checkout.chapa.co/checkout/payment/GLkyGoh8oX8UmDC61fhMuY3bhOwTnMMjBaI5GrwE85DSQ', '', 'pending', NULL, '2026-06-05 12:23:05', NULL),
(242, 5928771903, 14.00, 'DEP-5928771903-1780662364318-600176bf', NULL, 'https://checkout.chapa.co/checkout/payment/b8nI32NLQazvyEq2HInY5i66Y2U5RHZYrtTZUARCYjD5Q', '', 'pending', NULL, '2026-06-05 12:26:04', NULL),
(243, 5928771903, 10.00, 'DEP-5928771903-1780662799589-cab6d3ee', NULL, 'https://checkout.chapa.co/checkout/payment/Bkab8sczT9SIYYfe2Umsw21QvXGkxU8PTzlohi36CJOBK', '', 'pending', NULL, '2026-06-05 12:33:19', NULL),
(244, 5928771903, 10.00, 'DEP-5928771903-1780662934866-3d8f17a8', 'APPH5wtdsQCG', 'https://checkout.chapa.co/checkout/payment/13082HEDMeMlRxpovTFskkDKMXaGDO9YvGIXJrggU7GqD', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APPH5wtdsQCG\",\"tx_ref\":\"DEP-5928771903-1780662934866-3d8f17a8\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T12:35:44.000000Z\",\"updated_at\":\"2026-06-05T12:36:00.000000Z\"}}', '2026-06-05 12:35:34', '2026-06-05 12:36:09'),
(245, 5928771903, 10.00, 'DEP-5928771903-1780663015163-897770db', NULL, 'https://checkout.chapa.co/checkout/payment/ZbyaAZ1imYJ5USh9Q45RXn0wlDx5Ax9G7dOGd0yCWSJ5p', '', 'pending', NULL, '2026-06-05 12:36:55', NULL),
(246, 5928771903, 20.00, 'DEP-5928771903-1780663062258-d41d7ea0', NULL, 'https://checkout.chapa.co/checkout/payment/jpMdequggGhmAxmHJNWR2Qe2g75QqTsDMbLbxYd0osQWM', '', 'pending', NULL, '2026-06-05 12:37:42', NULL),
(247, 5928771903, 10.00, 'DEP-5928771903-1780663257924-55b1e5f6', 'APa1Q3ViNra5', 'https://checkout.chapa.co/checkout/payment/sIYmzrR7tvQ6ERqrQ2WF6jvHpkQP1gpmtk3KUWigZKJmG', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APa1Q3ViNra5\",\"tx_ref\":\"DEP-5928771903-1780663257924-55b1e5f6\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T12:41:22.000000Z\",\"updated_at\":\"2026-06-05T12:41:31.000000Z\"}}', '2026-06-05 12:40:57', '2026-06-05 12:41:36'),
(248, 5928771903, 20.00, 'DEP-5928771903-1780663318058-f597ed51', NULL, 'https://checkout.chapa.co/checkout/payment/oNhuUVZXnaZL6JMTMA9LM88hy81ukQ6orhoADi7z73ih8', '', 'pending', NULL, '2026-06-05 12:41:58', NULL),
(249, 5928771903, 10.00, 'DEP-5928771903-1780663925290-b45225c7', 'APYmxHcGL7dt', 'https://checkout.chapa.co/checkout/payment/V67hPUrtDaOOYYU0BdUbC9GtziHjjDYCaOOCVwUzAPv29', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APYmxHcGL7dt\",\"tx_ref\":\"DEP-5928771903-1780663925290-b45225c7\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T12:52:15.000000Z\",\"updated_at\":\"2026-06-05T12:52:22.000000Z\"}}', '2026-06-05 12:52:05', '2026-06-05 12:52:32'),
(250, 5928771903, 10.00, 'DEP-5928771903-1780664045627-21115f7d', 'APK2lgt2Lbpx', 'https://checkout.chapa.co/checkout/payment/N96wpsSr8roseLb9MirMvH77NCCbC8JlcwY6BPOEwIWWk', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APK2lgt2Lbpx\",\"tx_ref\":\"DEP-5928771903-1780664045627-21115f7d\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T12:54:18.000000Z\",\"updated_at\":\"2026-06-05T12:54:28.000000Z\"}}', '2026-06-05 12:54:05', '2026-06-05 12:54:35'),
(251, 5928771903, 10.00, 'DEP-5928771903-1780664972654-0163ff21', NULL, 'https://checkout.chapa.co/checkout/payment/oXPuP0xTfMhYaXV46g52EIcUyRXCtRGjbza0mu6e74uAi', '', 'pending', NULL, '2026-06-05 13:09:32', NULL),
(252, 5928771903, 10.00, 'DEP-5928771903-1780664994242-f0c18c0f', NULL, 'https://checkout.chapa.co/checkout/payment/ZiTfngmleJT1yschTBJUwXutXzoPWyU9TGlmrWWuCXXBA', '', 'pending', NULL, '2026-06-05 13:09:54', NULL),
(253, 5928771903, 20.00, 'DEP-5928771903-1780665136274-580ea235', 'APBPO4iwKofS', 'https://checkout.chapa.co/checkout/payment/xvC4tskqAu8fnMCavXYFw7VeejQukfj2rIRCIQn0EC5qF', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":20,\"charge\":0.5,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APBPO4iwKofS\",\"tx_ref\":\"DEP-5928771903-1780665136274-580ea235\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T13:12:29.000000Z\",\"updated_at\":\"2026-06-05T13:12:49.000000Z\"}}', '2026-06-05 13:12:16', '2026-06-05 13:12:55'),
(254, 5928771903, 10.00, 'DEP-5928771903-1780666010077-307a43fd', NULL, 'https://checkout.chapa.co/checkout/payment/VHpeURRCqwuiyzKeRHdzWeUOxe6Xw1bMODMFF983v65vY', '', 'pending', NULL, '2026-06-05 13:26:50', NULL),
(255, 5928771903, 10.00, 'DEP-5928771903-1780666060041-a28c9de9', NULL, 'https://checkout.chapa.co/checkout/payment/Vl1XxzUIAXQYPe7IvTrfFDfC0HG4DKhj2qOzTtltwqp0l', '', 'pending', NULL, '2026-06-05 13:27:40', NULL),
(256, 5928771903, 10.00, 'DEP-5928771903-1780666523806-9fcc07cf', 'APwfqBskokli', 'https://checkout.chapa.co/checkout/payment/E1W8pRce9PP89wv2wTYt2p0KedFwZFFLLOELuYXQKSbbs', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APwfqBskokli\",\"tx_ref\":\"DEP-5928771903-1780666523806-9fcc07cf\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T13:36:10.000000Z\",\"updated_at\":\"2026-06-05T13:36:27.000000Z\"}}', '2026-06-05 13:35:23', '2026-06-05 13:36:29'),
(257, 5928771903, 10.00, 'DEP-5928771903-1780666661585-502ee838', NULL, 'https://checkout.chapa.co/checkout/payment/BoUlOpRbz8fR6x4P7yd7OINdx5OeBfHyKZaBd5eoPJZeo', '', 'pending', NULL, '2026-06-05 13:37:41', NULL),
(258, 5928771903, 10.00, 'DEP-5928771903-1780667489771-ab7d2296', NULL, 'https://checkout.chapa.co/checkout/payment/m7HfJnwdzgGIemPstgV5NLkIkkDhE1wMIUm8l46XCSqYf', '', 'pending', NULL, '2026-06-05 13:51:29', NULL),
(259, 5928771903, 10.00, 'DEP-5928771903-1780667647453-6a5bea50', 'APbVDakqJncQ', 'https://checkout.chapa.co/checkout/payment/AhkWRhFsvQ0dvjRoFOD7GhdWCl7Sq1uzheXgjsBY2oCl7', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APbVDakqJncQ\",\"tx_ref\":\"DEP-5928771903-1780667647453-6a5bea50\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T13:54:22.000000Z\",\"updated_at\":\"2026-06-05T13:54:30.000000Z\"}}', '2026-06-05 13:54:07', '2026-06-05 13:54:39'),
(260, 5928771903, 10.00, 'DEP-5928771903-1780668152334-0c868bd6', NULL, 'https://checkout.chapa.co/checkout/payment/Tyfai0n3ju1ozoNU40jGdsjDdKXaHHPM2V1krItMFTU7u', '', 'pending', NULL, '2026-06-05 14:02:32', NULL),
(261, 5928771903, 20.00, 'DEP-5928771903-1780668297142-13041b74', NULL, 'https://checkout.chapa.co/checkout/payment/mRRmb0g7iYP3Qn6o7CJKnH3fMOSRSNN3w9TgCD2LJwU4L', '', 'pending', NULL, '2026-06-05 14:04:57', NULL),
(262, 5928771903, 10.00, 'DEP-5928771903-1780668420193-baa9e4a8', NULL, 'https://checkout.chapa.co/checkout/payment/en76IUJvn48YLLqiqh7jZnN9tcoOwPNikNmFZOAHznG3E', '', 'pending', NULL, '2026-06-05 14:07:00', NULL),
(263, 5928771903, 20.00, 'DEP-5928771903-1780668664447-f41a9a5b', NULL, 'https://checkout.chapa.co/checkout/payment/eDyNR52Wv5m9IASeQni6mRvfPYTVD6P1epJMehIW3518L', '', 'pending', NULL, '2026-06-05 14:11:04', NULL),
(264, 779060335, 10.00, 'DEP-779060335-1780668751974-427d159f', NULL, 'https://checkout.chapa.co/checkout/payment/Ff5qJ9bwZGfj7oeiQI53kOENfgx9BJBmndq2hHf5vmyI3', '', 'pending', NULL, '2026-06-05 14:12:31', NULL),
(265, 5928771903, 10.00, 'DEP-5928771903-1780668782394-fa08d01b', 'APE03NI5vrNx', 'https://checkout.chapa.co/checkout/payment/bubwNQ9SnRrmRoKieVYk191IcMUxPj6GO5DokFAkD8hz5', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APE03NI5vrNx\",\"tx_ref\":\"DEP-5928771903-1780668782394-fa08d01b\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T14:13:13.000000Z\",\"updated_at\":\"2026-06-05T14:13:21.000000Z\"}}', '2026-06-05 14:13:02', '2026-06-05 14:13:29'),
(266, 5928771903, 20.00, 'DEP-5928771903-1780668919590-45d78e34', NULL, 'https://checkout.chapa.co/checkout/payment/O9ev5J2omQ7zRKIqOK2xO2M9wCAYviqhrWafED2Of4nqG', '', 'pending', NULL, '2026-06-05 14:15:19', NULL),
(267, 5928771903, 10.00, 'DEP-5928771903-1780669516463-fa5d4319', NULL, 'https://checkout.chapa.co/checkout/payment/S4q3PIlTQKPQUBa8DaFNapbiMBX6soAcGnc2fCQx6yRQ8', '', 'pending', NULL, '2026-06-05 14:25:16', NULL),
(268, 5928771903, 10.00, 'DEP-5928771903-1780669784461-0921e428', NULL, 'https://checkout.chapa.co/checkout/payment/YCA534xVewNn3atbc0msuOFoPfJWSjzJJwUCLdWUYAaME', '', 'pending', NULL, '2026-06-05 14:29:44', NULL),
(269, 5928771903, 10.00, 'DEP-5928771903-1780670291837-2a9dab80', NULL, 'https://checkout.chapa.co/checkout/payment/FUOXN8pkIjVbY9xP2S4P9wQLbBjGRnuH7uMiAFc755PXU', '', 'pending', NULL, '2026-06-05 14:38:11', NULL),
(270, 5928771903, 10.00, 'DEP-5928771903-1780670770074-f73cb1c5', NULL, 'https://checkout.chapa.co/checkout/payment/3J32vCp8tHBxlvztYqD0tb5oB7ktURVNGriAJbo9RLoSl', '', 'pending', NULL, '2026-06-05 14:46:10', NULL),
(271, 5928771903, 10.00, 'DEP-5928771903-1780670774315-06cc3977', NULL, 'https://checkout.chapa.co/checkout/payment/4gI738tuNxlMNhnszsO8E3DHYGaVUZC9aQWA6etT1G7rL', '', 'pending', NULL, '2026-06-05 14:46:14', NULL),
(272, 779060335, 10.00, 'DEP-779060335-1780671623564-2671c9ba', NULL, 'https://checkout.chapa.co/checkout/payment/otm6yTeSRNuWANqhvYOP6Ajn5PllYZAyd2M1D3hl1p9vD', '', 'pending', NULL, '2026-06-05 15:00:23', NULL),
(273, 779060335, 10.00, 'DEP-779060335-1780671740571-e2495a84', 'APY7KQthRJtN', 'https://checkout.chapa.co/checkout/payment/PgOXgplN5F9xUCjGoKur29ywNopspwKACVTMNiwCnlNVI', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Paxyo\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251965919473\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APY7KQthRJtN\",\"tx_ref\":\"DEP-779060335-1780671740571-e2495a84\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T15:02:35.000000Z\",\"updated_at\":\"2026-06-05T15:02:46.000000Z\"}}', '2026-06-05 15:02:20', '2026-06-05 15:02:51'),
(274, 779060335, 10.00, 'DEP-779060335-1780671842838-f175ccf9', NULL, 'https://checkout.chapa.co/checkout/payment/pD4h0jpVHX9t4qAtMZrp3l2mAJrU73XqDyV3Z851DNDG5', '', 'pending', NULL, '2026-06-05 15:04:02', NULL),
(275, 779060335, 10.00, 'DEP-779060335-1780671941474-7fb67710', 'APCxe0q7TMXD', 'https://checkout.chapa.co/checkout/payment/4fzxrOhyp3TA25bcpeYExcx3nwZFDxsGns2h7sF8ikMpE', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Paxyo\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251965919473\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APCxe0q7TMXD\",\"tx_ref\":\"DEP-779060335-1780671941474-7fb67710\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T15:05:49.000000Z\",\"updated_at\":\"2026-06-05T15:05:58.000000Z\"}}', '2026-06-05 15:05:41', '2026-06-05 15:06:00'),
(276, 779060335, 10.00, 'DEP-779060335-1780671985853-9d3ac8d9', 'APP2Nc0g0Wtv', 'https://checkout.chapa.co/checkout/payment/PryhV0yuhGA5msgmLiRFhI6DO5xSRIaSMHNRkNguT1Fqv', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Paxyo\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251965919473\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APP2Nc0g0Wtv\",\"tx_ref\":\"DEP-779060335-1780671985853-9d3ac8d9\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T15:06:34.000000Z\",\"updated_at\":\"2026-06-05T15:06:43.000000Z\"}}', '2026-06-05 15:06:25', '2026-06-05 15:06:45'),
(277, 779060335, 10.00, 'DEP-779060335-1780672012872-fbc38602', 'APjjrTduaGki', 'https://checkout.chapa.co/checkout/payment/RJynNwRkay2LpuRNmcij021qb2pYIY3TiBObcVnnr18K1', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Paxyo\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251965919473\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APjjrTduaGki\",\"tx_ref\":\"DEP-779060335-1780672012872-fbc38602\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T15:06:59.000000Z\",\"updated_at\":\"2026-06-05T15:07:09.000000Z\"}}', '2026-06-05 15:06:52', '2026-06-05 15:07:12'),
(278, 7573961936, 40.00, 'DEP-7573961936-1780676771642-62497959', NULL, 'https://checkout.chapa.co/checkout/payment/8TStRryt6YMnwlB8xoZmBOrMscf032OnkSoK36BnuHuv8', '', 'pending', NULL, '2026-06-05 16:26:11', NULL),
(279, 7573961936, 40.00, 'DEP-7573961936-1780677040583-02019fdd', NULL, 'https://checkout.chapa.co/checkout/payment/VLzjpVxlwmFumsztlXcCgW72yC4a2n3OAEoBcyd7gnOvJ', '', 'pending', NULL, '2026-06-05 16:30:40', NULL),
(280, 6195785370, 40.00, 'DEP-6195785370-1780677114106-34193fc6', 'APSF6y5dCfgV', 'https://checkout.chapa.co/checkout/payment/mAAlZv2Pt5WTefabILqFLaV20lIM6BrumXe22Xt0RNtjn', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"アケ\",\"last_name\":\"ake\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251936142121\",\"currency\":\"ETB\",\"amount\":40,\"charge\":1,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APSF6y5dCfgV\",\"tx_ref\":\"DEP-6195785370-1780677114106-34193fc6\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T16:32:23.000000Z\",\"updated_at\":\"2026-06-05T16:32:36.000000Z\"}}', '2026-06-05 16:31:54', '2026-06-05 16:32:37'),
(281, 779060335, 10.00, 'DEP-779060335-1780677850240-3cbd9150', NULL, 'https://checkout.chapa.co/checkout/payment/mY9kz58H89B6ST29bZGXExN7r2QKHujwiEFeMbZwSsHrj', '', 'pending', NULL, '2026-06-05 16:44:10', NULL),
(282, 779060335, 10.00, 'DEP-779060335-1780677927889-5a24ec9a', NULL, 'https://checkout.chapa.co/checkout/payment/MlYWIxJp5b2M2m7pn4nOsMdXiWPEaMCyb24sbXzSId0TL', '', 'pending', NULL, '2026-06-05 16:45:27', NULL),
(283, 7999410461, 10.00, 'DEP-7999410461-1780687911693-2bded10e', 'APDIVLFa8SVJ', 'https://checkout.chapa.co/checkout/payment/SVAvyo5U5VtFduovCP5hqEBq6cG3Jqxp5ivtnwnktS2tb', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"The Samurai\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251990552803\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APDIVLFa8SVJ\",\"tx_ref\":\"DEP-7999410461-1780687911693-2bded10e\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T19:32:07.000000Z\",\"updated_at\":\"2026-06-05T19:32:14.000000Z\"}}', '2026-06-05 19:31:51', '2026-06-05 19:32:26'),
(284, 7999410461, 50.00, 'DEP-7999410461-1780688014264-e8f0e0d1', 'APRQvVhWaQUO', 'https://checkout.chapa.co/checkout/payment/zzsXgN4ZbHMK8TISxFpmPnYhiQLXd1UaGa8HfQsIpvGqb', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"The Samurai\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251990552803\",\"currency\":\"ETB\",\"amount\":50,\"charge\":1.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APRQvVhWaQUO\",\"tx_ref\":\"DEP-7999410461-1780688014264-e8f0e0d1\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T19:33:49.000000Z\",\"updated_at\":\"2026-06-05T19:33:57.000000Z\"}}', '2026-06-05 19:33:34', '2026-06-05 19:34:00'),
(285, 5826257535, 10.00, 'DEP-5826257535-1780688727381-bb9283b3', NULL, 'https://checkout.chapa.co/checkout/payment/Kod42sJtlpjHj3H3bxMnKxdKnrodDGgo6m0w0KpHXOFCt', '', 'pending', NULL, '2026-06-05 19:45:27', NULL),
(286, 5826257535, 50.00, 'DEP-5826257535-1780688903743-e017fb81', 'APE2oUpdpjib', 'https://checkout.chapa.co/checkout/payment/Wq5YS6KWekJsoUMu27JyLhtoPBVv55WFqivDhnWWQSa3q', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"FRAME VIDEO PRODUCTION\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251964875380\",\"currency\":\"ETB\",\"amount\":50,\"charge\":1.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APE2oUpdpjib\",\"tx_ref\":\"DEP-5826257535-1780688903743-e017fb81\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T19:48:32.000000Z\",\"updated_at\":\"2026-06-05T19:48:40.000000Z\"}}', '2026-06-05 19:48:23', '2026-06-05 19:48:43'),
(287, 7999410461, 50.00, 'DEP-7999410461-1780693998095-0863aa8f', 'APQzT8J8I8hz', 'https://checkout.chapa.co/checkout/payment/r4eYS6lHxzzCcPIkvvJeLSRgp5ERIDyLljhOq0xYYhoq8', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"The Samurai\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251990552803\",\"currency\":\"ETB\",\"amount\":50,\"charge\":1.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APQzT8J8I8hz\",\"tx_ref\":\"DEP-7999410461-1780693998095-0863aa8f\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-05T21:13:30.000000Z\",\"updated_at\":\"2026-06-05T21:13:41.000000Z\"}}', '2026-06-05 21:13:18', '2026-06-05 21:13:43'),
(288, 5928771903, 10.00, 'DEP-5928771903-1780713517724-a4b9c2a7', NULL, 'https://checkout.chapa.co/checkout/payment/vk7lKIgBWtrzN6tfJoEhQz4F7RgHYyyjYyIWfmvC2jXtu', '', 'pending', NULL, '2026-06-06 02:38:37', NULL),
(289, 5928771903, 10.00, 'DEP-5928771903-1780741625641-66d1db7b', 'APiNaKonJ9e4', 'https://checkout.chapa.co/checkout/payment/j0aZwhBhirof5xPVv3FcmMLa3nZ4ize63c7akYvaID0Zi', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Abel\",\"last_name\":\"🕊️\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251717679691\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"mpesa\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APiNaKonJ9e4\",\"tx_ref\":\"DEP-5928771903-1780741625641-66d1db7b\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-06T10:27:52.000000Z\",\"updated_at\":\"2026-06-06T10:28:04.000000Z\"}}', '2026-06-06 10:27:05', '2026-06-06 10:28:09'),
(290, 5928771903, 10.00, 'DEP-5928771903-1780742119191-32d38020', NULL, 'https://checkout.chapa.co/checkout/payment/WFgVEQK1LEwxYPZgbRv8dIaXEiyeIXNCCdneofgrm1vZN', '', 'pending', NULL, '2026-06-06 10:35:19', NULL),
(291, 5928771903, 10.00, 'DEP-5928771903-1780742596329-06f64150', NULL, 'https://checkout.chapa.co/checkout/payment/meRFevfvJJnkTfsQTmNvCKp3Al8VRf74YsOjqMoEr3Hmv', '', 'pending', NULL, '2026-06-06 10:43:16', NULL),
(292, 5928771903, 10.00, 'DEP-5928771903-1780742875703-1b21e344', NULL, 'https://checkout.chapa.co/checkout/payment/JEOOEuBFyWhfFI9WhlA9U0tI28ezzQWEt7Q8bdbyEKdpZ', '', 'pending', NULL, '2026-06-06 10:47:55', NULL),
(293, 5928771903, 10.00, 'DEP-5928771903-1780743171076-6e8d0d5a', NULL, 'https://checkout.chapa.co/checkout/payment/E0f0LQPSdfkoBCzQ3pyEF7kX4qSUMiScjzKKF0HwZA6yh', '', 'pending', NULL, '2026-06-06 10:52:51', NULL),
(294, 5928771903, 10.00, 'DEP-5928771903-1780743369036-46f70667', NULL, 'https://checkout.chapa.co/checkout/payment/aCYksIKqgnjsFrLW9g9jP5xw5A6J5p0vye61mmaMhOxDi', '', 'pending', NULL, '2026-06-06 10:56:09', NULL),
(295, 5928771903, 10.00, 'DEP-5928771903-1780743793150-b77920ac', NULL, 'https://checkout.chapa.co/checkout/payment/kedKqE0ghX9iPOtic2xZIznoyEf9flqBhgN3Ae0hxbmle', '', 'pending', NULL, '2026-06-06 11:03:13', NULL),
(296, 5928771903, 10.00, 'DEP-5928771903-1780743849136-f5a7cc9d', NULL, 'https://checkout.chapa.co/checkout/payment/IKtPYcxIpN0c7aLN2MtmbGxBUifBhTZzN3WehOXVXJz6P', '', 'pending', NULL, '2026-06-06 11:04:09', NULL),
(297, 5928771903, 10.00, 'DEP-5928771903-1780743879300-98b41b45', NULL, 'https://checkout.chapa.co/checkout/payment/1TZIaXORTGJs7ftUvVUY95sJuHmMyh3u18CwDLnb7E4QO', '', 'pending', NULL, '2026-06-06 11:04:39', NULL),
(298, 5928771903, 10.00, 'DEP-5928771903-1780744600279-e145952f', NULL, 'https://checkout.chapa.co/checkout/payment/ORWoeCjikVpurvd3RcNnmFGLkUn1M9e2GOYfQ5AwWNQ2w', '', 'pending', NULL, '2026-06-06 11:16:40', NULL),
(299, 5928771903, 10.00, 'DEP-5928771903-1780747616544-2f8cccd7', NULL, 'https://checkout.chapa.co/checkout/payment/8oevZPlH5lOEzPZjMKhe2WUlbZo5ZeipH3Y3L7hdA3ro1', '', 'pending', NULL, '2026-06-06 12:06:56', NULL),
(300, 5928771903, 10.00, 'DEP-5928771903-1780747640689-4bad5097', NULL, 'https://checkout.chapa.co/checkout/payment/k2x33TH3F2htZfOioqVeLTP1DVhiV8eSl9J9t1Ot1BFWb', '', 'pending', NULL, '2026-06-06 12:07:20', NULL),
(301, 5928771903, 10.00, 'DEP-5928771903-1780748397373-f51d1827', NULL, 'https://checkout.chapa.co/checkout/payment/ebKqZ70MJD0qesDvlqAvNsafql1B4zcT21RzuD5BxtRmZ', '', 'pending', NULL, '2026-06-06 12:19:57', NULL),
(302, 5928771903, 10.00, 'DEP-5928771903-1780748701974-62273d47', NULL, 'https://checkout.chapa.co/checkout/payment/qrj2bq4LxYaPAUC4EpJo4xtoVY8DoguARkQVnS8b8lA0m', '', 'pending', NULL, '2026-06-06 12:25:01', NULL),
(303, 5928771903, 10.00, 'DEP-5928771903-1780748718059-3a302a7b', NULL, 'https://checkout.chapa.co/checkout/payment/zMg9FYT25wdDdsX0JmXHhSb8EE10ISb5G5uFkrWWC26df', '', 'pending', NULL, '2026-06-06 12:25:18', NULL),
(304, 5928771903, 20.00, 'DEP-5928771903-1780752096485-59b32169', NULL, 'https://checkout.chapa.co/checkout/payment/oXhLKUsfUoPWGEJpR3iaX6knlN1lgKK3pM2v8fGDG5HT3', '', 'pending', NULL, '2026-06-06 13:21:36', NULL),
(305, 779060335, 10.00, 'DEP-779060335-1780753095971-b1efcd00', NULL, 'https://checkout.chapa.co/checkout/payment/67SwQtP0CzirsjwdvACD6wYZ4mh7BpGGhitiZbgKRWFDi', '', 'pending', NULL, '2026-06-06 13:38:15', NULL),
(306, 779060335, 100.00, 'DEP-779060335-1780756628433-832fa0e4', NULL, 'https://checkout.chapa.co/checkout/payment/SzzMtKg6P2Ch4k8vsgEgP20xGTUFtzJan4TdETIXVqyOV', '', 'pending', NULL, '2026-06-06 14:37:08', NULL),
(307, 779060335, 100.00, 'DEP-779060335-1780757700884-c7ca4b73', NULL, 'https://checkout.chapa.co/checkout/payment/hEBGCkGlhfnlfWFLXbLlIGPpGudGexNtT9TCeWQDajIIw', '', 'pending', NULL, '2026-06-06 14:55:00', NULL),
(308, 6528707984, 10.00, 'DEP-6528707984-1780768622755-8c2ac21d', NULL, 'https://checkout.chapa.co/checkout/payment/aQRXBq8TMbRfPeZ0h1SCKpOJygcgaevJsmv0oV2B0H1sD', '', 'pending', NULL, '2026-06-06 17:57:02', NULL),
(309, 779060335, 10.00, 'DEP-779060335-1780774916821-fa312f92', NULL, 'https://checkout.chapa.co/checkout/payment/UpGW3kXIqjhMKpFUc0VynbDV6nBXCWPIDDMOZrWVV0Ou2', '', 'pending', NULL, '2026-06-06 19:41:56', NULL),
(310, 5928771903, 10.00, 'DEP-5928771903-1780775877454-8a231435', NULL, 'https://checkout.chapa.co/checkout/payment/25KCj5KK8HOw5F3LGkSf2O6I0qvscAjW3VN1paHIodx5a', '', 'pending', NULL, '2026-06-06 19:57:57', NULL),
(311, 779060335, 10.00, 'DEP-779060335-1780775895317-fe0d9ee0', NULL, 'https://checkout.chapa.co/checkout/payment/8vmNflLsLc2NoItr5velTuuHPjWodw5YMqOsBFavJzeDO', '', 'pending', NULL, '2026-06-06 19:58:15', NULL),
(312, 779060335, 10.00, 'DEP-779060335-1780775944560-07d7b7a8', NULL, 'https://checkout.chapa.co/checkout/payment/30bB8fMGBXBPtU18QGEMLhyHokARS2TXp33BYRkZZCbNa', '', 'pending', NULL, '2026-06-06 19:59:04', NULL),
(313, 7360255928, 70.00, 'DEP-7360255928-1780789553099-3ce29b1d', NULL, 'https://checkout.chapa.co/checkout/payment/4u7WJJYiVwI7cajFrDZvJYKkRhL6h9Gz1oee2BNdiF9ro', '', 'pending', NULL, '2026-06-06 23:45:53', NULL),
(314, 779060335, 10.00, 'DEP-779060335-1780820386341-644eaa88', 'APCg9OHdiTxq', 'https://checkout.chapa.co/checkout/payment/bBbDGMCQoeNDiZcBfh7ZD6QiaIkYbbNt5xSnt1MpxHwdT', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Paxyo\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251965919473\",\"currency\":\"ETB\",\"amount\":10,\"charge\":0.25,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APCg9OHdiTxq\",\"tx_ref\":\"DEP-779060335-1780820386341-644eaa88\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-07T08:20:13.000000Z\",\"updated_at\":\"2026-06-07T08:20:24.000000Z\"}}', '2026-06-07 08:19:46', '2026-06-07 08:20:28'),
(315, 0, 10.00, 'DEP-unauth_local_user-1780822990888-bb55d5ed', NULL, 'https://t.me/$kVLmwjwJKVHOBQAAVkcs8YnJMFw', '', 'pending', NULL, '2026-06-07 09:03:10', NULL),
(316, 0, 10.00, 'DEP-unauth_local_user-1780824120709-8ff6defd', NULL, 'https://t.me/$GvEY_TwJKVHRBQAAlV_yD8GSbog', '', 'pending', NULL, '2026-06-07 09:22:00', NULL),
(317, 5928771903, 10.00, 'DEP-5928771903-1780824901234-63ec7028', NULL, 'https://t.me/$Qe-eTDwJKVHUBQAAcWDZrhRMJaE', '', 'pending', NULL, '2026-06-07 09:35:01', NULL),
(318, 5928771903, 10.00, 'DEP-5928771903-1780828383146-d9bdab6b', NULL, 'https://t.me/$TFksajwJKVHVBQAA_qsUZe5CC3c', '', 'pending', NULL, '2026-06-07 10:33:03', NULL),
(319, 0, 10.00, 'DEP-unauth_local_user-1780828597944-2d825375', NULL, 'https://t.me/$7tBCoDwJKVHWBQAAEZpg17a2uBY', '', 'pending', NULL, '2026-06-07 10:36:37', NULL),
(320, 0, 10.00, 'DEP-unauth_local_user-1780828931029-79f45cfd', NULL, 'https://t.me/$cFNDETwJKVHXBQAAtk9Evg2zbt8', '', 'pending', NULL, '2026-06-07 10:42:11', NULL),
(321, 0, 20.00, 'DEP-unauth_local_user-1780829117231-9ab1abdc', NULL, 'https://t.me/$wca9YjwJKVHYBQAAYdRxrvWGhvA', '', 'pending', NULL, '2026-06-07 10:45:17', NULL),
(322, 0, 10.00, 'DEP-unauth_local_user-1780829388987-4a8edd50', NULL, 'https://t.me/$s3gU9DwJKVHZBQAAVHTolJOm2PU', '', 'pending', NULL, '2026-06-07 10:49:48', NULL),
(323, 0, 10.00, 'DEP-unauth_local_user-1780829805966-11aa75f4', NULL, 'https://t.me/$yAUOhzwJKVHaBQAAF2lXiShIymI', '', 'pending', NULL, '2026-06-07 10:56:45', NULL),
(324, 0, 10.00, 'DEP-unauth_local_user-1780830082313-f8965774', NULL, 'https://t.me/$M0KG1jwJKVHbBQAAjFkmEzgaM3I', '', 'pending', NULL, '2026-06-07 11:01:22', NULL),
(325, 0, 10.00, 'DEP-unauth_local_user-1780831771324-6d63d876', NULL, 'https://t.me/$fALD_DwJKVHcBQAApEDyJ_YWlIM', '', 'pending', NULL, '2026-06-07 11:29:31', NULL),
(326, 0, 10.00, 'DEP-unauth_local_user-1780831848535-569b5045', NULL, 'https://t.me/$ofaSxDwJKVHdBQAAWMBtyxl0H6s', '', 'pending', NULL, '2026-06-07 11:30:48', NULL),
(327, 0, 10.00, 'DEP-unauth_local_user-1780832152725-3bec13fe', NULL, 'https://t.me/$2Y8u1zwJKVHeBQAAN1E4swc5jho', '', 'pending', NULL, '2026-06-07 11:35:52', NULL),
(328, 0, 10.00, 'DEP-unauth_local_user-1780832696419-a55a1a2a', NULL, 'https://checkout.chapa.co/checkout/payment/IpN0MXi4t5qLDkZOFIM5Ok4hCVpQKaCLyQgKOQQ6zJEAu', '', 'pending', NULL, '2026-06-07 11:44:56', NULL),
(329, 0, 10.00, 'DEP-unauth_local_user-1780832966377-7232d43f', NULL, 'https://checkout.chapa.co/checkout/payment/HUFXSxlcq1n58hsoGJB3lm7UaFlBjIKEY2DqPxOcWaszJ', '', 'pending', NULL, '2026-06-07 11:49:26', NULL),
(330, 0, 10.00, 'DEP-unauth_local_user-1780833266305-faa4f61c', NULL, 'https://checkout.chapa.co/checkout/payment/rtp4aTuai3hDV8Xdxpe9ZXoyQEed7ca9wyJwXwFMee39D', '', 'pending', NULL, '2026-06-07 11:54:26', NULL),
(331, 0, 10.00, 'DEP-unauth_local_user-1780833337272-8c42b934', NULL, 'https://checkout.chapa.co/checkout/payment/wOGoNuC8XcU3fx7mwuTtTAo9ffGmR3ZhIwNxdXNwkQmam', '', 'pending', NULL, '2026-06-07 11:55:37', NULL),
(332, 0, 10.00, 'DEP-unauth_local_user-1780834032250-c6997cec', NULL, 'https://checkout.chapa.co/checkout/payment/cO6euumMlJkSm6evFvMKWLgv0R1cHlNSj1bhpyYSUrVMd', '', 'pending', NULL, '2026-06-07 12:07:12', NULL),
(333, 0, 10.00, 'DEP-unauth_local_user-1780834188992-5eb7e15c', NULL, 'https://checkout.chapa.co/checkout/payment/BFMQzHjOpH3monLxZyLPpNyY8dSJ1L7LPYPqPE47fdvJK', '', 'pending', NULL, '2026-06-07 12:09:48', NULL),
(334, 0, 20.00, 'DEP-unauth_local_user-1780834722430-eada53fe', NULL, 'https://checkout.chapa.co/checkout/payment/5uNBV2HOBVAGEm08ZdDv5O4yTxBr1Ydp7X9tnJTW81Ftu', '', 'pending', NULL, '2026-06-07 12:18:42', NULL),
(335, 0, 10.00, 'DEP-unauth_local_user-1780835487984-b038a6af', NULL, 'https://checkout.chapa.co/checkout/payment/u47VXTLfBiE4Pwfd9Pzqahs0vT1F2PWQu1z8m1jo4OCQO', '', 'pending', NULL, '2026-06-07 12:31:27', NULL),
(336, 5928771903, 10.00, 'DEP-5928771903-1780835487997-853e0a0e', NULL, 'https://checkout.chapa.co/checkout/payment/7xSVu6sEqNjbx5EmpQ4n6SpRo6fbX9iz7LxjNReM6FWaw', '', 'pending', NULL, '2026-06-07 12:31:27', NULL),
(337, 0, 10.00, 'DEP-unauth_local_user-1780837366747-2d5936cd', NULL, 'https://checkout.chapa.co/checkout/payment/Pkn0auBqhYozTQNPHeW1YZuex3oMKGyZqjPvU7YBUAT7d', '', 'pending', NULL, '2026-06-07 13:02:46', NULL),
(338, 0, 10.00, 'DEP-unauth_local_user-1780838028848-8b0b1cd1', NULL, 'https://checkout.chapa.co/checkout/payment/sn6nPlsQER9AzMe8a8ux6ns89pWxLG091K1RDZcVdpgKd', '', 'pending', NULL, '2026-06-07 13:13:48', NULL),
(339, 5928771903, 10.00, 'DEP-5928771903-1780838462484-34ba7d4b', NULL, 'https://checkout.chapa.co/checkout/payment/0SkstiR67WQbkpCeN6k8alIjFfm1AEOZRzbECpX0OuLO1', '', 'pending', NULL, '2026-06-07 13:21:02', NULL),
(340, 0, 10.00, 'DEP-unauth_local_user-1780844465107-4d98d5b1', NULL, 'https://checkout.chapa.co/checkout/payment/Io2qVhOuNJ0kkYJ59A7dWcvH6K8mGPUL9zost9zx61Uvz', '', 'pending', NULL, '2026-06-07 15:01:05', NULL),
(341, 0, 10.00, 'DEP-unauth_local_user-1780844498523-11c9d78a', NULL, 'https://checkout.chapa.co/checkout/payment/8EHTE9Y73SCL7OwCQH0o47tRyYzdatm7RqhruqYmAJiW7', '', 'pending', NULL, '2026-06-07 15:01:38', NULL),
(342, 0, 10.00, 'DEP-unauth_local_user-1780844532721-344ad27f', NULL, 'https://checkout.chapa.co/checkout/payment/Sbxtm9JcWeDlH5JqkhZ5ufGekoaDNSHKqMQGcD5oLDf4C', '', 'pending', NULL, '2026-06-07 15:02:12', NULL),
(343, 0, 200.00, 'DEP-unauth_local_user-1780857233966-20ec7ece', NULL, 'https://checkout.chapa.co/checkout/payment/saYquKisvb8CNrKYtrfpjM63cE53u84IbhUtXaAqc3jBr', '', 'pending', NULL, '2026-06-07 18:33:53', NULL),
(344, 0, 100.00, 'DEP-unauth_local_user-1780857462508-0e6d64c7', 'APwfDXX8bH5P', 'https://checkout.chapa.co/checkout/payment/1IYv7hH0E9XzLBLzXThoWpAwfqstV8T0VvrVGxFQYDlDz', '', 'success', '{\"message\":\"Payment details fetched successfully\",\"status\":\"success\",\"data\":{\"first_name\":\"Local\",\"last_name\":\"User\",\"email\":\"customer@paxyo.com\",\"phone_number\":\"251990552803\",\"currency\":\"ETB\",\"amount\":100,\"charge\":2.5,\"mode\":\"live\",\"method\":\"telebirr\",\"type\":\"API\",\"status\":\"success\",\"reference\":\"APwfDXX8bH5P\",\"tx_ref\":\"DEP-unauth_local_user-1780857462508-0e6d64c7\",\"customization\":{\"title\":\"Paxyo Deposit\",\"description\":\"Wallet deposit\",\"logo\":null},\"meta\":null,\"created_at\":\"2026-06-07T18:37:58.000000Z\",\"updated_at\":\"2026-06-07T18:38:07.000000Z\"}}', '2026-06-07 18:37:42', '2026-06-07 18:38:09'),
(345, 0, 10.00, 'DEP-unauth_local_user-1780892487388-f8ead3bc', NULL, 'https://checkout.chapa.co/checkout/payment/iSlPgXykUs37EzumwoGkUd4GsYt1U7Xuy6QcraVQhFh1b', '', 'pending', NULL, '2026-06-08 04:21:27', NULL),
(346, 0, 10.00, 'DEP-unauth_local_user-1780909902578-41989074', NULL, 'https://checkout.chapa.co/checkout/payment/BmqkTQV5yjZjYo57YTmkOXpEYa0zwO4tDIxJ0ZYZtP7Zk', '', 'pending', NULL, '2026-06-08 09:11:42', NULL),
(347, 0, 10.00, 'DEP-unauth_local_user-1780910129114-856577bb', NULL, 'https://checkout.chapa.co/checkout/payment/xf5qpMVbLoz9A0YbDZMSYSoSzn3ulCG2jr4Hfc6pukeCs', '', 'pending', NULL, '2026-06-08 09:15:29', NULL),
(348, 0, 10.00, 'DEP-unauth_local_user-1780910844933-8848', NULL, NULL, '', 'pending', NULL, '2026-06-08 09:27:26', NULL),
(349, 0, 10.00, 'DEP-unauth_local_user-1780910879190-3648', NULL, NULL, '', 'pending', NULL, '2026-06-08 09:28:00', NULL),
(350, 0, 10.00, 'DEP-unauth_local_user-1780910879790-2391', NULL, NULL, '', 'pending', NULL, '2026-06-08 09:28:00', NULL),
(351, 0, 10.00, 'DEP-unauth_local_user-1780910897254-2848', NULL, NULL, '', 'pending', NULL, '2026-06-08 09:28:26', NULL),
(352, 0, 10.00, 'DEP-unauth_local_user-1780910900932-9027', NULL, NULL, '', 'pending', NULL, '2026-06-08 09:28:26', NULL),
(353, 0, 10.00, 'DEP-unauth_local_user-1780910946914-5519', NULL, NULL, '', 'pending', NULL, '2026-06-08 09:29:08', NULL),
(354, 0, 10.00, 'DEP-unauth_local_user-1780911330602-e123e9e1', NULL, 'https://checkout.chapa.co/checkout/payment/80XgIOdfdiEVRx5ESBXUyiNcwecSGykCeyyH8hDpR0Ddo', '', 'pending', NULL, '2026-06-08 09:35:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `discount_percent` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'inactive',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `api_order_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `target_link` text DEFAULT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `link` text DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending',
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `remains` int(11) DEFAULT 0,
  `start_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `api_order_id`, `service_id`, `target_link`, `service_name`, `link`, `quantity`, `charge`, `status`, `custom_fields`, `remains`, `start_count`, `created_at`, `updated_at`) VALUES
(3, 111, 58995714, 3428, NULL, 'Kick.com Followers [ Max 5K ] | HQ | 30 Days ♻️ | Instant Start | Day 5K 🚀', 'https://kick.com/test', 100, 0.15, 'completed', NULL, 0, 18051, '2026-02-21 19:25:21', '2026-04-14 21:18:18'),
(4, 123456789, 60617341, 6702, NULL, 'X / Twitter Likes [ Worldwide 🌍 ] [ Max 5K ] | HQ Accounts | No Refill ⚠️ | Instant Start | Day 5K 🚀', 'dfgdfg', 100, 0.11, 'canceled', NULL, 0, 0, '2026-03-11 19:34:13', '2026-03-29 23:36:22'),
(6, 123456789, 62195501, 6764, 'https://sdfsdf.sdf', NULL, 'https://sdfsdf.sdf', 100, 0.00, 'completed', NULL, 0, 2, '2026-03-29 22:57:43', '2026-03-29 23:36:22'),
(7, 123456789, 62195935, 6764, 'https://asdasd.asd', NULL, 'https://asdasd.asd', 100, 0.00, 'completed', NULL, 0, 2, '2026-03-29 23:05:56', '2026-03-29 23:36:22'),
(8, 123456789, 62196970, 6764, 'https://sdfsd.fsdf', NULL, 'https://sdfsd.fsdf', 100, 0.00, 'completed', NULL, 0, 2, '2026-03-29 23:23:58', '2026-03-29 23:36:22'),
(9, 123456789, 62196990, 6764, 'https://t.me/sdfsdf.sdf', NULL, 'https://t.me/sdfsdf.sdf', 100, 0.00, 'completed', NULL, 0, 2, '2026-03-29 23:24:27', '2026-03-29 23:36:22'),
(10, 123456789, 62197613, 6764, 'https://ll.ll', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://ll.ll', 100, 0.00, 'in progress', NULL, 100, 0, '2026-03-29 23:32:59', '2026-03-29 23:36:22'),
(11, 123456789, 62201401, 6764, 'https://sdfsdf.sdfsdf', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://sdfsdf.sdfsdf', 100, 0.00, 'in progress', NULL, 100, 0, '2026-03-30 00:17:13', '2026-03-30 00:17:35'),
(12, 123456789, 62201825, 6764, 'https://ghj.fhfgh', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://ghj.fhfgh', 100, 0.00, 'in progress', NULL, 100, 0, '2026-03-30 00:24:09', '2026-03-30 00:24:34'),
(13, 123456789, 62202615, 6764, 'https://ghghj.fgh', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://ghghj.fgh', 100, 0.00, 'in progress', NULL, 100, 0, '2026-03-30 00:38:40', '2026-03-30 00:39:02'),
(14, 123456789, 62204280, 6764, 'https://jghj.ghj', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://jghj.ghj', 100, 0.00, 'in progress', NULL, 100, 0, '2026-03-30 01:04:49', '2026-03-30 01:05:07'),
(15, 123456789, 62204363, 6764, 'https://jghj.ghjgj', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://jghj.ghjgj', 100, 0.00, 'in progress', NULL, 100, 0, '2026-03-30 01:06:11', '2026-03-30 01:06:25'),
(16, 123456789, 62204844, 6764, 'https://fghfgh.fgh', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://fghfgh.fgh', 100, 0.00, 'completed', NULL, 0, 2, '2026-03-30 01:15:28', '2026-03-30 01:32:51'),
(17, 123456789, 62205348, 6764, 'https://vt.tiktok.com/ZSHRsSRqo/', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vt.tiktok.com/ZSHRsSRqo/', 300, 0.00, 'completed', NULL, 0, 204, '2026-03-30 01:24:46', '2026-03-30 01:26:36'),
(18, 123456789, 62211925, 257, 'https://www.youtube.com/live/6MsnQtxa798?si=mI2N2k4I2M_FPTwR', 'YouTube Live Stream Views + %100 Likes + % Comments [ Max 300K ] | %100 Real | 100% Concurrent | 15 Minutes', 'https://www.youtube.com/live/6MsnQtxa798?si=mI2N2k4I2M_FPTwR', 200, 0.30, 'canceled', NULL, 200, 0, '2026-03-30 03:00:55', '2026-04-14 21:18:19'),
(19, 123456789, 62794966, 7820, 'https://asdasd.asda.s', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://asdasd.asda.s', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-04 18:57:20', '2026-04-14 21:18:19'),
(20, 123456789, 62795877, 6764, 'https://sdfsdf.sdf', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://sdfsdf.sdf', 100, 0.00, 'completed', NULL, 0, 2, '2026-04-04 19:12:09', '2026-04-04 19:13:44'),
(21, 123456789, 62795918, 6764, 'https://sdfsdf.sdf', 'TikTok Video Views [ Min 100 ] [ Max Unlimited ] | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://sdfsdf.sdf', 100, 0.00, 'completed', NULL, 0, 2, '2026-04-04 19:12:41', '2026-04-04 19:13:47'),
(22, 123456789, 63056263, 7820, 'https://fhfgh.ffh', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://fhfgh.ffh', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 14:39:15', '2026-04-07 14:41:22'),
(23, 123456789, 63056341, 7820, 'https://fghfgh.ghj', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://fghfgh.ghj', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 14:40:14', '2026-04-07 14:42:37'),
(24, 123456789, 63056728, 7820, 'https://ffgh.fgh', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://ffgh.fgh', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 14:46:00', '2026-04-07 14:47:46'),
(25, 123456789, 63056786, 7820, 'https://ffgh.fgh', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://ffgh.fgh', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 14:46:55', '2026-04-14 21:18:19'),
(26, 123456789, 63056904, 1, 'https://instagram.com/test', 'Twitter Followers [ NFT Accounts ] [ Max 100K ] | HQ | Cancel Enable | Low Drop | 30 Days ♻️ | Day 20K', 'https://instagram.com/test', 100, 4.00, 'canceled', NULL, 100, 0, '2026-04-07 14:49:24', '2026-04-14 21:18:20'),
(27, 123456789, 63057085, 7439, 'https://ffgh.fgh', 'TikTok Likes + Views [ Max 10M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0% | No Refill ⚠️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://ffgh.fgh', 10, 0.00, 'canceled', NULL, 10, 0, '2026-04-07 14:52:05', '2026-04-14 21:18:20'),
(28, 123456789, 63057098, 1, 'https://instagram.com/test', 'Twitter Followers [ NFT Accounts ] [ Max 100K ] | HQ | Cancel Enable | Low Drop | 30 Days ♻️ | Day 20K', 'https://instagram.com/test', 100, 4.00, 'canceled', NULL, 100, 0, '2026-04-07 14:52:19', '2026-04-14 21:18:21'),
(29, 123456789, 63057498, 7439, 'https://ffgh.fgh', 'TikTok Likes + Views [ Max 10M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0% | No Refill ⚠️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://ffgh.fgh', 10, 0.00, 'canceled', NULL, 10, 0, '2026-04-07 14:58:52', '2026-04-14 21:18:21'),
(30, 123456789, 63058330, 7820, 'https://ffgh.fgh', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://ffgh.fgh', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 15:10:26', '2026-04-07 15:12:30'),
(34, 5928771903, 63065447, 7820, 'https://djfjf.xjcjf', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://djfjf.xjcjf', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 13:51:31', '2026-04-07 13:53:33'),
(35, 5928771903, 63067669, 7820, 'https://ffgh.fgh', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://ffgh.fgh', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 14:36:04', '2026-04-07 14:38:04'),
(36, 5928771903, 63068184, 7820, 'https://ffgh.fgh', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://ffgh.fgh', 100, 0.00, 'canceled', NULL, 100, 0, '2026-04-07 14:48:01', '2026-04-07 14:49:58'),
(37, 779060335, 63291760, 6534, 'https://t.me/c/2648724249/1271', 'Telegram Reactions + Free Views  [ Max 1M ] Positive - 👍🤩🎉🔥❤️🥰👏🏻🥳😍❤️‍🔥💯| Day 100K', 'https://t.me/c/2648724249/1271', 50, 0.00, 'canceled', NULL, 50, 0, '2026-04-10 06:54:52', '2026-04-10 06:57:00'),
(38, 5928771903, 63367694, 7820, '@yguyg', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', '@yguyg', 101, 0.01, 'canceled', NULL, 101, 0, '2026-04-11 10:42:00', '2026-04-11 10:43:47'),
(39, 779060335, 63368071, 1870, 'https://t.me/BTCprices/12110718', 'Telegram Post Views [ Max 50M ] | Last 1 Post', 'https://t.me/BTCprices/12110718', 50, 0.00, 'completed', NULL, 0, 0, '2026-04-11 10:51:05', '2026-04-11 10:54:41'),
(40, 779060335, 63440100, 6138, 'https://t.me/get_books_now/10936?single', 'Telegram Post Views [ Max Unlimited ] | Cancel Enable | Last 1 Post [ Slow Service ]', 'https://t.me/get_books_now/10936?single', 50, 0.00, 'completed', NULL, 0, 0, '2026-04-12 15:47:20', '2026-04-12 21:13:33'),
(41, 779060335, 63508521, 1871, 'T.me/Sjjsnd', 'Telegram Post Views [ Max 50M ] | Last 5 Post', 'T.me/Sjjsnd', 10, 0.00, 'canceled', NULL, 10, 0, '2026-04-13 15:02:40', '2026-04-13 15:05:06'),
(42, 5928771903, 63509062, 7821, 'https://gyu.gyg', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | 30 Days ♻️ | Instant Start | Day 10M 🚀', 'https://gyu.gyg', 200, 0.02, 'canceled', NULL, 200, 0, '2026-04-13 15:14:06', '2026-04-13 16:52:39'),
(43, 5928771903, 63514618, 7820, 'https://gyy.yg', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://gyy.yg', 100, 0.01, 'canceled', NULL, 100, 0, '2026-04-13 17:06:16', '2026-04-13 17:08:19'),
(44, 779060335, 63518168, 5853, 'https://vt.tiktok.com/ZSHgAQj7s/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Non Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀 [ Cheap ]', 'https://vt.tiktok.com/ZSHgAQj7s/', 1000, 0.09, 'completed', NULL, 0, 24, '2026-04-13 18:11:14', '2026-04-14 21:18:22'),
(45, 779060335, 63518207, 1870, 'https://t.me/get_books_now/11022?single', 'Telegram Post Views [ Max 50M ] | Last 1 Post', 'https://t.me/get_books_now/11022?single', 10, 0.00, 'completed', NULL, 0, 0, '2026-04-13 18:12:25', '2026-04-13 18:15:37'),
(46, 779060335, 63519862, 1870, 'https://t.me/get_books_now/11022?single', 'Telegram Post Views [ Max 50M ] | Last 1 Post', 'https://t.me/get_books_now/11022?single', 10, 0.03, 'completed', NULL, 0, 0, '2026-04-13 18:55:24', '2026-04-13 18:57:00'),
(47, 5928771903, 63780969, 7820, 'HTtps://tftkf.ft', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'HTtps://tftkf.ft', 100, 0.01, 'canceled', NULL, 100, 0, '2026-04-17 17:18:53', '2026-04-17 17:20:56'),
(48, 779060335, 63790526, 1870, 'https://t.me/paxyo251/191', 'Telegram Post Views [ Max 50M ] | Last 1 Post', 'https://t.me/paxyo251/191', 100, 0.00, 'completed', NULL, 0, 0, '2026-04-17 20:41:28', '2026-04-17 20:43:31'),
(49, 5928771903, 64990503, 2807, 'fkfkfj', 'TikTok Followers [ Max 20K ] | Bot Accounts | No Refill ⚠️ | Instant Start | Day 20K', 'fkfkfj', 10, 0.47, 'completed', NULL, 0, 6, '2026-05-06 07:24:40', '2026-05-06 07:29:53'),
(50, 2030466394, 65017199, 2694, 'https://www.facebook.com/100064875394900/posts/1405144581658042/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064875394900/posts/1405144581658042/?app=fbl', 1000, 1.12, 'completed', NULL, 0, 106, '2026-05-06 17:53:29', '2026-05-06 18:00:32'),
(51, 2030466394, 65017221, 2694, 'https://www.facebook.com/61572850353935/posts/122192059370761678/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/122192059370761678/?app=fbl', 700, 0.78, 'completed', NULL, 0, 11, '2026-05-06 17:54:17', '2026-05-06 18:00:57'),
(52, 2030466394, 65017255, 2694, 'https://www.facebook.com/100074422583584/posts/pfbid0MpkerGaQs2c8kQLnyNZVz5YDEz4WDX9EoE6C9yqtzdRP9gR1f3UJUKFGtyDQQjktl/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100074422583584/posts/pfbid0MpkerGaQs2c8kQLnyNZVz5YDEz4WDX9EoE6C9yqtzdRP9gR1f3UJUKFGtyDQQjktl/', 1000, 1.12, 'completed', NULL, 0, 10, '2026-05-06 17:55:18', '2026-05-06 18:04:02'),
(53, 2030466394, 65017304, 2694, 'https://www.facebook.com/100004706018347/posts/3477128065787360/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100004706018347/posts/3477128065787360/?app=fbl', 1000, 1.12, 'completed', NULL, 0, 84, '2026-05-06 17:56:56', '2026-05-06 18:03:37'),
(54, 2030466394, 65071356, 2694, 'https://www.facebook.com/share/1EomQmeB7Y/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/share/1EomQmeB7Y/', 1000, 1.12, 'completed', NULL, 0, 39, '2026-05-07 17:50:12', '2026-05-07 17:56:57'),
(55, 2030466394, 65071416, 2694, 'https://www.facebook.com/share/p/18kS8nxcWn/  አማን አስሳትፉበት 🙏', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/share/p/18kS8nxcWn/  አማን አስሳትፉበት 🙏', 1000, 1.12, 'completed', NULL, 0, 169, '2026-05-07 17:50:59', '2026-05-07 17:58:02'),
(56, 2030466394, 65071831, 2694, 'https://www.facebook.com/61572850353935/posts/122192130920761678/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/122192130920761678/?app=fbl', 200, 0.22, 'completed', NULL, 0, 53, '2026-05-07 18:00:16', '2026-05-07 18:06:28'),
(57, 2030466394, 65071850, 2694, 'https://www.facebook.com/61572850353935/posts/122192158970761678/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/122192158970761678/?app=fbl', 200, 0.22, 'completed', NULL, 0, 31, '2026-05-07 18:00:49', '2026-05-07 18:08:37'),
(58, 7115890811, 65103853, 6449, 'https://vt.tiktok.com/ZS9GcQTSt/', 'TikTok Video Views [ Max Unlimited ] | HQ | ⚙️ Lifetime Auto ♻️ | Instant Start | Day 100M 🚀🚀', 'https://vt.tiktok.com/ZS9GcQTSt/', 500, 1.36, 'in_progress', NULL, 500, 0, '2026-05-08 08:40:47', '2026-05-08 08:41:07'),
(59, 7115890811, 65103873, 6449, 'https://vt.tiktok.com/ZS9G3hR6S/', 'TikTok Video Views [ Max Unlimited ] | HQ | ⚙️ Lifetime Auto ♻️ | Instant Start | Day 100M 🚀🚀', 'https://vt.tiktok.com/ZS9G3hR6S/', 500, 1.36, 'in_progress', NULL, 500, 0, '2026-05-08 08:41:21', '2026-05-08 08:41:37'),
(60, 7115890811, 65103895, 6449, 'https://vt.tiktok.com/ZS9G3RMx8/', 'TikTok Video Views [ Max Unlimited ] | HQ | ⚙️ Lifetime Auto ♻️ | Instant Start | Day 100M 🚀🚀', 'https://vt.tiktok.com/ZS9G3RMx8/', 500, 1.36, 'in_progress', NULL, 500, 0, '2026-05-08 08:41:59', '2026-05-08 08:42:17'),
(61, 7115890811, 65103913, 6449, 'https://vt.tiktok.com/ZS9G3UY9N/', 'TikTok Video Views [ Max Unlimited ] | HQ | ⚙️ Lifetime Auto ♻️ | Instant Start | Day 100M 🚀🚀', 'https://vt.tiktok.com/ZS9G3UY9N/', 500, 1.36, 'in_progress', NULL, 500, 0, '2026-05-08 08:42:24', '2026-05-08 08:42:37'),
(62, 7115890811, 65103945, 6449, 'https://vt.tiktok.com/ZS9G3hyEv/', 'TikTok Video Views [ Max Unlimited ] | HQ | ⚙️ Lifetime Auto ♻️ | Instant Start | Day 100M 🚀🚀', 'https://vt.tiktok.com/ZS9G3hyEv/', 500, 1.36, 'in_progress', NULL, 500, 0, '2026-05-08 08:42:55', '2026-05-08 08:43:07'),
(63, 2030466394, 65118585, 2694, 'https://www.facebook.com/100044148803693/posts/pfbid0E4orZkM9fDwjy1hGJavXnH9C9VDDUwskHMvfEgXbYxNin7TtcWyEAcA8qbeAPzyHl/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100044148803693/posts/pfbid0E4orZkM9fDwjy1hGJavXnH9C9VDDUwskHMvfEgXbYxNin7TtcWyEAcA8qbeAPzyHl/', 2000, 8.78, 'completed', NULL, 0, 89, '2026-05-08 14:40:49', '2026-05-08 14:50:17'),
(64, 2030466394, 65118631, 2694, 'https://www.facebook.com/61572850353935/posts/122192234714761678/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/122192234714761678/?app=fbl', 500, 2.20, 'completed', NULL, 0, 152, '2026-05-08 14:41:49', '2026-05-08 14:48:12'),
(65, 2030466394, 65118661, 2694, 'https://www.facebook.com/100064875394900/posts/1406675294838304/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064875394900/posts/1406675294838304/?app=fbl', 90, 0.40, 'completed', NULL, 0, 32, '2026-05-08 14:42:44', '2026-05-08 14:49:52'),
(66, 2030466394, 65118690, 2694, 'https://www.facebook.com/100064148404402/posts/1395294895952111/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064148404402/posts/1395294895952111/?app=fbl', 90, 0.40, 'completed', NULL, 0, 63, '2026-05-08 14:43:25', '2026-05-08 14:47:11'),
(67, 1961928800, 65190840, 5834, 'https://vt.tiktok.com/@ethiopian_live/ZS97sX51V/', 'Tiktok Random Comments [ Max 50K ] | 100% Real Accounts | No Refill ⚠️ | Instant Start | Day 50K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS97sX51V/', 10, 2.61, 'completed', NULL, 0, 0, '2026-05-10 00:37:50', '2026-05-23 12:57:59'),
(68, 1961928800, 65191063, 7761, 'https://vt.tiktok.com/@ethiopian_live/ZS97nuA1a/', 'TikTok Reposts [ Max 500K ] | HQ Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS97nuA1a/', 10, 2.36, 'completed', NULL, 0, 0, '2026-05-10 00:49:16', '2026-05-13 13:03:36'),
(69, 1961928800, 65191862, 5295, 'https://vt.tiktok.com/ZS97nuA1a/', 'Tiktok Likes [ Max 200K ] | HQ Accounts | Cancel Enable | Drop 30-35% | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZS97nuA1a/', 100, 3.80, 'completed', NULL, 0, 91, '2026-05-10 01:22:53', '2026-05-10 02:03:09'),
(70, 1961928800, 65191879, 2794, 'https://www.instagram.com/mickey_s.i?igsh=MWh6dWltZmNkNTlhcw==', 'Instagram Followers [ Max 25K ] | Real &amp; Bot Accounts | Cancel Enable | Drop 50-90% | No Refill ⚠️ | Instant Start | Day 10K', 'https://www.instagram.com/mickey_s.i?igsh=MWh6dWltZmNkNTlhcw==', 100, 4.00, 'completed', NULL, 0, 3512, '2026-05-10 01:23:41', '2026-05-13 20:12:36'),
(71, 1961928800, 65193777, 7589, 'https://vt.tiktok.com/ZS97nuA1a/', 'TikTok Video Share [ Max 100K ] | HQ | Cancel Enable | Drop 0% | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZS97nuA1a/', 200, 1.26, 'completed', NULL, 0, 0, '2026-05-10 02:15:04', '2026-05-13 16:50:24'),
(72, 2030466394, 65205010, 2694, 'https://www.facebook.com/61572850353935/posts/122192425964761678/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/122192425964761678/?app=fbl', 50, 2.20, 'completed', NULL, 0, 37, '2026-05-10 08:51:08', '2026-05-10 08:55:09'),
(73, 2030466394, 65208318, 2694, 'https://www.facebook.com/61572850353935/posts/pfbid0zegmUzEyGzNEnsZ1v4LYEGXcycjeXVydGdphbKrwAWB43pH88Tpb21ZPqBgEqHQ5l/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/pfbid0zegmUzEyGzNEnsZ1v4LYEGXcycjeXVydGdphbKrwAWB43pH88Tpb21ZPqBgEqHQ5l/', 50, 2.20, 'completed', NULL, 0, 29, '2026-05-10 10:22:09', '2026-05-10 10:25:50'),
(74, 779060335, 65218793, 6140, 'https://t.me/paxyo251', 'Telegram Post Views [ Max Unlimited ] | Cancel Enable | Instant Start | Last 5 Post  🚀', 'https://t.me/paxyo251', 100, 0.50, 'completed', NULL, 0, 0, '2026-05-10 14:45:37', '2026-05-10 15:11:43'),
(75, 779060335, 65220964, 5773, 'https://vt.tiktok.com/ZS9cUqVc5/', 'TikTok Video Views [ Max Unlimited ] | HQ | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZS9cUqVc5/', 1000, 6.80, 'completed', NULL, 0, 0, '2026-05-10 15:37:52', '2026-05-10 15:47:39'),
(76, 779060335, 65221220, 6272, 'https://vt.tiktok.com/ZS9cUqVc5/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Non Drop | 365 Days ♻️ | Instant Start | Day 100K 🚀  [ Cheap ]', 'https://vt.tiktok.com/ZS9cUqVc5/', 200, 5.52, 'completed', NULL, 0, 1, '2026-05-10 15:43:34', '2026-05-10 15:56:04'),
(77, 779060335, 65221491, 4333, 'https://vt.tiktok.com/ZS9cUqVc5/', 'TikTok Video Share [ Max 100M ] | 30 Days ♻️ | Days 10M', 'https://vt.tiktok.com/ZS9cUqVc5/', 50, 1.80, 'completed', NULL, 0, 0, '2026-05-10 15:49:38', '2026-05-10 15:51:44'),
(78, 779060335, 65221568, 7312, 'https://vt.tiktok.com/ZS9cUqVc5/', 'TikTok Video Save [ Max Unlimited ] | HQ | Drop 0% | Lifetime ♻️ | Instant Start | Day 500K 🚀', 'https://vt.tiktok.com/ZS9cUqVc5/', 300, 1.20, 'completed', NULL, 0, 1, '2026-05-10 15:50:46', '2026-05-10 15:51:14'),
(79, 779060335, 65222149, 1358, 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 'Instagram Real Likes [ Max 1M ] | 100% Old Accounts | Cancel Enable | Drop 0-5% | 30 Days ♻️ | Instant Start | Day 200K 🚀', 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 120, 20.64, 'completed', NULL, 0, 0, '2026-05-10 16:04:08', '2026-05-10 16:08:59'),
(80, 779060335, 65222165, 4091, 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 'Instagram Save [ Max 30K ] | Real Profiles | All Link | Instant', 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 30, 0.72, 'completed', NULL, 0, 0, '2026-05-10 16:04:31', '2026-05-10 16:21:34'),
(81, 779060335, 65222171, 6279, 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 'Instagram Repost [ Max 10M ] | HQ Accounts | No Refill ⚠️ | Instant Start | Day 200K 🚀 [ Cheapest ]', 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 100, 11.20, 'completed', NULL, 0, 0, '2026-05-10 16:04:50', '2026-05-10 16:06:09'),
(82, 779060335, 65222190, 2775, 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 'Instagram Video Views [ Min 10 ] [ Max Unlimited ] | All Type Link | Instant Start | Day 5M 🚀', 'https://www.instagram.com/p/DYKgTb2DFTt/?igsh=bG05ZGM2cXN4M3Bx', 4000, 1.28, 'completed', NULL, 0, 0, '2026-05-10 16:05:13', '2026-05-10 16:05:44'),
(83, 1961928800, 65233671, 5672, 'https://vt.tiktok.com/ZS9cGvVXW/', 'TikTok Video Shares [ Max 1M ] | Cancel Enable | 30 Days ♻️ | Day 500K', 'https://vt.tiktok.com/ZS9cGvVXW/', 100, 2.40, 'processing', NULL, 100, 0, '2026-05-10 21:10:42', '2026-05-10 21:10:52'),
(84, 1961928800, 65235137, 6448, 'https://vt.tiktok.com/@ethiopian_live/ZS9cWnRnW/', 'Tiktok Emoji Comments [ Min 1 ] [ Max 50K ] | HQ Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9cWnRnW/', 10, 2.60, 'completed', NULL, 0, 0, '2026-05-10 21:55:02', '2026-05-23 12:57:59'),
(85, 1961928800, 65235652, 352, 'https://vt.tiktok.com/@ethiopian_live/ZS9cGvVXW/', 'Tiktok Emoji Comments [ Max 50K ] | 100% Real Accounts | No Refill ⚠️ | Instant Start | Day 50K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9cGvVXW/', 10, 2.61, 'completed', NULL, 0, 0, '2026-05-10 22:10:35', '2026-05-23 12:58:04'),
(86, 1961928800, 65238781, 5954, 'https://vt.tiktok.com/@ethiopian_live/ZS9c3RXon/', 'TikTok Likes [ Max 1M ] | Real &amp; Bot Accounts | 30 Days ♻️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9c3RXon/', 1000, 5.60, 'canceled', NULL, 1000, 0, '2026-05-10 23:26:43', '2026-05-11 11:08:22'),
(87, 1961928800, 65238928, 5295, 'https://vt.tiktok.com/@ethiopian_live/ZS9c3RXon/', 'Tiktok Likes [ Max 200K ] | HQ Accounts | Cancel Enable | Drop 30-35% | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9c3RXon/', 100, 3.80, 'completed', NULL, 0, 400, '2026-05-10 23:32:32', '2026-05-10 23:58:49'),
(88, 1961928800, 65239991, 5853, 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Non Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀 [ Cheap ]', 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 1000, 18.00, 'completed', NULL, 0, 762, '2026-05-11 00:14:19', '2026-05-11 01:42:01'),
(89, 1961928800, 65240350, 5295, 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 'Tiktok Likes [ Max 200K ] | HQ Accounts | Cancel Enable | Drop 30-35% | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 20, 0.76, 'completed', NULL, 0, 860, '2026-05-11 00:28:43', '2026-05-11 00:31:10'),
(90, 1961928800, 65240625, 7796, 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | 15 Days Auto Refill ♻️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 10000, 38.00, 'canceled', NULL, 10000, 0, '2026-05-11 00:39:25', '2026-05-11 11:06:02'),
(91, 1961928800, 65241021, 5295, 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 'Tiktok Likes [ Max 200K ] | HQ Accounts | Cancel Enable | Drop 30-35% | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 4200, 159.60, 'completed', NULL, 0, 1038, '2026-05-11 00:54:09', '2026-05-11 03:31:06'),
(92, 1961928800, 65247903, 5295, 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 'Tiktok Likes [ Max 200K ] | HQ Accounts | Cancel Enable | Drop 30-35% | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9cEm9LX/', 50, 1.90, 'partial', NULL, 1, 5956, '2026-05-11 04:14:31', '2026-05-11 05:05:57'),
(93, 5065467140, 65283096, 4052, 'https://vt.tiktok.com/ZS9weurrr/', 'TikTok Likes [ Max 5M ] | LQ Accounts | No Refill ⚠️ | Instant Start | Day 200K 🚀', 'https://vt.tiktok.com/ZS9weurrr/', 500, 10.00, 'completed', NULL, 0, 152, '2026-05-11 19:00:37', '2026-05-11 19:21:28'),
(94, 5065467140, 65283148, 7913, 'https://vt.tiktok.com/ZS9weurrr/', 'TikTok Video Views [ Max Unlimited ] | HQ | 30 Days ♻️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZS9weurrr/', 3700, 29.60, 'completed', NULL, 0, 429, '2026-05-11 19:02:09', '2026-05-11 19:02:37'),
(95, 5065467140, 65284353, 7913, 'https://vt.tiktok.com/ZS9weurrr/', 'TikTok Video Views [ Max Unlimited ] | HQ | 30 Days ♻️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZS9weurrr/', 2000, 16.00, 'completed', NULL, 0, 4947, '2026-05-11 19:26:24', '2026-05-11 19:27:18'),
(96, 5065467140, 65284361, 2428, 'https://vt.tiktok.com/ZS9weurrr/', 'TikTok Video Save [ Max Unlimited ] | HQ | Cancel Enable | Drop 0% | Lifetime ♻️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZS9weurrr/', 50, 0.12, 'in_progress', NULL, 50, 0, '2026-05-11 19:26:50', '2026-05-11 19:27:03'),
(97, 5065467140, 65285069, 2428, 'https://vt.tiktok.com/ZS9w875fj/', 'TikTok Video Save [ Max Unlimited ] | HQ | Cancel Enable | Drop 0% | Lifetime ♻️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZS9w875fj/', 50, 0.12, 'in_progress', NULL, 50, 0, '2026-05-11 19:42:26', '2026-05-11 19:42:43'),
(98, 1961928800, 65292753, 5853, 'https://vt.tiktok.com/@ethiopian_live/ZS9wjy6Yn/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Non Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀 [ Cheap ]', 'https://vt.tiktok.com/@ethiopian_live/ZS9wjy6Yn/', 1000, 18.00, 'completed', NULL, 0, 7350, '2026-05-11 23:41:14', '2026-05-11 23:44:55'),
(99, 1961928800, 65292941, 5853, 'https://vt.tiktok.com/@ethiopian_live/ZS9wjy6Yn/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Non Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀 [ Cheap ]', 'https://vt.tiktok.com/@ethiopian_live/ZS9wjy6Yn/', 16000, 288.00, 'completed', NULL, 0, 8454, '2026-05-11 23:47:22', '2026-05-12 02:54:47'),
(100, 1961928800, 65294532, 7589, 'https://vt.tiktok.com/@ethiopian_live/ZS9wjy6Yn/', 'TikTok Video Share [ Max 100K ] | HQ | Cancel Enable | Drop 0% | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/@ethiopian_live/ZS9wjy6Yn/', 300, 1.90, 'completed', NULL, 0, 0, '2026-05-12 00:44:01', '2026-05-13 06:14:12'),
(101, 2030466394, 65313173, 2694, 'https://www.facebook.com/100064148404402/posts/pfbid02JKBqtFBp2qthxAH3Pd5eGZT84Y3eQ1JFTERxf6jRCJj2CjmhGziQYbR5dVXpQPMcl/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064148404402/posts/pfbid02JKBqtFBp2qthxAH3Pd5eGZT84Y3eQ1JFTERxf6jRCJj2CjmhGziQYbR5dVXpQPMcl/', 50, 2.20, 'completed', NULL, 0, 39, '2026-05-12 09:34:17', '2026-05-12 09:39:22'),
(102, 2030466394, 65324410, 2694, 'https://www.facebook.com/61572850353935/posts/122192629706761678/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/122192629706761678/?app=fbl', 50, 2.20, 'completed', NULL, 0, 69, '2026-05-12 13:43:20', '2026-05-12 14:06:11'),
(103, 2030466394, 65326309, 2694, 'https://www.facebook.com/61572850353935/posts/pfbid0Mv7CfAtqS9mjLSKz8bA2Ngt7jQNPuNgd7wze42j3ujC78dDjnbXdwGq9tQ3JoBxxl/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/61572850353935/posts/pfbid0Mv7CfAtqS9mjLSKz8bA2Ngt7jQNPuNgd7wze42j3ujC78dDjnbXdwGq9tQ3JoBxxl/', 70, 3.07, 'completed', NULL, 0, 30, '2026-05-12 14:26:14', '2026-05-12 14:48:16'),
(104, 1961928800, 65327229, 5853, 'https://vt.tiktok.com/@ethiopian_live/ZS9KXfmVU/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Non Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀 [ Cheap ]', 'https://vt.tiktok.com/@ethiopian_live/ZS9KXfmVU/', 350, 6.30, 'completed', NULL, 0, 25537, '2026-05-12 14:48:08', '2026-05-12 18:54:24'),
(105, 2030466394, 65329223, 2694, 'https://www.facebook.com/100064875394900/posts/pfbid02t669FVsJCSjSNsAuLErx3ibyU9AnvaLH7LwjjxhdoFgXupfebM1SaB76g4YzyECJl/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064875394900/posts/pfbid02t669FVsJCSjSNsAuLErx3ibyU9AnvaLH7LwjjxhdoFgXupfebM1SaB76g4YzyECJl/', 70, 3.07, 'completed', NULL, 0, 45, '2026-05-12 15:31:10', '2026-05-12 15:56:17'),
(106, 2030466394, 65338778, 2694, 'https://www.facebook.com/100063478016760/posts/1581164927342759/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100063478016760/posts/1581164927342759/?app=fbl', 300, 13.18, 'completed', NULL, 0, 33, '2026-05-12 19:02:46', '2026-05-12 19:09:39'),
(107, 2030466394, 65339271, 2694, 'https://www.facebook.com/100063478016760/posts/1581164927342759/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100063478016760/posts/1581164927342759/?app=fbl', 400, 17.57, 'completed', NULL, 0, 360, '2026-05-12 19:16:21', '2026-05-12 19:54:10'),
(108, 2030466394, 65340050, 2694, 'https://www.facebook.com/100063478016760/posts/1581164927342759/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100063478016760/posts/1581164927342759/?app=fbl', 250, 10.98, 'completed', NULL, 0, 777, '2026-05-12 19:39:35', '2026-05-13 01:10:13'),
(109, 2030466394, 65360516, 2694, 'https://www.facebook.com/100064875394900/posts/1410831531089347/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064875394900/posts/1410831531089347/?app=fbl', 100, 4.39, 'completed', NULL, 0, 94, '2026-05-13 06:07:55', '2026-05-13 06:52:17'),
(110, 2030466394, 65360539, 2694, 'https://www.facebook.com/100064875394900/posts/1410827007756466/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064875394900/posts/1410827007756466/?app=fbl', 100, 4.39, 'completed', NULL, 0, 37, '2026-05-13 06:08:52', '2026-05-13 07:16:52'),
(111, 2030466394, 65360631, 2694, 'https://www.facebook.com/100064875394900/posts/1410821961090304/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100064875394900/posts/1410821961090304/?app=fbl', 100, 4.39, 'completed', NULL, 0, 71, '2026-05-13 06:11:06', '2026-05-13 07:17:37'),
(112, 2030466394, 65381197, 2694, 'https://www.facebook.com/100069415110377/posts/1301813145475844/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100069415110377/posts/1301813145475844/?app=fbl', 600, 26.35, 'completed', NULL, 0, 48, '2026-05-13 15:08:44', '2026-05-13 21:34:32'),
(113, 5928771903, 65390510, 7852, 'https://www.youtube.com/live/BnBuUQ_2xHc?si=XCnHNPsyWwf_1Mop', 'YouTube Live Stream Views [ Max 100K ] | Concurrent 80-120% | 15 Minutes', 'https://www.youtube.com/live/BnBuUQ_2xHc?si=XCnHNPsyWwf_1Mop', 100, 2.07, 'completed', NULL, 0, 0, '2026-05-13 18:39:59', '2026-05-13 19:01:51'),
(114, 779060335, 65392881, 7312, 'https://vt.tiktok.com/ZSx1Exq4t/', 'TikTok Video Save [ Max Unlimited ] | HQ | Drop 0% | Lifetime ♻️ | Instant Start | Day 500K 🚀', 'https://vt.tiktok.com/ZSx1Exq4t/', 200, 0.80, 'in_progress', NULL, 200, 0, '2026-05-13 19:56:07', '2026-05-13 19:56:21'),
(115, 779060335, 65392892, 4333, 'https://vt.tiktok.com/ZSx1Exq4t/', 'TikTok Video Share [ Max 100M ] | 30 Days ♻️ | Days 10M', 'https://vt.tiktok.com/ZSx1Exq4t/', 120, 4.32, 'completed', NULL, 0, 0, '2026-05-13 19:56:31', '2026-05-13 19:59:26'),
(116, 779060335, 65392907, 6272, 'https://vt.tiktok.com/ZSx1Exq4t/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Non Drop | 365 Days ♻️ | Instant Start | Day 100K 🚀  [ Cheap ]', 'https://vt.tiktok.com/ZSx1Exq4t/', 300, 8.28, 'completed', NULL, 0, 2, '2026-05-13 19:57:01', '2026-05-13 20:20:47'),
(117, 779060335, 65393049, 7670, 'www.tiktok.com/@paxyo251/video/7639464514810957074?cid=NzYzOTQ2NDUyNzUyODUxMDIyNg', 'Tiktok Comment Likes [ Max 1M ] | HQ Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'www.tiktok.com/@paxyo251/video/7639464514810957074?cid=NzYzOTQ2NDUyNzUyODUxMDIyNg', 10000, 280.00, 'completed', NULL, 0, 10013, '2026-05-13 20:02:27', '2026-05-15 00:18:54'),
(118, 2047670227, 65471789, 6541, 'https://t.me/mustefa_Apologetics/2247', 'Telegram Reactions + Free Views | Thumbs up 👍 | [ Max 1M ] | Day 100K', 'https://t.me/mustefa_Apologetics/2247', 10, 0.05, 'completed', NULL, 0, 0, '2026-05-15 06:33:43', '2026-05-15 07:08:24'),
(119, 2047670227, 65471884, 209, 'https://t.me/mustefa_Apologetics/2247', 'Telegram Post Views [ Max 50K ] | Last 1 Old Posts', 'https://t.me/mustefa_Apologetics/2247', 1000, 2.40, 'completed', NULL, 0, 0, '2026-05-15 06:36:12', '2026-05-15 07:03:04'),
(120, 2030466394, 65474683, 2694, 'https://www.facebook.com/100069415110377/posts/1303188408671651/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100069415110377/posts/1303188408671651/?app=fbl', 150, 6.59, 'completed', NULL, 0, 15, '2026-05-15 07:56:24', '2026-05-15 08:17:20'),
(121, 8126556091, 65534798, 5971, 'https://www.tiktok.com/@april28.08?_r=1&_t=ZN-96PKwxEw1eo', 'TikTok Followers [ Max 1M ] | Real &amp; Bot Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.tiktok.com/@april28.08?_r=1&_t=ZN-96PKwxEw1eo', 10, 2.60, 'completed', NULL, 0, 0, '2026-05-16 11:29:27', '2026-05-17 01:18:38'),
(122, 8126556091, 65534927, 1085, 'https://www.tiktok.com/@april28.08?_r=1&_t=ZN-96PKwxEw1eo', 'TikTok Followers [ Max 10K ] | Bot Accounts | Cancel Enable | High Drop | No Refill ⚠️ | Instant Start | Day 10K 🚀', 'https://www.tiktok.com/@april28.08?_r=1&_t=ZN-96PKwxEw1eo', 10, 5.28, 'completed', NULL, 0, 90, '2026-05-16 11:32:13', '2026-05-16 11:37:18'),
(123, 8126556091, 65535396, 7580, 'https://vm.tiktok.com/ZNRGwnKAP/', 'TikTok Video Views  [ Max Unlimited ] | HQ | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vm.tiktok.com/ZNRGwnKAP/', 250, 1.47, 'processing', NULL, 250, 0, '2026-05-16 11:41:50', '2026-05-16 11:42:03'),
(124, 8126556091, 65535441, 3955, 'https://vm.tiktok.com/ZNRGwnKAP/', 'TikTok Likes [ Max 1M ] | Real &amp; Bot Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vm.tiktok.com/ZNRGwnKAP/', 100, 0.48, 'processing', NULL, 100, 0, '2026-05-16 11:42:56', '2026-05-16 11:43:08'),
(125, 8126556091, 65536132, 1100, 'https://www.tiktok.com/@april28.08?_r=1&_t=ZN-96PKwRxdV4A', 'TikTok Followers [ Max 1M ] | 100% Real Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 30K [ Rec. ⭐ ] ᴺᴱᵂ', 'https://www.tiktok.com/@april28.08?_r=1&_t=ZN-96PKwRxdV4A', 10, 4.92, 'completed', NULL, 0, 101, '2026-05-16 11:57:55', '2026-05-17 02:47:23'),
(126, 2047670227, 65548894, 2026, 'https://t.me/mustefa_Apologetics/2247', 'Telegram Reactions + Free Views | Like 👍| [ Max 100K ] | Day 100K', 'https://t.me/mustefa_Apologetics/2247', 100, 1.57, 'completed', NULL, 0, 0, '2026-05-16 16:41:03', '2026-05-16 16:48:12'),
(127, 2047670227, 65549102, 6541, 'https://t.me/mustefa_Apologetics/2247', 'Telegram Reactions + Free Views | Thumbs up 👍 | [ Max 1M ] | Day 100K', 'https://t.me/mustefa_Apologetics/2247', 100, 0.52, 'completed', NULL, 0, 0, '2026-05-16 16:46:03', '2026-05-16 18:12:17'),
(128, 6128857120, 65552691, 5773, 'https://www.tiktok.com/@abuanhna/video/7640350911205739783?_r=1&u_code=echm9fghme1598&preview_pb=0&sharer_language=en&_d=ecd59jkl121958&share_item_id=7640350911205739783&source=h5_m&timestamp=1778954949&user_id=7336611151604106245&sec_user_id=MS4wLjABAAAAIKnbSDxN7e1jQ-8_ckG0iXfdLB_GrxHhFa1w6lJDfryRmmhDa5hiPbS7xHM7Ht7L&item_author_type=2&social_share_type=0&utm_source=copy&utm_campaign=client_share&utm_medium=android&share_iid=7639825134300464914&share_link_id=bd147a5b-5d04-43a3-8e40-46aeb0c74d55&share_app_id=1233&ugbiz_name=MAIN&ug_btm=b5836%2Cb2878&sp_root_share_link_id=bd147a5b-5d04-43a3-8e40-46aeb0c74d55&link_reflow_popup_iteration_sharer=%7B%22click_empty_to_play%22%3A1%2C%22dynamic_cover%22%3A1%2C%22follow_to_play_duration%22%3A-1.0%2C%22profile_clickable%22%3A1%7D&enable_checksum=1&sp_level=1&sp_root_u=echm9fghme1598&sp_root_d=ecd59jkl121958', 'TikTok Video Views [ Max Unlimited ] | HQ | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://www.tiktok.com/@abuanhna/video/7640350911205739783?_r=1&u_code=echm9fghme1598&preview_pb=0&sharer_language=en&_d=ecd59jkl121958&share_item_id=7640350911205739783&source=h5_m&timestamp=1778954949&user_id=7336611151604106245&sec_user_id=MS4wLjABAAAAIKnbSDxN7e1jQ-8_ckG0iXfdLB_GrxHhFa1w6lJDfryRmmhDa5hiPbS7xHM7Ht7L&item_author_type=2&social_share_type=0&utm_source=copy&utm_campaign=client_share&utm_medium=android&share_iid=7639825134300464914&share_link_id=bd147a5b-5d04-43a3-8e40-46aeb0c74d55&share_app_id=1233&ugbiz_name=MAIN&ug_btm=b5836%2Cb2878&sp_root_share_link_id=bd147a5b-5d04-43a3-8e40-46aeb0c74d55&link_reflow_popup_iteration_sharer=%7B%22click_empty_to_play%22%3A1%2C%22dynamic_cover%22%3A1%2C%22follow_to_play_duration%22%3A-1.0%2C%22profile_clickable%22%3A1%7D&enable_checksum=1&sp_level=1&sp_root_u=echm9fghme1598&sp_root_d=ecd59jkl121958', 40000, 272.00, 'completed', NULL, 0, 72505, '2026-05-16 18:10:20', '2026-05-16 18:17:22'),
(129, 6128857120, 65553689, 6272, 'https://vt.tiktok.com/ZSxF8X4T4/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Low Drop | 365 Days ♻️ | Instant Start | Day 100K 🚀  [ Cheap ]', 'https://vt.tiktok.com/ZSxF8X4T4/', 3000, 82.80, 'completed', NULL, 0, 9758, '2026-05-16 18:38:51', '2026-05-17 13:10:45'),
(130, 2047670227, 65555780, 6541, 'https://t.me/mustefa_Apologetics/2247', 'Telegram Reactions + Free Views | Thumbs up 👍 | [ Max 1M ] | Day 100K', 'https://t.me/mustefa_Apologetics/2247', 150, 0.78, 'completed', NULL, 0, 0, '2026-05-16 19:41:59', '2026-05-16 19:52:29'),
(131, 1961928800, 65556142, 6040, 'https://t.me/EthioAgerigna', 'Telegram Members [ Max 500K ] | LQ Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://t.me/EthioAgerigna', 50, 0.13, 'completed', NULL, 0, 261, '2026-05-16 19:54:40', '2026-05-17 05:21:30'),
(132, 6128857120, 65571757, 3955, 'https://vt.tiktok.com/ZSxFTpnbg/', 'TikTok Likes [ Max 1M ] | Real &amp; Bot Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSxFTpnbg/', 3000, 14.40, 'processing', NULL, 3000, 0, '2026-05-17 04:55:57', '2026-05-17 04:56:05'),
(133, 5826257535, 65624042, 7675, 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSx2Theh7/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | 30 Days ♻️ | Instant Start | Day 10M 🚀', 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSx2Theh7/', 3000, 7.80, 'completed', NULL, 0, 102, '2026-05-18 03:27:04', '2026-05-29 16:20:03'),
(134, 5826257535, 65631635, 7703, 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSxjkLkjt/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | 30 Days ♻️ | Instant Start | Day 100M 🚀', 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSxjkLkjt/', 2880, 0.92, 'completed', NULL, 0, 165, '2026-05-18 06:58:51', '2026-05-18 07:03:13'),
(135, 5826257535, 65632086, 5557, 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSxjBjCvt/', 'TikTok Likes [ Max 1M ] | Hidden Accounts | Cancel Enable | 30 Days ♻️ | Instant Start | Day 100K 🚀', 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSxjBjCvt/', 80, 1.25, 'completed', NULL, 0, 25, '2026-05-18 07:10:25', '2026-05-18 07:16:33'),
(136, 6581678657, 65639180, 7344, 'https://vt.tiktok.com/ZSxjXTW4a/', 'TikTok Video Views [ Min 500 ] [ Max Unlimited ] | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vt.tiktok.com/ZSxjXTW4a/', 30000, 36.00, 'completed', NULL, 0, 303, '2026-05-18 10:01:02', '2026-05-18 12:21:31'),
(137, 6581678657, 65639477, 7570, 'https://vt.tiktok.com/ZSxj4GSc7/', 'Tiktok Video Views [ Min 10K ] [ Max 10M ] | HQ | Cancel Enable | 30 Days Auto Refill ♻️ | Instant Start | Day 5M 🚀', 'https://vt.tiktok.com/ZSxj4GSc7/', 2000, 14.40, 'canceled', NULL, 2000, 0, '2026-05-18 10:07:56', '2026-05-18 10:49:40'),
(138, 6581678657, 65639895, 7570, 'https://vt.tiktok.com/ZSxjqYnM4/', 'Tiktok Video Views [ Min 10K ] [ Max 10M ] | HQ | Cancel Enable | 30 Days Auto Refill ♻️ | Instant Start | Day 5M 🚀', 'https://vt.tiktok.com/ZSxjqYnM4/', 3000, 21.60, 'canceled', NULL, 3000, 0, '2026-05-18 10:18:07', '2026-05-18 10:49:40'),
(139, 6655261307, 65640428, 7344, 'https://vt.tiktok.com/ZSxjq7W2F/', 'TikTok Video Views [ Min 500 ] [ Max Unlimited ] | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vt.tiktok.com/ZSxjq7W2F/', 10000, 12.00, 'completed', NULL, 0, 144, '2026-05-18 10:29:35', '2026-05-18 12:45:37'),
(140, 6581678657, 65641137, 7675, 'https://vt.tiktok.com/ZSxjg5d5K/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | 30 Days ♻️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZSxjg5d5K/', 3000, 7.80, 'completed', NULL, 0, 142, '2026-05-18 10:38:02', '2026-05-29 13:34:02'),
(141, 8126556091, 65677302, 5085, 'https://vt.tiktok.com/ZSx6T6gft/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vt.tiktok.com/ZSx6T6gft/', 10000, 2.80, 'completed', NULL, 0, 1, '2026-05-18 23:33:11', '2026-05-18 23:39:04'),
(142, 8126556091, 65677340, 3955, 'https://vt.tiktok.com/ZSx6T6gft/', 'TikTok Likes [ Max 1M ] | Real &amp; Bot Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSx6T6gft/', 500, 2.40, 'processing', NULL, 500, 0, '2026-05-18 23:34:30', '2026-05-18 23:34:39'),
(143, 8126556091, 65677707, 5816, 'https://vt.tiktok.com/ZSx6T6gft/', 'TikTok Likes [ Max 1M ] | Hidden Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSx6T6gft/', 10, 0.10, 'completed', NULL, 0, 1, '2026-05-18 23:45:33', '2026-05-18 23:50:15'),
(144, 779060335, 65714884, 2252, 'T.me/Paxyo251', 'Telegram Premium Members [ Max 200K ] | Premium Accounts | Cancel Enable | Non Drop | Instant Start | Day 50K [ 3 Days ⭐ ]', 'T.me/Paxyo251', 10, 5.80, 'completed', NULL, 0, 377, '2026-05-19 14:20:45', '2026-05-19 14:39:06'),
(145, 6128857120, 65759728, 5773, 'https://vt.tiktok.com/ZSxkCJCuL/', 'TikTok Video Views [ Max Unlimited ] | HQ | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZSxkCJCuL/', 10000, 68.00, 'completed', NULL, 0, 415, '2026-05-20 08:38:33', '2026-05-20 08:41:17'),
(146, 6128857120, 65760060, 6272, 'https://vt.tiktok.com/ZSxkCCQYD/', 'TikTok Likes [ Max 1M ] | 100% Real Accounts | Cancel Enable | Low Drop | 365 Days ♻️ | Instant Start | Day 100K 🚀  [ Cheap ]', 'https://vt.tiktok.com/ZSxkCCQYD/', 2000, 55.20, 'canceled', NULL, 2000, 60, '2026-05-20 08:46:06', '2026-05-20 13:25:11'),
(147, 6128857120, 65761028, 5814, 'https://vt.tiktok.com/ZSxkVVPkx/', 'TikTok Likes [ Max 10M ] | Bot Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSxkVVPkx/', 600, 7.08, 'canceled', NULL, 600, 61, '2026-05-20 09:08:13', '2026-05-20 11:13:44'),
(148, 779060335, 65763048, 4376, 'https://vt.tiktok.com/ZSxkGmPQU/', 'TikTok Likes [ Max 1M ] | HQ Profiles | Cancel Enable | 30 Days ♻️ | Instant Start | Day 50K', 'https://vt.tiktok.com/ZSxkGmPQU/', 3000, 105.60, 'completed', NULL, 0, 72, '2026-05-20 10:03:10', '2026-05-20 10:16:54'),
(149, 1961928800, 65797081, 1871, 'https://t.me/EthioAgerigna', 'Telegram Post Views [ Max 50M ] | Last 5 Post', 'https://t.me/EthioAgerigna', 500, 11.20, 'completed', NULL, 0, 500, '2026-05-20 22:12:20', '2026-05-20 22:15:22'),
(150, 1961928800, 65797099, 1758, 'https://t.me/EthioAgerigna/1123', 'Auto - Telegram Reactions + Free Views | Positive - 👍 ❤️ 🔥 🎉 😁| [ Max 2M ] | 10 Future Posts', 'https://t.me/EthioAgerigna/1123', 20, 6.40, 'completed', NULL, 0, 0, '2026-05-20 22:13:02', '2026-05-20 22:13:27'),
(151, 1961928800, 65802411, 6307, 'https://t.me/EthioAgerigna/1123', 'Telegram Bot Start [ Max 100K ] | With Static | Non Drop | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://t.me/EthioAgerigna/1123', 50, 3.20, 'canceled', NULL, 50, 0, '2026-05-21 00:38:59', '2026-05-21 00:39:24'),
(152, 1961928800, 65802535, 6040, 'https://t.me/EthioAgerigna', 'Telegram Members [ Max 500K ] | LQ Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://t.me/EthioAgerigna', 10000, 25.20, 'completed', NULL, 0, 249, '2026-05-21 00:42:20', '2026-05-21 18:15:51'),
(153, 1961928800, 65802935, 1735, 'https://t.me/EthioAgerigna/1123', 'Telegram Reactions + Free Views | Positive -👌 😍 ❤️ 🤡 👍 🐳| [ Max 1M ] | Day 100K', 'https://t.me/EthioAgerigna/1123', 50, 2.00, 'partial', NULL, 50, 50, '2026-05-21 00:54:49', '2026-05-21 00:55:49'),
(154, 2047670227, 65845703, 1714, 'https://t.me/RyKorea/105', 'Telegram Reactions + Free Views | Positive  - 👍 ❤️ 🔥 🎉 👏 | [ Max 1M ] | Day 100K', 'https://t.me/RyKorea/105', 150, 1.50, 'completed', NULL, 0, 0, '2026-05-21 17:10:36', '2026-05-21 17:20:20'),
(155, 2047670227, 65846417, 1714, 'https://t.me/mahircomp123/8917', 'Telegram Reactions + Free Views | Positive  - 👍 ❤️ 🔥 🎉 👏 | [ Max 1M ] | Day 100K', 'https://t.me/mahircomp123/8917', 1500, 15.00, 'completed', NULL, 0, 0, '2026-05-21 17:25:34', '2026-05-21 19:06:16'),
(156, 1961928800, 65970316, 6534, 'https://t.me/onesheildplussl/429523?single', 'Telegram Reactions + Free Views  [ Max 1M ] Positive - 👍🤩🎉🔥❤️🥰👏🏻🥳😍❤️‍🔥💯| Day 100K', 'https://t.me/onesheildplussl/429523?single', 300, 1.56, 'partial', NULL, 300, 0, '2026-05-23 14:34:11', '2026-05-23 15:22:25'),
(157, 2030466394, 66184590, 2694, 'https://www.facebook.com/100044148803693/posts/1574003974081214/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100044148803693/posts/1574003974081214/?app=fbl', 300, 13.18, 'completed', NULL, 0, 249, '2026-05-26 16:52:13', '2026-05-26 17:40:36'),
(158, 2030466394, 66184731, 2694, 'https://www.facebook.com/share/v/1BeGDJn8wh/', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/share/v/1BeGDJn8wh/', 300, 13.18, 'completed', NULL, 0, 115, '2026-05-26 16:55:09', '2026-05-26 17:40:36'),
(159, 2030466394, 66184806, 2694, 'https://www.facebook.com/100044148803693/posts/1574306474050964/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100044148803693/posts/1574306474050964/?app=fbl', 300, 13.18, 'completed', NULL, 0, 48, '2026-05-26 16:56:27', '2026-05-26 17:40:36'),
(160, 2030466394, 66235628, 2694, 'https://www.facebook.com/100044148803693/posts/1574937527321192/?app=fbl', 'Facebook Post Likes [ Max 1M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.facebook.com/100044148803693/posts/1574937527321192/?app=fbl', 150, 6.59, 'completed', NULL, 0, 36, '2026-05-27 15:30:12', '2026-05-27 15:38:25');
INSERT INTO `orders` (`id`, `user_id`, `api_order_id`, `service_id`, `target_link`, `service_name`, `link`, `quantity`, `charge`, `status`, `custom_fields`, `remains`, `start_count`, `created_at`, `updated_at`) VALUES
(161, 7360255928, 66251365, 2714, 'https://vm.tiktok.com/ZS9YKYkCu3yTS-3tTa2/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 'TikTok Video Views [ Min 500 ] [ Max Unlimited ] | 30 Days ♻️ | Instant Start | Day 100M 🚀', 'https://vm.tiktok.com/ZS9YKYkCu3yTS-3tTa2/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 60000, 96.00, 'canceled', NULL, 60000, 0, '2026-05-27 20:31:32', '2026-05-27 20:31:49'),
(162, 7360255928, 66251546, 2714, 'https://vm.tiktok.com/ZS9YK2eXTnPA9-mlgHB/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 'TikTok Video Views [ Min 500 ] [ Max Unlimited ] | 30 Days ♻️ | Instant Start | Day 100M 🚀', 'https://vm.tiktok.com/ZS9YK2eXTnPA9-mlgHB/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 50000, 80.00, 'canceled', NULL, 50000, 0, '2026-05-27 20:35:45', '2026-05-27 20:35:54'),
(163, 7360255928, 66251779, 7344, 'https://vm.tiktok.com/ZS9YK2n2wmjyo-PUv2d/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 'TikTok Video Views [ Min 500 ] [ Max Unlimited ] | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vm.tiktok.com/ZS9YK2n2wmjyo-PUv2d/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 50000, 60.00, 'canceled', NULL, 50000, 0, '2026-05-27 20:40:31', '2026-05-28 14:06:11'),
(164, 7360255928, 66251868, 3955, 'https://vm.tiktok.com/ZS9YKjSkWcm7y-KXnrm/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 'TikTok Likes [ Max 1M ] | Real &amp; Bot Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vm.tiktok.com/ZS9YKjSkWcm7y-KXnrm/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 5000, 24.00, 'processing', NULL, 5000, 0, '2026-05-27 20:43:06', '2026-05-27 20:43:09'),
(165, 7360255928, 66310892, 3769, 'https://vm.tiktok.com/ZS92JR51gneks-LWNHt/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 'TikTok Live Stream Views [ Max 100K ] | 100% Real Accounts | Cancel Enable | Instant Start | 15 Minutes', 'https://vm.tiktok.com/ZS92JR51gneks-LWNHt/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 10, 1.72, 'canceled', NULL, 10, 0, '2026-05-28 17:42:39', '2026-05-28 18:01:03'),
(166, 779060335, 66473391, 4287, 'https://x.com/JohannesAbate7', 'X / Twitter Followers [ Max 100K ] | LQ Accounts | High Drop | No Refill | Instant Start | Day 100K 🚀 [ Fast Completed ]', 'https://x.com/JohannesAbate7', 100, 60.00, 'completed', NULL, 0, 22, '2026-05-31 04:23:45', '2026-05-31 04:26:19'),
(167, 779060335, 66552965, 1870, 'T.me/hsh', 'Telegram Post Views [ Max 50M ] | Last 1 Post', 'T.me/hsh', 100, 3.61, 'canceled', NULL, 100, 0, '2026-06-01 16:55:02', '2026-06-02 04:29:06'),
(168, 5928771903, 66583848, 6302, 'dgdfgdfg', 'TikTok Followers [ Max 10M ] | Bot Accounts | High Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'dgdfgdfg', 10, 5.82, 'canceled', NULL, 10, 49, '2026-06-02 10:06:44', '2026-06-06 16:11:08'),
(169, 779060335, 66583891, 5814, 'dfgdfgdfg', 'TikTok Likes [ Max 10M ] | Bot Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'dfgdfgdfg', 10, 0.80, 'processing', NULL, 10, 0, '2026-06-02 10:08:34', '2026-06-02 10:08:50'),
(170, 5928771903, 66586445, 6302, 'jfjfjfj', 'TikTok Followers [ Max 10M ] | Bot Accounts | High Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'jfjfjfj', 10, 5.82, 'in_progress', NULL, 10, 16, '2026-06-02 11:17:27', '2026-06-02 11:18:01'),
(171, 779060335, 66592119, 5814, 'fghfghfghfghfgh', 'TikTok Likes [ Max 10M ] | Bot Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'fghfghfghfghfgh', 10, 0.80, 'processing', NULL, 10, 0, '2026-06-02 13:42:09', '2026-06-02 13:42:18'),
(172, 779060335, 66597008, 2252, 'T.me/Paxyo251', 'Telegram Premium Members [ Max 200K ] | Premium Accounts | Cancel Enable | Non Drop | Instant Start | Day 50K [ 3 Days ⭐ ]', 'T.me/Paxyo251', 10, 5.95, 'completed', NULL, 0, 348, '2026-06-02 15:32:29', '2026-06-02 17:17:45'),
(173, 7999410461, 66636829, 5085, 'https://vt.tiktok.com/ZSxEfsXAW/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vt.tiktok.com/ZSxEfsXAW/', 100, 0.38, 'completed', NULL, 0, 21, '2026-06-03 09:07:04', '2026-06-03 09:07:51'),
(174, 7999410461, 66636895, 5816, 'https://vt.tiktok.com/ZSxEfsXAW/', 'TikTok Likes [ Max 1M ] | Hidden Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSxEfsXAW/', 10, 0.26, 'completed', NULL, 0, 2, '2026-06-03 09:08:25', '2026-06-03 09:37:36'),
(175, 7999410461, 66637018, 6302, 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96tlojwSLm1', 'TikTok Followers [ Max 10M ] | Bot Accounts | High Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96tlojwSLm1', 30, 17.47, 'in_progress', NULL, 30, 161, '2026-06-03 09:11:18', '2026-06-03 09:12:16'),
(176, 7999410461, 66637576, 1477, 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96tmzhzX3Ya', 'TikTok Followers [ Max 100K ] | HQ Accounts | Non Drop | No Refill ⚠️ | Instant Start | Day 50K', 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96tmzhzX3Ya', 10, 20.91, 'completed', NULL, 0, 165, '2026-06-03 09:27:10', '2026-06-03 12:14:52'),
(177, 7999410461, 66637603, 4331, 'https://vt.tiktok.com/ZSxEmYWpc/', 'TikTok Likes [ Max 10M ] | Bot Accounts | Cancel Enable | 30 Days ♻️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSxEmYWpc/', 20, 1.72, 'canceled', NULL, 20, 11, '2026-06-03 09:28:01', '2026-06-05 16:43:25'),
(178, 7999410461, 66640656, 5814, 'https://vt.tiktok.com/ZSxEgYdsy/', 'TikTok Likes [ Max 10M ] | Bot Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSxEgYdsy/', 20, 1.61, 'canceled', NULL, 20, 24, '2026-06-03 10:48:55', '2026-06-05 16:44:05'),
(179, 7999410461, 66642406, 6271, 'https://vt.tiktok.com/ZSxEWSx9P/', 'TikTok Likes [ Max 10M ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSxEWSx9P/', 30, 0.57, 'canceled', NULL, 30, 0, '2026-06-03 11:37:43', '2026-06-03 23:41:42'),
(180, 7999410461, 66642535, 6302, 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96twnCuHUiz', 'TikTok Followers [ Max 10M ] | Bot Accounts | High Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96twnCuHUiz', 10, 5.82, 'in_progress', NULL, 10, 165, '2026-06-03 11:40:40', '2026-06-03 11:41:47'),
(181, 7999410461, 66676143, 6360, 'https://vt.tiktok.com/ZSQ1tpp9v/', 'TikTok Likes + Views [ Max 5M ] | High Quality Accounts with Posts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQ1tpp9v/', 20, 1.07, 'completed', NULL, 0, 45, '2026-06-04 04:29:57', '2026-06-04 04:35:39'),
(182, 7999410461, 66702313, 6360, 'https://vt.tiktok.com/ZSQePu23H/', 'TikTok Likes + Views [ Max 5M ] | High Quality Accounts with Posts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQePu23H/', 10, 0.53, 'completed', NULL, 0, 14, '2026-06-04 15:47:50', '2026-06-04 15:54:12'),
(183, 6195785370, 66754790, 5085, 'https://vm.tiktok.com/ZS9245QrQsE4A-93ecT/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vm.tiktok.com/ZS9245QrQsE4A-93ecT/', 3000, 11.44, 'completed', NULL, 0, 414, '2026-06-05 16:34:09', '2026-06-05 16:35:50'),
(184, 6195785370, 66754804, 3955, 'https://vm.tiktok.com/ZS9245QrQsE4A-93ecT/', 'TikTok Likes [ Max 1M ] | Real &amp; Bot Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vm.tiktok.com/ZS9245QrQsE4A-93ecT/', 2000, 9.84, 'processing', NULL, 2000, 0, '2026-06-05 16:34:42', '2026-06-05 16:34:50'),
(185, 6195785370, 66754913, 5085, 'https://vm.tiktok.com/ZS924aRAas7Kx-Cfm4N/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vm.tiktok.com/ZS924aRAas7Kx-Cfm4N/', 2000, 7.63, 'completed', NULL, 0, 260, '2026-06-05 16:35:47', '2026-06-05 16:36:50'),
(186, 6195785370, 66754934, 3955, 'https://vm.tiktok.com/ZS924aRAas7Kx-Cfm4N/', 'TikTok Likes [ Max 1M ] | Real &amp; Bot Accounts | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vm.tiktok.com/ZS924aRAas7Kx-Cfm4N/', 2000, 9.84, 'processing', NULL, 2000, 0, '2026-06-05 16:36:18', '2026-06-05 16:36:25'),
(187, 7999410461, 66762678, 4373, 'https://vt.tiktok.com/ZSQ87gyqT/', 'TikTok Likes [ Max 1M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-5% | 30 Days ♻️ | Instant | Day 50K 🚀  [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQ87gyqT/', 100, 10.66, 'completed', NULL, 0, 18, '2026-06-05 19:33:10', '2026-06-05 21:01:48'),
(188, 7999410461, 66762710, 7821, 'https://vt.tiktok.com/ZSQ8vytQH/', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | 30 Days ♻️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZSQ8vytQH/', 200, 4.51, 'completed', NULL, 0, 128, '2026-06-05 19:35:02', '2026-06-05 19:36:52'),
(189, 7999410461, 66762878, 6360, 'https://vt.tiktok.com/ZSQ8vdYug/', 'TikTok Likes + Views [ Max 5M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-50% | No Refill ⚠️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQ8vdYug/', 60, 3.20, 'completed', NULL, 0, 76, '2026-06-05 19:41:29', '2026-06-05 19:58:18'),
(190, 7999410461, 66763011, 5816, 'https://vt.tiktok.com/ZSQ8vdYug/', 'TikTok Likes [ Max 1M ] | Hidden Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSQ8vdYug/', 50, 1.31, 'completed', NULL, 0, 144, '2026-06-05 19:46:17', '2026-06-08 05:51:13'),
(191, 5826257535, 66763067, 4373, 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSQ8cYKwd/', 'TikTok Likes [ Max 1M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-5% | 30 Days ♻️ | Instant | Day 50K 🚀  [ Rec. ⭐ ]', 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSQ8cYKwd/', 150, 15.99, 'completed', NULL, 0, 46, '2026-06-05 19:49:25', '2026-06-05 22:31:34'),
(192, 7999410461, 66763502, 4373, 'https://vt.tiktok.com/ZSQ83JJ9n/', 'TikTok Likes [ Max 1M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-5% | 30 Days ♻️ | Instant | Day 50K 🚀  [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQ83JJ9n/', 50, 5.33, 'completed', NULL, 0, 45, '2026-06-05 20:02:48', '2026-06-05 20:38:23'),
(193, 7999410461, 66763525, 7820, 'https://vt.tiktok.com/ZSQ8ccaer/', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZSQ8ccaer/', 200, 3.69, 'completed', NULL, 0, 822, '2026-06-05 20:03:37', '2026-06-05 20:05:33'),
(194, 7999410461, 66764177, 4373, 'https://vt.tiktok.com/ZSQ8T4wyK/', 'TikTok Likes [ Max 1M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-5% | 30 Days ♻️ | Instant | Day 50K 🚀  [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQ8T4wyK/', 40, 4.26, 'completed', NULL, 0, 95, '2026-06-05 20:25:34', '2026-06-05 21:33:44'),
(195, 5826257535, 66764451, 7675, 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSQ8wm9eT/', 'TikTok Video Views [ Max Unlimited ] | HQ | Cancel Enable | 30 Days ♻️ | Instant Start | Day 10M 🚀', 'Check out this TikTok I posted!  ▶️Watch the full video now!  https://vt.tiktok.com/ZSQ8wm9eT/', 5000, 13.32, 'completed', NULL, 0, 6321, '2026-06-05 20:35:45', '2026-06-05 21:24:23'),
(196, 7999410461, 66765413, 1115, 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96y1HCsBj8f', 'TikTok Followers [ Max 1M ] | HQ Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 20K', 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-96y1HCsBj8f', 50, 39.98, 'completed', NULL, 0, 183, '2026-06-05 21:14:22', '2026-06-05 21:25:53'),
(197, 7999410461, 66765457, 7820, 'https://vt.tiktok.com/ZSQ8ETroo/', 'TikTok Video Views [ Max 100M ] | No Drop Since 30 Days | Cancel Enable | No Refill ⚠️ | Instant Start | Day 10M 🚀', 'https://vt.tiktok.com/ZSQ8ETroo/', 300, 5.53, 'completed', NULL, 0, 661, '2026-06-05 21:15:51', '2026-06-05 21:17:18'),
(198, 7999410461, 66808754, 5816, 'https://vt.tiktok.com/ZSQFoAMsh/', 'TikTok Likes [ Max 1M ] | Hidden Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://vt.tiktok.com/ZSQFoAMsh/', 100, 2.62, 'completed', NULL, 0, 182, '2026-06-06 19:33:41', '2026-06-08 08:11:11'),
(199, 7999410461, 66808760, 6362, 'https://vt.tiktok.com/ZSQFoAMsh/', 'TikTok Likes + Views [ Max 5M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-50% | 30 Days ♻️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQFoAMsh/', 50, 4.00, 'completed', NULL, 0, 98, '2026-06-06 19:33:58', '2026-06-06 19:49:56'),
(200, 7999410461, 66810836, 6362, 'https://vt.tiktok.com/ZSQYRPLvQ/', 'TikTok Likes + Views [ Max 5M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-50% | 30 Days ♻️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQYRPLvQ/', 60, 4.80, 'completed', NULL, 0, 95, '2026-06-06 20:56:13', '2026-06-06 21:11:42'),
(201, 7360255928, 66815405, 5772, 'https://vm.tiktok.com/ZS92GRuXFsRFW-hMhm8/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 'TikTok Video Views [ Max Unlimited ] | HQ | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vm.tiktok.com/ZS92GRuXFsRFW-hMhm8/ This post is shared via TikTok Lite. Download TikTok Lite to enjoy more posts: https://www.tiktok.com/tiktoklite', 10000, 16.40, 'canceled', NULL, 10000, 0, '2026-06-06 23:44:51', '2026-06-06 23:45:03'),
(202, 7999410461, 66828093, 6360, 'https://vt.tiktok.com/ZSQ2LDt3T/', 'TikTok Likes + Views [ Max 5M ] | High Quality Accounts with Posts | Cancel Enable | Drop 0-50% | No Refill ⚠️ | Instant Start | Day 200K 🚀 [ Rec. ⭐ ]', 'https://vt.tiktok.com/ZSQ2LDt3T/', 50, 2.67, 'completed', NULL, 0, 88, '2026-06-07 05:42:43', '2026-06-07 05:57:08'),
(203, 7999410461, 66828262, 5772, 'https://vt.tiktok.com/ZSQ2LMJKx/', 'TikTok Video Views [ Max Unlimited ] | HQ | No Refill ⚠️ | Instant Start | Day 100M 🚀', 'https://vt.tiktok.com/ZSQ2LMJKx/', 600, 0.98, 'completed', NULL, 0, 1160, '2026-06-07 05:46:23', '2026-06-07 06:10:18'),
(204, 7999410461, 66829105, 1100, 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-970LYf3SbQ4', 'TikTok Followers [ Max 1M ] | 100% Real Accounts | Cancel Enable | No Refill ⚠️ | Instant Start | Day 30K', 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-970LYf3SbQ4', 20, 11.89, 'canceled', NULL, 20, 0, '2026-06-07 06:02:49', '2026-06-07 18:39:47'),
(205, 0, 66871042, 6302, 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-971Ej072jBN', 'TikTok Followers [ Max 10M ] | Bot Accounts | High Drop | No Refill ⚠️ | Instant Start | Day 100K 🚀', 'https://www.tiktok.com/@sami98.com7?_r=1&_t=ZS-971Ej072jBN', 150, 87.33, 'in_progress', NULL, 150, 240, '2026-06-07 18:39:42', '2026-06-07 18:40:22'),
(206, 0, 66871100, 2234, 'https://vt.tiktok.com/ZSQjVGdPq/', 'TikTok Likes [ Max 50K ] | HQ Accounts | Cancel Enable | Low Drop | No Refill ⚠️ | Instant Start | Day 50K 🚀', 'https://vt.tiktok.com/ZSQjVGdPq/', 100, 6.56, 'completed', NULL, 0, 103, '2026-06-07 18:40:57', '2026-06-07 19:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL,
  `tg_id` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `tg_id`, `phone_number`, `otp`, `expires_at`, `created_at`) VALUES
(12, '7159821786', '251993960702', '8195', '2026-06-01 15:26:59', '2026-06-01 15:16:29'),
(17, '779060335', '251993960703', '5387', '2026-06-02 07:38:38', '2026-06-02 07:28:38'),
(18, '7999410461', '251990552803', '8304', '2026-06-04 05:37:24', '2026-06-03 09:05:03'),
(35, '7573961936', '098686031', '3387', '2026-06-05 16:40:16', '2026-06-05 16:29:42'),
(38, '5826257535', '251964875380', '3738', '2026-06-05 19:53:30', '2026-06-05 19:42:41'),
(48, '7360255928', '0989146464', '9331', '2026-06-06 23:53:41', '2026-06-06 23:43:41');

-- --------------------------------------------------------

--
-- Table structure for table `recommended_services`
--

CREATE TABLE `recommended_services` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL DEFAULT 'Default',
  `rate` decimal(10,2) NOT NULL,
  `min` int(11) NOT NULL,
  `max` int(11) NOT NULL,
  `average_time` varchar(100) NOT NULL DEFAULT 'N/A',
  `refill` tinyint(1) DEFAULT 0,
  `cancel` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `category`, `name`, `type`, `rate`, `min`, `max`, `average_time`, `refill`, `cancel`, `created_at`) VALUES
(1, 'Instagram', 'Instagram Followers', 'Default', 150.00, 100, 5000, '1-2 hours', 0, 0, '2026-02-20 23:05:33'),
(2, 'Instagram', 'Instagram Likes', 'Default', 50.00, 20, 1000, '5-30 mins', 0, 0, '2026-02-20 23:05:33'),
(3, 'Instagram', 'Instagram Views', 'Default', 30.00, 100, 10000, '5-30 mins', 0, 0, '2026-02-20 23:05:33'),
(4, 'Instagram', 'Instagram Comments', 'Custom Comments', 200.00, 10, 100, '1-2 hours', 0, 0, '2026-02-20 23:05:33'),
(5, 'TikTok', 'TikTok Followers', 'Default', 120.00, 100, 10000, '1-3 hours', 0, 0, '2026-02-20 23:05:33'),
(6, 'TikTok', 'TikTok Likes', 'Default', 40.00, 50, 5000, '10-30 mins', 0, 0, '2026-02-20 23:05:33'),
(7, 'TikTok', 'TikTok Views', 'Default', 25.00, 100, 50000, '5-15 mins', 0, 0, '2026-02-20 23:05:33'),
(8, 'YouTube', 'YouTube Subscribers', 'Default', 500.00, 100, 2000, '1-6 hours', 0, 0, '2026-02-20 23:05:33'),
(9, 'YouTube', 'YouTube Views', 'Default', 80.00, 500, 100000, '1-4 hours', 0, 0, '2026-02-20 23:05:33'),
(10, 'YouTube', 'YouTube Likes', 'Default', 100.00, 20, 1000, '1-2 hours', 0, 0, '2026-02-20 23:05:33'),
(11, 'Facebook', 'Facebook Followers', 'Default', 100.00, 100, 5000, '1-3 hours', 0, 0, '2026-02-20 23:05:34'),
(12, 'Facebook', 'Facebook Page Likes', 'Default', 150.00, 100, 5000, '1-4 hours', 0, 0, '2026-02-20 23:05:34'),
(13, 'Twitter', 'Twitter Followers', 'Default', 80.00, 100, 5000, '1-2 hours', 0, 0, '2026-02-20 23:05:34'),
(14, 'Twitter', 'Twitter Likes', 'Default', 30.00, 20, 1000, '5-30 mins', 0, 0, '2026-02-20 23:05:34'),
(15, 'Twitter', 'Twitter Retweets', 'Default', 50.00, 20, 1000, '1-2 hours', 0, 0, '2026-02-20 23:05:34'),
(16, 'Telegram', 'Telegram Channel Members', 'Default', 100.00, 500, 10000, '1-3 hours', 0, 0, '2026-02-20 23:05:34'),
(17, 'Telegram', 'Telegram Post Views', 'Default', 20.00, 100, 50000, '5-15 mins', 0, 0, '2026-02-20 23:05:34');

-- --------------------------------------------------------

--
-- Table structure for table `service_adjustments`
--

CREATE TABLE `service_adjustments` (
  `service_id` int(11) NOT NULL,
  `average_time` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_custom`
--

CREATE TABLE `service_custom` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `custom_rate` decimal(10,2) DEFAULT NULL,
  `profit_margin` decimal(5,2) DEFAULT 0.00,
  `is_enabled` tinyint(1) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(255) DEFAULT NULL,
  `custom_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_custom`
--

INSERT INTO `service_custom` (`id`, `service_id`, `custom_rate`, `profit_margin`, `is_enabled`, `updated_at`, `updated_by`, `custom_description`) VALUES
(1, 6488, NULL, 0.00, 0, '2026-04-04 20:46:23', NULL, NULL),
(5, 2681, NULL, 0.00, 1, '2026-04-07 18:21:11', NULL, NULL),
(7, 5665, NULL, 0.00, 1, '2026-04-04 23:48:52', NULL, NULL),
(11, 4555, NULL, 0.00, 0, '2026-04-06 11:12:39', NULL, NULL),
(15, 5385, NULL, NULL, 1, '2026-05-19 12:05:37', NULL, 'new'),
(19, 2385, NULL, NULL, 1, '2026-05-19 13:06:29', NULL, NULL),
(20, 1125, NULL, NULL, 1, '2026-05-19 13:19:57', NULL, 'bb'),
(21, 4382, NULL, NULL, 1, '2026-05-19 13:22:44', NULL, 'bb');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('discount_percent', '0'),
('holiday_name', 'Happy Easter '),
('maintenance_allowed_ids', ''),
('maintenance_mode', '0'),
('marquee_text', 'We’re improving Paxyo. This is test mode, but you can still place orders. Maintenance is ongoing.'),
('rate_multiplier', '410'),
('top_services_ids', '3769, 5773, 6140, 6272, 3764, 7297, 4151, 2814, 4333, 6533, 2428, 7312, 1358, 4091, 6279, 2775'),
('user_can_order', '1');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL COMMENT 'tg_id from auth table',
  `type` enum('deposit','withdrawal','order','bonus','refund') NOT NULL,
  `amount` decimal(12,2) NOT NULL COMMENT 'Positive = credit, Negative = debit',
  `balance_after` decimal(12,2) NOT NULL COMMENT 'Snapshot of balance after this transaction',
  `reference_type` varchar(30) DEFAULT NULL COMMENT 'deposit, order, etc.',
  `reference_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to deposits.id or orders.id',
  `description` varchar(255) DEFAULT NULL COMMENT 'Human-readable description',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `balance_after`, `reference_type`, `reference_id`, `description`, `created_at`) VALUES
(1, 123456789, 'deposit', 21.00, 1321.00, 'deposit', 25, 'Manual Force Complete', '2026-03-11 13:51:51'),
(2, 123456789, 'deposit', 10.00, 1331.00, 'deposit', 28, 'Chapa deposit (verified)', '2026-03-11 14:16:20'),
(3, 123456789, 'deposit', 23.00, 1354.00, 'deposit', 29, 'Chapa deposit (verified)', '2026-03-11 14:16:55'),
(4, 123456789, 'deposit', 28.00, 1382.00, 'deposit', 30, 'Chapa deposit (verified)', '2026-03-11 14:17:25'),
(5, 123456789, 'deposit', 10.00, 1392.00, 'deposit', 33, 'Chapa deposit (verified)', '2026-03-11 14:36:35'),
(6, 123456789, 'deposit', 10.00, 1402.00, 'deposit', 35, 'Chapa deposit (verified)', '2026-03-11 14:44:55'),
(7, 123456789, 'deposit', 27.00, 1429.00, 'deposit', 36, 'Chapa deposit (verified)', '2026-03-11 14:45:32'),
(8, 123456789, 'deposit', 10.00, 1438.89, 'deposit', 38, 'Chapa deposit (verified)', '2026-03-25 12:54:43'),
(10, 123456789, 'order', 0.00, 1438.89, 'order', 6, 'Placed Order #6', '2026-03-29 22:57:43'),
(11, 123456789, 'order', 0.00, 1438.89, 'order', 7, 'Placed Order #7', '2026-03-29 23:05:56'),
(12, 123456789, 'order', 0.00, 1438.89, 'order', 8, 'Placed Order #8', '2026-03-29 23:23:58'),
(13, 123456789, 'order', 0.00, 1438.89, 'order', 9, 'Placed Order #9', '2026-03-29 23:24:27'),
(14, 123456789, 'order', 0.00, 1438.89, 'order', 10, 'Placed Order #10', '2026-03-29 23:32:59'),
(15, 123456789, 'refund', 0.11, 1439.00, 'order_refund', 4, 'Refund for Order #4', '2026-03-29 23:36:22'),
(16, 123456789, 'refund', 0.11, 1439.11, 'order_refund', 4, 'Refund for Order #4', '2026-03-29 23:36:22'),
(17, 123456789, 'order', 0.00, 1439.11, 'order', 11, 'Placed Order #11', '2026-03-30 00:17:13'),
(18, 123456789, 'order', 0.00, 1439.11, 'order', 12, 'Placed Order #12', '2026-03-30 00:24:09'),
(19, 123456789, 'order', 0.00, 1439.11, 'order', 13, 'Placed Order #13', '2026-03-30 00:38:40'),
(20, 123456789, 'order', 0.00, 1439.11, 'order', 14, 'Placed Order #14', '2026-03-30 01:04:49'),
(21, 123456789, 'order', 0.00, 1439.11, 'order', 15, 'Placed Order #15', '2026-03-30 01:06:11'),
(22, 123456789, 'order', 0.00, 1439.11, 'order', 16, 'Placed Order #16', '2026-03-30 01:15:28'),
(23, 123456789, 'order', 0.00, 1439.11, 'order', 17, 'Placed Order #17', '2026-03-30 01:24:46'),
(24, 123456789, 'deposit', 10.00, 1449.11, 'deposit', 40, 'Chapa deposit (verified)', '2026-03-30 01:28:04'),
(25, 123456789, 'deposit', 20.00, 1469.11, 'deposit', 41, 'Chapa deposit (verified)', '2026-03-30 01:28:48'),
(26, 123456789, 'order', -0.30, 1468.81, 'order', 18, 'Placed Order #18', '2026-03-30 03:00:55'),
(27, 123456789, 'order', 0.00, 1468.81, 'order', 19, 'Placed Order #19', '2026-04-04 18:57:20'),
(28, 123456789, 'order', 0.00, 1468.81, 'order', 20, 'Placed Order #20', '2026-04-04 19:12:09'),
(29, 123456789, 'order', 0.00, 1468.81, 'order', 21, 'Placed Order #21', '2026-04-04 19:12:41'),
(30, 123456789, '', 233.00, 1701.81, 'admin', NULL, 'Admin balance adjustment', '2026-04-07 00:07:03'),
(31, 123456789, '', 4545.00, 6246.81, 'admin', NULL, 'Admin balance adjustment', '2026-04-07 00:07:17'),
(32, 123456789, 'order', 0.00, 6246.81, 'order', 22, 'Placed Order #22', '2026-04-07 14:39:15'),
(33, 123456789, 'order', 0.00, 6246.81, 'order', 23, 'Placed Order #23', '2026-04-07 14:40:14'),
(34, 123456789, 'refund', 0.00, 6246.81, 'order_refund', 22, 'Refund #22', '2026-04-07 14:41:22'),
(35, 123456789, 'refund', 0.00, 6246.81, 'order_refund', 23, 'Refund #23', '2026-04-07 14:42:37'),
(36, 123456789, 'order', 0.00, 6246.81, 'order', 24, 'Placed Order #24', '2026-04-07 14:46:00'),
(37, 123456789, 'order', 0.00, 6246.81, 'order', 25, 'Placed Order #25', '2026-04-07 14:46:55'),
(38, 123456789, 'refund', 0.00, 6246.81, 'order_refund', 24, 'Refund #24', '2026-04-07 14:47:46'),
(39, 123456789, 'order', -4.00, 6242.81, 'order', 26, 'Placed Order #26', '2026-04-07 14:49:24'),
(40, 123456789, 'order', 0.00, 6242.81, 'order', 27, 'Placed Order #27', '2026-04-07 14:52:05'),
(41, 123456789, 'order', -4.00, 6238.81, 'order', 28, 'Placed Order #28', '2026-04-07 14:52:19'),
(42, 123456789, 'order', 0.00, 6238.81, 'order', 29, 'Placed Order #29', '2026-04-07 14:58:52'),
(43, 123456789, 'order', 0.00, 6238.81, 'order', 30, 'Placed Order #30', '2026-04-07 15:10:26'),
(44, 123456789, 'refund', 0.00, 6238.81, 'order_refund', 30, 'Refund #30', '2026-04-07 15:12:30'),
(45, 5928771903, 'order', 0.00, 100.00, 'order', 31, 'Placed Order #31', '2026-04-07 13:03:30'),
(46, 5928771903, 'order', 0.00, 100.00, 'order', 32, 'Placed Order #32', '2026-04-07 13:04:09'),
(47, 5928771903, 'refund', 0.00, 100.00, 'order_refund', 31, 'Refund #31', '2026-04-07 13:05:23'),
(48, 5928771903, 'refund', 0.00, 100.00, 'order_refund', 32, 'Refund #32', '2026-04-07 13:06:08'),
(49, 5928771903, 'order', 0.00, 100.00, 'order', 33, 'Placed Order #33', '2026-04-07 13:08:40'),
(50, 5928771903, 'refund', 0.00, 100.00, 'order_refund', 33, 'Refund #33', '2026-04-07 13:10:38'),
(51, 5928771903, 'order', 0.00, 200.00, 'order', 34, 'Placed Order #34', '2026-04-07 13:51:31'),
(52, 5928771903, 'refund', 0.00, 200.00, 'order_refund', 34, 'Refund #34', '2026-04-07 13:53:33'),
(53, 5928771903, 'order', 0.00, 200.00, 'order', 35, 'Placed Order #35', '2026-04-07 14:36:04'),
(54, 5928771903, 'refund', 0.00, 200.00, 'order_refund', 35, 'Refund #35', '2026-04-07 14:38:04'),
(55, 5928771903, 'order', 0.00, 200.00, 'order', 36, 'Placed Order #36', '2026-04-07 14:48:01'),
(56, 5928771903, 'refund', 0.00, 200.00, 'order_refund', 36, 'Refund #36', '2026-04-07 14:49:58'),
(57, 779060335, 'order', 0.00, 9000.00, 'order', 37, 'Placed Order #37', '2026-04-10 06:54:52'),
(58, 779060335, 'refund', 0.00, 9000.00, 'order_refund', 37, 'Refund #37', '2026-04-10 06:57:00'),
(59, 5928771903, 'order', -0.01, 199.99, 'order', 38, 'Placed Order #38', '2026-04-11 10:42:00'),
(60, 5928771903, 'refund', 0.01, 200.00, 'order_refund', 38, 'Refund #38', '2026-04-11 10:43:47'),
(61, 779060335, 'order', 0.00, 9000.00, 'order', 39, 'Placed Order #39', '2026-04-11 10:51:05'),
(62, 779060335, 'order', 0.00, 9000.00, 'order', 40, 'Placed Order #40', '2026-04-12 15:47:20'),
(63, 779060335, 'order', 0.00, 9000.00, 'order', 41, 'Placed Order #41', '2026-04-13 15:02:40'),
(64, 779060335, 'refund', 0.00, 9000.00, 'order_refund', 41, 'Refund #41', '2026-04-13 15:05:06'),
(65, 5928771903, 'order', -0.02, 199.98, 'order', 42, 'Placed Order #42', '2026-04-13 15:14:06'),
(66, 5928771903, 'refund', 0.02, 200.00, 'order_refund', 42, 'Refund #42', '2026-04-13 16:52:39'),
(67, 5928771903, 'order', -0.01, 199.99, 'order', 43, 'Placed Order #43', '2026-04-13 17:06:16'),
(68, 5928771903, 'refund', 0.01, 200.00, 'order_refund', 43, 'Refund #43', '2026-04-13 17:08:19'),
(69, 779060335, 'order', -0.09, 8999.91, 'order', 44, 'Placed Order #44', '2026-04-13 18:11:14'),
(70, 779060335, 'order', 0.00, 8999.91, 'order', 45, 'Placed Order #45', '2026-04-13 18:12:25'),
(71, 779060335, 'order', -0.03, 8999.88, 'order', 46, 'Placed Order #46', '2026-04-13 18:55:24'),
(72, 123456789, 'refund', 0.30, 6239.11, 'order_refund', 18, 'Refund #18', '2026-04-14 21:18:19'),
(73, 123456789, 'refund', 4.00, 6243.11, 'order_refund', 26, 'Refund #26', '2026-04-14 21:18:20'),
(74, 123456789, 'refund', 4.00, 6247.11, 'order_refund', 28, 'Refund #28', '2026-04-14 21:18:21'),
(75, 0, 'deposit', 10.00, 10.00, 'deposit', 88, 'Chapa deposit (verified) - CHANrax7efVGV', '2026-04-15 04:39:02'),
(76, 779060335, 'deposit', 10.00, 9009.88, 'deposit', 91, 'Chapa deposit (verified) - CHTS6tGmE4feQ', '2026-04-15 17:20:38'),
(77, 5928771903, 'order', -0.01, 199.99, 'order', 47, 'Placed Order #47', '2026-04-17 17:18:53'),
(78, 5928771903, 'refund', 0.01, 200.00, 'order_refund', 47, 'Refund #47', '2026-04-17 17:20:56'),
(79, 779060335, 'order', 0.00, 9009.88, 'order', 48, 'Placed Order #48', '2026-04-17 20:41:28'),
(80, 779060335, 'deposit', 10.00, 9019.88, 'deposit', 94, 'Chapa deposit (verified) - CHF5IIBKnJl8Z', '2026-04-21 06:37:53'),
(81, 5928771903, 'order', -0.47, 199.53, 'order', 49, 'Placed Order #49', '2026-05-06 07:24:40'),
(82, 2030466394, 'deposit', 63.00, 63.00, 'deposit', 110, 'Chapa deposit (verified) - CHc9xBFru2yEv', '2026-05-06 17:51:17'),
(83, 2030466394, 'order', -1.12, 61.88, 'order', 50, 'Placed Order #50', '2026-05-06 17:53:29'),
(84, 2030466394, 'order', -0.78, 61.10, 'order', 51, 'Placed Order #51', '2026-05-06 17:54:17'),
(85, 2030466394, 'order', -1.12, 59.98, 'order', 52, 'Placed Order #52', '2026-05-06 17:55:18'),
(86, 2030466394, 'order', -1.12, 58.86, 'order', 53, 'Placed Order #53', '2026-05-06 17:56:56'),
(87, 2030466394, 'order', -1.12, 57.74, 'order', 54, 'Placed Order #54', '2026-05-07 17:50:12'),
(88, 2030466394, 'order', -1.12, 56.62, 'order', 55, 'Placed Order #55', '2026-05-07 17:50:59'),
(89, 2030466394, 'order', -0.22, 56.40, 'order', 56, 'Placed Order #56', '2026-05-07 18:00:16'),
(90, 2030466394, 'order', -0.22, 56.18, 'order', 57, 'Placed Order #57', '2026-05-07 18:00:49'),
(91, 7115890811, 'deposit', 15.00, 15.00, 'deposit', 111, 'Chapa deposit (verified) - CHvrXm0FX7GOG', '2026-05-08 08:39:48'),
(92, 7115890811, 'order', -1.36, 13.64, 'order', 58, 'Placed Order #58', '2026-05-08 08:40:47'),
(93, 7115890811, 'order', -1.36, 12.28, 'order', 59, 'Placed Order #59', '2026-05-08 08:41:21'),
(94, 7115890811, 'order', -1.36, 10.92, 'order', 60, 'Placed Order #60', '2026-05-08 08:41:59'),
(95, 7115890811, 'order', -1.36, 9.56, 'order', 61, 'Placed Order #61', '2026-05-08 08:42:24'),
(96, 7115890811, 'order', -1.36, 8.20, 'order', 62, 'Placed Order #62', '2026-05-08 08:42:55'),
(97, 2030466394, 'deposit', 40.00, 96.18, 'deposit', 112, 'Chapa deposit (verified) - CHiWL4kfbPGTO', '2026-05-08 14:39:47'),
(98, 2030466394, 'order', -8.78, 87.40, 'order', 63, 'Placed Order #63', '2026-05-08 14:40:49'),
(99, 2030466394, 'order', -2.20, 85.20, 'order', 64, 'Placed Order #64', '2026-05-08 14:41:49'),
(100, 2030466394, 'order', -0.40, 84.80, 'order', 65, 'Placed Order #65', '2026-05-08 14:42:44'),
(101, 2030466394, 'order', -0.40, 84.40, 'order', 66, 'Placed Order #66', '2026-05-08 14:43:25'),
(102, 1961928800, 'deposit', 50.00, 50.00, 'deposit', 116, 'Chapa deposit (verified) - CHTsoqOkDehgP', '2026-05-10 00:36:55'),
(103, 1961928800, 'order', -2.61, 47.39, 'order', 67, 'Placed Order #67', '2026-05-10 00:37:50'),
(104, 1961928800, 'order', -2.36, 45.03, 'order', 68, 'Placed Order #68', '2026-05-10 00:49:16'),
(105, 1961928800, 'order', -3.80, 41.23, 'order', 69, 'Placed Order #69', '2026-05-10 01:22:53'),
(106, 1961928800, 'order', -4.00, 37.23, 'order', 70, 'Placed Order #70', '2026-05-10 01:23:41'),
(107, 1961928800, 'order', -1.26, 35.97, 'order', 71, 'Placed Order #71', '2026-05-10 02:15:04'),
(108, 2030466394, 'order', -2.20, 82.20, 'order', 72, 'Placed Order #72', '2026-05-10 08:51:08'),
(109, 2030466394, 'order', -2.20, 80.00, 'order', 73, 'Placed Order #73', '2026-05-10 10:22:09'),
(110, 779060335, 'order', -0.50, 9019.38, 'order', 74, 'Placed Order #74', '2026-05-10 14:45:37'),
(111, 779060335, 'order', -6.80, 9012.58, 'order', 75, 'Placed Order #75', '2026-05-10 15:37:52'),
(112, 779060335, 'order', -5.52, 9007.06, 'order', 76, 'Placed Order #76', '2026-05-10 15:43:34'),
(113, 779060335, 'order', -1.80, 9005.26, 'order', 77, 'Placed Order #77', '2026-05-10 15:49:38'),
(114, 779060335, 'order', -1.20, 9004.06, 'order', 78, 'Placed Order #78', '2026-05-10 15:50:46'),
(115, 779060335, 'order', -20.64, 8983.42, 'order', 79, 'Placed Order #79', '2026-05-10 16:04:08'),
(116, 779060335, 'order', -0.72, 8982.70, 'order', 80, 'Placed Order #80', '2026-05-10 16:04:31'),
(117, 779060335, 'order', -11.20, 8971.50, 'order', 81, 'Placed Order #81', '2026-05-10 16:04:50'),
(118, 779060335, 'order', -1.28, 8970.22, 'order', 82, 'Placed Order #82', '2026-05-10 16:05:13'),
(119, 1961928800, 'order', -2.40, 33.57, 'order', 83, 'Placed Order #83', '2026-05-10 21:10:42'),
(120, 1961928800, 'order', -2.60, 30.97, 'order', 84, 'Placed Order #84', '2026-05-10 21:55:02'),
(121, 1961928800, 'order', -2.61, 28.36, 'order', 85, 'Placed Order #85', '2026-05-10 22:10:35'),
(122, 1961928800, 'order', -5.60, 22.76, 'order', 86, 'Placed Order #86', '2026-05-10 23:26:43'),
(123, 1961928800, 'order', -3.80, 18.96, 'order', 87, 'Placed Order #87', '2026-05-10 23:32:32'),
(124, 1961928800, 'order', -18.00, 0.96, 'order', 88, 'Placed Order #88', '2026-05-11 00:14:19'),
(125, 1961928800, 'order', -0.76, 0.20, 'order', 89, 'Placed Order #89', '2026-05-11 00:28:43'),
(126, 1961928800, 'deposit', 200.00, 200.20, 'deposit', 117, 'Chapa deposit (verified) - CHfbiL5oGxyto', '2026-05-11 00:38:46'),
(127, 1961928800, 'order', -38.00, 162.20, 'order', 90, 'Placed Order #90', '2026-05-11 00:39:25'),
(128, 1961928800, 'order', -159.60, 2.60, 'order', 91, 'Placed Order #91', '2026-05-11 00:54:09'),
(129, 1961928800, 'order', -1.90, 0.70, 'order', 92, 'Placed Order #92', '2026-05-11 04:14:31'),
(130, 1961928800, 'refund', 0.04, 0.74, 'order_refund', 92, 'Partial Refund #92', '2026-05-11 05:05:57'),
(131, 1961928800, 'refund', 38.00, 38.74, 'order_refund', 90, 'Refund #90', '2026-05-11 11:06:02'),
(132, 1961928800, 'refund', 5.60, 44.34, 'order_refund', 86, 'Refund #86', '2026-05-11 11:08:22'),
(133, 5065467140, 'deposit', 40.00, 40.00, 'deposit', 118, 'Chapa deposit (verified) - CHm23VzUy5YxD', '2026-05-11 19:00:14'),
(134, 5065467140, 'order', -10.00, 30.00, 'order', 93, 'Placed Order #93', '2026-05-11 19:00:37'),
(135, 5065467140, 'order', -29.60, 0.40, 'order', 94, 'Placed Order #94', '2026-05-11 19:02:09'),
(136, 5065467140, 'deposit', 20.00, 20.40, 'deposit', 119, 'Chapa deposit (verified) - CHXNT7KxhwpWI', '2026-05-11 19:25:18'),
(137, 5065467140, 'order', -16.00, 4.40, 'order', 95, 'Placed Order #95', '2026-05-11 19:26:24'),
(138, 5065467140, 'order', -0.12, 4.28, 'order', 96, 'Placed Order #96', '2026-05-11 19:26:50'),
(139, 5065467140, 'order', -0.12, 4.16, 'order', 97, 'Placed Order #97', '2026-05-11 19:42:26'),
(140, 1961928800, 'order', -18.00, 26.34, 'order', 98, 'Placed Order #98', '2026-05-11 23:41:14'),
(141, 1961928800, 'deposit', 270.00, 296.34, 'deposit', 121, 'Chapa deposit (verified) - CH7V3ulFHpXsA', '2026-05-11 23:46:28'),
(142, 1961928800, 'order', -288.00, 8.34, 'order', 99, 'Placed Order #99', '2026-05-11 23:47:22'),
(143, 1961928800, 'order', -1.90, 6.44, 'order', 100, 'Placed Order #100', '2026-05-12 00:44:01'),
(144, 2030466394, 'order', -2.20, 77.80, 'order', 101, 'Placed Order #101', '2026-05-12 09:34:17'),
(145, 2030466394, 'order', -2.20, 75.60, 'order', 102, 'Placed Order #102', '2026-05-12 13:43:20'),
(146, 2030466394, 'order', -3.07, 72.53, 'order', 103, 'Placed Order #103', '2026-05-12 14:26:14'),
(147, 1961928800, 'order', -6.30, 0.14, 'order', 104, 'Placed Order #104', '2026-05-12 14:48:08'),
(148, 2030466394, 'order', -3.07, 69.46, 'order', 105, 'Placed Order #105', '2026-05-12 15:31:10'),
(149, 2030466394, 'order', -13.18, 56.28, 'order', 106, 'Placed Order #106', '2026-05-12 19:02:46'),
(150, 2030466394, 'order', -17.57, 38.71, 'order', 107, 'Placed Order #107', '2026-05-12 19:16:21'),
(151, 2030466394, 'order', -10.98, 27.73, 'order', 108, 'Placed Order #108', '2026-05-12 19:39:35'),
(152, 2030466394, 'deposit', 20.00, 47.73, 'deposit', 125, 'Chapa deposit (verified) - CHUjXychhjhmB', '2026-05-13 06:06:47'),
(153, 2030466394, 'order', -4.39, 43.34, 'order', 109, 'Placed Order #109', '2026-05-13 06:07:55'),
(154, 2030466394, 'order', -4.39, 38.95, 'order', 110, 'Placed Order #110', '2026-05-13 06:08:52'),
(155, 2030466394, 'order', -4.39, 34.56, 'order', 111, 'Placed Order #111', '2026-05-13 06:11:06'),
(156, 2030466394, 'order', -26.35, 8.21, 'order', 112, 'Placed Order #112', '2026-05-13 15:08:44'),
(157, 5928771903, 'order', -2.07, 197.46, 'order', 113, 'Placed Order #113', '2026-05-13 18:39:59'),
(158, 779060335, 'order', -0.80, 8969.42, 'order', 114, 'Placed Order #114', '2026-05-13 19:56:07'),
(159, 779060335, 'order', -4.32, 8965.10, 'order', 115, 'Placed Order #115', '2026-05-13 19:56:31'),
(160, 779060335, 'order', -8.28, 8956.82, 'order', 116, 'Placed Order #116', '2026-05-13 19:57:01'),
(161, 779060335, 'order', -280.00, 8676.82, 'order', 117, 'Placed Order #117', '2026-05-13 20:02:27'),
(162, 2047670227, 'deposit', 70.00, 70.00, 'deposit', 126, 'Chapa deposit (verified) - CHJTd08g3LuZc', '2026-05-15 06:29:52'),
(163, 2047670227, 'order', -0.05, 69.95, 'order', 118, 'Placed Order #118', '2026-05-15 06:33:43'),
(164, 2047670227, 'order', -2.40, 67.55, 'order', 119, 'Placed Order #119', '2026-05-15 06:36:12'),
(165, 2030466394, 'order', -6.59, 1.62, 'order', 120, 'Placed Order #120', '2026-05-15 07:56:24'),
(166, 8126556091, 'deposit', 10.00, 10.00, 'deposit', 127, 'Chapa deposit (verified) - CH5OOPmSFBOHd', '2026-05-16 11:27:31'),
(167, 8126556091, 'order', -2.60, 7.40, 'order', 121, 'Placed Order #121', '2026-05-16 11:29:27'),
(168, 8126556091, 'order', -5.28, 2.12, 'order', 122, 'Placed Order #122', '2026-05-16 11:32:13'),
(169, 8126556091, 'order', -1.47, 0.65, 'order', 123, 'Placed Order #123', '2026-05-16 11:41:50'),
(170, 8126556091, 'order', -0.48, 0.17, 'order', 124, 'Placed Order #124', '2026-05-16 11:42:56'),
(171, 8126556091, 'deposit', 10.00, 10.17, 'deposit', 128, 'Chapa deposit (verified) - CHAEKJCNUlIE1', '2026-05-16 11:53:44'),
(172, 8126556091, 'order', -4.92, 5.25, 'order', 125, 'Placed Order #125', '2026-05-16 11:57:55'),
(173, 6128857120, 'deposit', 300.00, 300.00, 'deposit', 130, 'Chapa deposit (verified) - CH3R69dFanVOy', '2026-05-16 15:32:29'),
(174, 2047670227, 'order', -1.57, 65.98, 'order', 126, 'Placed Order #126', '2026-05-16 16:41:03'),
(175, 2047670227, 'order', -0.52, 65.46, 'order', 127, 'Placed Order #127', '2026-05-16 16:46:03'),
(176, 6128857120, 'order', -272.00, 28.00, 'order', 128, 'Placed Order #128', '2026-05-16 18:10:20'),
(177, 6128857120, 'deposit', 200.00, 228.00, 'deposit', 131, 'Chapa deposit (verified) - CHrcEGLPMkhvu', '2026-05-16 18:38:00'),
(178, 6128857120, 'order', -82.80, 145.20, 'order', 129, 'Placed Order #129', '2026-05-16 18:38:51'),
(179, 2047670227, 'order', -0.78, 64.68, 'order', 130, 'Placed Order #130', '2026-05-16 19:41:59'),
(180, 1961928800, 'order', -0.13, 0.01, 'order', 131, 'Placed Order #131', '2026-05-16 19:54:40'),
(181, 6128857120, 'order', -14.40, 130.80, 'order', 132, 'Placed Order #132', '2026-05-17 04:55:57'),
(182, 999999, '', 10.00, 10.00, 'admin', NULL, 'Admin balance adjustment', '2026-05-17 08:12:10'),
(183, 5826257535, 'deposit', 10.00, 10.00, 'deposit', 134, 'Chapa deposit (verified) - CHXOZMFq7v08W', '2026-05-18 03:25:48'),
(184, 5826257535, 'order', -7.80, 2.20, 'order', 133, 'Placed Order #133', '2026-05-18 03:27:04'),
(185, 5826257535, 'order', -0.92, 1.28, 'order', 134, 'Placed Order #134', '2026-05-18 06:58:51'),
(186, 5826257535, 'order', -1.25, 0.03, 'order', 135, 'Placed Order #135', '2026-05-18 07:10:25'),
(187, 6581678657, 'deposit', 40.00, 40.00, 'deposit', 135, 'Chapa deposit (verified) - CHKfYKPyCXW7O', '2026-05-18 10:00:34'),
(188, 6581678657, 'order', -36.00, 4.00, 'order', 136, 'Placed Order #136', '2026-05-18 10:01:02'),
(189, 6581678657, 'deposit', 40.00, 44.00, 'deposit', 136, 'Chapa deposit (verified) - CHpWmzJ5AKpzk', '2026-05-18 10:07:13'),
(190, 6581678657, 'order', -14.40, 29.60, 'order', 137, 'Placed Order #137', '2026-05-18 10:07:56'),
(191, 6581678657, 'order', -21.60, 8.00, 'order', 138, 'Placed Order #138', '2026-05-18 10:18:07'),
(192, 6655261307, 'deposit', 20.00, 20.00, 'deposit', 137, 'Chapa deposit (verified) - CHA2hhEIqlUmY', '2026-05-18 10:28:51'),
(193, 6655261307, 'order', -12.00, 8.00, 'order', 139, 'Placed Order #139', '2026-05-18 10:29:35'),
(194, 6581678657, 'order', -7.80, 0.20, 'order', 140, 'Placed Order #140', '2026-05-18 10:38:02'),
(195, 6581678657, 'refund', 14.40, 14.60, 'order_refund', 137, 'Refund #137', '2026-05-18 10:49:40'),
(196, 6581678657, 'refund', 21.60, 36.20, 'order_refund', 138, 'Refund #138', '2026-05-18 10:49:40'),
(197, 8126556091, 'order', -2.80, 2.45, 'order', 141, 'Placed Order #141', '2026-05-18 23:33:11'),
(198, 8126556091, 'order', -2.40, 0.05, 'order', 142, 'Placed Order #142', '2026-05-18 23:34:30'),
(199, 8126556091, 'deposit', 10.00, 10.05, 'deposit', 138, 'Chapa deposit (verified) - CHuhTL8vpeltA', '2026-05-18 23:42:36'),
(200, 8126556091, 'order', -0.10, 9.95, 'order', 143, 'Placed Order #143', '2026-05-18 23:45:33'),
(201, 779060335, 'order', -5.80, 8671.02, 'order', 144, 'Placed Order #144', '2026-05-19 14:20:45'),
(202, 6128857120, 'order', -68.00, 62.80, 'order', 145, 'Placed Order #145', '2026-05-20 08:38:33'),
(203, 6128857120, 'order', -55.20, 7.60, 'order', 146, 'Placed Order #146', '2026-05-20 08:46:06'),
(204, 6128857120, 'order', -7.08, 0.52, 'order', 147, 'Placed Order #147', '2026-05-20 09:08:13'),
(205, 779060335, 'deposit', 10.00, 8681.02, 'deposit', 150, 'Chapa deposit (verified) - CHWz8YYZ4ZY7b', '2026-05-20 09:26:55'),
(206, 779060335, 'order', -105.60, 8575.42, 'order', 148, 'Placed Order #148', '2026-05-20 10:03:10'),
(207, 6128857120, 'refund', 7.08, 7.60, 'order_refund', 147, 'Refund #147', '2026-05-20 11:13:44'),
(208, 6128857120, 'refund', 55.20, 62.80, 'order_refund', 146, 'Refund #146', '2026-05-20 13:25:11'),
(209, 1961928800, 'deposit', 50.00, 50.01, 'deposit', 156, 'Chapa deposit (verified) - CHSQfsbUhTjG0', '2026-05-20 22:08:49'),
(210, 1961928800, 'order', -11.20, 38.81, 'order', 149, 'Placed Order #149', '2026-05-20 22:12:20'),
(211, 1961928800, 'order', -6.40, 32.41, 'order', 150, 'Placed Order #150', '2026-05-20 22:13:02'),
(212, 1961928800, 'order', -3.20, 29.21, 'order', 151, 'Placed Order #151', '2026-05-21 00:38:59'),
(213, 1961928800, 'refund', 3.20, 32.41, 'order_refund', 151, 'Refund #151', '2026-05-21 00:39:24'),
(214, 1961928800, 'order', -25.20, 7.21, 'order', 152, 'Placed Order #152', '2026-05-21 00:42:20'),
(215, 1961928800, 'order', -2.00, 5.21, 'order', 153, 'Placed Order #153', '2026-05-21 00:54:49'),
(216, 1961928800, 'refund', 2.00, 7.21, 'order_refund', 153, 'Partial Refund #153', '2026-05-21 00:55:49'),
(217, 2047670227, 'order', -1.50, 63.18, 'order', 154, 'Placed Order #154', '2026-05-21 17:10:36'),
(218, 2047670227, 'order', -15.00, 48.18, 'order', 155, 'Placed Order #155', '2026-05-21 17:25:34'),
(219, 1961928800, 'order', -1.56, 5.65, 'order', 156, 'Placed Order #156', '2026-05-23 14:34:11'),
(220, 1961928800, 'refund', 1.56, 7.21, 'order_refund', 156, 'Partial Refund #156', '2026-05-23 15:22:25'),
(221, 2030466394, 'deposit', 40.00, 41.62, 'deposit', 161, 'Chapa deposit (verified) - CHRwJCn5zYSRR', '2026-05-26 14:34:06'),
(222, 2030466394, 'order', -13.18, 28.44, 'order', 157, 'Placed Order #157', '2026-05-26 16:52:13'),
(223, 2030466394, 'order', -13.18, 15.26, 'order', 158, 'Placed Order #158', '2026-05-26 16:55:09'),
(224, 2030466394, 'order', -13.18, 2.08, 'order', 159, 'Placed Order #159', '2026-05-26 16:56:27'),
(225, 2030466394, 'deposit', 20.00, 22.08, 'deposit', 164, 'Chapa deposit (verified) - CHLpE98JyQBHL', '2026-05-27 15:28:17'),
(226, 2030466394, 'order', -6.59, 15.49, 'order', 160, 'Placed Order #160', '2026-05-27 15:30:12'),
(227, 7360255928, 'deposit', 100.00, 100.00, 'deposit', 167, 'Chapa deposit (verified) - CHlqcD1vWjQzJ', '2026-05-27 20:30:26'),
(228, 7360255928, 'order', -96.00, 4.00, 'order', 161, 'Placed Order #161', '2026-05-27 20:31:32'),
(229, 7360255928, 'refund', 96.00, 100.00, 'order_refund', 161, 'Refund #161', '2026-05-27 20:31:49'),
(230, 7360255928, 'order', -80.00, 20.00, 'order', 162, 'Placed Order #162', '2026-05-27 20:35:45'),
(231, 7360255928, 'refund', 80.00, 100.00, 'order_refund', 162, 'Refund #162', '2026-05-27 20:35:54'),
(232, 7360255928, 'order', -60.00, 40.00, 'order', 163, 'Placed Order #163', '2026-05-27 20:40:31'),
(233, 7360255928, 'order', -24.00, 16.00, 'order', 164, 'Placed Order #164', '2026-05-27 20:43:06'),
(234, 7360255928, 'refund', 60.00, 76.00, 'order_refund', 163, 'Refund #163', '2026-05-28 14:06:11'),
(235, 7360255928, 'order', -1.72, 74.28, 'order', 165, 'Placed Order #165', '2026-05-28 17:42:39'),
(236, 7360255928, 'refund', 1.72, 76.00, 'order_refund', 165, 'Refund #165', '2026-05-28 18:01:03'),
(237, 779060335, 'order', -60.00, 8515.42, 'order', 166, 'Placed Order #166', '2026-05-31 04:23:45'),
(238, 779060335, 'order', -3.61, 8511.81, 'order', 167, 'Placed Order #167', '2026-06-01 16:55:02'),
(239, 779060335, 'refund', 3.61, 8515.42, 'order_refund', 167, 'Refund #167', '2026-06-02 04:29:06'),
(240, 5928771903, 'order', -5.82, 191.64, 'order', 168, 'Placed Order #168', '2026-06-02 10:06:44'),
(241, 779060335, 'order', -0.80, 8514.62, 'order', 169, 'Placed Order #169', '2026-06-02 10:08:34'),
(242, 5928771903, 'order', -5.82, 185.82, 'order', 170, 'Placed Order #170', '2026-06-02 11:17:27'),
(243, 779060335, 'order', -0.80, 8513.82, 'order', 171, 'Placed Order #171', '2026-06-02 13:42:09'),
(244, 779060335, 'order', -5.95, 8507.87, 'order', 172, 'Placed Order #172', '2026-06-02 15:32:29'),
(245, 7999410461, 'deposit', 50.00, 50.00, 'deposit', 170, 'Chapa deposit (verified) - CHzBLG0HiF3rV', '2026-06-03 09:06:26'),
(246, 7999410461, 'order', -0.38, 49.62, 'order', 173, 'Placed Order #173', '2026-06-03 09:07:04'),
(247, 7999410461, 'order', -0.26, 49.36, 'order', 174, 'Placed Order #174', '2026-06-03 09:08:25'),
(248, 7999410461, 'order', -17.47, 31.89, 'order', 175, 'Placed Order #175', '2026-06-03 09:11:18'),
(249, 7999410461, 'order', -20.91, 10.98, 'order', 176, 'Placed Order #176', '2026-06-03 09:27:10'),
(250, 7999410461, 'order', -1.72, 9.26, 'order', 177, 'Placed Order #177', '2026-06-03 09:28:01'),
(251, 7999410461, 'order', -1.61, 7.65, 'order', 178, 'Placed Order #178', '2026-06-03 10:48:55'),
(252, 7999410461, 'order', -0.57, 7.08, 'order', 179, 'Placed Order #179', '2026-06-03 11:37:43'),
(253, 7999410461, 'order', -5.82, 1.26, 'order', 180, 'Placed Order #180', '2026-06-03 11:40:40'),
(254, 7999410461, 'refund', 0.57, 1.83, 'order_refund', 179, 'Refund #179', '2026-06-03 23:41:42'),
(255, 7999410461, 'order', -1.07, 0.76, 'order', 181, 'Placed Order #181', '2026-06-04 04:29:57'),
(256, 7999410461, 'order', -0.53, 0.23, 'order', 182, 'Placed Order #182', '2026-06-04 15:47:50'),
(257, 5928771903, 'deposit', 10.00, 215.82, 'deposit', 244, 'Chapa deposit (callback) - APPH5wtdsQCG', '2026-06-05 12:36:09'),
(258, 111, '', 0.70, 500.55, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 12:36:09'),
(259, 5928771903, 'deposit', 10.00, 225.82, 'deposit', 247, 'Chapa deposit (verified) - APa1Q3ViNra5', '2026-06-05 12:41:36'),
(260, 111, '', 0.70, 501.25, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 12:41:36'),
(261, 5928771903, 'deposit', 10.00, 235.82, 'deposit', 249, 'Chapa deposit (verified) - APYmxHcGL7dt', '2026-06-05 12:52:32'),
(262, 111, '', 0.70, 501.95, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 12:52:32'),
(263, 5928771903, 'deposit', 10.00, 245.82, 'deposit', 250, 'Chapa deposit (verified) - APK2lgt2Lbpx', '2026-06-05 12:54:35'),
(264, 111, '', 0.70, 502.65, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 12:54:35'),
(265, 5928771903, 'deposit', 20.00, 265.82, 'deposit', 253, 'Chapa deposit (verified) - APBPO4iwKofS', '2026-06-05 13:12:55'),
(266, 111, '', 1.40, 504.05, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 13:12:55'),
(267, 5928771903, 'deposit', 10.00, 275.82, 'deposit', 256, 'Chapa deposit (verified) - APwfqBskokli', '2026-06-05 13:36:29'),
(268, 111, '', 0.70, 504.75, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 13:36:29'),
(269, 5928771903, 'deposit', 10.00, 285.82, 'deposit', 259, 'Chapa deposit (callback) - APbVDakqJncQ', '2026-06-05 13:54:39'),
(270, 111, '', 0.70, 505.45, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 13:54:39'),
(271, 5928771903, 'deposit', 10.00, 295.82, 'deposit', 265, 'Chapa deposit (callback) - APE03NI5vrNx', '2026-06-05 14:13:29'),
(272, 111, '', 0.70, 506.15, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-05 14:13:29'),
(273, 779060335, 'deposit', 10.00, 8517.87, 'deposit', 273, 'Chapa deposit (callback) - APY7KQthRJtN', '2026-06-05 15:02:51'),
(274, 779060335, 'deposit', 10.00, 8527.87, 'deposit', 275, 'Chapa deposit (callback) - APCxe0q7TMXD', '2026-06-05 15:06:00'),
(275, 779060335, 'deposit', 10.00, 8537.87, 'deposit', 276, 'Chapa deposit (callback) - APP2Nc0g0Wtv', '2026-06-05 15:06:45'),
(276, 779060335, 'deposit', 10.00, 8547.87, 'deposit', 277, 'Chapa deposit (callback) - APjjrTduaGki', '2026-06-05 15:07:12'),
(277, 6195785370, 'deposit', 40.00, 40.00, 'deposit', 280, 'Chapa deposit (callback) - APSF6y5dCfgV', '2026-06-05 16:32:37'),
(278, 6195785370, 'order', -11.44, 28.56, 'order', 183, 'Placed Order #183', '2026-06-05 16:34:09'),
(279, 6195785370, 'order', -9.84, 18.72, 'order', 184, 'Placed Order #184', '2026-06-05 16:34:42'),
(280, 6195785370, 'order', -7.63, 11.09, 'order', 185, 'Placed Order #185', '2026-06-05 16:35:47'),
(281, 6195785370, 'order', -9.84, 1.25, 'order', 186, 'Placed Order #186', '2026-06-05 16:36:18'),
(282, 7999410461, 'refund', 1.72, 1.95, 'order_refund', 177, 'Refund #177', '2026-06-05 16:43:25'),
(283, 7999410461, 'refund', 1.61, 3.56, 'order_refund', 178, 'Refund #178', '2026-06-05 16:44:05'),
(284, 7999410461, 'deposit', 10.00, 13.56, 'deposit', 283, 'Chapa deposit (callback) - APDIVLFa8SVJ', '2026-06-05 19:32:26'),
(285, 7999410461, 'order', -10.66, 2.90, 'order', 187, 'Placed Order #187', '2026-06-05 19:33:10'),
(286, 7999410461, 'deposit', 50.00, 52.90, 'deposit', 284, 'Chapa deposit (callback) - APRQvVhWaQUO', '2026-06-05 19:34:00'),
(287, 7999410461, 'order', -4.51, 48.39, 'order', 188, 'Placed Order #188', '2026-06-05 19:35:02'),
(288, 7999410461, 'order', -3.20, 45.19, 'order', 189, 'Placed Order #189', '2026-06-05 19:41:29'),
(289, 7999410461, 'order', -1.31, 43.88, 'order', 190, 'Placed Order #190', '2026-06-05 19:46:17'),
(290, 5826257535, 'deposit', 50.00, 50.03, 'deposit', 286, 'Chapa deposit (callback) - APE2oUpdpjib', '2026-06-05 19:48:43'),
(291, 5826257535, 'order', -15.99, 34.04, 'order', 191, 'Placed Order #191', '2026-06-05 19:49:25'),
(292, 7999410461, 'order', -5.33, 38.55, 'order', 192, 'Placed Order #192', '2026-06-05 20:02:48'),
(293, 7999410461, 'order', -3.69, 34.86, 'order', 193, 'Placed Order #193', '2026-06-05 20:03:37'),
(294, 7999410461, 'order', -4.26, 30.60, 'order', 194, 'Placed Order #194', '2026-06-05 20:25:34'),
(295, 5826257535, 'order', -13.32, 20.72, 'order', 195, 'Placed Order #195', '2026-06-05 20:35:45'),
(296, 7999410461, 'deposit', 50.00, 80.60, 'deposit', 287, 'Chapa deposit (callback) - APQzT8J8I8hz', '2026-06-05 21:13:43'),
(297, 7999410461, 'order', -39.98, 40.62, 'order', 196, 'Placed Order #196', '2026-06-05 21:14:22'),
(298, 7999410461, 'order', -5.53, 35.09, 'order', 197, 'Placed Order #197', '2026-06-05 21:15:51'),
(299, 5928771903, 'deposit', 10.00, 305.82, 'deposit', 289, 'Chapa deposit (callback) - APiNaKonJ9e4', '2026-06-06 10:28:09'),
(300, 111, '', 0.70, 506.85, 'referral_user', 4294967295, '7% referral commission from user #5928771903 deposit', '2026-06-06 10:28:09'),
(301, 5928771903, 'refund', 5.82, 311.64, 'order_refund', 168, 'Refund #168', '2026-06-06 16:11:08'),
(302, 5928771903, 'refund', 5.82, 317.46, 'order_refund', 168, 'Refund #168', '2026-06-06 16:11:08'),
(303, 7999410461, 'order', -2.62, 32.47, 'order', 198, 'Placed Order #198', '2026-06-06 19:33:41'),
(304, 7999410461, 'order', -4.00, 28.47, 'order', 199, 'Placed Order #199', '2026-06-06 19:33:58'),
(305, 7999410461, 'order', -4.80, 23.67, 'order', 200, 'Placed Order #200', '2026-06-06 20:56:13'),
(306, 7360255928, 'order', -16.40, 59.60, 'order', 201, 'Placed Order #201', '2026-06-06 23:44:51'),
(307, 7360255928, 'refund', 16.40, 76.00, 'order_refund', 201, 'Refund #201', '2026-06-06 23:45:03'),
(308, 7999410461, 'order', -2.67, 21.00, 'order', 202, 'Placed Order #202', '2026-06-07 05:42:43'),
(309, 7999410461, 'order', -0.98, 20.02, 'order', 203, 'Placed Order #203', '2026-06-07 05:46:23'),
(310, 7999410461, 'order', -11.89, 8.13, 'order', 204, 'Placed Order #204', '2026-06-07 06:02:49'),
(311, 779060335, 'deposit', 10.00, 8557.87, 'deposit', 314, 'Chapa deposit (callback) - APCg9OHdiTxq', '2026-06-07 08:20:28'),
(312, 0, 'deposit', 100.00, 110.00, 'deposit', 344, 'Chapa deposit (callback) - APwfDXX8bH5P', '2026-06-07 18:38:09'),
(313, 0, 'order', -87.33, 22.67, 'order', 205, 'Placed Order #205', '2026-06-07 18:39:42'),
(314, 7999410461, 'refund', 11.89, 20.02, 'order_refund', 204, 'Refund #204', '2026-06-07 18:39:47'),
(315, 0, 'order', -6.56, 16.11, 'order', 206, 'Placed Order #206', '2026-06-07 18:40:57');

-- --------------------------------------------------------

--
-- Table structure for table `user_alerts`
--

CREATE TABLE `user_alerts` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_alerts`
--

INSERT INTO `user_alerts` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 111, 'Welcome to Paxyo SMM! Your account is ready.', 1, '2026-02-19 18:03:37'),
(2, 111, 'Your deposit of 500 ETB has been credited.', 1, '2026-02-19 18:03:37'),
(3, 111, 'System maintenance completed successfully.', 1, '2026-02-19 18:03:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_recommended_services`
--
ALTER TABLE `admin_recommended_services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alerts_user_id` (`user_id`);

--
-- Indexes for table `auth`
--
ALTER TABLE `auth`
  ADD PRIMARY KEY (`tg_id`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD KEY `idx_auth_last_deposit` (`last_deposit`),
  ADD KEY `idx_auth_last_order` (`last_order`),
  ADD KEY `idx_auth_created_at` (`created_at`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_tx_ref` (`tx_ref`),
  ADD KEY `idx_deposits_user_id` (`user_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_user_id` (`user_id`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tg_id` (`tg_id`);

--
-- Indexes for table `recommended_services`
--
ALTER TABLE `recommended_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_id` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_adjustments`
--
ALTER TABLE `service_adjustments`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_custom`
--
ALTER TABLE `service_custom`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_id` (`service_id`),
  ADD KEY `idx_service_custom_id` (`service_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_transactions_user_id` (`user_id`);

--
-- Indexes for table `user_alerts`
--
ALTER TABLE `user_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=355;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `recommended_services`
--
ALTER TABLE `recommended_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `service_custom`
--
ALTER TABLE `service_custom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=316;

--
-- AUTO_INCREMENT for table `user_alerts`
--
ALTER TABLE `user_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deposits`
--
ALTER TABLE `deposits`
  ADD CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `auth` (`tg_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `auth` (`tg_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_alerts`
--
ALTER TABLE `user_alerts`
  ADD CONSTRAINT `user_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `auth` (`tg_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
