# 💻 GitHub Copilot Prompt: Paytrail Payment Configuration API

Use this prompt verbatim in GitHub Copilot (or as guidance when implementing manually). It specifies the Firestore-backed storage shape, encryption, controller endpoints, routes, and a strict policy to enforce three-tier authorization.

---

## Objective

You are tasked with building the necessary API endpoints and database structure in a Laravel application for managing Payment Gateway Configurations, specifically tailored for a restaurant management system with multi-level roles. The configuration must be manageable per branch of a restaurant, and all sensitive data must be secured.

Assumptions:
- Laravel 10/11 with Sanctum for API auth.
- Users have one of the roles: Super Admin, Restaurant Admin, Branch Admin.
- User scoping fields exist (or will be added) as:
  - `users.role` in {`super_admin`, `restaurant_admin`, `branch_admin`}
  - `users.restaurant_id` (nullable) for Restaurant Admin scope
  - `users.branch_id` (nullable) for Branch Admin scope
- Branch model/table exists as `branches` with `id` and `restaurant_id`.

If these fields/models are named differently, adapt the policy checks accordingly.

---

## 1) Storage Shape (Firestore)

Important: this project stores domain data in Firestore via the existing `FirebaseService`. Do NOT create MySQL migrations or Eloquent models for payment configs. Instead store the payment gateway configuration as a nested map on the branch document.

- Location: `restaurants/{restaurantId}/branches/{branchId}` (existing branch doc)
- Field name inside branch doc: `paymentConfig` (a map)

`paymentConfig` map fields (recommended names):
- `gatewayName` (string) — default `Paytrail`
- `merchantId` (string)
- `secretKeyEnc` (string) — encrypted secret (use `Crypt::encryptString()` before writing)
- `isActive` (boolean)
- `createdAt` / `updatedAt` (ISO timestamps)

Why: keeping the config inside the branch document keeps all branch-scoped data together and avoids separate collections or foreign keys in Firestore.

---

## 2) Controller & Routes

Create `App\Http\Controllers\PaymentConfigController` (or update the one you already have) with the following behavior:

- `GET /api/v1/payment-configs/{restaurantId}/{branchId}`
    - Loads the branch document at `restaurants/{restaurantId}/branches/{branchId}`.
    - Controller signature: `show(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId)`.
    - Reads the nested `paymentConfig` map, decrypts `secretKeyEnc` with `Crypt::decryptString()` and returns the decrypted `secret_key` to authorized clients.

- `PUT|PATCH /api/v1/payment-configs/{restaurantId}/{branchId}`
    - Controller signature: `upsert(Request $request, FirebaseService $firebase, string $restaurantId, string $branchId)`.
    - Validates incoming fields (see Section 4).
    - Authorizes via policy/scope using the provided `restaurantId` and `branchId`.
    - Upserts the nested map `paymentConfig` inside the branch document: write `gatewayName`, `merchantId`, `secretKeyEnc` (encrypted), `isActive`, timestamps.

- `GET /api/v1/payment-configs` (Super Admin)
    - Since configs are nested inside branches, the index endpoint should iterate `restaurants` → `branches` and collect existing `paymentConfig` maps for listing. This is appropriate for admin UIs with modest dataset sizes.

Routes: you can expose these routes either under `routes/web.php` (session-protected admin UI) or under `routes/api.php` with Sanctum token protection for non-browser clients. Example route definitions (session-protected):

```php
Route::prefix('api/v1')->middleware([AdminMiddleware::class, BranchAdminMiddleware::class, RestaurantAdminMiddleware::class, AdminAuditMiddleware::class])->group(function () {
    Route::get('/payment-configs', [PaymentConfigController::class, 'index']);
    Route::get('/payment-configs/{restaurantId}/{branchId}', [PaymentConfigController::class, 'show']);
    Route::match(['put','patch'], '/payment-configs/{restaurantId}/{branchId}', [PaymentConfigController::class, 'upsert']);
});
```

The implementation in this repo uses session-based admin middlewares and routes under `/api/v1` in `web.php` — adapt as needed.

---

## 3) Authorization Policy (CRITICAL)

Generate a `PaymentGatewayConfigPolicy` and register it in `AuthServiceProvider`. The policy concept is the same but it must validate access against branch documents in Firestore or against your session-scoped `restaurantId`/`branchId` values.

Rules (summary):
- Super Admin: full access.
- Restaurant Admin: allowed for branches that belong to their `restaurantId`.
- Branch Admin: allowed only for their `branchId`.

Implementation notes:
- If your app sets `session('restaurantId')` and `session('branchId')` via middleware (this repo does), the policy can rely on those session values to make decisions without additional Firestore reads.
- If not, the policy may call `FirebaseService` to load the branch doc and compare `restaurantId` ownership.

---

## 4) Security & Data Handling

- Encrypt the secret before saving to `paymentConfig.secretKeyEnc` using `Crypt::encryptString()`.
- Decrypt only when returning to an authorized client: `Crypt::decryptString($secretKeyEnc)`; do not decrypt for logs or audit snapshots.
- Validation guidance:
    - On initial create: require `merchantId` and `secret_key`.
    - On update: allow partial updates (e.g., toggling `isActive` or updating `gatewayName`) and only overwrite `secretKeyEnc` when `secret_key` is provided.
- Audit & logs: filter out secret fields (`secret_key`, `secretKeyEnc`, `secret`) from request params and snapshots. Use `AdminAuditMiddleware` to set `audit_before` and `audit_after` but mask secret values.

---

## 5) Acceptance Criteria

- One config per branch: `paymentConfig` exists as at most one nested map per branch document.
- Super Admin can list all configs; Restaurant Admin and Branch Admin can only access configs within their scope.
- `secret_key` is encrypted at rest (`secretKeyEnc`) and only returned decrypted to authorized clients.
- Endpoints are protected (session or Sanctum) and unauthorized requests are denied.

---

## 6) Commands (reference)

No SQL migrations are required because configs are stored in Firestore. Useful artisan commands:

```bash
php artisan make:policy PaymentGatewayConfigPolicy
php artisan make:controller PaymentConfigController
```

Also update branch create/edit views to include `gatewayName`, `merchantId`, `secret_key` (do not prefill on edit), and `isActive`.

---

## 7) Notes for Integration

- Routes: place endpoints under `routes/web.php` if you rely on session admin middlewares (the current repo does this), or under `routes/api.php` with Sanctum for token-based clients.
- Ensure `FirebaseService` is available and the service account credentials are configured for the environment.
- Policy implementation can use session-scoped `restaurantId`/`branchId` (preferred) or load branch docs via `FirebaseService` to validate ownership.
- Views: when adding payment inputs to branch create/edit views, ensure the secret is not pre-filled on edit and require explicit entry to change the stored secret. Add a show/hide toggle for the secret input and avoid logging the secret.

