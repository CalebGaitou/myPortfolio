-- A file entry is now either a real uploaded file OR an external link, not
-- both. storedFilename/originalFilename become nullable (null for a link
-- entry); linkUrl is null for a real file entry. Enforced in upLoad.php,
-- not at the DB level.

ALTER TABLE `files`
    MODIFY `storedFilename` VARCHAR(255) NULL,
    MODIFY `originalFilename` VARCHAR(255) NULL,
    ADD COLUMN `linkUrl` VARCHAR(500) NULL AFTER `originalFilename`;