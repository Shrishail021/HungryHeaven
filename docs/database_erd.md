# HungryHeaven Database ERD

Below is the Entity Relationship Diagram for the HungryHeaven restaurant management system. This diagram shows all tables and their relationships.

## Mermaid ERD Code

```mermaid
erDiagram
    USERS ||--o{ ORDERS : places
    USERS ||--o{ RESERVATIONS : makes
    USERS ||--o{ ADDRESSES : has
    CATEGORIES ||--o{ MENU_ITEMS : contains
    MENU_ITEMS ||--o{ ORDER_ITEMS : includes
    ORDERS ||--o{ ORDER_ITEMS : contains
    
    USERS {
        int id PK
        varchar name
        varchar email
        varchar phone
        varchar password
        varchar role
        timestamp created_at
        timestamp updated_at
    }
    
    CATEGORIES {
        int id PK
        varchar name
        text description
        varchar image
        tinyint status
        timestamp created_at
        timestamp updated_at
    }
    
    MENU_ITEMS {
        int id PK
        int category_id FK
        varchar name
        text description
        decimal price
        varchar image
        tinyint status
        tinyint is_featured
        timestamp created_at
        timestamp updated_at
    }
    
    ORDERS {
        int id PK
        int user_id FK
        varchar order_number
        decimal total_amount
        decimal tax_amount
        decimal delivery_charge
        varchar payment_method
        varchar payment_id
        varchar status
        text address
        varchar phone
        text notes
        timestamp order_date
        timestamp delivery_date
    }
    
    ORDER_ITEMS {
        int id PK
        int order_id FK
        int menu_item_id FK
        int quantity
        decimal price
        decimal subtotal
    }
    
    RESERVATIONS {
        int id PK
        int user_id FK
        varchar name
        varchar email
        varchar phone
        date date
        time time
        int guests
        varchar status
        text special_request
        timestamp created_at
        timestamp updated_at
    }
    
    ADDRESSES {
        int id PK
        int user_id FK
        varchar address_line1
        varchar address_line2
        varchar city
        varchar state
        varchar postal_code
        tinyint is_default
        timestamp created_at
        timestamp updated_at
    }
    
    SETTINGS {
        int id PK
        varchar setting_key
        text setting_value
        varchar setting_type
        timestamp created_at
        timestamp updated_at
    }
```

## Relationship Details

1. **USERS to ORDERS**: One-to-Many
   - A user can place multiple orders
   - Each order belongs to a single user

2. **USERS to RESERVATIONS**: One-to-Many
   - A user can make multiple reservations
   - Each reservation belongs to a single user

3. **USERS to ADDRESSES**: One-to-Many
   - A user can have multiple addresses
   - Each address belongs to a single user

4. **CATEGORIES to MENU_ITEMS**: One-to-Many
   - A category can contain multiple menu items
   - Each menu item belongs to a single category

5. **MENU_ITEMS to ORDER_ITEMS**: One-to-Many
   - A menu item can be included in multiple order items
   - Each order item refers to a single menu item

6. **ORDERS to ORDER_ITEMS**: One-to-Many
   - An order can contain multiple order items
   - Each order item belongs to a single order

## How to View this ERD

To view this ERD as a diagram:

1. **GitHub**: If you paste this mermaid code in a GitHub markdown file, it will automatically render as a diagram.

2. **Mermaid Live Editor**: Visit [Mermaid Live Editor](https://mermaid.live/) and paste the code between the mermaid tags to visualize the diagram.

3. **VS Code**: Install a Mermaid extension like "Markdown Preview Mermaid Support" to view the diagram directly in VS Code.

4. **Export Options**: From the Mermaid Live Editor, you can export the diagram as SVG, PNG, or PDF for inclusion in your documentation.

## Database Implementation Notes

- All primary keys are auto-increment integers.
- Foreign keys maintain referential integrity between tables.
- Timestamps are used to track creation and modification dates.
- The settings table uses a key-value structure for application configuration.
- Status fields use predefined values (e.g., "pending", "completed", "cancelled") to track state.
