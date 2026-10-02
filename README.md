# GlowHub Salon Booking and Management System

A university **Database Systems** project built with **PHP 8+, MySQL, XAMPP, HTML5, CSS3, Bootstrap 5, and JavaScript**. It manages salon customers, staff, services, bookings, payments, suppliers, inventory, reports, and database-table administration.

> The system uses PHP PDO prepared statements, password-hashed administrator authentication, server-side validation, CSRF tokens, escaped output, and MySQL foreign keys.

## Quick start with XAMPP

1. Install and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy this complete folder to `C:\xampp\htdocs\salon_management`.
3. Open **phpMyAdmin** at `http://localhost/phpmyadmin`.
4. Select **Import**, choose `database/salon_management.sql`, then click **Import**.
   - The file creates the `salon_management` database, all tables, relationships, constraints, and sample data.
5. Confirm `config/database.php` matches local MySQL settings. The XAMPP defaults are already configured:
   ```php
   DB_HOST = 'localhost'
   DB_NAME = 'salon_management'
   DB_USER = 'root'
   DB_PASS = ''
   ```
6. Browse to [http://localhost/salon_management/](http://localhost/salon_management/).

If you already imported an older version of this project, back up the database and run `database/migrate_customer_login.sql` in phpMyAdmin instead of dropping your existing data. New installations should import the complete `database/salon_management.sql` file.

## Default administrator login

| Field | Value |
| --- | --- |
| Username | `admin` |
| Password | `Admin@123` |
| Role | Administrator |

The SQL file stores a `password_hash()` bcrypt hash, **not** the plaintext password.

### Sample customer login

| Field | Value |
| --- | --- |
| Username | `thandi` |
| Password | `Customer@123` |
| Role | Customer |

## Sample database content

| Entity | Provided sample records |
| --- | ---: |
| Customers | 15 |
| Staff members | 5 |
| Services | 8 |
| Appointments | 20 |
| Payments | 15 |
| Suppliers | 5 |
| Products | 15 |

The sample data uses realistic South African names, phone numbers, and Rand prices.

## Implemented features

- **Dashboard:** totals, revenue, today’s appointments, upcoming bookings, recent payments, and low-stock alerts.
- **Unified role-based login:** administrators use the management dashboard; customers can create an account, sign in through the same page, book appointments, review appointment history, and cancel future scheduled visits.
- **Customer self-service:** customers register at `/register.php`, use the same login screen as administrators, and are automatically routed to a customer-only portal.
- **Customer management:** add, search, edit, delete, and view full booking/payment history.
- **Staff management:** add, search, edit, delete, change availability, and assign services through the `staff_services` M:N table.
- **Service management:** activate/deactivate services, set duration and Rand price.
- **Appointments:** selection of customer/service/staff/date/time/notes; automatic price retrieval; staff-service eligibility; double-booking prevention; Scheduled, Completed, Cancelled and No-show statuses.
- **Payments:** record, edit, search, delete, full/partial/unpaid states, Cash/Card/EFT methods, and automatic charge lookup.
- **Inventory:** supplier-linked product CRUD, stock additions, appointment product-usage records, automatic stock deduction, and low-stock alerts.
- **Reports:** Appointment, Revenue, Staff Performance, Popular Services, Outstanding Payments and Low Stock; report pages are print-friendly.
- **Table management:** administrators can view tables/structures, create tables, add columns, remove non-primary-key columns, and delete tables after confirmation.

## Project structure

```text
salon_management/
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── config/database.php
├── database/
│   ├── salon_management.sql
│   ├── index.php
│   ├── create_table.php
│   ├── structure.php
│   ├── delete_table.php
│   └── delete_column.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── header.php / sidebar.php / footer.php
│   ├── master_module.php
│   ├── appointment_module.php
│   ├── payment_module.php
│   ├── report_nav.php
│   └── table_admin.php
├── customers/  staff/  services/  suppliers/
├── appointments/  payments/  products/  reports/
├── docs/
│   ├── PROJECT_DOCUMENTATION.md
│   ├── ERD.md
│   ├── NORMALIZATION.md
│   └── TESTING.md
├── index.php
├── login.php
└── logout.php
```

## Validation and test instructions

1. Import the SQL file into an empty/local `salon_management` database.
2. Log in and confirm the dashboard cards show sample data.
3. Add a customer and search for that customer.
4. Try creating two active appointments for the same staff member/date/time; the second booking must display **“That staff member is already booked for this time.”**
5. Record a payment and confirm the appointment charge comes from the selected appointment/service.
6. Record product usage and confirm stock decreases; use a low-stock product to verify the alert/report.
7. Open each report and apply the available filters, then use **Print report**.
8. See [docs/TESTING.md](docs/TESTING.md) for the full test-case table.

## Additional project documentation

- [System documentation](docs/PROJECT_DOCUMENTATION.md)
- [ER diagram specification](docs/ERD.md)
- [UNF to 3NF normalization walkthrough](docs/NORMALIZATION.md)
- [Test cases and verification instructions](docs/TESTING.md)
