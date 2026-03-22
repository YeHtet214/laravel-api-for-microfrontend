# Product App API Specification (Frontend Contract)

## 1. Overview
This document serves as the formal API contract for the Product microfrontend. It outlines the endpoints, request/response shapes, and business rules that the frontend must adhere to.

**Key Technical Constraints:**
- **Integer IDs:** All resource IDs (category, product, variant, attribute) are integers. UUIDs are not used in this API.
- **Microfrontend Ready:** This contract is designed to be consumed by a frontend application with minimal ambiguity.

---

## 2. Authentication Notes
The API uses **Laravel Sanctum** for session-based authentication.
- **Credentials:** The frontend must include credentials (cookies) in every request to protected routes.
- **XSRF-TOKEN:** For POST/PUT/DELETE requests, the frontend must include the `X-XSRF-TOKEN` header derived from the `XSRF-TOKEN` cookie.
- **Protected Routes:** All routes under `/api/categories`, `/api/products`, and `/api/variants` require an authenticated session.

---

## 3. Global Response Conventions
### Single Resource Wrapper
Individual resources are returned within a `data` object.
```json
{
  "data": {
    "id": 1,
    "name": "Example Resource",
    ...
  }
}
```

### Paginated Resource Structure
List endpoints (specifically `/api/products`) return a paginated response with `data`, `links`, and `meta`.

**Example: GET /api/products**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Product A",
      "slug": "product-a",
      "sku": "SKU-001",
      "base_price": "29.99",
      "stock_quantity": 100,
      "status": "active",
      "has_variants": false,
      "created_at": "2026-03-22T10:00:00.000000Z",
      "updated_at": "2026-03-22T10:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/products?page=1",
    "last": "http://localhost/api/products?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      { "url": null, "label": "&laquo; Previous", "active": false },
      { "url": "http://localhost/api/products?page=1", "label": "1", "active": true },
      { "url": null, "label": "Next &raquo;", "active": false }
    ],
    "path": "http://localhost/api/products",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

---

## 4. Error Response Examples
### 401 Unauthenticated
Returned when the session is invalid or missing.
```json
{
  "message": "Unauthenticated."
}
```

### 404 Not Found
Returned when a resource (e.g., category or product) does not exist.
```json
{
  "message": "The resource was not found."
}
```

### 422 Validation Error
Returned when the request payload fails validation.
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "slug": ["The slug has already been taken."],
    "sku": ["The sku field is required."]
  }
}
```

---

## 5. Category Management
### List All Categories
- **Method:** `GET`
- **Path:** `/api/categories`
- **Response Example:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "Electronic gadgets",
      "status": "active",
      "created_at": "2026-03-22T10:00:00.000000Z",
      "updated_at": "2026-03-22T10:00:00.000000Z"
    }
  ]
}
```
- **Notes:** Returns all categories. No status filtering is currently applied.

### Create Category
- **Method:** `POST`
- **Path:** `/api/categories`
- **Payload:**
```json
{
  "name": "New Category",
  "slug": "new-category",
  "description": "Optional description",
  "status": "active"
}
```

### Update Category (Partial)
- **Method:** `PUT`
- **Path:** `/api/categories/{id}`
- **Notes:** Supports partial updates. Only provided fields will be updated.

---

## 6. Product Management
### List Products
- **Method:** `GET`
- **Path:** `/api/products`
- **Query Params:**
  - `search`: Filter by name or SKU
  - `category_id`: Filter by integer category ID
  - `status`: Filter by `active` or `inactive`
  - `sort`: `latest` (default) or `oldest`
  - `per_page`: Number of items per page (default 15)

### Create Product
- **Method:** `POST`
- **Path:** `/api/products`
- **Payload:**
```json
{
  "category_id": 1,
  "name": "Sample Product",
  "slug": "sample-product",
  "description": "Description",
  "sku": "SAMPLE-SKU",
  "base_price": 99.99,
  "stock_quantity": 50,
  "status": "active",
  "has_variants": false
}
```

### Get Product Detail
- **Method:** `GET`
- **Path:** `/api/products/{id}`
- **Response Example (with Category & Variants):**
```json
{
  "data": {
    "id": 1,
    "name": "Pro Smartphone",
    "slug": "pro-smartphone",
    "description": "A high-end smartphone",
    "sku": "PHN-PRO",
    "base_price": "899.00",
    "stock_quantity": 0,
    "status": "active",
    "has_variants": true,
    "category": {
      "id": 2,
      "name": "Phones",
      "slug": "phones"
    },
    "variants": [
      {
        "id": 10,
        "name": "128GB Black",
        "sku": "PHN-PRO-BLK-128",
        "price": "899.00",
        "stock_quantity": 15,
        "status": "active",
        "attributes": [
          { "attribute_name": "Color", "attribute_value": "Black" },
          { "attribute_name": "Storage", "attribute_value": "128GB" }
        ]
      }
    ]
  }
}
```

### Delete Product
- **Method:** `DELETE`
- **Path:** `/api/products/{id}`
- **Note:** This endpoint performs a **soft delete**. The record remains in the database with a `deleted_at` timestamp.

### Stock Interpretation Rule
- **`has_variants = false`**: Use the product level `stock_quantity` as the sellable stock.
- **`has_variants = true`**: Use the `stock_quantity` from individual **variants**. The product-level `stock_quantity` should be ignored for sellable stock purposes.

---

