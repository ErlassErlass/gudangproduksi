# Agent Instructions — Erlass PWMS

## 1. Role
Act as a senior software architect and full-stack engineer implementing Erlass PWMS. Treat the documents in this package as the project specification and source of truth.

## 2. Mandatory first actions
1. Read `README.md` and every numbered document.
2. Inspect the existing repository before changing files.
3. Report detected stack, current modules, migrations, conventions, and gaps.
4. Produce an implementation plan mapped to the roadmap.
5. Identify contradictions or missing decisions; do not silently invent material business rules.
6. Start with foundation and inventory domain design; do not build disconnected CRUD screens first.

## 3. Technology constraints
- Backend Laravel 12.
- Frontend React.js.
- MySQL/InnoDB.
- REST API `/api/v1`.
- Laravel Sanctum.
- Use the package manager and versions already established by the repository unless incompatible.
- Use migrations, factories, seeders, Form Requests, Policies, API Resources, backed enums, and automated tests.
- Keep secrets in environment variables.

## 4. Architecture constraints
- Modular Monolith with domain boundaries.
- No microservices in phase 1.
- No direct stock balance mutation from controllers or feature modules.
- All inventory mutations go through one Inventory Posting Service.
- Posted records immutable; corrections via reversal.
- Use DB transactions, row locks, idempotency, and audit logs for posting.
- Explicit FK relationships for core workflows.
- Keep business logic out of React components and HTTP controllers.

## 5. Implementation discipline
For each feature:
1. Define acceptance criteria.
2. Design schema and indexes.
3. Implement domain service/action and state transitions.
4. Implement authorization policy.
5. Add unit and feature tests.
6. Implement API endpoint and response resource.
7. Implement frontend workflow.
8. Add audit/event/notification behavior if required.
9. Update docs and changelog.
10. Run relevant tests and report results.

Do not mark a feature complete if tests are skipped or failing.

## 6. Database rules
- Never use FLOAT/DOUBLE for quantity or money.
- Use DECIMAL precision from database spec.
- Add foreign keys and indexes.
- Do not cascade-delete posted transactional records.
- Avoid nullable unique-index assumptions in MySQL.
- Use migrations that can be rolled forward safely.
- Keep demo seeders separate from production import commands.

## 7. Inventory service contract
All stock posting operations must:
- Validate product tracking method.
- Resolve product/location/batch/serial/status dimensions.
- Acquire row locks for affected balance dimensions.
- Validate available quantity and reservation.
- Persist transaction header and lines.
- Update balance projection atomically.
- Persist unit cost snapshot where applicable.
- Persist idempotency result.
- Add audit/outbox event.
- Commit as one transaction.
- Fail without partial writes.

## 8. Prohibited shortcuts
- No direct SQL stock edits in business controllers.
- No hardcoded rack layout or role names as authorization logic.
- No arbitrary status updates from frontend.
- No deleting or editing posted inventory transactions.
- No automatic PO creation from MRP.
- No treating vendor dispatch as inventory consumption.
- No treating returned rental goods as available before QC.
- No including labor/overhead/GAP service fee in phase-1 material costing.
- No treating program/service as ordinary stock item without classification checks.
- No silently ignoring quantity variance.

## 9. Coding and delivery expectations
- Prefer small, cohesive classes.
- Use explicit names and typed method signatures.
- Avoid premature generic abstractions.
- Keep frontend feature folders aligned with backend domains.
- Provide loading, empty, error, and permission-denied UI states.
- Add seed data only for development/testing.
- Use server-side pagination/filtering for large lists.
- Provide a concise progress report with completed files, tests run, known gaps, and next tasks.

## 10. First milestone requested from agent
Before implementing business modules, return:
- repository assessment,
- proposed folder structure,
- domain dependency map,
- initial ERD review,
- migration order,
- critical risks,
- phase 0 task breakdown.
Then implement Phase 0 only unless the user explicitly authorizes a broader scope.
