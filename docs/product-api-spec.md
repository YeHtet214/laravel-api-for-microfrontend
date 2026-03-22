# Product App API Specification (Contract)

This document specifies the API contract for the Product microfrontend. All endpoints require session-based authentication (Sanctum) and should follow the [Global Conventions](file:///c:/Users/yhtet/herd/laravel-api-for-microfrontend/docs/frontend-api-integration.md) (Pagination, Errors, etc.).

---

## 1. Category Management

### GET `/api/categories`
**Purpose**: List all product categories.
**Response**: Simple list (no pagination by default).
```json
{
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "Gadgets and devices",
      "status": "active",
      "created_at": "...", "updated_at": "..."
    }
  ]
}
```

### POST `/api/categories`
**Payload**:
```json
{
  "name": "Apparel",
  "slug": "apparel",
  "description": "Clothing and accessories",
  "status": "active"
}
```

### GET `/api/categories/{category}`
**Response**: Single category object.

### PUT `/api/categories/{category}`
**Payload**: All fields optional (`sometimes`).

### DELETE `/api/categories/{category}`
**Notes**: Normal delete. Cascades or restricts based on DB foreign key rules.

---

## 2. Product Management

### GET `/api/products`
**Purpose**: Searchable and paginated list of products.
**Query Params**:
- `search`: Matches `name` or `sku`.
- `category_id`: Filter by specific category.
- `status`: `active` or `inactive`.
- `sort`: `latest` (default) or `oldest`.
- `per_page`: Number of results (default 15).
**Response**: Paginated list with `category` eager loaded.

### POST `/api/products`
**Payload**:
```json
{
  "category_id": 1,
  "name": "Smartphone X",
  "slug": "smartphone-x",
  "description": "High-end smartphone",
  "sku": "PHN-X-001",
  "base_price": 999.00,
  "stock_quantity": 50,
  "status": "active",
  "has_variants": false
}
```

### GET `/api/products/{product}`
**Purpose**: Detailed view of a product.
**Response**: Includes `category`, `variants`, and variant `attributes`.

### PUT `/api/products/{product}`
**Payload**: All fields optional (`sometimes`).

### DELETE `/api/products/{product}`
**Notes**: **Soft delete only**. The record remains in DB with `deleted_at` timestamp.

---

## 3. Product Variant Management

### GET `/api/variants`
**Query Params**:
- `product_id`: Filter variants for a specific product.
**Response**: List of variants with `attributes` eager loaded.

### POST `/api/variants`
**Purpose**: Create a new variant for a product.
**Payload**:
```json
{
  "product_id": 1,
  "name": "Red XL",
  "sku": "PHN-X-RED-XL",
  "price": 1050.00,
  "stock_quantity": 10,
  "status": "active",
  "attributes": [
    { "attribute_name": "Color", "attribute_value": "Red" },
    { "attribute_name": "Size", "attribute_value": "XL" }
  ]
}
```

### GET `/api/variants/{variant}`
**Response**: Single variant with `attributes`.

### PUT `/api/variants/{variant}`
**Payload**: All fields optional.
- `attributes`: Providing this array will replace ALL existing attributes for the variant.

### DELETE `/api/variants/{variant}`
**Notes**: Permanent delete.

---

## 4. Data Models (Resources)

### ProductResource
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | Integer | |
| `category_id` | Integer | Nullable |
| `name` | String | |
| `slug` | String | Unique |
| `sku` | String | Unique |
| `base_price` | Decimal | Formatted as string `"0.00"` |
| `stock_quantity` | Integer | |
| `has_variants` | Boolean | |
| `status` | String | `active, inactive` |
| `category` | Object | Only when loaded |
| `variants` | Array | Only when loaded |

### ProductVariantResource
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | Integer | |
| `product_id` | Integer | |
| `name` | String | |
| `sku` | String | Unique |
| `price` | Decimal | Nullable |
| `stock_quantity` | Integer | |
| `attributes` | Array | Only when loaded |

---

## 5. Validation Reference (Business Rules)

| Resource | Field | Rules |
| :--- | :--- | :--- |
| **Category** | `name`, `slug` | Required. `slug` unique. |
| **Product** | `name`, `slug`, `sku` | Required. `slug` and `sku` unique. |
| | `base_price` | Numeric, min 0. |
| | `stock_quantity`| Integer, min 0. |
| **Variant** | `product_id`, `name`, `sku` | Required. `sku` unique. |
| | `price` | Optional, numeric, min 0. |
| | `stock_quantity`| Required, integer, min 0. |
| **Attributes** | `attribute_name`, `attribute_value` | Required if attributes array provided. |
