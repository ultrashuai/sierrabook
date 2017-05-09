CREATE TABLE IF NOT EXISTS SierraAddressBook (
    entryID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    firstName NVARCHAR(255) NULL,
    lastName NVARCHAR(255) NOT NULL,
    title NVARCHAR(255) NULL,
    companyName NVARCHAR(255) NULL,
    createDate DATETIME NOT NULL,
    updateDate DATETIME NULL
);

CREATE TABLE IF NOT EXISTS SierraAddressBookAddress (
    addressID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	entryID INT NOT NULL,
    addressType ENUM('Home', 'Office', 'Temporary', 'Other') NOT NULL,
    address NVARCHAR(255) NOT NULL,
    address2 NVARCHAR(255) NULL,
    addressCity NVARCHAR(255) NULL,
    addressState CHAR(2) NULL,
    addressZip NVARCHAR(15) NULL,
    addressCountry NVARCHAR(255) NULL,
	isPrimary BIT NOT NULL DEFAULT 0,
    createDate DATETIME NOT NULL,
    updateDate DATETIME NULL,
    FOREIGN KEY(entryID) REFERENCES SierraAddressBook(entryID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS SierraAddressBookPhone (
	phoneID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	entryID INT NOT NULL,
	phoneType ENUM('Home', 'Work', 'Mobile', 'Fax', 'Other') NOT NULL,
	phoneNumber NVARCHAR(255) NOT NULL,
	isPrimary BIT NOT NULL DEFAULT 0,
	createDate DATETIME NOT NULL,
	updateDate DATETIME NULL,
	FOREIGN KEY(entryID) REFERENCES SierraAddressBook(entryID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS SierraAddressBookEmail (
	emailID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	entryID INT NOT NULL,
	emailType ENUM('Personal', 'Work', 'Other') NOT NULL,
	emailAddress NVARCHAR(255) NOT NULL,
	isPrimary BIT NOT NULL DEFAULT 0,
	createDate DATETIME NOT NULL,
	updateDate DATETIME NULL,
	FOREIGN KEY(entryID) REFERENCES SierraAddressBook(entryID) ON DELETE CASCADE
);

CREATE OR REPLACE VIEW vwSierraAddressBookEntries AS (
	SELECT b.*, CONCAT(firstName, ' ', lastName) AS fullName, a.address AS primaryAddress, p.phoneNumber AS primaryPhoneNumber, CONCAT(p.phoneNumber, ' (', p.phoneType, ')') AS phoneNumberWithType, e.emailAddress AS primaryEmailAddress, CONCAT(e.emailAddress, ' (', e.emailType, ')') AS emailAddressWithType
	FROM SierraAddressBook b
	LEFT JOIN SierraAddressBookAddress a ON a.entryID = b.entryID AND a.isPrimary = 1
	LEFT JOIN SierraAddressBookPhone p ON p.entryID = b.entryID AND p.isPrimary = 1
	LEFT JOIN SierraAddressBookEmail e ON e.entryID = b.entryID AND e.isPrimary = 1
);

DELIMITER //
DROP PROCEDURE IF EXISTS spSierraAddressBookDetails//
CREATE PROCEDURE spSierraAddressBookDetails (IN entryID INT)
BEGIN
	SELECT * FROM SierraAddressBook WHERE entryID = entryID;
	SELECT * FROM SierraAddressBookAddress WHERE entryID = entryID ORDER BY isPrimary DESC;
	SELECT * FROM SierraAddressBookPhone WHERE entryID = entryID ORDER BY isPrimary DESC;
	SELECT * FROM SierraAddressBookEmail WHERE entryID = entryID ORDER BY isPrimary DESC;
END //

DROP PROCEDURE IF EXISTS spSierraAddressBookInsert//
CREATE PROCEDURE spSierraAddressBookInsert
(IN firstName NVARCHAR(255), IN lastName NVARCHAR(255), IN title NVARCHAR(255), IN companyName NVARCHAR(255),
	IN phoneType NVARCHAR(255), IN phoneNumber NVARCHAR(255), IN addressType NVARCHAR(255), IN address NVARCHAR(255), IN address2 NVARCHAR(255),
	IN addressCity NVARCHAR(255), IN addressState NCHAR(2), IN addressZip NVARCHAR(15), IN addressCountry NVARCHAR(255),
	IN emailType NVARCHAR(255), IN emailAddress NVARCHAR(255),
	OUT entryID INT)
BEGIN
START TRANSACTION;
	INSERT INTO SierraAddressBook (firstName, lastName, title, companyName, createDate)
	VALUES(firstName, lastName, title, companyName, NOW());
	SET entryID = LAST_INSERT_ID();
	IF address IS NOT NULL THEN
		INSERT INTO SierraAddressBookAddress (entryID, addressType, address, address2, addressCity, addressState, addressZip, addressCountry, isPrimary, createDate) VALUES(entryID, addressType, address, address2, addressCity, addressState, addressZip, addressCountry, 1, NOW());
	END IF;
	IF phoneNumber IS NOT NULL THEN
		INSERT INTO SierraAddressBookPhone (entryID, phoneType, phoneNumber, isPrimary, createDate) VALUES(entryID, phoneType, phoneNumber, 1, NOW());
	END IF;
	IF emailAddress IS NOT NULL THEN
		INSERT INTO SierraAddressBookEmail (entryID, emailType, emailAddress, isPrimary, createDate) VALUES(entryID, emailType, emailAddress, 1, NOW());
	END IF;
COMMIT;
END //

DROP PROCEDURE IF EXISTS spSierraAddressBookUpdate//
CREATE PROCEDURE spSierraAddressBookUpdate
(IN entryID INT, IN firstName NVARCHAR(255), IN lastName NVARCHAR(255), IN title NVARCHAR(255), IN companyName NVARCHAR(255))
BEGIN
	UPDATE SierraAddressBook SET firstName = firstName, lastName = lastName, title = title, companyName = companyName
	WHERE entryID = entryID;
END //

DROP PROCEDURE IF EXISTS spSierraAddressBookInsertAddress//
CREATE PROCEDURE spSierraAddressBookInsertAddress
(IN entryID INT, IN addressType NVARCHAR(255), IN address NVARCHAR(255), IN address2 NVARCHAR(255),
	IN addressCity NVARCHAR(255), IN addressState NCHAR(2), IN addressZip NVARCHAR(15), IN addressCountry NVARCHAR(255), IN isPrimary BIT)
BEGIN
		INSERT INTO SierraAddressBookAddress (entryID, addressType, address, addressCity, addressState, addressZip, addressCountry, isPrimary, createDate) VALUES(entryID, addressType, address, address2, addressCity, addressState, addressZip, addressCountry, isPrimary, NOW());
END //

DROP PROCEDURE IF EXISTS spSierraAddressBookInsertPhone//
CREATE PROCEDURE spSierraAddressBookInsertPhone
(IN entryID INT, IN phoneType NVARCHAR(255), IN phoneNumber NVARCHAR(255), IN isPrimary BIT)
BEGIN
		INSERT INTO SierraAddressBookPhone (entryID, phoneType, phoneNumber, isPrimary, createDate) VALUES(entryID, phoneType, phoneNumber, isPrimary, NOW());
END //

DROP PROCEDURE IF EXISTS spSierraAddressBookInsertEmail//
CREATE PROCEDURE spSierraAddressBookInsertEmail
(IN entryID INT, IN emailType NVARCHAR(255), IN emailAddress NVARCHAR(255), IN isPrimary BIT)
BEGIN
	INSERT INTO SierraAddressBookEmail (entryID, emailType, emailAddress, isPrimary, createDate) VALUES(entryID, emailType, emailAddress, isPrimary, NOW());
END //

DELIMITER ;
