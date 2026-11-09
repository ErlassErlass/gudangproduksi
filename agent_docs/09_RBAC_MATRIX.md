# 09 — RBAC Matrix

## Roles
- Super Admin
- Management
- Production Manager
- Production Staff
- Procurement Staff
- Warehouse Manager
- Warehouse Staff
- QC Inspector
- Supporting / Marketing
- Rental Staff
- Finance
- Auditor

## Permission convention
Use granular permission codes, e.g. `production.plan.create`, `production.plan.approve`, `inventory.transaction.post`, `rental.return.inspect`. Role names alone must not be hardcoded in controllers.

## Default role mapping
| Capability | Super Admin | Management | Production Mgr | Production Staff | Procurement | Warehouse Mgr | Warehouse Staff | QC | Supporting | Rental | Finance | Auditor |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| View dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manage master data | ✓ | R | R | R | R | R | R | R | R | R | R | R |
| Create production plan | ✓ | R | ✓ | — | — | R | — | — | — | — | R | R |
| Approve production plan | ✓ | ✓ | ✓* | — | — | — | — | — | — | — | — | R |
| Execute work order | ✓ | — | ✓ | ✓ | — | R | — | — | — | — | — | R |
| Create/approve PO | ✓ | ✓* | R | — | ✓ | R | — | — | — | — | R | R |
| Post inventory transaction | ✓ | — | R | — | R | ✓ | ✓* | — | — | — | — | R |
| Perform QC | ✓ | — | R | — | R | R | — | ✓ | — | ✓* | — | R |
| Manage vendor processing | ✓ | R | ✓ | ✓* | R | ✓ | ✓* | R | — | — | — | R |
| Manage rental | ✓ | R | R | — | — | R | — | R | R | ✓ | — | R |
| Dispatch distribution | ✓ | R | R | — | — | ✓ | ✓* | — | ✓* | ✓* | — | R |
| View material cost | ✓ | ✓ | ✓ | R | R | R | — | — | — | — | ✓ | ✓ |
| Manage users/roles | ✓ | — | — | — | — | — | — | — | — | — | — | R |
| View audit logs | ✓ | ✓ | R | — | — | R | — | R | — | — | R | ✓ |

Legend: ✓ full; R read-only; — no access; ✓* only with explicit assigned permission/approval policy. This table is a starting baseline; implement permissions as data and enforce separation of duties.

## Separation of duties
- Creator should not approve own high-risk transaction when policy requires independent approval.
- Inventory adjustment requires approval.
- Production plan and purchase orders may have amount/quantity thresholds.
- Reversal requires elevated permission and reason.
- Auditor is read-only and cannot post, edit, approve, or delete.
