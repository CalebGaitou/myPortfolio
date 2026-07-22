-- Adds category/module structure, file metadata, and per-visitor file access
-- to support upLoad.php. Existing `users` table is untouched.

CREATE TABLE IF NOT EXISTS `categories` (
    `categoryID` INT(11) NOT NULL AUTO_INCREMENT,
    `yearNumber` TINYINT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `folderPath` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`categoryID`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `files` (
    `fileID` INT(11) NOT NULL AUTO_INCREMENT,
    `categoryID` INT(11) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `storedFilename` VARCHAR(255) NOT NULL,
    `originalFilename` VARCHAR(255) NOT NULL,
    `uploaderUserID` INT(11) NOT NULL,
    `status` ENUM('submitted','approved','rejected') NOT NULL DEFAULT 'submitted',
    `uploadedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`fileID`),
    KEY `categoryID` (`categoryID`),
    KEY `uploaderUserID` (`uploaderUserID`),
    CONSTRAINT `files_categoryID_fk` FOREIGN KEY (`categoryID`) REFERENCES `categories` (`categoryID`) ON DELETE RESTRICT,
    CONSTRAINT `files_uploaderUserID_fk` FOREIGN KEY (`uploaderUserID`) REFERENCES `users` (`userID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `file_access` (
    `fileID` INT(11) NOT NULL,
    `userID` INT(11) NOT NULL,
    PRIMARY KEY (`fileID`, `userID`),
    CONSTRAINT `file_access_fileID_fk` FOREIGN KEY (`fileID`) REFERENCES `files` (`fileID`) ON DELETE CASCADE,
    CONSTRAINT `file_access_userID_fk` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed categories to match the folders that already exist on disk.
INSERT INTO `categories` (`yearNumber`, `name`, `slug`, `folderPath`) VALUES
(NULL, 'Personal', 'personal', 'Files/Personal/'),
(1, 'Web Development', 'webDev', 'Files/Year1/WebDev/'),
(1, 'Project Web Development', 'projectWebDev', 'Files/Year1/ProjectWebDev/'),
(1, 'Database Engineering', 'dBEngineering', 'Files/Year1/DBEngineering/'),
(1, 'Project DB', 'projectDB', 'Files/Year1/ProjectDB/'),
(1, 'OOP 1', 'Oop1', 'Files/Year1/Oop1/'),
(1, 'Project BattleBots', 'projectBattleBots', 'Files/Year1/ProjectBattleBots/');
