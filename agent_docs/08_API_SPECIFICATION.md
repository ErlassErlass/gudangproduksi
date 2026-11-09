# 08 — API Specification

## 1. Conventions
- Base: `/api/v1`.
- JSON request/response.
- Sanctum authentication.
- Use `Authorization: Bearer` for token clients where configured.
- Paginated list response: `data`, `meta`, `links`.
- Validation errors: HTTP 422.
- Unauthorized: 401; forbidden: 403; missing: 404; conflict/state violation: 409.
- Posting endpoints require `Idempotency-Key` header.

## 2. Suggested endpoint inventory
### Auth and administration
- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`
- `GET|POST /users`
- `GET|PUT /users/{id}`
- `GET|POST /roles`
- `GET|POST /permissions`

### Master data
- `GET|POST /products`
- `GET|PUT /products/{id}`
- `GET /products/{id}/stock`
- `GET|POST /categories`
- `GET|POST /uoms`
- `GET|POST /warehouses`
- `GET|POST /locations`
- `GET|POST /customers`
- `GET|POST /suppliers`
- `GET|POST /vendors`

### BOM
- `GET|POST /boms`
- `GET /boms/{id}`
- `POST /boms/{id}/versions`
- `POST /bom-versions/{id}/submit`
- `POST /bom-versions/{id}/approve`
- `POST /bom-versions/{id}/activate`
- `GET /bom-versions/{id}/explosion`

### Procurement
- `GET|POST /purchase-requisitions`
- `POST /purchase-requisitions/{id}/submit`
- `POST /purchase-requisitions/{id}/approve`
- `GET|POST /purchase-orders`
- `POST /purchase-orders/{id}/submit`
- `POST /purchase-orders/{id}/approve`
- `POST /purchase-orders/{id}/goods-receipts`
- `POST /goods-receipts/{id}/inspect`
- `POST /goods-receipts/{id}/post`

### Production
- `GET|POST /production-plans`
- `POST /production-plans/{id}/mrp-runs`
- `POST /production-plans/{id}/submit`
- `POST /production-plans/{id}/approve`
- `POST /production-plans/{id}/create-orders`
- `GET /production-orders/{id}`
- `POST /production-orders/{id}/release`
- `POST /production-orders/{id}/hold`
- `POST /production-orders/{id}/work-orders`
- `POST /work-orders/{id}/material-requests`
- `POST /material-requests/{id}/approve`
- `POST /material-issues/{id}/post`
- `POST /material-issues/{id}/return-unused`
- `POST /work-orders/{id}/results`
- `POST /production-orders/{id}/close`

### Inventory and warehouse
- `GET /inventory/balances`
- `GET /inventory/transactions`
- `GET /inventory/stock-card`
- `POST /inventory/transfers`
- `POST /inventory/adjustments`
- `POST /inventory/adjustments/{id}/approve`
- `POST /inventory/transactions/{id}/post`
- `POST /inventory/transactions/{id}/reverse`
- `GET|POST /stock-opnames`
- `POST /stock-opnames/{id}/counts`
- `POST /stock-opnames/{id}/submit`
- `POST /stock-opnames/{id}/close`

### Vendor / GAP
- `GET|POST /vendor-orders`
- `POST /vendor-orders/{id}/dispatch`
- `POST /vendor-orders/{id}/record-results`
- `POST /vendor-orders/{id}/receipts`
- `POST /vendor-receipts/{id}/inspect`
- `POST /vendor-receipts/{id}/post`

### Rental / QC / distribution
- `GET|POST /rental-orders`
- `POST /rental-orders/{id}/reserve`
- `POST /rental-orders/{id}/dispatch`
- `POST /rental-orders/{id}/returns`
- `POST /rental-returns/{id}/inspect`
- `GET|POST /quality-inspections`
- `POST /quality-inspections/{id}/complete`
- `GET|POST /shipments`
- `POST /shipments/{id}/dispatch`
- `POST /shipments/{id}/confirm-delivery`

### Reports
- `GET /reports/inventory`
- `GET /reports/production`
- `GET /reports/procurement`
- `GET /reports/vendor`
- `GET /reports/rental`
- `GET /reports/quality`
- `GET /reports/material-cost`
- `POST /reports/exports`

## 3. Example: post inventory transfer
Request:
```json
{
  "source_location_id": 12,
  "destination_location_id": 24,
  "items": [
    {"product_id": 101, "quantity": "5.0000", "batch_id": null}
  ],
  "notes": "Relokasi rak"
}
```
Headers: `Idempotency-Key: unique-client-generated-key`

Response:
```json
{
  "data": {
    "id": 880,
    "document_no": "TRF-20261109-0001",
    "status": "POSTED"
  }
}
```

## 4. Example error envelope
```json
{
  "message": "Insufficient available stock.",
  "code": "INVENTORY_INSUFFICIENT_STOCK",
  "errors": {
    "items.0.quantity": ["Requested quantity exceeds available quantity."]
  }
}
```

## 5. API implementation rules
- Controllers orchestrate request/response only.
- Use FormRequest, Policy, Action/Service, Resource.
- Lifecycle transitions are explicit action endpoints.
- Never accept client-submitted `status=POSTED` as a normal update.
- Validate ownership and source-document state.
- Do not expose internal file paths.
- Add filters: `search`, `status`, `from`, `to`, `warehouse_id`, `location_id`, `product_id`, `per_page`, `sort`.
