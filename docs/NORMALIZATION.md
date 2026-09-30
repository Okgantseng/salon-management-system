# Normalization Walkthrough: UNF → 1NF → 2NF → 3NF

This example uses a salon booking with services, payment details and products consumed during the appointment.

## Unnormalized Form (UNF)

A spreadsheet-like booking record might contain repeating groups:

```text
BOOKING_UNF(
  AppointmentID, AppointmentDate, AppointmentTime,
  CustomerID, CustomerName, CustomerPhone,
  StaffID, StaffName, StaffPhone,
  {ServiceID, ServiceName, ServicePrice},
  {ProductID, ProductName, QuantityUsed, SupplierName, SupplierPhone},
  PaymentMethod, PaymentStatus, AmountPaid
)
```

Problems:

- One appointment can contain many product-use values in a repeating group.
- A customer/staff/service name is repeated on every booking.
- Product supplier facts are repeated wherever that product is used.
- Updating a service price or supplier phone in one row can create inconsistencies.

## First Normal Form (1NF)

1NF requires atomic values and no repeating groups. Split the repeating product group into one row per used product:

```text
APPOINTMENT_1NF(AppointmentID, CustomerID, CustomerName, CustomerPhone,
                StaffID, StaffName, StaffPhone, ServiceID, ServiceName,
                ServicePrice, AppointmentDate, AppointmentTime,
                PaymentMethod, PaymentStatus, AmountPaid)

PRODUCT_USAGE_1NF(AppointmentID, ProductID, ProductName, QuantityUsed,
                  SupplierName, SupplierPhone)
```

Each `PRODUCT_USAGE_1NF` row now stores one product per appointment. However, customer facts depend only on `CustomerID`, service facts only on `ServiceID`, and product/supplier facts only on `ProductID`/`SupplierID`, not on the entire booking row.

## Second Normal Form (2NF)

2NF removes partial dependencies from a table with a composite logical key. Separate attributes that depend on only part of the key:

```text
CUSTOMERS(CustomerID, FirstName, LastName, Phone, Email, Gender)
STAFF(StaffID, FirstName, LastName, Phone, Position, Status)
SERVICES(ServiceID, ServiceName, Description, DurationMinutes, Price, Status)
APPOINTMENTS(AppointmentID, CustomerID, StaffID, ServiceID,
             AppointmentDate, AppointmentTime, Status, Notes, ServicePrice)
PRODUCTS(ProductID, SupplierID, ProductName, Category, Quantity,
         ReorderLevel, UnitPrice, Status)
PRODUCT_USAGE(AppointmentID, ProductID, QuantityUsed, UsageDate)
PAYMENTS(PaymentID, AppointmentID, Amount, PaymentMethod, PaymentStatus, PaymentDate)
```

`CustomerName` and `CustomerPhone` move to `CUSTOMERS`; staff details move to `STAFF`; service details move to `SERVICES`; and product details move to `PRODUCTS`. `PRODUCT_USAGE` contains only facts about the product/appointment combination.

## Third Normal Form (3NF)

3NF removes transitive dependencies: non-key attributes must depend only on the key, not on another non-key attribute.

```text
SUPPLIERS(SupplierID, SupplierName, Phone, Email, Address)
PRODUCTS(ProductID, SupplierID FK, ProductName, Category, Quantity,
         ReorderLevel, UnitPrice, Status)
STAFF_SERVICES(StaffID PK/FK, ServiceID PK/FK)
```

`SupplierName` and `SupplierPhone` are removed from `PRODUCTS` because they depend on `SupplierID`, not `ProductID`. They belong in `SUPPLIERS`, and `PRODUCTS.supplier_id` is the foreign key. The M:N fact “which staff member can perform which service” is moved into `STAFF_SERVICES`, where the full composite key is required.

## Final 3NF result

The final schema in `database/salon_management.sql` has these properties:

- Every table represents one entity or relationship.
- Repeating groups are represented by rows in relationship tables.
- Each non-key field depends on the key, the whole key, and nothing but the key.
- Foreign keys preserve the links without duplicating source facts.
- `appointments.service_price` is a documented business snapshot exception: it stores the agreed historical price at booking time, rather than a duplicate current-service attribute.
