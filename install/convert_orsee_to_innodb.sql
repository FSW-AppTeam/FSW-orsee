-- ORSEE MyISAM to InnoDB migration script.
-- Run this against the ORSEE database after creating a full database backup.
-- This script may run some time, so run it during ORSEE downtime or at least while the public area is deactivated.
-- Existing InnoDB tables are left unchanged.

DROP PROCEDURE IF EXISTS orsee_migrate_myisam_to_innodb;

DELIMITER //

CREATE PROCEDURE orsee_migrate_myisam_to_innodb()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE current_table_name VARCHAR(255);

    DECLARE myisam_tables CURSOR FOR
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_TYPE = 'BASE TABLE'
          AND ENGINE = 'MyISAM'
        ORDER BY TABLE_NAME;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    IF DATABASE() IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No database selected. Select the ORSEE database before running this script.';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'or_http_sessions'
          AND COLUMN_NAME = 'LastUpdated'
    ) THEN
        UPDATE `or_http_sessions`
        SET `LastUpdated` = '1970-01-01 00:00:01'
        WHERE `LastUpdated` < '1000-01-01 00:00:00';

        ALTER TABLE `or_http_sessions`
            MODIFY `LastUpdated` datetime NOT NULL DEFAULT '1970-01-01 00:00:01';
    END IF;

    OPEN myisam_tables;

    convert_loop: LOOP
        FETCH myisam_tables INTO current_table_name;
        IF done = 1 THEN
            LEAVE convert_loop;
        END IF;

        SET @orsee_innodb_sql = CONCAT('ALTER TABLE `', REPLACE(current_table_name, '`', '``'), '` ENGINE=InnoDB');
        SELECT CONCAT('Converting ', current_table_name, ' to InnoDB') AS '';
        PREPARE orsee_innodb_stmt FROM @orsee_innodb_sql;
        EXECUTE orsee_innodb_stmt;
        DEALLOCATE PREPARE orsee_innodb_stmt;
    END LOOP;

    CLOSE myisam_tables;

    SELECT 'Remaining MyISAM tables:' AS '';

    SELECT table_name AS ''
    FROM (
        SELECT TABLE_NAME AS table_name
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_TYPE = 'BASE TABLE'
          AND ENGINE = 'MyISAM'
        UNION ALL
        SELECT 'none' AS table_name
        WHERE NOT EXISTS (
            SELECT 1
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_TYPE = 'BASE TABLE'
              AND ENGINE = 'MyISAM'
        )
    ) AS remaining_myisam_list
    ORDER BY table_name;
END//

DELIMITER ;

CALL orsee_migrate_myisam_to_innodb();

DROP PROCEDURE IF EXISTS orsee_migrate_myisam_to_innodb;