## 7. Product Variant Management
### Create Variant
- **Method:** `POST`
- **Path:** `/api/variants`
- **Payload:**
```json
{
  "product_id": 1,
  "name": "Blue / 256GB",
  "sku": "PHN-PRO-BLU-256",
  "price": 999.00,
  "stock_quantity": 10,
  "status": "active",
  "attributes": [
    { "attribute_name": "Color", "attribute_value": "Blue" },
    { "attribute_name": "Storage", "attribute_value": "256GB" }
  ]
}
```

### Update Variant (Partial + Attribute Replacement)
- **Method:** `PUT`
- **Path:** `/api/variants/{id}`
- **Payload Behavior:**
  - Supports partial updates for scalar fields (`name`, `price`, etc.).
  - **Attribute Replacement Rule:**
    - If `attributes` field is **omitted**: Existing attributes remain unchanged.
    - If `attributes` is an **empty array `[]`**: All existing attributes are removed.
    - If `attributes` contains **values**: All existing attributes are replaced with the new set.

---

## 8. Resource Shapes
### CategoryResource
| Field | Type | Nullable? | Notes |
| :--- | :--- | :--- | :--- |
| id | integer | No | Primary identifier |
| name | string | No | |
| slug | string | No | Unique identifier for URLs |
| description | string | Yes | |
| status | string | No | `active` or `inactive` |
| created_at | datetime string | No | ISO 8601 |
| updated_at | datetime string | No | ISO 8601 |

### ProductResource
| Field | Type | Nullable? | Notes |
| :--- | :--- | :--- | :--- |
| id | integer | No | |
| category_id | integer | Yes | |
| name | string | No | |
| slug | string | No | Unique |
| description | string | Yes | |
| sku | string | No | Unique |
| base_price | decimal string | No | 2 decimal places |
| stock_quantity | integer | No | Sellable only if `has_variants` is false |
| status | string | No | `active` or `inactive` |
| has_variants | boolean | No | |
| created_at | datetime string | No | ISO 8601 |
| updated_at | datetime string | No | ISO 8601 |
| category | object \| null | Yes | Included when loaded |
| variants | array | No | Collection of variants; empty array if none |

### ProductVariantResource
| Field | Type | Nullable? | Notes |
| :--- | :--- | :--- | :--- |
| id | integer | No | |
| product_id | integer | No | |
| name | string | No | |
| sku | string | No | Unique |
| price | decimal string | Yes | Null defaults to product base price |
| stock_quantity | integer | No | Sellable stock for this variant |
| status | string | No | `active` or `inactive` |
| created_at | datetime string | No | ISO 8601 |
| updated_at | datetime string | No | ISO 8601 |
| attributes | array | No | Collection of attributes; empty array if none |

### VariantAttributeResource
| Field | Type | Nullable? | Notes |
| :--- | :--- | :--- | :--- |
| id | integer | No | |
| product_variant_id | integer | No | |
| attribute_name | string | No | e.g., "Color" |
| attribute_value | string | No | e.g., "Red" |
| created_at | datetime string | No | ISO 8601 |
| updated_at | datetime string | No | ISO 8601 |

---

## 9. Validation Reference
| Resource | Field | Rules |
| :--- | :--- | :--- |
| **GET /api/products** | search | nullable, string, max 255 |
| | category_id | nullable, integer, exists in categories |
| | status | active, inactive |
| | sort | latest, oldest |
| | per_page | integer, min 1, max 100 |
| **GET /api/variants** | product_id | nullable, integer, exists in products |
| **Category** | name | required, max 255 |
| | slug | required, unique, max 255 |
| | status | active, inactive |
| **Product** | category_id | nullable, exists in categories |
| | name | required, max 255 |
| | slug | required, unique, max 255 |
| | sku | required, unique, max 255 |
| | base_price | required, numeric, min 0 |
| | stock_quantity | required, integer, min 0 |
| | status | active, inactive |
| | has_variants | boolean |
| **Variant** | product_id | required, exists in products |
| | name | required, max 255 |
| | sku | required, unique, max 255 |
| | price | nullable, numeric, min 0 |
| | stock_quantity | required, integer, min 0 |
| | attributes | array, optional |

---

## 10. Frontend Integration Notes
- **IDs are Integers:** Ensure your state management and API types use `number` (not string/UUID).
- **Partial Updates:** PUT endpoints allow sending only the fields you wish to change.
- **Nullability:** Always check `category_id` and `category` object for null before rendering.
- **Empty States:** Relationships like `variants` and `attributes` will return `[]` (empty array), never `null`.
- **Stock Logic:** 
  - IF `has_variants` is true, display stock from the variant list. 
  - IF `has_variants` is false, display stock from the product level.
- **Soft Deletes:** Deleting a product returns a 204 No Content, but the resource is hidden from normal queries.
- **Attribute Replacement:** Updating attributes on a variant is destructive; you must send the full set of desired attributes.

## Contract Revisions Made
- **Integer IDs Standardized:** Replaced all UUID wording/examples with consistent integer IDs.
- **Pagination Shape Clarified:** Included explicit `data`, `links`, and `meta` examples for product list.
- **Error Examples Added:** Provided exact Laravel-style JSON for 401, 404, and 422 errors.
- **Nullability Clarified:** Documented nullable fields and empty array behavior for relationships.
- **Partial Update Behavior Clarified:** Documented PUT support for partial field updates.
- **Attribute Replacement Semantics Clarified:** Defined behavior for omitted, empty, or provided attributes in variant updates.
- **Stock Interpretation Clarified:** Explicitly defined how to interpret `stock_quantity` based on `has_variants`.
