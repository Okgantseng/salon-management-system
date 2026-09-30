# Testing Plan and Test Cases

## How to run the tests

1. Follow the XAMPP import instructions in the root [README](../README.md).
2. Sign in as `admin` / `Admin@123`.
3. Run each case below. Capture a screenshot or note the displayed message for the project presentation.
4. Restore the sample database by re-importing `database/salon_management.sql` if a destructive test changes the data.

> **Build validation note:** PHP 8.3 syntax linting completed successfully for all PHP files. The SQL file was also imported successfully into MySQL 8.0.46, confirming all tables and the requested sample counts. An authenticated HTTP smoke test successfully loaded the login, dashboard, appointment-booking, and revenue-report pages. Scenario cases not exercised against the live UI remain marked **Pending XAMPP execution** rather than fabricated.

| ID | Test case | Steps | Expected result | Actual result | Status |
| --- | --- | --- | --- | --- | --- |
| TC01 | Add customer | Customers → Add Customer; enter all required fields with a unique SA phone; save. | Friendly “Customer added successfully.” message and new row appears. | Pending XAMPP execution | Pending |
| TC02 | Update customer | Open a customer; change email/phone; save. | Updated values display in customer list/history. | Pending XAMPP execution | Pending |
| TC03 | Delete customer | Add a temporary customer without appointments; click Delete; accept confirmation. | Customer is removed and success message appears. | Pending XAMPP execution | Pending |
| TC04 | Create appointment | Choose a customer, active service, qualified available staff, future date/time; save. | Price is populated from service; appointment is saved. | Pending XAMPP execution | Pending |
| TC05 | Prevent double booking | Create another non-cancelled booking for the exact same staff/date/time. | “That staff member is already booked for this time.”; no second row is inserted. | Pending XAMPP execution | Pending |
| TC06 | Block unavailable/incorrect staff | Choose unavailable staff or a staff member not assigned to the service. | Booking is rejected with a friendly availability/assignment error. | Pending XAMPP execution | Pending |
| TC07 | Record payment | Payments → Record; choose an appointment; select Paid, Card, and a date. | Charge is retrieved; Paid amount is automatically set to appointment price. | Pending XAMPP execution | Pending |
| TC08 | Partial/unpaid payment | Record a partial amount below charge, then an unpaid record for another appointment. | Partial balance and outstanding report are correct; unpaid amount is R 0.00. | Pending XAMPP execution | Pending |
| TC09 | Add product and detect low stock | Add active product with quantity at/below reorder level. | Product list/dashboard/Low Stock report flag the product. | Pending XAMPP execution | Pending |
| TC10 | Record product usage | Select stocked product and appointment; record usage less than available stock. | Usage row is saved; stock reduces by that quantity. | Pending XAMPP execution | Pending |
| TC11 | Prevent negative stock | Try recording usage greater than available quantity. | System rejects the operation; stock never becomes negative. | Pending XAMPP execution | Pending |
| TC12 | Generate reports | Open all six reports; apply appointment and revenue filters; click Print report. | Tables display real query data, filters work, and print styling hides navigation. | Revenue report rendered with imported MySQL data in the authenticated HTTP smoke test; remaining report/filter/print interactions pending. | Partial |
| TC13 | Login authentication | Try invalid password, then valid admin credentials; open Table Management while logged in. | Invalid login is rejected; valid login succeeds; table section is administrator-only. | `admin` / `Admin@123` successfully verified and opened the protected dashboard; invalid-login and table-admin interaction pending. | Partial |
| TC14 | Table management confirmation | Create a test table, add a column, then remove/drop it after confirmation. | Structure changes occur only after confirmation; primary-key column cannot be removed. | Pending XAMPP execution | Pending |

## Automated checks performed in the build workspace

| Check | Result |
| --- | --- |
| PHP syntax (`php -l` over all PHP pages/includes) | Pass — 58 PHP files, zero syntax errors |
| PHP version available for validation | PHP 8.3 |
| SQL import into MySQL 8.0.46 | Pass — all 10 tables created successfully |
| Default admin credential is a bcrypt `password_hash()` value | Pass — `password_verify('Admin@123', hash)` succeeded |
| Sample data targets (15 customers, 5 staff, 8 services, 20 appointments, 15 payments, 5 suppliers, 15 products) | Pass — queried after MySQL import |
| Authenticated page rendering | Pass — login, dashboard, booking and revenue report rendered over HTTP |

## Presentation checklist

- Demonstrate TC04 and TC05 together to show automatic price lookup, staff-service validation and double-booking prevention.
- Demonstrate TC10 with a low-stock product, then show the Dashboard and Low Stock Report.
- Show Staff Performance and Popular Services reports after explaining the foreign-key relationships.
- Use [ERD.md](ERD.md) and [NORMALIZATION.md](NORMALIZATION.md) while explaining the database design.
