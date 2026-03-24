# Ordering App API Specification (Contract)

This document defines the API contract for the Ordering microfrontend. It serves as the authoritative source of truth for communication between the React Ordering App and the Laravel backend.

- All endpoints require authenticated access via Sanctum.
- Actual action availability depends on backend permission checks through RBAC.
- Frontend must not hardcode role assumptions (e.g., admin/staff). Instead, rely on HTTP status codes and response data.
- Authorization is enforced by the backend based on permissions granted to the user.

## 1. Overview

The Ordering System manages the lifecycle of customer orders, including creation, item snapshotting, total calculations, and status transitions.

| Endpoint | Method | Purpose | Required Permission |
| :--- | :--- | :--- | :--- |
| `/api/orders` | `GET` | List paginated orders | `orders.view` |
| `/api/orders` | `POST` | Create a new order | `orders.create` |
| `/api/orders/{id}` | `GET` | View order details | `orders.view` |
| `/api/orders/{id}` | `PUT` | Update order details/items | `orders.update` |
| `/api/orders/{id}/status` | `PATCH` | Update order status | `orders.status.update` |

## 2. Global Conventions

- **Base Path**: `/api`
- **Content-Type**: `application/json`
- **Success Response**: Standard JSON with a `data` key (and `meta`/`links` for pagination).
- **Error Response**: Standard Laravel validation or business rule error format.
- **Pagination**: Uses standard Laravel pagination structure.

### Standard Success (Collection)
```json
{
  "data": [...],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "path": "...", "per_page": 15, "to": 1, "total": 1 }
}
```

### Standard Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "items.0.quantity": ["The items.0.quantity field must be at least 1."]
  }
}
```

## 3. Order Status Rules

### Available Statuses
- `pending`: Initial state upon creation.
- `confirmed`: Order is verified.
- `processing`: Order is being prepared.
- `completed`: Order is finished (Terminal).
- `cancelled`: Order is cancelled (Terminal).

### Allowed Transitions
- `pending` → `confirmed`, `cancelled`
- `confirmed` → `processing`, `cancelled`
- `processing` → `completed`
- `completed` → No further transitions
- `cancelled` → No further transitions

### Editability Rule
- **Full Order Editing** (PUT) is only allowed when the status is `pending` or `confirmed`.
- If the status is `processing`, `completed`, or `cancelled`, the backend will reject update requests with a `403 Forbidden` error.

## 4. Endpoints

### List Orders
- **Method**: `GET`
- **URL**: `/api/orders`
- **Query Parameters**:
    - `search` (string): Search by `order_number`, `customer_name`, or `customer_phone`.
    - `status` (string): Filter by status (`pending`, `confirmed`, etc.).
    - `per_page` (integer): Number of items per page (default: 15).
    - `page` (integer): Page number for pagination.
- **Default Sorting**: Newest first (`created_at` DESC).

### Create Order
- **Method**: `POST`
- **URL**: `/api/orders`
- **Request Body**:
```json
{
  "customer_name": "John Doe",
  "customer_phone": "0912345678",
  "customer_email": "john@example.com",
  "notes": "Please deliver after 5 PM",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 5, "quantity": 1 }
  ]
}
```

### View Order Detail
- **Method**: `GET`
- **URL**: `/api/orders/{id}`
- **Note**: Includes full item details and snapshots.

### Update Order
- **Method**: `PUT`
- **URL**: `/api/orders/{id}`
- **Request Body**: Same structure as **Create Order** (fields are `sometimes` required).
- **Behavior**: Replaces all existing items with the new set provided. Recalculates all totals.

### Update Order Status
- **Method**: `PATCH`
- **URL**: `/api/orders/{id}/status`
- **Request Body**:
```json
{
  "status": "confirmed"
}
```

## 5. Data Shapes

### Order Object
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | `integer` | Primary ID |
| `order_number` | `string` | Unique identifier (e.g., ORD-20260324-0001) |
| `customer_name` | `string` | Customer's full name |
| `customer_phone` | `string` | Customer's contact number |
| `customer_email` | `string\|null` | Customer's email address |
| `notes` | `string\|null` | Internal or customer notes |
| `status` | `string` | One of the allowed statuses |
| `subtotal` | `string` | Sum of all line totals (decimal string) |
| `total` | `string` | Final total amount (decimal string) |
| `items` | `array` | Array of **Order Item Objects** (included in details) |
| `creator` | `object` | User object of the person who created the order |
| `created_at` | `string` | ISO 8601 datetime string |
| `updated_at` | `string` | ISO 8601 datetime string |

### Order Item Object
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | `integer` | Primary ID |
| `product_id` | `integer` | Reference to the product |
| `product_name` | `string` | Snapshotted name at time of order |
| `product_sku` | `string\|null` | Snapshotted SKU at time of order |
| `unit_price` | `string` | Snapshotted unit price (decimal string) |
| `quantity` | `integer` | Quantity ordered |
| `line_total` | `string` | `unit_price * quantity` (decimal string) |

## 6. Validation Rules

- `customer_name`: Required, String, Max 255.
- `customer_phone`: Required, String, Max 255.
- `customer_email`: Nullable, Valid Email, Max 255.
- `items`: Required, Array, Minimum 1 item.
- `items.*.product_id`: Required, Integer, Must exist in `products` table.
- `items.*.quantity`: Required, Integer, Minimum 1.
- `status`: Required for status update, must be a valid status string and follow transition rules.

## 7. Error Response Examples

### 403 Forbidden (Non-editable Status)
```json
{
  "message": "Forbidden."
}
```

### 422 Unprocessable Entity (Invalid Transition)
```json
{
  "message": "Invalid order status transition from processing to pending."
}
```

## 8. Frontend Integration Notes

- **Trusted Totals**: Frontend should not calculate final trusted totals. The backend response is the source of truth for all monetary fields.
- **Action Visibility**: Frontend should hide or disable "Edit" buttons when the order status is not `pending` or `confirmed`.
- **Status Source of Truth**: Always use the `status` returned by the API to determine which transitions are possible in the UI.
- **Snapshots**: The `product_name` and `unit_price` in the order details are snapshots. They may differ from the current live product data if the product was updated after the order was placed.
- **Product Selection**: When creating an order, fetch products from `GET /api/products`. Use the `id` for the request and `base_price` for UI-side estimated calculations.
- **Refresh Strategy**: After a successful `POST`, `PUT`, or `PATCH`, the backend returns the updated order resource. Use this to update the local state.

---

### **Notes / Assumptions**
- The backend assumes the `products` table has `id`, `name`, `sku`, and `base_price` fields.
- Deletion is not implemented; use the `cancelled` status for order termination.
- RBAC permissions (`orders.view`, `orders.create`, etc.) must be assigned to the user's role for access.
