-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for untad_lms
CREATE DATABASE IF NOT EXISTS `untad_lms` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `untad_lms`;

-- Dumping structure for table untad_lms.assignments
CREATE TABLE IF NOT EXISTS `assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_mk` varchar(20) DEFAULT NULL,
  `pertemuan_ke` int DEFAULT NULL,
  `judul` varchar(255) DEFAULT NULL,
  `deskripsi` text,
  `due_date` datetime DEFAULT NULL,
  `bobot` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `kode_mk` (`kode_mk`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`kode_mk`) REFERENCES `course_details` (`kode_mk`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table untad_lms.assignments: ~4 rows (approximately)
INSERT INTO `assignments` (`id`, `kode_mk`, `pertemuan_ke`, `judul`, `deskripsi`, `due_date`, `bobot`) VALUES
	(1, 'C789', 5, 'Tugas Laporan Perencanaan Proyek Sistem Informasi', 'Silakan buat laporan berdasarkan studi kasus...', '2026-04-20 23:59:00', 20),
	(2, 'C789', 4, 'Kuis 1 - Pemahaman Dasar', 'Kerjakan kuis pilihan ganda berikut.', '2026-04-18 23:59:00', 10),
	(3, 'C789', 8, 'Ujian Tengah Semester (UTS)', 'Upload lembar jawaban UTS Anda.', '2026-05-10 12:00:00', 30),
	(4, 'C789', 16, 'Ujian Akhir Semester (UAS)', 'Upload project akhir beserta dokumentasinya.', '2026-07-20 23:59:00', 40);

-- Dumping structure for table untad_lms.courses
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_mk` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT 'S1 Sistem Informasi',
  `tema_warna` varchar(20) DEFAULT 'bg-blue',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table untad_lms.courses: ~3 rows (approximately)
INSERT INTO `courses` (`id`, `nama_mk`, `kategori`, `tema_warna`) VALUES
	(1, 'Manajemen Proyek Sistem Informasi D', 'S1 Sistem Informasi', 'bg-pink'),
	(2, 'Big Data', 'S1 Sistem Informasi', 'bg-blue'),
	(3, 'Audit Sistem Informasi', 'S1 Sistem Informasi', 'bg-yellow');

-- Dumping structure for table untad_lms.course_details
CREATE TABLE IF NOT EXISTS `course_details` (
  `kode_mk` varchar(20) NOT NULL,
  `deskripsi` text,
  `pengumuman` varchar(255) DEFAULT NULL,
  `file_rps` varchar(255) DEFAULT NULL,
  `cpl_data` json DEFAULT NULL,
  PRIMARY KEY (`kode_mk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table untad_lms.course_details: ~2 rows (approximately)
INSERT INTO `course_details` (`kode_mk`, `deskripsi`, `pengumuman`, `file_rps`, `cpl_data`) VALUES
	('A123', 'Mata kuliah Pemrograman Web bertujuan untuk membekali mahasiswa dengan keterampilan membangun aplikasi web dinamis menggunakan HTML, CSS, JavaScript, dan PHP. Mahasiswa akan belajar hingga tahap koneksi database dan otentikasi.', 'Tugas Kelompok 2 sudah dibuka di repository!', 'RPS_Pemrograman_Web.docx', '[{"kode": "CPL 01", "deskripsi": "Mampu membangun antarmuka web responsif"}, {"kode": "CPL 02", "deskripsi": "Mampu mengelola state dan session pada web"}]'),
	('B456', 'Mata kuliah ini membahas konsep arsitektur basis data yang tersebar di berbagai jaringan, replikasi data, dan teknik fragmentasi.', 'Materi Instalasi Node/Cluster tersedia.', 'RPS_BD_Terdistribusi.pdf', '[{"kode": "CPL 01", "deskripsi": "Mampu merancang skema data terdistribusi"}]'),
	('C789', 'mnatap', 'tesssss', NULL, NULL);

-- Dumping structure for table untad_lms.submissions
CREATE TABLE IF NOT EXISTS `submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int DEFAULT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `nilai` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `nim` (`nim`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`nim`) REFERENCES `users` (`nim`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table untad_lms.submissions: ~3 rows (approximately)
INSERT INTO `submissions` (`id`, `assignment_id`, `nim`, `file_path`, `submitted_at`, `nilai`) VALUES
	(1, 1, 'F52123021', '1776331011_MODUL 2 (Preprocessing).pdf', '2026-04-16 09:16:51', 70),
	(2, 3, 'F52123021', '1776332234_MODUL 2 (Preprocessing).pdf', '2026-04-16 09:37:14', 100),
	(3, 4, 'F52123021', '1776332249_FORMAT LAPORAN PRAKTIKUM.pdf', '2026-04-16 09:37:29', 90),
	(4, 2, 'F52123021', '1776333955_MODUL 2 (Preprocessing).pdf', '2026-04-16 10:05:55', 10);

-- Dumping structure for table untad_lms.users
CREATE TABLE IF NOT EXISTS `users` (
  `nim` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('dosen','mahasiswa') DEFAULT 'mahasiswa',
  PRIMARY KEY (`nim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table untad_lms.users: ~3 rows (approximately)
INSERT INTO `users` (`nim`, `password`, `nama_lengkap`, `role`) VALUES
	('198001012010121001', '0192023a7bbd73250516f069df18b500', 'Andi Syahkty', 'dosen'),
	('F52123021', '25d55ad283aa400af464c76d713c07ad', 'Syahril Ramadhan', 'mahasiswa'),
	('f52123043', '482c811da5d5b4bc6d497ffa98491e38', 'ANDI SYAHKTY ALIFAH ASSALAM', 'mahasiswa');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
