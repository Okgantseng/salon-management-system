-- One-time migration for an existing salon_management database created before customer login.
-- Back up the database first. For a fresh installation, use salon_management.sql instead.
USE salon_management;

ALTER TABLE users
    ADD COLUMN customer_id INT UNSIGNED NULL AFTER user_id;

ALTER TABLE users
    MODIFY role ENUM('Administrator', 'Customer') NOT NULL DEFAULT 'Administrator';

ALTER TABLE users
    ADD CONSTRAINT fk_users_customer
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- Customer accounts are normally created through register.php so passwords are hashed.
-- To create an account, open http://localhost/salon_management/register.php after migration.
