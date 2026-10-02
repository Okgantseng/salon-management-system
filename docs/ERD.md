# ER Diagram Specification

## Cardinality diagram

```mermaid
erDiagram
    USERS {
        int user_id PK
        int customer_id FK
        string full_name
        string username UK
        string password_hash
        string role
    }
    CUSTOMERS {
        int customer_id PK
        string first_name
        string last_name
        string phone UK
        string email UK
        string gender
        date date_registered
    }
    STAFF {
        int staff_id PK
        string first_name
        string last_name
        string phone UK
        string email UK
        string position
        string status
    }
    SERVICES {
        int service_id PK
        string service_name UK
        string description
        int duration_minutes
        decimal price
        string status
    }
    STAFF_SERVICES {
        int staff_id PK, FK
        int service_id PK, FK
    }
    APPOINTMENTS {
        int appointment_id PK
        int customer_id FK
        int staff_id FK
        int service_id FK
        date appointment_date
        time appointment_time
        string status
        string notes
        decimal service_price
    }
    PAYMENTS {
        int payment_id PK
        int appointment_id FK, UK
        decimal amount
        string payment_method
        string payment_status
        date payment_date
    }
    SUPPLIERS {
        int supplier_id PK
        string supplier_name UK
        string phone
        string email
        string address
    }
    PRODUCTS {
        int product_id PK
        int supplier_id FK
        string product_name
        string category
        int quantity
        int reorder_level
        decimal unit_price
        string status
    }
    PRODUCT_USAGE {
        int usage_id PK
        int product_id FK
        int appointment_id FK
        int quantity_used
        date usage_date
    }

    CUSTOMERS ||--o{ APPOINTMENTS : books
    CUSTOMERS o|--o| USERS : authenticates_as
    STAFF ||--o{ APPOINTMENTS : performs
    SERVICES ||--o{ APPOINTMENTS : provides
    STAFF ||--o{ STAFF_SERVICES : qualifies_for
    SERVICES ||--o{ STAFF_SERVICES : is_performed_by
    APPOINTMENTS ||--o| PAYMENTS : has
    SUPPLIERS ||--o{ PRODUCTS : supplies
    APPOINTMENTS ||--o{ PRODUCT_USAGE : consumes
    PRODUCTS ||--o{ PRODUCT_USAGE : is_used_in
```

## Relationship notes

| Parent | Child | Cardinality | Enforcement |
| --- | --- | --- | --- |
| Customers | Appointments | 1:M | `appointments.customer_id` FK |
| Staff | Appointments | 1:M | `appointments.staff_id` FK |
| Services | Appointments | 1:M | `appointments.service_id` FK |
| Staff | Services | M:N | `staff_services` with composite PK (`staff_id`, `service_id`) |
| Appointments | Payments | 1:0..1 | `payments.appointment_id` FK plus `UNIQUE` |
| Suppliers | Products | 1:M | `products.supplier_id` FK |
| Appointments | Product Usage | 1:M | `product_usage.appointment_id` FK |
| Products | Product Usage | 1:M | `product_usage.product_id` FK |

## Design decisions

- `staff_services` has no surrogate key because the pair `(staff_id, service_id)` uniquely identifies a staff qualification.
- `payments.appointment_id` is unique, making one optional payment record per appointment. `amount` represents amount received, while `appointments.service_price` retains the charge due.
- `appointments.service_price` is a deliberate historical snapshot; it prevents past invoices/reports changing when the current `services.price` changes.
- Foreign keys use restrictive deletes for operational master data, avoiding accidental deletion of records that are still referenced. Payment records cascade when their appointment is deliberately deleted.
