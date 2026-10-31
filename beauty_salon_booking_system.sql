-- =============================================================
-- BEAUTY SALON BOOKING SYSTEM - DATABASE SPECIFICATION
-- Module: ICT2213 Web Technologies and Security
-- DBMS: MySQL 8.0+ / MariaDB (phpMyAdmin compatible)
-- =============================================================

CREATE DATABASE IF NOT EXISTS beauty_salon_booking_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE beauty_salon_booking_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS customer_orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS appointment_services;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS staff_services;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS customers;
SET FOREIGN_KEY_CHECKS = 1;

-- Stores registered salon customers.
CREATE TABLE customers (
    customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    address VARCHAR(255) NULL,
    account_status ENUM('Active', 'Inactive', 'Blocked') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Stores employees who perform salon services.
CREATE TABLE staff (
    staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    job_title VARCHAR(80) NOT NULL,
    hire_date DATE NOT NULL,
    staff_status ENUM('Active', 'On Leave', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Catalogue of bookable services.
CREATE TABLE services (
    service_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    price DECIMAL(10,2) UNSIGNED NOT NULL,
    service_status ENUM('Available', 'Unavailable') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_service_duration CHECK (duration_minutes > 0),
    CONSTRAINT chk_service_price CHECK (price >= 0)
) ENGINE=InnoDB;

-- Resolves the many-to-many relationship between staff and services.
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

-- Appointment header. One customer can have several appointments.
CREATE TABLE appointments (
    appointment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    staff_id INT UNSIGNED NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    appointment_status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'No Show')
        NOT NULL DEFAULT 'Pending',
    customer_notes VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appointment_customer (customer_id),
    INDEX idx_appointment_staff_date (staff_id, appointment_date, start_time),
    CONSTRAINT fk_appointments_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_staff
        FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_appointment_time CHECK (end_time > start_time)
) ENGINE=InnoDB;

-- Services selected for each appointment; price is recorded at booking time.
CREATE TABLE appointment_services (
    appointment_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    price_at_booking DECIMAL(10,2) UNSIGNED NOT NULL,
    PRIMARY KEY (appointment_id, service_id),
    CONSTRAINT fk_appointment_services_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_appointment_services_service
        FOREIGN KEY (service_id) REFERENCES services(service_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_booking_price CHECK (price_at_booking >= 0)
) ENGINE=InnoDB;

-- Products offered by the salon.
CREATE TABLE products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    category VARCHAR(80) NOT NULL,
    unit_price DECIMAL(10,2) UNSIGNED NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    reorder_level INT UNSIGNED NOT NULL DEFAULT 5,
    product_status ENUM('Available', 'Out of Stock', 'Discontinued') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_product_price CHECK (unit_price >= 0)
) ENGINE=InnoDB;

-- Product order header. Named customer_orders because ORDER is an SQL keyword.
CREATE TABLE customer_orders (
    order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    order_status ENUM('Pending', 'Confirmed', 'Ready', 'Collected', 'Cancelled')
        NOT NULL DEFAULT 'Pending',
    collection_method ENUM('Salon Pickup', 'Delivery') NOT NULL DEFAULT 'Salon Pickup',
    delivery_address VARCHAR(255) NULL,
    subtotal DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    INDEX idx_order_customer (customer_id),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_order_amounts CHECK (
        subtotal >= 0 AND delivery_fee >= 0 AND total_amount = subtotal + delivery_fee
    )
) ENGINE=InnoDB;

-- Products and quantities belonging to an order.
CREATE TABLE order_items (
    order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) UNSIGNED NOT NULL,
    line_total DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    UNIQUE KEY uq_order_product (order_id, product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES customer_orders(order_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_order_item_quantity CHECK (quantity > 0),
    CONSTRAINT chk_order_item_price CHECK (unit_price >= 0)
) ENGINE=InnoDB;

-- Payments can relate to either an appointment or a product order.
CREATE TABLE payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NULL,
    order_id INT UNSIGNED NULL,
    amount DECIMAL(10,2) UNSIGNED NOT NULL,
    payment_method ENUM('Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Mobile Payment')
        NOT NULL,
    payment_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending',
    transaction_reference VARCHAR(100) NULL UNIQUE,
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_customer (customer_id),
    CONSTRAINT fk_payments_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_appointment
        FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES customer_orders(order_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_payment_amount CHECK (amount > 0),
    CONSTRAINT chk_payment_source CHECK (
        (appointment_id IS NOT NULL AND order_id IS NULL)
        OR (appointment_id IS NULL AND order_id IS NOT NULL)
    )
) ENGINE=InnoDB;

-- =============================================================
-- SAMPLE DATA FOR TESTING AND DEMONSTRATION
-- Password values below are placeholders for secure password hashes.
-- =============================================================

INSERT INTO customers
    (first_name, last_name, email, phone, password_hash, address)
VALUES
    ('Aisha', 'Raman', 'aisha.raman@example.com', '57123456', '$2y$10$exampleHashCustomerOne', 'Triolet, Mauritius'),
    ('Maya', 'Devi', 'maya.devi@example.com', '58123456', '$2y$10$exampleHashCustomerTwo', 'Grand Baie, Mauritius');

INSERT INTO staff
    (first_name, last_name, email, phone, job_title, hire_date)
VALUES
    ('Anjali', 'Sharma', 'anjali.sharma@salon.mu', '59123456', 'Hair Stylist', '2025-01-10'),
    ('Priya', 'Patel', 'priya.patel@salon.mu', '59123457', 'Beauty Therapist', '2025-02-15');

INSERT INTO services
    (service_name, description, duration_minutes, price)
VALUES
    ('Hair Styling', 'Wash and professional hair styling', 60, 900.00),
    ('Facial and Skincare', 'Cleansing and facial skincare treatment', 75, 1200.00),
    ('Nail Care', 'Manicure and nail care treatment', 45, 650.00);

INSERT INTO staff_services (staff_id, service_id)
VALUES (1, 1), (2, 2), (2, 3);

INSERT INTO appointments
    (customer_id, staff_id, appointment_date, start_time, end_time, appointment_status, customer_notes)
VALUES
    (1, 1, '2026-09-10', '10:00:00', '11:00:00', 'Confirmed', 'Customer requested a simple hairstyle.'),
    (2, 2, '2026-09-11', '13:00:00', '13:45:00', 'Pending', NULL);

INSERT INTO appointment_services (appointment_id, service_id, price_at_booking)
VALUES (1, 1, 900.00), (2, 3, 650.00);

INSERT INTO products
    (product_name, description, category, unit_price, stock_quantity, reorder_level)
VALUES
    ('Nourishing Hair Mask', 'Hair treatment for regular salon aftercare', 'Hair Care', 750.00, 20, 5),
    ('Gentle Face Cleanser', 'Daily facial cleanser', 'Skincare', 500.00, 15, 5),
    ('Nail Care Oil', 'Oil for nail and cuticle care', 'Nail Care', 300.00, 25, 8);

INSERT INTO customer_orders
    (customer_id, order_status, collection_method, delivery_address, subtotal, delivery_fee, total_amount)
VALUES
    (1, 'Confirmed', 'Salon Pickup', NULL, 1050.00, 0.00, 1050.00);

INSERT INTO order_items (order_id, product_id, quantity, unit_price)
VALUES (1, 1, 1, 750.00), (1, 3, 1, 300.00);

INSERT INTO payments
    (customer_id, appointment_id, order_id, amount, payment_method, payment_status,
     transaction_reference, payment_date)
VALUES
    (1, 1, NULL, 900.00, 'Credit Card', 'Paid', 'APT-2026-0001', '2026-09-05 10:30:00'),
    (1, NULL, 1, 1050.00, 'Mobile Payment', 'Paid', 'ORD-2026-0001', '2026-09-05 10:35:00');

-- Useful views for salon staff and reporting.
CREATE OR REPLACE VIEW appointment_details AS
SELECT
    a.appointment_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    CONCAT(s.first_name, ' ', s.last_name) AS staff_name,
    sv.service_name,
    a.appointment_date,
    a.start_time,
    a.end_time,
    a.appointment_status,
    aps.price_at_booking
FROM appointments a
JOIN customers c ON c.customer_id = a.customer_id
LEFT JOIN staff s ON s.staff_id = a.staff_id
JOIN appointment_services aps ON aps.appointment_id = a.appointment_id
JOIN services sv ON sv.service_id = aps.service_id;

CREATE OR REPLACE VIEW order_details AS
SELECT
    o.order_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    p.product_name,
    oi.quantity,
    oi.unit_price,
    oi.line_total,
    o.order_status,
    o.total_amount
FROM customer_orders o
JOIN customers c ON c.customer_id = o.customer_id
JOIN order_items oi ON oi.order_id = o.order_id
JOIN products p ON p.product_id = oi.product_id;

-- End of database specification.
