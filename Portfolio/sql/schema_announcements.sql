-- A single "current announcement" board. Only ever holds one row in practice:
-- posting a new announcement deletes the old one (enforced in postAnnouncement.php,
-- not at the DB level). The 4-day auto-expiry is enforced by the SELECT that reads
-- it (WHERE postedAt >= NOW() - INTERVAL 4 DAY), not by a scheduled job.

CREATE TABLE IF NOT EXISTS `announcements` (
    `announcementID` INT(11) NOT NULL AUTO_INCREMENT,
    `message` TEXT NOT NULL,
    `postedByUserID` INT(11) NOT NULL,
    `postedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`announcementID`),
    KEY `postedByUserID` (`postedByUserID`),
    CONSTRAINT `announcements_postedByUserID_fk` FOREIGN KEY (`postedByUserID`) REFERENCES `users` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
