-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 01:36 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_voting_osis`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_admin`
--

CREATE TABLE `tb_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_admin`
--

INSERT INTO `tb_admin` (`id`, `username`, `password`) VALUES
(11, 'admin', '$2y$10$7h/Bp5L2JTXco7KNMy4uq.uRYOqeBdbzjCwFMyWMpTQnpeypwW8aC'),
(12, 'fahiim', '$2y$10$mbBEtn.gUz5vNvZ2qM2C/Os.pGcP8/enLYNoDbPiolEe1pqJeNVL6');

-- --------------------------------------------------------

--
-- Table structure for table `tb_buat_token`
--

CREATE TABLE `tb_buat_token` (
  `id` int(11) NOT NULL,
  `token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `status_token` enum('belum','sudah') DEFAULT 'belum',
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_kandidat`
--

CREATE TABLE `tb_kandidat` (
  `id` int(11) NOT NULL,
  `nomor_kandidat` int(11) NOT NULL,
  `nama_ketua` varchar(100) NOT NULL,
  `kelas_ketua` varchar(50) NOT NULL,
  `foto_ketua` varchar(255) NOT NULL,
  `nama_wakil` varchar(100) NOT NULL,
  `kelas_wakil` varchar(50) NOT NULL,
  `foto_wakil` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kandidat`
--

INSERT INTO `tb_kandidat` (`id`, `nomor_kandidat`, `nama_ketua`, `kelas_ketua`, `foto_ketua`, `nama_wakil`, `kelas_wakil`, `foto_wakil`, `created_at`) VALUES
(18, 1, 'ketua 1', 'XI-1', '1762027902_ketua_nopal.jpg', 'Wakil ketua 1', 'X-1', '1762027902_wakil_jayu.jpg', '2025-10-25 14:59:04'),
(19, 3, 'Ketua 3', 'XI-2', '1762027943_ketua_saed.jpg', 'Wakil ketua 3', 'X-1', '1762027943_wakil_erol.jpg', '2025-10-25 15:00:26'),
(20, 2, 'Ketua 2', 'XI-2', '1762027925_ketua_hakim.jpg', 'Wakil ketua 2', 'X-1', '1762027925_wakil_zikri.jpg', '2025-10-25 15:01:44');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kelas`
--

CREATE TABLE `tb_kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `jumlah_siswa` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kelas`
--

INSERT INTO `tb_kelas` (`id`, `nama_kelas`, `jumlah_siswa`, `created_at`) VALUES
(56, 'x1-tkj', 21, '2026-05-22 10:57:13');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kode_guru`
--

CREATE TABLE `tb_kode_guru` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status_kode` enum('belum','sudah') NOT NULL DEFAULT 'belum',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_voter`
--

CREATE TABLE `tb_voter` (
  `id` int(11) NOT NULL,
  `token_id` int(11) DEFAULT NULL,
  `nama_voter` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `kode_guru_id` int(10) UNSIGNED DEFAULT NULL,
  `role` enum('siswa','guru') NOT NULL DEFAULT 'siswa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `tb_voter`
--
DELIMITER $$
CREATE TRIGGER `after_voter_delete` AFTER DELETE ON `tb_voter` FOR EACH ROW BEGIN
  UPDATE tb_buat_token
  SET status_token = 'belum'
  WHERE id = OLD.token_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_voter_insert` AFTER INSERT ON `tb_voter` FOR EACH ROW BEGIN
  UPDATE tb_buat_token
  SET status_token = 'sudah'
  WHERE id = NEW.token_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tb_vote_log`
--

CREATE TABLE `tb_vote_log` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `nomor_kandidat` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_admin`
--
ALTER TABLE `tb_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tb_buat_token`
--
ALTER TABLE `tb_buat_token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_buat_token_kelas` (`kelas_id`);

--
-- Indexes for table `tb_kandidat`
--
ALTER TABLE `tb_kandidat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_kandidat` (`nomor_kandidat`);

--
-- Indexes for table `tb_kelas`
--
ALTER TABLE `tb_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`);

--
-- Indexes for table `tb_kode_guru`
--
ALTER TABLE `tb_kode_guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `tb_voter`
--
ALTER TABLE `tb_voter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_token_voter` (`token_id`),
  ADD KEY `kode_guru_id` (`kode_guru_id`);

--
-- Indexes for table `tb_vote_log`
--
ALTER TABLE `tb_vote_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voter_id` (`voter_id`),
  ADD KEY `tb_vote_log_ibfk_2` (`nomor_kandidat`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_admin`
--
ALTER TABLE `tb_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tb_buat_token`
--
ALTER TABLE `tb_buat_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=439;

--
-- AUTO_INCREMENT for table `tb_kandidat`
--
ALTER TABLE `tb_kandidat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `tb_kelas`
--
ALTER TABLE `tb_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `tb_kode_guru`
--
ALTER TABLE `tb_kode_guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `tb_voter`
--
ALTER TABLE `tb_voter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT for table `tb_vote_log`
--
ALTER TABLE `tb_vote_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_buat_token`
--
ALTER TABLE `tb_buat_token`
  ADD CONSTRAINT `fk_buat_token_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `tb_kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_token_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `tb_kelas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tb_voter`
--
ALTER TABLE `tb_voter`
  ADD CONSTRAINT `fk_token_voter` FOREIGN KEY (`token_id`) REFERENCES `tb_buat_token` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_voter_kode_guru` FOREIGN KEY (`kode_guru_id`) REFERENCES `tb_kode_guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_vote_log`
--
ALTER TABLE `tb_vote_log`
  ADD CONSTRAINT `tb_vote_log_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `tb_voter` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_vote_log_ibfk_2` FOREIGN KEY (`nomor_kandidat`) REFERENCES `tb_kandidat` (`nomor_kandidat`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
