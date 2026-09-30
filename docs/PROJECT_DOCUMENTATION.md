# GlowHub Salon Management System — Project Documentation

## 1. System overview

GlowHub is a web-based management system for a small salon. It centralizes client information, staff/service assignments, appointment scheduling, payment records and inventory in one relational MySQL database. The interface is intentionally simple and presentation-friendly for a Database Systems project.

## 2. Problem statement

Manual salon records make it difficult to prevent double bookings, trace service revenue, identify unpaid bookings, monitor stock, and create reliable operational reports. The system replaces disconnected paper/spreadsheet records with related tables and real CRUD processes.

## 3. Objectives

1. Store accurate customer, staff, service, booking, payment, supplier and product records.
2. Demonstrate primary keys, foreign keys, 1:M, M:N and 1:1 relationships.
3. Prevent invalid bookings and double bookings.
4. Automate price lookup, payment calculations, low-stock detection and dashboard totals.
5. Produce useful, filtered reports from real MySQL data.
6. Apply normalization through Third Normal Form (3NF).

## 4. Functional requirements

- Administrator authentication and protected pages.
- CRUD plus search for all principal entities.
- Customer booking history and staff-service assignments.
- Appointment creation with customer, service, staff, date, time, status and notes.
- Payment recording linked to exactly one appointment.
- Product stock adjustment and product-usage recording per appointment.
- Six live reports and a restricted table-management section.

## 5. Non-functional requirements

| Area | Implementation |
| --- | --- |
| Usability | Bootstrap 5 cards, tables, forms, clear navigation and readable feedback messages. |
| Reliability | Foreign keys, MySQL validation constraints, transactions for product usage, and server-side validation. |
| Security | PDO prepared statements, output escaping, CSRF tokens, sessions, password hashing and administrator authorization. |
| Portability | PHP 8+, MySQL and XAMPP; no PHP framework or Node/React dependency. |
| Maintainability | Shared helpers and reusable CRUD handlers reduce duplicate beginner-level code. |

## 6. ER diagram description

The ERD is documented in [ERD.md](ERD.md). `appointments` is the operational hub: each appointment belongs to one customer, staff member and service. `staff_services` resolves the M:N Staff–Services relationship. `product_usage` resolves product consumption per appointment.

## 7–9. Database tables, keys and relationships

| Table | Primary key | Important foreign keys | Purpose |
| --- | --- | --- | --- |
| `users` | `user_id` | — | Password-hashed administrator account. |
| `customers` | `customer_id` | — | Customer contact and registration details. |
| `staff` | `staff_id` | — | Staff contact, position and availability. |
| `services` | `service_id` | — | Service description, duration, current price and active state. |
| `staff_services` | `staff_id`, `service_id` | Both keys reference parent tables. | Composite-key junction table for service skills. |
| `appointments` | `appointment_id` | `customer_id`, `staff_id`, `service_id` | Booking details and historical service-price snapshot. |
| `payments` | `payment_id` | `appointment_id` (UNIQUE) | One optional payment record per appointment. |
| `suppliers` | `supplier_id` | — | Product supplier details. |
| `products` | `product_id` | `supplier_id` | Quantity, reorder level and unit price. |
| `product_usage` | `usage_id` | `product_id`, `appointment_id` | Stock consumed during an appointment. |

Relationship summary:

- Customer **1:M** Appointments
- Staff **1:M** Appointments
- Service **1:M** Appointments
- Staff **M:N** Services through `staff_services`
- Appointment **1:0..1** Payment (`payments.appointment_id` is unique)
- Supplier **1:M** Products
- Appointment **1:M** Product Usage
- Product **1:M** Product Usage

## 10. Normalization

A worked UNF → 1NF → 2NF → 3NF example is in [NORMALIZATION.md](NORMALIZATION.md). The final design separates non-key facts into Customers, Staff, Services, Suppliers, Products and appointment/payment/usage tables, eliminating repeating groups and transitive dependencies.

## 11. System architecture

```text
Browser (Bootstrap 5 / HTML / small JavaScript)
                │ HTTP session and CSRF-protected forms
                ▼
PHP 8 application pages and shared handlers
                │ PDO prepared statements
                ▼
MySQL `salon_management` database
```

`config/database.php` owns the PDO connection. `includes/auth.php` owns authentication and session guards. Reusable module handlers perform validation and CRUD. MySQL owns relationships, constraints and persisted data.

## 12. CRUD operations

Customers, Staff, Services, Appointments, Payments, Suppliers and Products all have create, read/list/search, update and delete flows. Delete actions submit POST forms, require CSRF tokens, show a browser confirmation, and return a friendly success/error message. Customer and appointment detail pages provide related-history reads.

## 13. Automation processes

| Process | Implementation |
| --- | --- |
| Staff availability | New booking validation only accepts staff with `Available` status. |
| Staff/service qualification | Booking checks `staff_services` before saving. |
| Double booking prevention | A prepared query checks the staff/date/time combination excluding cancelled bookings. |
| Service price | The server reads `services.price`; client input is never trusted. A price snapshot is stored on the appointment. |
| Payment amount | A `Paid` record automatically uses the appointment charge; partial values are validated below the charge. |
| Unpaid identification | Outstanding report uses a left join to find missing, unpaid or partial payment records. |
| Low stock | Dashboard, product list and report flag `quantity <= reorder_level`. |
| Appointment status | Past unattended `Scheduled` appointments are automatically set to `No-show` when dashboard/appointment pages load. |
| Dashboard totals | Summary queries calculate counts and revenue from current MySQL records. |

## 14. Reports

1. **Appointment Report:** appointment/customer/service/staff/date/time/status; filters by date and status.
2. **Revenue Report:** payment, appointment, customer, amount, method and date; date-range filter and total.
3. **Staff Performance Report:** total/completed/cancelled appointments and revenue by staff member.
4. **Popular Services Report:** bookings and collected revenue, sorted by number of bookings.
5. **Outstanding Payments Report:** unpaid and partially paid appointments with remaining balance.
6. **Low Stock Report:** products at/below reorder level with supplier contact details.

All report pages use clean tables and include a print action with print-specific styling.

## 15. Security measures

- PDO prepared statements for values supplied by users.
- HTML escaping via `htmlspecialchars()` for output.
- CSRF token required on every state-changing form.
- PHP session cookie configured as HTTP-only and SameSite Lax.
- `password_hash()` value stored in the database and `password_verify()` used at sign-in.
- Session ID regeneration after successful sign-in.
- `require_login()` protects application pages; `require_admin()` protects table management.
- Identifier validation and a safe type allow-list protect dynamic table/column management.
- Friendly database errors avoid showing raw SQL errors to normal users.

## 16. Testing

See [TESTING.md](TESTING.md). PHP syntax checking is included in the repository validation process; the scenario table provides repeatable XAMPP/MySQL tests for all principal processes.

## 17. Conclusion

The system meets the project objective by combining a normalized MySQL database with a functional PHP user interface. It demonstrates relational design, real CRUD, validation, automated business rules, inventory control, reporting and secure administrator access without unnecessary framework complexity.
