-- Salon Booking and Management System
-- MySQL 8+ / XAMPP import file
-- Default administrator: admin / Admin@123

CREATE DATABASE IF NOT EXISTS salon_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE salon_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS product_usage;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS staff_services;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NULL,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Administrator', 'Customer') NOT NULL DEFAULT 'Administrator',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE customers (
    customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(120) NULL UNIQUE,
    gender ENUM('Female', 'Male', 'Other') NOT NULL,
    date_registered DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE users
    ADD CONSTRAINT fk_users_customer
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
    ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE staff (
    staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(120) NULL UNIQUE,
    position VARCHAR(80) NOT NULL,
    status ENUM('Available', 'Unavailable', 'Inactive') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE services (
    service_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_service_duration CHECK (duration_minutes BETWEEN 5 AND 600),
    CONSTRAINT chk_service_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE TABLE staff_services (
    staff_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (staff_id, service_id),
    CONSTRAINT fk_staff_services_staff
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_staff_services_service
        FOREIGN KEY (service_id) REFERENCES services(service_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
    appointment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    staff_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-show') NOT NULL DEFAULT 'Scheduled',
    notes VARCHAR(500) NULL,
    service_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appointments_schedule (staff_id, appointment_date, appointment_time),
    INDEX idx_appointments_customer (customer_id),
    INDEX idx_appointments_status_date (status, appointment_date),
    CONSTRAINT fk_appointments_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_staff
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_service
        FOREIGN KEY (service_id) REFERENCES services(service_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_appointment_price CHECK (service_price >= 0)
) ENGINE=InnoDB;

CREATE TABLE payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNSIGNED NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'EFT') NOT NULL,
    payment_status ENUM('Paid', 'Partially Paid', 'Unpaid') NOT NULL DEFAULT 'Unpaid',
    payment_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_payment_amount CHECK (amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    supplier_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(120) NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    reorder_level INT UNSIGNED NOT NULL DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_supplier (supplier_id, product_name),
    INDEX idx_products_low_stock (quantity, reorder_level),
    CONSTRAINT fk_products_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_product_price CHECK (unit_price >= 0)
) ENGINE=InnoDB;

CREATE TABLE product_usage (
    usage_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NOT NULL,
    quantity_used INT UNSIGNED NOT NULL,
    usage_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usage_appointment (appointment_id),
    CONSTRAINT fk_product_usage_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_product_usage_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_product_usage_quantity CHECK (quantity_used > 0)
) ENGINE=InnoDB;

INSERT INTO users (full_name, username, password_hash, role) VALUES
('System Administrator', 'admin', '$2y$10$IWEr4dTFR9jdSTzbd7KMdeJ9cAG7aj4/XzLd3jdLXO2mjFkTcnzVG', 'Administrator');

INSERT INTO customers (first_name, last_name, phone, email, gender, date_registered) VALUES
('Thandi', 'Mokoena', '0823456710', 'thandi.mokoena@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 90 DAY)),
('Lerato', 'Nkosi', '0834567821', 'lerato.nkosi@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 82 DAY)),
('Sipho', 'Dlamini', '0845678932', 'sipho.dlamini@example.co.za', 'Male', DATE_SUB(CURDATE(), INTERVAL 75 DAY)),
('Nomsa', 'Khumalo', '0766789043', 'nomsa.khumalo@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 68 DAY)),
('Kagiso', 'Molefe', '0717890154', 'kagiso.molefe@example.co.za', 'Male', DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
('Zanele', 'Mthembu', '0728901265', 'zanele.mthembu@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 54 DAY)),
('Bongani', 'Ndlovu', '0739012376', 'bongani.ndlovu@example.co.za', 'Male', DATE_SUB(CURDATE(), INTERVAL 49 DAY)),
('Precious', 'Sithole', '0740123487', 'precious.sithole@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 42 DAY)),
('Mandla', 'Zulu', '0781234598', 'mandla.zulu@example.co.za', 'Male', DATE_SUB(CURDATE(), INTERVAL 35 DAY)),
('Ayanda', 'Mahlangu', '0792345609', 'ayanda.mahlangu@example.co.za', 'Other', DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
('Nokuthula', 'Radebe', '0813456720', 'nokuthula.radebe@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 24 DAY)),
('Tshepo', 'Maseko', '0824567831', 'tshepo.maseko@example.co.za', 'Male', DATE_SUB(CURDATE(), INTERVAL 18 DAY)),
('Karabo', 'Mokoena', '0835678942', 'karabo.mokoena@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
('Faith', 'Mabaso', '0846789053', 'faith.mabaso@example.co.za', 'Female', DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
('Sibusiso', 'Nene', '0767890164', 'sibusiso.nene@example.co.za', 'Male', DATE_SUB(CURDATE(), INTERVAL 4 DAY));

INSERT INTO users (customer_id, full_name, username, password_hash, role)
SELECT customer_id, CONCAT(first_name, ' ', last_name), 'thandi', '$2y$10$mpXt0HEMbYhklLq8Hz38weYdvOSQi5mvVONwEXSeGMqvRe0z1g642', 'Customer'
FROM customers WHERE email = 'thandi.mokoena@example.co.za';

INSERT INTO staff (first_name, last_name, phone, email, position, status) VALUES
('Naledi', 'Petersen', '0821112233', 'naledi.petersen@glowhub.co.za', 'Senior Hair Stylist', 'Available'),
('Mpho', 'Jacobs', '0832223344', 'mpho.jacobs@glowhub.co.za', 'Nail Technician', 'Available'),
('Sizwe', 'Naidoo', '0843334455', 'sizwe.naidoo@glowhub.co.za', 'Barber', 'Available'),
('Keisha', 'Adams', '0764445566', 'keisha.adams@glowhub.co.za', 'Beauty Therapist', 'Available'),
('Anele', 'Mthethwa', '0715556677', 'anele.mthethwa@glowhub.co.za', 'Junior Stylist', 'Unavailable');

INSERT INTO services (service_name, description, duration_minutes, price, status) VALUES
('Ladies Haircut', 'Consultation, wash and precision haircut.', 60, 280.00, 'Active'),
('Gentleman''s Cut', 'Clipper and scissor cut with finish.', 45, 220.00, 'Active'),
('Hair Colouring', 'Single-process colour application and finish.', 150, 850.00, 'Active'),
('Knotless Braiding', 'Medium knotless braids, hair excluded.', 240, 1200.00, 'Active'),
('Gel Manicure', 'Nail shaping, cuticle care and gel polish.', 60, 300.00, 'Active'),
('Spa Pedicure', 'Foot soak, exfoliation and polish.', 75, 380.00, 'Active'),
('Express Facial', 'Cleanse, exfoliate, mask and moisturise.', 60, 450.00, 'Active'),
('Occasion Makeup', 'Event-ready makeup application.', 90, 650.00, 'Active');

INSERT INTO staff_services (staff_id, service_id) VALUES
(1, 1), (1, 3), (1, 4),
(2, 5), (2, 6),
(3, 2),
(4, 7), (4, 8),
(5, 1), (5, 4);

INSERT INTO suppliers (supplier_name, phone, email, address) VALUES
('Jozi Beauty Supplies', '0115550111', 'orders@jozibeauty.co.za', '12 Market Street, Johannesburg, Gauteng'),
('Cape Salon Wholesale', '0215550222', 'sales@capesalon.co.za', '48 Loop Street, Cape Town, Western Cape'),
('Durban Hair & Co', '0315550333', 'info@durbanhair.co.za', '22 Florida Road, Durban, KwaZulu-Natal'),
('Pretoria Professional Beauty', '0125550444', 'hello@pretoriabeauty.co.za', '7 Church Square, Pretoria, Gauteng'),
('Soweto Nails Depot', '0105550555', 'stock@sowetonails.co.za', '18 Vilakazi Street, Soweto, Gauteng');

INSERT INTO products (supplier_id, product_name, category, quantity, reorder_level, unit_price, status) VALUES
(1, 'Hydrating Shampoo 1L', 'Hair Care', 14, 8, 185.00, 'Active'),
(1, 'Colour Developer 1L', 'Hair Colour', 5, 8, 120.00, 'Active'),
(2, 'Moisture Repair Conditioner 1L', 'Hair Care', 10, 6, 205.00, 'Active'),
(2, 'Professional Colour Tubes', 'Hair Colour', 25, 10, 95.00, 'Active'),
(3, 'Braiding Gel 500ml', 'Styling', 7, 8, 75.00, 'Active'),
(3, 'Edge Control 250ml', 'Styling', 18, 6, 65.00, 'Active'),
(4, 'Deep Cleansing Facial Wash', 'Skincare', 9, 5, 110.00, 'Active'),
(4, 'Hydrating Facial Mask', 'Skincare', 4, 6, 85.00, 'Active'),
(5, 'Gel Polish - Nude', 'Nail Care', 22, 8, 72.00, 'Active'),
(5, 'Gel Polish - Berry', 'Nail Care', 6, 8, 72.00, 'Active'),
(5, 'Nail Buffer Pack', 'Nail Care', 30, 10, 45.00, 'Active'),
(1, 'Thermal Protect Spray', 'Hair Care', 11, 5, 95.00, 'Active'),
(2, 'Disposable Towels Pack', 'Salon Supplies', 3, 5, 150.00, 'Active'),
(3, 'Braid Extensions Pack', 'Hair Extensions', 20, 10, 130.00, 'Active'),
(4, 'Makeup Setting Spray', 'Makeup', 8, 5, 140.00, 'Active');

INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, appointment_time, status, notes, service_price) VALUES
(1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 15 DAY), '09:00:00', 'Completed', 'Layered bob trim.', 280.00),
(2, 3, 2, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '10:00:00', 'Completed', 'Low taper requested.', 220.00),
(3, 2, 5, DATE_SUB(CURDATE(), INTERVAL 13 DAY), '11:00:00', 'Completed', 'Natural gel finish.', 300.00),
(4, 4, 7, DATE_SUB(CURDATE(), INTERVAL 12 DAY), '13:00:00', 'Completed', 'Sensitive skin products.', 450.00),
(5, 1, 3, DATE_SUB(CURDATE(), INTERVAL 11 DAY), '09:30:00', 'Completed', 'Warm brown colour.', 850.00),
(6, 2, 6, DATE_SUB(CURDATE(), INTERVAL 10 DAY), '14:00:00', 'Completed', 'French polish.', 380.00),
(7, 1, 4, DATE_SUB(CURDATE(), INTERVAL 9 DAY), '10:00:00', 'Completed', 'Medium length braids.', 1200.00),
(8, 4, 8, DATE_SUB(CURDATE(), INTERVAL 8 DAY), '15:00:00', 'Completed', 'Wedding guest look.', 650.00),
(9, 3, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'Cancelled', 'Customer cancelled by phone.', 220.00),
(10, 1, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '12:00:00', 'No-show', 'No arrival after 20 minutes.', 280.00),
(11, 4, 7, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '10:00:00', 'Completed', 'Brightening facial.', 450.00),
(12, 2, 5, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '11:30:00', 'Completed', 'Clear gel application.', 300.00),
(13, 1, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '13:00:00', 'Completed', 'Root touch-up.', 850.00),
(14, 3, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Completed', 'Classic short back and sides.', 220.00),
(15, 4, 8, CURDATE(), '09:00:00', 'Scheduled', 'Matric dance makeup.', 650.00),
(1, 1, 1, CURDATE(), '11:00:00', 'Scheduled', 'Fringe trim.', 280.00),
(2, 2, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Scheduled', 'No polish preference yet.', 380.00),
(3, 4, 7, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '12:00:00', 'Scheduled', 'First facial consultation.', 450.00),
(4, 1, 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', 'Scheduled', 'Long knotless braids.', 1200.00),
(5, 3, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '15:00:00', 'Scheduled', 'Beard line-up included.', 220.00);

INSERT INTO payments (appointment_id, amount, payment_method, payment_status, payment_date) VALUES
(1, 280.00, 'Card', 'Paid', DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
(2, 220.00, 'Cash', 'Paid', DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(3, 300.00, 'Card', 'Paid', DATE_SUB(CURDATE(), INTERVAL 13 DAY)),
(4, 450.00, 'EFT', 'Paid', DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(5, 850.00, 'Card', 'Paid', DATE_SUB(CURDATE(), INTERVAL 11 DAY)),
(6, 380.00, 'Cash', 'Paid', DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(7, 600.00, 'EFT', 'Partially Paid', DATE_SUB(CURDATE(), INTERVAL 9 DAY)),
(8, 650.00, 'Card', 'Paid', DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
(11, 450.00, 'EFT', 'Paid', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(12, 300.00, 'Cash', 'Paid', DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(13, 850.00, 'Card', 'Paid', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(14, 220.00, 'Cash', 'Paid', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(15, 300.00, 'EFT', 'Partially Paid', CURDATE()),
(16, 280.00, 'Card', 'Paid', CURDATE()),
(17, 0.00, 'Cash', 'Unpaid', NULL);

INSERT INTO product_usage (product_id, appointment_id, quantity_used, usage_date) VALUES
(1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
(9, 3, 1, DATE_SUB(CURDATE(), INTERVAL 13 DAY)),
(7, 4, 1, DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(2, 5, 2, DATE_SUB(CURDATE(), INTERVAL 11 DAY)),
(10, 6, 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(5, 7, 2, DATE_SUB(CURDATE(), INTERVAL 9 DAY)),
(15, 8, 1, DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
(8, 11, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(9, 12, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(4, 13, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(1, 16, 1, CURDATE());

-- 3NF design note: service_price is an appointment-time price snapshot so historical
-- invoices/reports remain correct after a service's current price changes.
USE salon_management;

INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Thandi Mokoena', 'thandi', '$2y$10$rHVRWul3TB/XE3oCDVUo2uxH65dlreCYWgS/u0.QFOmYnwd2INlTm', 'Customer' FROM customers WHERE customer_id = 1 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 1);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Lerato Nkosi', 'lerato.nkosi', '$2y$10$kBcObiwB3wjared4J7Lco.iMOoebYzR1sCH3xNptfH8XUHCtrFCkG', 'Customer' FROM customers WHERE customer_id = 2 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 2);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Sipho Dlamini', 'sipho.dlamini', '$2y$10$BlEqG35odfBsTFJW.LvL2./BKVVipTjwbSBlaj8Xt/RfgiIk.vUz2', 'Customer' FROM customers WHERE customer_id = 3 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 3);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Nomsa Khumalo', 'nomsa.khumalo', '$2y$10$afjXMaaxhoAEM8eyRWLBMu9nBebLAN6tVm2N.IkRXhJd3s.EwSBpm', 'Customer' FROM customers WHERE customer_id = 4 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 4);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Kagiso Molefe', 'kagiso.molefe', '$2y$10$S4ZkKjj6FFeEgQ0fvXUxWeIrhGU9efyfuumBFjyz8iRQAwaHbPZE6', 'Customer' FROM customers WHERE customer_id = 5 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 5);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Zanele Mthembu', 'zanele.mthembu', '$2y$10$0YB4Sk2BPndz8e8RGHr8q.TkretHqn.S6QieDuIGcCL9RQispuDlq', 'Customer' FROM customers WHERE customer_id = 6 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 6);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Bongani Ndlovu', 'bongani.ndlovu', '$2y$10$R3tCz4bogjaLfzFbfFPyyudQMhgY.rAAriEsbASFlFL.DAEzml7Ou', 'Customer' FROM customers WHERE customer_id = 7 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 7);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Precious Sithole', 'precious.sithole', '$2y$10$LdcWwjeVJtuxqUAXvAqr7eeWrC6R69b9Sj4jlVhn4As0vGn5fun9C', 'Customer' FROM customers WHERE customer_id = 8 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 8);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Mandla Zulu', 'mandla.zulu', '$2y$10$jPbqyHe0tUlDZ8DQxv/gIOWDxeFvifEg/Oy9cHbPSgNCKZ1dRi1zm', 'Customer' FROM customers WHERE customer_id = 9 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 9);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Ayanda Mahlangu', 'ayanda.mahlangu', '$2y$10$SMR3XLlwWbRQODre7SrDYesQJu7eH4jYMSPDW0rZ8cVbwFLDrA7IC', 'Customer' FROM customers WHERE customer_id = 10 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 10);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Nokuthula Radebe', 'nokuthula.radebe', '$2y$10$oeeYovtu4tpjqEI9bcaoGeph69l5KsrVOkvJfZe1UCJsDMy1I/OdW', 'Customer' FROM customers WHERE customer_id = 11 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 11);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Tshepo Maseko', 'tshepo.maseko', '$2y$10$0QEakUmOwlZNn5Ew2KFG2eTbv61BPWOjlWNWzj5ACU402oHExRcmi', 'Customer' FROM customers WHERE customer_id = 12 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 12);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Karabo Mokoena', 'karabo.mokoena', '$2y$10$jXhoHzayN8mC8y6pv.BZEuPEQ05ZievNVkXFLoY/LdiLbV8PoYBAq', 'Customer' FROM customers WHERE customer_id = 13 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 13);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Faith Mabaso', 'faith.mabaso', '$2y$10$to1KLJBCjXbCqlMmqQSfc.FJBJRwwcUFRIdqELPQC8m6dtyKwYYoe', 'Customer' FROM customers WHERE customer_id = 14 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 14);
INSERT INTO users (customer_id, full_name, username, password_hash, role) SELECT customer_id, 'Sibusiso Nene', 'sibusiso.nene', '$2y$10$i76xOttEd3/ofoI4nbf5nOlmxAws58q47B9wtczJBX9lnMjv6H..C', 'Customer' FROM customers WHERE customer_id = 15 AND NOT EXISTS (SELECT 1 FROM users WHERE customer_id = 15);
