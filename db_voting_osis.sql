
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `tb_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_admin` (`id`, `username`, `password`) VALUES
(11, 'admin', '$2y$10$7h/Bp5L2JTXco7KNMy4uq.uRYOqeBdbzjCwFMyWMpTQnpeypwW8aC'),
(12, 'fahiim', '$2y$10$mbBEtn.gUz5vNvZ2qM2C/Os.pGcP8/enLYNoDbPiolEe1pqJeNVL6');

CREATE TABLE `tb_buat_token` (
  `id` int(11) NOT NULL,
  `token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `status_token` enum('belum','sudah') DEFAULT 'belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tb_kandidat` (
  `id` int(11) NOT NULL,
  `nomor_kandidat` int(11) NOT NULL,
  `nama_ketua` varchar(100) NOT NULL,
  `kelas_ketua` varchar(50) NOT NULL,
  `foto_ketua` varchar(255) NOT NULL,
  `nama_wakil` varchar(100) NOT NULL,
  `kelas_wakil` varchar(50) NOT NULL,
  `foto_wakil` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_kandidat` (`id`, `nomor_kandidat`, `nama_ketua`, `kelas_ketua`, `foto_ketua`, `nama_wakil`, `kelas_wakil`, `foto_wakil`) VALUES
(18, 1, 'ketua 1', 'XI-1', '1762027902_ketua_nopal.jpg', 'Wakil ketua 1', 'X-1', '1762027902_wakil_jayu.jpg'),
(19, 3, 'Ketua 3', 'XI-2', '1762027943_ketua_saed.jpg', 'Wakil ketua 3', 'X-1', '1762027943_wakil_erol.jpg'),
(20, 2, 'Ketua 2', 'XI-2', '1762027925_ketua_hakim.jpg', 'Wakil ketua 2', 'X-1', '1762027925_wakil_zikri.jpg');

CREATE TABLE `tb_kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `jumlah_siswa` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tb_kelas` (`id`, `nama_kelas`, `jumlah_siswa`) VALUES
(56, 'x1-tkj', 21);

CREATE TABLE `tb_kode_guru` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status_kode` enum('belum','sudah') NOT NULL DEFAULT 'belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `tb_voter` (
  `id` int(11) NOT NULL,
  `token_id` int(11) DEFAULT NULL,
  `nama_voter` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `kode_guru_id` int(10) UNSIGNED DEFAULT NULL,
  `role` enum('siswa','guru') NOT NULL DEFAULT 'siswa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `tb_vote_log` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `nomor_kandidat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tb_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `tb_buat_token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_buat_token_kelas` (`kelas_id`);

ALTER TABLE `tb_kandidat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_kandidat` (`nomor_kandidat`);

ALTER TABLE `tb_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`);

ALTER TABLE `tb_kode_guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

ALTER TABLE `tb_voter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_token_voter` (`token_id`),
  ADD KEY `kode_guru_id` (`kode_guru_id`);

ALTER TABLE `tb_vote_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voter_id` (`voter_id`),
  ADD KEY `tb_vote_log_ibfk_2` (`nomor_kandidat`);

ALTER TABLE `tb_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `tb_buat_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=439;

ALTER TABLE `tb_kandidat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

ALTER TABLE `tb_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

ALTER TABLE `tb_kode_guru`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

ALTER TABLE `tb_voter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

ALTER TABLE `tb_vote_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

ALTER TABLE `tb_buat_token`
  ADD CONSTRAINT `fk_buat_token_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `tb_kelas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_token_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `tb_kelas` (`id`) ON DELETE SET NULL;

ALTER TABLE `tb_voter`
  ADD CONSTRAINT `fk_token_voter` FOREIGN KEY (`token_id`) REFERENCES `tb_buat_token` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_voter_kode_guru` FOREIGN KEY (`kode_guru_id`) REFERENCES `tb_kode_guru` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tb_vote_log`
  ADD CONSTRAINT `tb_vote_log_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `tb_voter` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_vote_log_ibfk_2` FOREIGN KEY (`nomor_kandidat`) REFERENCES `tb_kandidat` (`nomor_kandidat`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;