-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2026 at 12:50 PM
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
-- Database: `rock_music`
--

-- --------------------------------------------------------

--
-- Table structure for table `kolekcija`
--

CREATE TABLE `kolekcija` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `playlist_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `dodano` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kolekcija`
--

INSERT INTO `kolekcija` (`id`, `user_id`, `playlist_id`, `song_id`, `dodano`) VALUES
(68, 2, 3, 44, '2026-05-24 10:42:35'),
(69, 2, 8, 14, '2026-05-24 10:43:15'),
(70, 2, 8, 5, '2026-05-24 10:43:32'),
(71, 2, 8, 37, '2026-05-24 10:43:42'),
(72, 2, 8, 13, '2026-05-24 10:43:44'),
(73, 2, 8, 25, '2026-05-24 10:43:48'),
(74, 2, 8, 35, '2026-05-24 10:43:52'),
(75, 2, 8, 3, '2026-05-24 10:43:57'),
(77, 2, 8, 24, '2026-05-24 10:44:00'),
(78, 2, 8, 8, '2026-05-24 10:44:03');

-- --------------------------------------------------------

--
-- Table structure for table `ocjene`
--

CREATE TABLE `ocjene` (
  `id` int(11) NOT NULL,
  `id_korisnik` int(11) NOT NULL,
  `id_slika` int(11) NOT NULL,
  `ocjena` tinyint(4) NOT NULL CHECK (`ocjena` between 1 and 5),
  `vrijeme_ocjene` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ocjene`
--

INSERT INTO `ocjene` (`id`, `id_korisnik`, `id_slika`, `ocjena`, `vrijeme_ocjene`) VALUES
(1, 2, 1, 4, '2026-05-24 10:33:52'),
(12, 3, 1, 5, '2026-05-24 10:35:15'),
(13, 3, 2, 3, '2026-05-24 10:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `naziv` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `kreirana` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`id`, `user_id`, `naziv`, `is_default`, `kreirana`) VALUES
(3, 2, 'My Playlist', 1, '2026-05-21 10:27:26'),
(7, 3, 'My Playlist', 1, '2026-05-24 10:35:11'),
(8, 2, 'High Adrenalin', 0, '2026-05-24 10:43:10');

-- --------------------------------------------------------

--
-- Table structure for table `slike`
--

CREATE TABLE `slike` (
  `id` int(11) NOT NULL,
  `naziv_datoteke` varchar(255) NOT NULL,
  `opis` varchar(500) DEFAULT NULL,
  `putanja` varchar(500) NOT NULL,
  `izvor` enum('lokalno','api') DEFAULT 'lokalno',
  `dodano` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slike`
--

INSERT INTO `slike` (`id`, `naziv_datoteke`, `opis`, `putanja`, `izvor`, `dodano`) VALUES
(1, 'img_6a12d08ad4d375.82303870.jpg', 'The Beatles, Queen, Led Zeppelin', 'img/gallery/img_6a12d08ad4d375.82303870.jpg', 'lokalno', '2026-05-24 10:18:50'),
(2, 'img_6a12d50af3e806.06584409.jpg', 'Rockband set', 'img/gallery/img_6a12d50af3e806.06584409.jpg', 'lokalno', '2026-05-24 10:38:03');

-- --------------------------------------------------------

--
-- Table structure for table `songs`
--

CREATE TABLE `songs` (
  `id` int(11) NOT NULL,
  `ext_id` int(11) NOT NULL,
  `naslov` varchar(120) NOT NULL,
  `izvodac` varchar(100) NOT NULL,
  `zanr` varchar(60) NOT NULL,
  `bpm` int(11) NOT NULL,
  `godina` int(11) NOT NULL,
  `popularnost` decimal(3,1) NOT NULL,
  `raspolozenje` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `songs`
--

INSERT INTO `songs` (`id`, `ext_id`, `naslov`, `izvodac`, `zanr`, `bpm`, `godina`, `popularnost`, `raspolozenje`) VALUES
(1, 2, 'Bohemian Rhapsody', 'Queen', 'Rock', 72, 1975, 4.9, 'Dramatic'),
(2, 5, 'Hotel California', 'Eagles', 'Rock', 74, 1976, 4.8, 'Melancholic'),
(3, 7, 'Thunderstruck', 'AC/DC', 'Hard Rock', 134, 1990, 4.7, 'Powerful'),
(4, 9, 'Smells Like Teen Spirit', 'Nirvana', 'Grunge', 117, 1991, 4.9, 'Aggressive'),
(5, 10, 'In the End', 'Linkin Park', 'Nu Metal', 105, 2000, 4.8, 'Emotional'),
(6, 12, 'Don\'t Stop Believin\'', 'Journey', 'Rock', 119, 1981, 4.6, 'Inspirational'),
(7, 17, 'Dreams', 'Fleetwood Mac', 'Soft Rock', 120, 1977, 4.8, 'Dreamy'),
(8, 18, 'Master of Puppets', 'Metallica', 'Thrash Metal', 212, 1986, 4.9, 'Intense'),
(9, 21, 'Mr. Brightside', 'The Killers', 'Indie Rock', 148, 2004, 4.7, 'Anthemic'),
(10, 23, 'Another One Bites the Dust', 'Queen', 'Funk Rock', 110, 1980, 4.7, 'Confident'),
(11, 25, 'Highway to Hell', 'AC/DC', 'Hard Rock', 116, 1979, 4.8, 'Wild'),
(12, 26, 'Sweet Child O\' Mine', 'Guns N\' Roses', 'Hard Rock', 125, 1987, 4.9, 'Romantic'),
(13, 28, 'Seven Nation Army', 'The White Stripes', 'Garage Rock', 124, 2003, 4.7, 'Gritty'),
(14, 31, 'Back in Black', 'AC/DC', 'Hard Rock', 94, 1980, 4.9, 'Powerful'),
(15, 32, 'Paranoid', 'Black Sabbath', 'Heavy Metal', 164, 1970, 4.8, 'Dark'),
(16, 33, 'Enter Sandman', 'Metallica', 'Heavy Metal', 123, 1991, 4.8, 'Intense'),
(17, 34, 'Everlong', 'Foo Fighters', 'Alternative Rock', 158, 1997, 4.7, 'Emotional'),
(18, 35, 'Californication', 'Red Hot Chili Peppers', 'Alternative Rock', 96, 1999, 4.8, 'Laid-back'),
(19, 36, 'Kashmir', 'Led Zeppelin', 'Rock', 83, 1975, 4.9, 'Majestic'),
(20, 37, 'Paint It Black', 'The Rolling Stones', 'Rock', 160, 1966, 4.8, 'Dark'),
(21, 38, 'Livin\' on a Prayer', 'Bon Jovi', 'Rock', 123, 1986, 4.8, 'Anthemic'),
(22, 39, 'The Pretender', 'Foo Fighters', 'Alternative Rock', 173, 2007, 4.7, 'Intense'),
(23, 40, 'Boulevard of Broken Dreams', 'Green Day', 'Alternative Rock', 84, 2004, 4.6, 'Melancholic'),
(24, 41, 'Holiday', 'Green Day', 'Punk Rock', 150, 2004, 4.5, 'Energetic'),
(25, 42, 'Chop Suey!', 'System of a Down', 'Alternative Metal', 127, 2001, 4.9, 'Chaotic'),
(26, 43, 'Welcome to the Jungle', 'Guns N\' Roses', 'Hard Rock', 123, 1987, 4.9, 'Aggressive'),
(27, 44, 'Black Hole Sun', 'Soundgarden', 'Grunge', 104, 1994, 4.7, 'Moody'),
(28, 45, 'Come As You Are', 'Nirvana', 'Grunge', 120, 1991, 4.8, 'Reflective'),
(29, 46, 'Iron Man', 'Black Sabbath', 'Heavy Metal', 157, 1970, 4.8, 'Heavy'),
(30, 47, 'Wonderwall', 'Oasis', 'Britpop', 87, 1995, 4.8, 'Melancholic'),
(31, 48, 'Take Me Out', 'Franz Ferdinand', 'Indie Rock', 105, 2004, 4.6, 'Energetic'),
(32, 49, 'Creep', 'Radiohead', 'Alternative Rock', 92, 1992, 4.9, 'Emotional'),
(33, 50, 'Karma Police', 'Radiohead', 'Alternative Rock', 75, 1997, 4.7, 'Reflective'),
(34, 51, 'Lithium', 'Nirvana', 'Grunge', 124, 1991, 4.6, 'Gritty'),
(35, 52, 'Song 2', 'Blur', 'Britpop', 130, 1997, 4.5, 'Wild'),
(36, 53, 'Otherside', 'Red Hot Chili Peppers', 'Alternative Rock', 125, 1999, 4.7, 'Emotional'),
(37, 54, 'Supermassive Black Hole', 'Muse', 'Alternative Rock', 119, 2006, 4.6, 'Intense'),
(38, 55, 'Use Somebody', 'Kings of Leon', 'Indie Rock', 140, 2008, 4.7, 'Inspiring'),
(39, 56, 'Starlight', 'Muse', 'Alternative Rock', 121, 2006, 4.6, 'Dreamy'),
(40, 57, 'Paranoid Android', 'Radiohead', 'Progressive Rock', 80, 1997, 4.8, 'Complex'),
(41, 58, 'No Surprises', 'Radiohead', 'Alternative Rock', 76, 1997, 4.5, 'Mellow'),
(42, 59, 'Iris', 'Goo Goo Dolls', 'Alternative Rock', 76, 1998, 4.8, 'Romantic'),
(43, 60, 'Numb', 'Linkin Park', 'Nu Metal', 110, 2003, 4.9, 'Angsty'),
(44, 61, 'Zombie', 'The Cranberries', 'Alternative Rock', 84, 1994, 4.8, 'Powerful'),
(45, 62, 'Stairway to Heaven', 'Led Zeppelin', 'Classic Rock', 72, 1971, 5.0, 'Epic'),
(46, 63, 'Born to Run', 'Bruce Springsteen', 'Rock', 147, 1975, 4.8, 'Energetic'),
(47, 64, 'Purple Haze', 'Jimi Hendrix', 'Psychedelic Rock', 105, 1967, 4.9, 'Wild'),
(48, 65, 'Comfortably Numb', 'Pink Floyd', 'Progressive Rock', 128, 1979, 5.0, 'Dreamy');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Pero', 'pero.peric@gmail.com', '$2y$10$yZE1ZygEutMD57NW03yqqOioIdBOlh8yNurLk0g8FFwtXSVEfQpl2', 'user', '2026-05-11 12:19:34'),
(2, 'FKampic', 'filipkampic@gmail.com', '$2y$10$VCsg93ZK4FvBldLLcHTcXOUO6OvWSMEqvQpT39..KcfVsQP2BiW8G', 'user', '2026-05-20 07:04:59'),
(3, 'Ivan', 'ivan.ivankovic@gmail.com', '$2y$10$bMPaumUqOQYWDaJoVDP2BOPQxVDFMVnDntSTB/jIECJIl1Jw4s4BS', 'user', '2026-05-24 10:34:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kolekcija`
--
ALTER TABLE `kolekcija`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`song_id`);

--
-- Indexes for table `ocjene`
--
ALTER TABLE `ocjene`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_slika` (`id_korisnik`,`id_slika`),
  ADD KEY `id_slika` (`id_slika`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `slike`
--
ALTER TABLE `slike`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `songs`
--
ALTER TABLE `songs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kolekcija`
--
ALTER TABLE `kolekcija`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `ocjene`
--
ALTER TABLE `ocjene`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `slike`
--
ALTER TABLE `slike`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `songs`
--
ALTER TABLE `songs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ocjene`
--
ALTER TABLE `ocjene`
  ADD CONSTRAINT `ocjene_ibfk_1` FOREIGN KEY (`id_korisnik`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ocjene_ibfk_2` FOREIGN KEY (`id_slika`) REFERENCES `slike` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
