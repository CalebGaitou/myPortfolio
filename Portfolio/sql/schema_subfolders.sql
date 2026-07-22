-- Lets a category optionally live inside another category (one level of
-- nesting only; enforced in manageModules.php, not at the DB level).

ALTER TABLE `categories`
    ADD COLUMN `parentCategoryID` INT(11) NULL AFTER `categoryID`,
    ADD KEY `parentCategoryID` (`parentCategoryID`),
    ADD CONSTRAINT `categories_parentCategoryID_fk` FOREIGN KEY (`parentCategoryID`) REFERENCES `categories` (`categoryID`) ON DELETE CASCADE;
