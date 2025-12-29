# Food Ordering Admin Panel - Complete Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Architecture & Technology Stack](#architecture--technology-stack)
3. [Project Structure](#project-structure)
4. [Authentication & Authorization](#authentication--authorization)
5. [Core Workflows](#core-workflows)
6. [Key Services](#key-services)
7. [Data Models & Storage](#data-models--storage)
8. [API Endpoints](#api-endpoints)
9. [Frontend Components](#frontend-components)
10. [Payment Integration](#payment-integration)
11. [Additional Features](#additional-features)
12. [Development Guidelines](#development-guidelines)
13. [Environment Setup](#environment-setup)
14. [Security Considerations](#security-considerations)
15. [Troubleshooting](#troubleshooting)

---

## Project Overview

This is a **Laravel-based admin panel** for managing a multi-restaurant food ordering system. The application uses **Firebase Firestore** as its primary database instead of traditional SQL databases, and implements a **three-tier role-based access control** system.

### Key Features
- Multi-restaurant & multi-branch management
- Role-based access control (Super Admin, Restaurant Admin, Branch Admin)
- Menu management with categories, items, sizes, bases, and ingredients
- Order tracking and management
- Staff management
- Promotions and special offers
- Lounas (lunch) hours configuration
- Reports and analytics
- Payment gateway integration (Paytrail)
- Push notification system
- Audit logging

---

## Architecture & Technology Stack

### Backend
- **Framework**: Laravel 10/11
- **Language**: PHP 8.x
- **Database**: Google Cloud Firestore (NoSQL)
- **Authentication**: Firebase Authentication
- **Payment Gateway**: Paytrail SDK
- **PDF Generation**: Dompdf

### Frontend
- **Templating**: Blade
- **CSS Framework**: Bootstrap 5
- **Icons**: Bootstrap Icons
- **Charts**: Chart.js
- **Build Tool**: Vite

### Key Dependencies
```json
{
  "kreait/firebase-php": "Firebase SDK integration",
  "guzzlehttp/guzzle": "HTTP client for API calls",
  "paytrail/paytrail-php-sdk": "Payment processing"
}
```

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/          # All business logic controllers
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── MenuController.php
│   │   ├── OrdersController.php
│   │   ├── PaymentController.php
│   │   ├── PaymentConfigController.php
│   │   ├── SettingsController.php
│   │   ├── StaffController.php
│   │   └── ... (others)
│   └── Middleware/           # Request interceptors
│       ├── AdminMiddleware.php
│       ├── RestaurantAdminMiddleware.php
│       ├── BranchAdminMiddleware.php
│       └── AdminAuditMiddleware.php
├── Services/                 # Core service classes
│   ├── FirebaseService.php   # Firestore operations
│   └── PaytrailClient.php    # Payment processing
└── Utils/                    # Utility classes
    └── UIStrings.php         # Internationalization

resources/
├── views/
│   ├── admin/                # Admin panel views
│   │   ├── dashboard.blade.php
│   │   ├── menu/
│   │   ├── orders.blade.php
│   │   ├── settings/
│   │   └── ... (others)
│   ├── auth/                 # Authentication views
│   └── layouts/
│       └── admin.blade.php   # Main layout

routes/
├── web.php                   # Web routes
├── api.php                   # API routes
└── console.php               # Artisan commands

config/
├── app.php                   # Application config
├── services.php              # Third-party services
└── ... (other configs)

storage/
└── app/
    └── firebase/
        └── firebase_credentials.json  # Firebase service account key
```

---

## Authentication & Authorization

### Three-Tier Role System

#### 1. **Super Admin** (`admin`)
- Full system access
- Manages all restaurants and branches
- Can create/edit/delete any resource
- Access to audit logs and system settings

#### 2. **Restaurant Admin** (`restaurant_admin`)
- Scoped to a single restaurant
- Manages all branches within their restaurant
- Cannot change restaurant selection (forced context)
- Blocked routes: `settings.context.save`, `settings.restaurants.*`

#### 3. **Branch Admin** (`branch_admin`)
- Scoped to a single branch within a restaurant
- Most restricted access
- Cannot change restaurant or branch selection
- Cannot access restaurant-level or system settings

### Authentication Flow

```php
// 1. Login Request (AuthController::login)
POST /login
├── Validates email/password
├── Calls Firebase Authentication API
├── Retrieves user document from Firestore
├── Sets session variables:
│   ├── firebase_user (uid, email, token)
│   ├── role (admin/restaurant_admin/branch_admin)
│   ├── restaurantId (if applicable)
│   └── branchId (if applicable)
└── Redirects to /admin

// 2. Context Enforcement (Middleware)
Request → AdminMiddleware
         → RestaurantAdminMiddleware (forces restaurantId)
         → BranchAdminMiddleware (forces branchId)
         → Controller
```

### Session Management

```php
// Session structure
session([
    'firebase_user' => [
        'uid' => 'firebase_user_id',
        'email' => 'user@example.com',
        'token' => 'firebase_auth_token'
    ],
    'role' => 'restaurant_admin',
    'restaurantId' => 'rest_abc123',
    'branchId' => 'branch_xyz789',
    'ui_lang' => 'en' // or 'fi'
]);
```

---

## Core Workflows

### 1. Restaurant & Branch Management

#### Firestore Structure
```
restaurants/
  {restaurantId}/
    fields:
      name: string
      status: string
      location: map
    branches/
      {branchId}/
        fields:
          name: string
          contact: string
          status: string
          address: map
          serviceCharge: double
          deliveryCharge: double
          paymentConfig: map  # Nested payment configuration
            gatewayName: string
            merchantId: string
            secretKeyEnc: string (encrypted)
            isActive: boolean
```

#### Workflow: Create Branch

```php
// routes/web.php
Route::post('/admin/settings/restaurants/{restaurantId}/branches', 
    [SettingsController::class, 'storeBranch']);

// SettingsController::storeBranch()
1. Validate input (name, contact, address, etc.)
2. Generate branchId: 'br_' + random(6)
3. Prepare Firestore document data
4. Call FirebaseService::createDocument()
   └── Path: "restaurants/{restaurantId}/branches"
5. Redirect with success message
```

### 2. Menu Management

#### Firestore Structure
```
restaurants/{restaurantId}/branches/{branchId}/
  menus/                          # Categories
    {categoryId}/
      fields:
        name_en, name_fi
        description_en, description_fi
        displayOrder: int
        isSpecial: boolean
        imageUrl: string
      items/                      # Menu items
        {itemId}/
          fields:
            name_en, name_fi
            description_en, description_fi
            price: double
            offerPrice: double (optional)
            offerPriceAvailable: boolean
            availability: boolean
            imageUrl: string
          sizes/                  # Item size variants
            {sizeId}/
              fields:
                name_en, name_fi
                price: double
          bases/                  # Pizza base options
            {baseId}/
              fields:
                name_en, name_fi
                price: double
          ingredients/            # Toppings/extras
            {ingredientId}/
              fields:
                name: string
                maxSelections: int
              sizeLimits/         # Per-size limits
                {sizeId}/
                  fields:
                    maxSelections: int
```

#### Workflow: Create Menu Item

```php
// routes/web.php
Route::post('/admin/menu/{categoryId}/items', 
    [MenuController::class, 'storeItem']);

// MenuController::storeItem()
1. Get context (restaurantId, branchId)
2. Validate input:
   - name_en, name_fi
   - description_en, description_fi
   - price
   - offerPrice (optional)
   - offerPriceAvailable (checkbox)
   - image file
   - sizes[] (array of sizeIds)
   - bases[] (array of baseIds)
   - ingredients[] (array of ingredientIds)
   - ingredient_max[ingredientId][sizeId] (nested limits)

3. Handle image upload:
   if (file provided) {
       filename = {timestamp}_{original}
       move to public/uploads/items/
       imageUrl = asset('uploads/items/{filename}')
   }

4. Determine final price:
   if (offerPriceAvailable && offerPrice provided) {
       finalPrice = offerPrice
   } else {
       finalPrice = price
   }

5. Generate itemId: 'item_' + random(6)

6. Create item document:
   path: "restaurants/{restaurantId}/branches/{branchId}/menus/{categoryId}/items"
   fields: name_en, name_fi, description_en, description_fi, 
           price, offerPrice, offerPriceAvailable, availability, imageUrl

7. Create size subcollections:
   for each sizeId in sizes[]:
       Fetch size document from global or branch sizes
       Create: items/{itemId}/sizes/{sizeId}
       fields: name_en, name_fi, price

8. Create base subcollections:
   for each baseId in bases[]:
       Fetch base document
       Create: items/{itemId}/bases/{baseId}
       fields: name_en, name_fi, price

9. Create ingredient subcollections:
   for each ingredientId in ingredients[]:
       Fetch ingredient document
       Create: items/{itemId}/ingredients/{ingredientId}
       fields: name, maxSelections (default 0)
       
       for each sizeId in ingredient_max[ingredientId]:
           Create: ingredients/{ingredientId}/sizeLimits/{sizeId}
           fields: maxSelections

10. Set audit attributes for logging
11. Redirect to menu index
```

### 3. Order Management

#### Firestore Structure
```
orders/
  {orderId}/
    fields:
      restaurantId: string
      branchId: string
      customerId: string
      customerName: string
      customerEmail: string
      orderType: string (delivery/pickup)
      status: string (pending/confirmed/preparing/ready/completed/cancelled)
      items: array of maps
        - itemId
        - itemName
        - quantity
        - price
        - selectedSize
        - selectedBase
        - selectedIngredients
      subtotal: double
      tax: double
      serviceCharge: double
      deliveryCharge: double
      totalAmount: double
      paymentMethod: string
      paymentStatus: string
      createdAt: timestamp
      updatedAt: timestamp
```

#### Workflow: Order Status Update

```php
// OrdersController::updateStatus()
1. Validate orderId and new status
2. Load order document from Firestore
3. Update status field
4. Update updatedAt timestamp
5. If status = 'confirmed' or 'preparing':
   - Send push notification to customer
   - Log status change in audit
6. Return JSON response
```

### 4. Payment Processing (Paytrail Integration)

#### Configuration Storage

Payment credentials are stored **nested inside branch documents**:

```php
// Firestore path: restaurants/{restaurantId}/branches/{branchId}
paymentConfig: {
    gatewayName: "Paytrail",
    merchantId: "123456",
    secretKeyEnc: "encrypted_secret_key",  // encrypted with Crypt::encryptString()
    isActive: true,
    createdAt: "2024-01-15T10:30:00Z",
    updatedAt: "2024-01-15T10:30:00Z"
}
```

#### Workflow: Payment Initiation

```php
// POST /api/v1/payments/initiate
PaymentController::initiate()

1. Validate request:
   - branch_id
   - restaurant_id
   - order_id
   - amount
   - customer_email

2. Load branch document:
   path: "restaurants/{restaurantId}/branches/{branchId}"

3. Resolve payment config:
   paymentConfig = branchDoc['fields']['paymentConfig']['mapValue']['fields']
   
4. Decrypt secret:
   secretKey = Crypt::decryptString(paymentConfig['secretKeyEnc'])

5. Create Paytrail client:
   client = new PaytrailClient(merchantId, secretKey)

6. Build payment request:
   {
       stamp: orderId + '_' + timestamp,
       reference: orderId,
       amount: amountCents (€ * 100),
       currency: 'EUR',
       language: 'FI',
       customer: {
           email: customer_email
       },
       redirectUrls: {
           success: url('/api/v1/payments/callback?status=success'),
           cancel: url('/api/v1/payments/callback?status=cancel')
       }
   }

7. Send to Paytrail API:
   response = client.createPayment(payload)

8. Update order with payment reference:
   updateDocument('orders', orderId, {
       paymentTransactionId: response['transactionId'],
       paymentStatus: 'pending'
   })

9. Return payment URL to client:
   { payment_url: response['payment_url'] }
```

#### Workflow: Payment Callback

```php
// GET /api/v1/payments/callback
PaymentController::callback()

1. Extract query parameters:
   - checkout-transaction-id
   - checkout-status
   - checkout-reference (orderId)
   - signature (HMAC)

2. Verify HMAC signature:
   PaytrailSignature::validate(params, headers, secretKey)

3. Load order document

4. Update payment status:
   if (status === 'ok') {
       paymentStatus = 'paid'
       orderStatus = 'confirmed'
   } else {
       paymentStatus = 'failed'
   }

5. Update order in Firestore

6. Redirect customer:
   if (paid) → success page
   else → failure page
```

---

## Key Services

### FirebaseService

The `FirebaseService` class (located at `app/Services/FirebaseService.php`) handles all Firestore operations.

#### Key Methods

##### 1. **Authentication**
```php
private function getAccessToken(): string
```
- Uses service account credentials
- Generates OAuth2 access token
- Caches token until expiry
- Used for all Firestore API calls

##### 2. **Read Operations**
```php
public function getCollection(string $collectionName): array
```
- Fetches all documents in a collection
- Returns: `['documents' => [...]]`

```php
public function getDocument(string $collectionName, string $documentId): array
```
- Fetches single document by ID
- Returns: `['fields' => [...], 'name' => '...']`

```php
public function queryDocuments(string $collectionName, array $conditions): array
```
- Structured query with filters
- Example:
```php
$orders = $firebase->queryDocuments('orders', [
    ['field' => 'restaurantId', 'op' => 'EQUAL', 'value' => 'rest_123'],
    ['field' => 'status', 'op' => 'EQUAL', 'value' => 'pending']
]);
```

##### 3. **Write Operations**
```php
public function createDocument(string $collectionName, array $data, ?string $documentId = null): array
```
- Creates new document
- Auto-generates ID if not provided
- Encodes data to Firestore format

```php
public function updateDocument(string $collectionName, string $documentId, array $data): array
```
- Partial update using field masks
- Only updates specified fields
- Preserves other fields

```php
public function deleteDocument(string $collectionName, string $documentId): void
```
- Permanently deletes document
- Does NOT cascade to subcollections

##### 4. **Data Encoding**
```php
private function encodeFirestoreData(array $data): array
```
Converts PHP data to Firestore format:
```php
// Input
['name' => 'Pizza', 'price' => 12.50, 'active' => true]

// Output
[
    'name' => ['stringValue' => 'Pizza'],
    'price' => ['doubleValue' => 12.50],
    'active' => ['booleanValue' => true]
]
```

#### Example Usage

```php
// Create a new category
$firebase = app(\App\Services\FirebaseService::class);

$firebase->createDocument(
    "restaurants/rest_123/branches/br_456/menus",
    [
        'name_en' => 'Pizzas',
        'name_fi' => 'Pizzat',
        'displayOrder' => 1,
        'isSpecial' => false
    ],
    'cat_abc789'
);

// Update category
$firebase->updateDocument(
    "restaurants/rest_123/branches/br_456/menus",
    'cat_abc789',
    ['displayOrder' => 2]
);

// Fetch all categories
$response = $firebase->getCollection("restaurants/rest_123/branches/br_456/menus");
$categories = $response['documents'] ?? [];
```

---

### PaytrailClient

The `PaytrailClient` class (located at `app/Services/PaytrailClient.php`) wraps the Paytrail SDK for payment processing.

```php
class PaytrailClient
{
    public function __construct(string $merchantId, string $secret)
    
    public function createPayment(array $payload): array
    // Returns: ['payment_url' => '...', 'transactionId' => '...']
    
    public function verifySignature(array $params, array $headers): bool
}
```

---

## Data Models & Storage

### Firestore Collections

#### Root Collections
- `restaurants/` - Restaurant master data
- `users/` - Admin users (role, restaurantId, branchId)
- `customers/` - Customer accounts
- `orders/` - All orders (flat structure)
- `system_settings/` - Global settings
- `audit_logs/` - Audit trail
- `consents/` - GDPR consent records

#### Nested Collections (Subcollections)

Under each restaurant:
```
restaurants/{restaurantId}/
  branches/{branchId}/
    menus/{categoryId}/
      items/{itemId}/
        sizes/{sizeId}/
        bases/{baseId}/
        ingredients/{ingredientId}/
          sizeLimits/{sizeId}/
    sizes/ (branch-specific sizes)
    bases/ (branch-specific bases)
    ingredients/ (branch-specific ingredients)
      subingredients/{subId}/
    lounas_hours/ (lunch hours by day)
    promotions/ (branch promotions)
```

### Data Decoding Pattern

Throughout the codebase, you'll see this pattern for reading Firestore data:

```php
$doc = $firebase->getDocument('restaurants', 'rest_123');
$fields = $doc['fields'] ?? [];

// Extract values
$name = $fields['name']['stringValue'] ?? '';
$serviceCharge = $fields['serviceCharge']['doubleValue'] ?? 0;
$isActive = $fields['isActive']['booleanValue'] ?? false;

// Recursive decode function (common in controllers)
$decode = function($v) use (&$decode) {
    if (!is_array($v)) return $v;
    if (isset($v['stringValue'])) return $v['stringValue'];
    if (isset($v['integerValue'])) return (int)$v['integerValue'];
    if (isset($v['doubleValue'])) return (float)$v['doubleValue'];
    if (isset($v['booleanValue'])) return (bool)$v['booleanValue'];
    if (isset($v['nullValue'])) return null;
    if (isset($v['arrayValue']['values'])) {
        return array_map($decode, $v['arrayValue']['values']);
    }
    if (isset($v['mapValue']['fields'])) {
        $out = [];
        foreach ($v['mapValue']['fields'] as $k => $vv) {
            $out[$k] = $decode($vv);
        }
        return $out;
    }
    return $v;
};
```

---

## API Endpoints

### Authentication Endpoints
```
POST   /login               - User login
POST   /logout              - User logout
GET    /forgot-password     - Password reset form
POST   /forgot-password     - Send reset email
```

### Admin Panel Routes (Session-protected)
```
GET    /admin                           - Dashboard
GET    /admin/menu                      - Menu management
GET    /admin/orders                    - Orders list
POST   /admin/orders/{id}/status        - Update order status
GET    /admin/staff                     - Staff management
GET    /admin/reports                   - Analytics & reports
GET    /admin/settings                  - Settings hub
GET    /admin/audit-logs                - Audit log viewer
```

### API Routes (Token/Session-protected)
```
# Payment Configuration
GET    /api/v1/payment-configs                          - List all configs (super admin)
GET    /api/v1/payment-configs/{restaurantId}/{branchId} - Get config
PUT    /api/v1/payment-configs/{restaurantId}/{branchId} - Upsert config

# Payment Processing
POST   /api/v1/payments/initiate        - Start payment
POST   /api/v1/payments/webhook         - Paytrail webhook
GET    /api/v1/payments/callback        - Payment redirect callback

# Reports
GET    /api/reports/sales               - Sales data
GET    /api/reports/items               - Top items
GET    /api/reports/orders-by-status    - Order breakdown
GET    /api/reports/orders-by-type      - Delivery vs pickup
```

---

## Frontend Components

### Layouts

#### `resources/views/layouts/admin.blade.php`
Main layout template with:
- **Sidebar navigation** with role-based menu filtering
- **Top bar** with restaurant/branch selectors (role-dependent)
- **Loading overlay** for async operations
- **Mobile responsive** menu toggle
- **Language switcher** (EN/FI)

Key Features:
```php
// Context selectors (top bar)
@if(session('role') === 'admin')
    <select id="restaurantSelect">...</select>
    <select id="branchSelect">...</select>
@endif

// Sidebar nav with role checks
<a href="/admin/settings/restaurants" 
   class="{{ in_array(session('role'), ['restaurant_admin','branch_admin']) ? 'd-none' : '' }}">
    Restaurants
</a>
```

### Dashboard

`admin/dashboard.blade.php`

- **Statistics cards**: Total orders, revenue, customers, success rate
- **Recent orders table**
- **Quick actions** (role-filtered)
- **Performance metrics**

Data Loading:
```php
// AdminController::index()
1. Get context (restaurantId, branchId)
2. Fetch orders across all restaurants (admin) or scoped
3. Calculate stats:
   - totalOrders, totalRevenue
   - todayOrders, weekOrders, monthOrders
   - avgOrderValue, successRate
4. Get recent orders (last 10)
5. Pass to view
```

### Menu Management

`admin/menu/`

Key Views:
- `index.blade.php` - Category and item listing
- `create-category.blade.php` - Add category form
- `edit-category.blade.php` - Edit category
- `create-item.blade.php` - Add menu item (complex form)
- `edit-item.blade.php` - Edit item

**Create Item Form Features:**
- Basic info (name EN/FI, description EN/FI, price)
- Offer price with checkbox toggle
- Image upload with preview
- Multi-select for sizes
- Multi-select for bases
- Ingredient selector with per-size max limits (dynamic)

JavaScript for dynamic ingredient limits:
```javascript
// When ingredient checkbox is checked
function toggleIngredient(checkbox, ingredientId) {
    const container = document.getElementById('limits_' + ingredientId);
    if (checkbox.checked) {
        container.style.display = 'block';
        // Show input for each size
        sizes.forEach(size => {
            // Create input: ingredient_max[ingredientId][sizeId]
        });
    } else {
        container.style.display = 'none';
    }
}
```

### Orders Management

`admin/orders.blade.php`

Real-time order dashboard with:
- **Filter panel**: Restaurant, Branch, Status, Order Type
- **Statistics cards**: Total, Active, Completed, Cancelled
- **Order cards** with status badges
- **Quick actions**: View details, Update status
- **Auto-refresh** every 30 seconds

Status Update Flow:
```javascript
async function updateOrderStatus(orderId, newStatus) {
    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('status', newStatus);
    
    const response = await fetch(`/admin/orders/${orderId}/status`, {
        method: 'POST',
        body: formData
    });
    
    if (response.ok) {
        // Reload orders without page refresh
        loadOrders();
        showToast('Order status updated');
    }
}
```

### Reports & Analytics

`admin/reports.blade.php`

Interactive reports with:
- **Period selector**: Daily, Weekly, Monthly
- **Date range picker**
- **Branch filter**
- **Chart.js visualizations**:
  - Sales trends (line chart)
  - Top items (bar chart)
  - Order status breakdown (doughnut)
  - Order type distribution (pie)
- **Export options**: CSV, Excel, PDF

Data fetching pattern:
```javascript
async function loadSales() {
    const params = {
        period: document.getElementById('period').value,
        branchId: document.getElementById('branchId').value,
        dateFrom: document.getElementById('dateFrom').value,
        dateTo: document.getElementById('dateTo').value
    };
    
    const data = await fetchJson('/api/reports/sales', params);
    
    // Update chart
    updateChart(salesChart, data);
    
    // Update metrics
    document.getElementById('totalRevenue').textContent = 
        formatCurrency(data.reduce((sum, d) => sum + d.total, 0));
}
```

---

## Payment Integration

### Paytrail Configuration Management

See `docs/copilot-prompts/payment-configs.md` for full specification.

#### Controller: `PaymentConfigController`

**Endpoints:**

1. **List All Configs** (Super Admin only)
```php
GET /api/v1/payment-configs

PaymentConfigController::index()
1. Check role === 'admin'
2. Iterate all restaurants
3. For each branch, extract paymentConfig map
4. Decrypt secrets for authorized view
5. Return JSON array
```

2. **Get Single Config**
```php
GET /api/v1/payment-configs/{restaurantId}/{branchId}

PaymentConfigController::show()
1. Authorize access (policy check)
2. Load branch document
3. Extract paymentConfig map
4. Decrypt secretKeyEnc → secret_key
5. Return JSON
```

3. **Upsert Config**
```php
PUT /api/v1/payment-configs/{restaurantId}/{branchId}

PaymentConfigController::upsert()
1. Validate input:
   - gatewayName (optional, default 'Paytrail')
   - merchantId (required)
   - secret_key (required on create, optional on update)
   - isActive (boolean)

2. Authorize access

3. Load existing branch document

4. If secret_key provided:
   encrypted = Crypt::encryptString(secret_key)
   
5. Build paymentConfig map:
   {
       gatewayName,
       merchantId,
       secretKeyEnc,  # only update if secret_key was provided
       isActive,
       updatedAt: now()
   }

6. Update branch document (partial update):
   updateDocument(path, branchId, { paymentConfig: map })

7. Return updated config (with decrypted secret)
```

#### Authorization Policy

```php
// Example policy logic
public function manage(User $user, string $restaurantId, string $branchId): bool
{
    if ($user->role === 'admin') {
        return true; // Super admin: full access
    }
    
    if ($user->role === 'restaurant_admin') {
        return $user->restaurantId === $restaurantId;
    }
    
    if ($user->role === 'branch_admin') {
        return $user->restaurantId === $restaurantId 
            && $user->branchId === $branchId;
    }
    
    return false;
}
```

### Payment Workflow (Customer-Facing)

Though this is an admin panel, here's how payments work end-to-end:

```
Customer Mobile App
    ↓
1. Creates order
2. Calls: POST /api/v1/payments/initiate
    {
        branch_id,
        restaurant_id,
        order_id,
        amount,
        customer_email
    }
    ↓
3. Admin panel resolves payment config from branch
4. Creates Paytrail payment session
5. Returns: { payment_url }
    ↓
6. Customer redirected to Paytrail hosted page
7. Completes payment
    ↓
8. Paytrail redirects to: /api/v1/payments/callback
9. Admin panel verifies HMAC signature
10. Updates order: paymentStatus = 'paid', status = 'confirmed'
11. Sends push notification to customer
12. Redirects to success page
```

---

## Additional Features

### 1. Audit Logging

All admin actions are logged via `AdminAuditMiddleware` (located at `app/Http/Middleware/AdminAuditMiddleware.php`).

Logged data:
- User (uid, email, role)
- Action (method, route, path)
- Context (restaurantId, branchId)
- Request params (sanitized, secrets removed)
- Before/After snapshots (for updates)
- Timestamp, IP address

Storage: `audit_logs/{logId}` in Firestore

View logs: `admin/audit-logs`

### 2. Push Notifications

`NotificationsController` (located at `app/Http/Controllers/NotificationsController.php`)

Send to:
- **Device token** (individual user)
- **Topic** (all subscribers, e.g., "all_customers")
- **Restaurant/branch customers**

Example:
```php
$firebase->messaging->send([
    'token' => $deviceToken,
    'notification' => [
        'title' => 'Order Confirmed',
        'body' => 'Your order #123 is being prepared'
    ],
    'data' => [
        'orderId' => '123',
        'type' => 'order_update'
    ]
]);
```

### 3. Lounas Hours

`LounasHourController` (located at `app/Http/Controllers/LounasHourController.php`)

Manage lunch hours per branch:
- Day of week (0=Sun, 1=Mon, ..., 6=Sat)
- Start time (HH:MM)
- End time (HH:MM)
- Active status

Stored: `restaurants/{restaurantId}/branches/{branchId}/lounas_hours/{hourId}`

### 4. Promotions

`PromotionsController` (located at `app/Http/Controllers/PromotionsController.php`)

Create promotional banners/offers:
- Title, description (EN/FI)
- Discount percentage
- Valid from/to dates
- Image upload
- Active status

Stored: `restaurants/{restaurantId}/branches/{branchId}/promotions/{promoId}`

### 5. Internationalization

`UIStrings` (located at `app/Utils/UIStrings.php`)

Provides translations for UI elements:
```php
\App\Utils\UIStrings::t('dashboard.title')  // "Dashboard"
\App\Utils\UIStrings::t('orders.status.pending')  // "Pending"
```

Language toggled via session:
```php
session(['ui_lang' => 'fi']);
```

---

## Development Guidelines

### Adding a New Feature

1. **Create Controller** (if needed)
```bash
php artisan make:controller FeatureController
```

2. **Define Routes** in `routes/web.php`
```php
Route::prefix('admin')->middleware([...])->group(function () {
    Route::get('/feature', [FeatureController::class, 'index']);
    Route::post('/feature', [FeatureController::class, 'store']);
});
```

3. **Create View** in `resources/views/admin/feature/`
```php
@extends('layouts.admin')
@section('content')
    <!-- Your content -->
@endsection
```

4. **Add Navigation Link** in `layouts/admin.blade.php`
```php
<a href="{{ url('/admin/feature') }}">
    <i class="bi bi-icon"></i>
    Feature Name
</a>
```

5. **Implement Firestore Operations**
```php
$firebase = app(\App\Services\FirebaseService::class);
$firebase->createDocument('collection_name', $data, $id);
```

### Testing Firestore Operations

Use the test endpoint:
```php
// app/Http/Controllers/FirebaseController.php
public function test()
{
    $this->firebase->createDocument('testing', [
        'message' => 'Test data',
        'timestamp' => now()->toIso8601String()
    ]);
    
    return 'Success!';
}
```

Visit: `/firebase-test`

---

## Environment Setup

### Required Files

1. **`.env`** - Application configuration
```env
APP_NAME="Food Ordering Admin"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

FIREBASE_PROJECT_ID=your-project-id
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
```

2. **`storage/app/firebase/firebase_credentials.json`**
```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk-...@your-project.iam.gserviceaccount.com",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  ...
}
```

### Installation

```bash
# 1. Clone repository
git clone <repository-url>
cd food-ordering-admin-panel

# 2. Install dependencies
composer install
npm install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Add Firebase credentials
# Place firebase_credentials.json in storage/app/firebase/

# 6. Build assets
npm run build

# 7. Serve application
php artisan serve
```

---

## Security Considerations

### 1. **Secret Encryption**
All payment secrets stored encrypted:
```php
$encrypted = Crypt::encryptString($secret);
$decrypted = Crypt::decryptString($encrypted);
```

### 2. **CSRF Protection**
All forms include CSRF token:
```blade
<form method="POST">
    @csrf
    ...
</form>
```

### 3. **Role-Based Access**
Middleware enforces scoping:
- RestaurantAdminMiddleware forces restaurantId
- BranchAdminMiddleware forces branchId
- Blocked routes return 403 for unauthorized access

### 4. **Audit Logging**
All admin actions logged with:
- User context
- Before/After state
- Sanitized params (secrets removed)

### 5. **Input Validation**
All requests validated:
```php
$data = $request->validate([
    'name' => 'required|string|max:255',
    'price' => 'required|numeric|min:0',
]);
```

---

## Troubleshooting

### Common Issues

**1. Firestore Connection Fails**
- Check `firebase_credentials.json` exists and is valid
- Verify `FIREBASE_PROJECT_ID` in `.env`
- Check service account has Firestore permissions

**2. Payment Integration Errors**
- Verify Paytrail credentials are correct
- Check `paymentConfig` exists on branch
- Ensure `secretKeyEnc` can be decrypted

**3. Session Lost After Login**
- Check `SESSION_DRIVER` in `.env`
- Clear cache: `php artisan cache:clear`
- Check session lifetime in `config/session.php`

**4. Images Not Uploading**
- Verify `public/uploads` directory exists and is writable
- Check file permissions: `chmod -R 775 public/uploads`
- Increase upload limits in `php.ini`

---

## Summary

This food ordering admin panel is a **Laravel application** that uses **Firebase Firestore** for data storage instead of SQL databases. It implements a **three-tier role system** (Super Admin, Restaurant Admin, Branch Admin) with strict access controls. 

Key architectural patterns:
- **Firestore service layer** abstracts all database operations
- **Nested subcollections** organize hierarchical data (restaurants → branches → menus → items)
- **Session-based authentication** with Firebase
- **Middleware enforcement** of role-based access
- **Encrypted storage** of sensitive data (payment secrets)
- **Audit logging** of all admin actions

The codebase is well-structured with clear separation of concerns, making it maintainable and extensible for future features.

---

**Document Version**: 1.0  
**Last Updated**: December 29, 2025  
**Maintained By**: Development Team
