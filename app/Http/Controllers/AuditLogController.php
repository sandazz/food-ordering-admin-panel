<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogController extends Controller
{
    public function index(Request $request, FirebaseService $firebase)
    {
        $role = (string) $request->session()->get('role');
        $uid = (string) $request->session()->get('firebase_user.uid');
        $restaurantId = (string) $request->session()->get('restaurantId');
        $branchId = (string) $request->session()->get('branchId');

        // Filters from query
        $qRestaurant = $request->query('restaurantId');
        $qBranch = $request->query('branchId');
        // username/email filter (replaces UID and route filters)
        $qUser = $request->query('user');
        $qMethod = $request->query('method');

        // Fetch documents (Firestore simple approach: list and filter in PHP)
        $resp = $firebase->getCollection('audit_logs');
        $docs = $resp['documents'] ?? [];
        $logs = [];
        foreach ($docs as $doc) {
            $name = Str::afterLast($doc['name'] ?? '', '/');
            $f = $doc['fields'] ?? [];
            $log = [
                'id' => $name,
                'createdAt' => $f['createdAt']['stringValue'] ?? '',
                'uid' => $f['uid']['stringValue'] ?? '',
                'userEmail' => $f['userEmail']['stringValue'] ?? '',
                'role' => $f['role']['stringValue'] ?? '',
                'restaurantId' => $f['restaurantId']['stringValue'] ?? '',
                'branchId' => $f['branchId']['stringValue'] ?? '',
                'method' => $f['method']['stringValue'] ?? '',
                'route' => $f['route']['stringValue'] ?? '',
                'path' => $f['path']['stringValue'] ?? '',
                'status' => (int) ($f['status']['integerValue'] ?? 0),
                'ip' => $f['ip']['stringValue'] ?? '',
                'params' => $this->decodeMap($f['params'] ?? null),
                // Optional: before/after snapshots if present in the log entry
                'before' => $this->decodeMap($f['before'] ?? null),
                'after' => $this->decodeMap($f['after'] ?? null),
            ];

            // Role scoping
            if ($role === 'admin') {
                // full access
            } elseif ($role === 'restaurant_admin') {
                if ($log['restaurantId'] !== $restaurantId) { continue; }
            } else { // branch_admin or others
                if ($log['restaurantId'] !== $restaurantId || $log['branchId'] !== $branchId) { continue; }
            }

            // Apply filters
            if ($qRestaurant && $log['restaurantId'] !== $qRestaurant) { continue; }
            if ($qBranch && $log['branchId'] !== $qBranch) { continue; }
            if ($qUser) {
                $hay = strtolower(($log['userEmail'] ?? '').' '.$log['uid']);
                if (stripos($hay, strtolower($qUser)) === false) { continue; }
            }
            if ($qMethod && $log['method'] !== $qMethod) { continue; }

            $logs[] = $log;
        }

        // Sort by createdAt desc
        usort($logs, function($a, $b) {
            return strcmp($b['createdAt'], $a['createdAt']);
        });

        // Limit to recent 1000 entries for performance baseline
        $logs = array_slice($logs, 0, 1000);

        // Enrich logs with human-friendly fields (names, formatted time, summaries, diffs)
        [$restaurantNames, $branchNames] = $this->buildNameCaches($firebase, $logs);
        foreach ($logs as &$log) {
            $log['restaurantName'] = $restaurantNames[$log['restaurantId']] ?? $log['restaurantId'];
            $log['branchName'] = isset($branchNames[$log['restaurantId']][$log['branchId']])
                ? $branchNames[$log['restaurantId']][$log['branchId']]
                : $log['branchId'];

            // Time formatting
            $dt = null;
            try { $dt = Carbon::parse($log['createdAt']); } catch (\Throwable $e) { $dt = null; }
            $log['createdAtFormatted'] = $dt ? $dt->format('Y-m-d H:i:s') : $log['createdAt'];
            $log['createdAtAgo'] = $dt ? $dt->diffForHumans() : '';

            // Concise param summary (kept for internal usage if needed)
            $log['paramSummary'] = $this->summarizeParams($log['params'] ?? []);

            // Field-level changes if before/after available
            $log['changes'] = $this->buildChanges($log['before'] ?? [], $log['after'] ?? [], $log['params'] ?? [], $log['method']);
            // Context lines for display (e.g., item name and number)
            [$ctxName, $ctxNumber, $ctxNumberKey] = $this->deriveContextPair($log['before'] ?? [], $log['after'] ?? []);
            $log['changeContext'] = $ctxName;
            $log['changeContextNumber'] = $ctxNumber;
            $log['changeContextNumberLabel'] = $ctxNumberKey;
        }
        unset($log);

        // Load options for filters
        $restaurants = $this->listRestaurants($firebase, $role, $restaurantId);
        $branches = [];
        if ($role === 'admin' && $qRestaurant) {
            $branches = $this->listBranches($firebase, $qRestaurant);
        } elseif ($role === 'restaurant_admin') {
            $branches = $this->listBranches($firebase, $restaurantId);
        } elseif ($role === 'branch_admin') {
            if ($restaurantId) { $branches = $this->listBranches($firebase, $restaurantId); }
        }

        // Pagination (array-based)
        $perPage = (int) max(5, min(100, (int) $request->query('per_page', 25)));
        $page = (int) max(1, (int) $request->query('page', 1));
        $total = count($logs);
        $offset = ($page - 1) * $perPage;
        $itemsForCurrentPage = array_slice($logs, $offset, $perPage);

        $logs = new LengthAwarePaginator(
            $itemsForCurrentPage,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.audit_logs.index', compact('logs','restaurants','branches','role','restaurantId','branchId','qRestaurant','qBranch','qUser','qMethod'));
    }

    private function decodeMap($value)
    {
        if (!is_array($value)) { return []; }
        $fields = $value['mapValue']['fields'] ?? [];
        $out = [];
        foreach ($fields as $k => $v) {
            $out[$k] = $this->decodeField($v);
        }
        return $out;
    }

    private function decodeField($v)
    {
        if (!is_array($v)) { return $v; }
        if (isset($v['stringValue'])) { return $v['stringValue']; }
        if (isset($v['integerValue'])) { return (int)$v['integerValue']; }
        if (isset($v['doubleValue'])) { return (float)$v['doubleValue']; }
        if (isset($v['booleanValue'])) { return (bool)$v['booleanValue']; }
        if (isset($v['nullValue'])) { return null; }
        if (isset($v['arrayValue']['values'])) {
            return array_map(function($x){ return $this->decodeField($x); }, $v['arrayValue']['values']);
        }
        if (isset($v['mapValue']['fields'])) {
            $out = [];
            foreach ($v['mapValue']['fields'] as $k => $vv) { $out[$k] = $this->decodeField($vv); }
            return $out;
        }
        return $v;
    }

    private function listRestaurants(FirebaseService $firebase, string $role, ?string $restaurantId)
    {
        if ($role === 'restaurant_admin' || $role === 'branch_admin') {
            if (!$restaurantId) { return []; }
            $doc = $firebase->getDocument('restaurants', $restaurantId);
            $f = $doc['fields'] ?? [];
            return [[ 'id' => $restaurantId, 'name' => $f['name']['stringValue'] ?? $restaurantId ]];
        }
        $resp = $firebase->getCollection('restaurants');
        $docs = $resp['documents'] ?? [];
        $restaurants = [];
        foreach ($docs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $restaurants[] = [ 'id' => $id, 'name' => $f['name']['stringValue'] ?? $id ];
        }
        return $restaurants;
    }

    private function listBranches(FirebaseService $firebase, string $restaurantId)
    {
        $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
        $docs = $resp['documents'] ?? [];
        $branches = [];
        foreach ($docs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $branches[] = [ 'id' => $id, 'name' => $f['name']['stringValue'] ?? $id ];
        }
        return $branches;
    }

    /**
     * Build caches for restaurant and branch names to avoid N+1 queries.
     * @return array{0: array<string,string>, 1: array<string,array<string,string>>}
     */
    private function buildNameCaches(FirebaseService $firebase, array $logs): array
    {
        $restaurantIds = [];
        $branchKeys = []; // [restaurantId|branchId] => true
        foreach ($logs as $log) {
            if (!empty($log['restaurantId'])) { $restaurantIds[$log['restaurantId']] = true; }
            if (!empty($log['restaurantId']) && !empty($log['branchId'])) {
                $branchKeys[$log['restaurantId'].'|'.$log['branchId']] = true;
            }
        }

        // Fetch restaurant names
        $restaurantNames = [];
        foreach (array_keys($restaurantIds) as $rid) {
            try {
                $doc = $firebase->getDocument('restaurants', $rid);
                $f = $doc['fields'] ?? [];
                $restaurantNames[$rid] = $f['name']['stringValue'] ?? $rid;
            } catch (\Throwable $e) {
                $restaurantNames[$rid] = $rid;
            }
        }

        // Fetch branch names per restaurant
        $branchNames = [];
        foreach (array_keys($branchKeys) as $key) {
            [$rid, $bid] = explode('|', $key, 2);
            try {
                $doc = $firebase->getDocument("restaurants/{$rid}/branches", $bid);
                $f = $doc['fields'] ?? [];
                $branchNames[$rid][$bid] = $f['name']['stringValue'] ?? $bid;
            } catch (\Throwable $e) {
                $branchNames[$rid][$bid] = $bid;
            }
        }

        return [$restaurantNames, $branchNames];
    }

    /**
     * Create a short, readable summary from request params.
     */
    private function summarizeParams(array $params): string
    {
        if (empty($params)) { return ''; }
        // Remove noisy keys
        $hidden = ['_method','_token','password','password_confirmation'];
        foreach ($hidden as $k) { unset($params[$k]); }

        $parts = [];
        foreach (['name','title','email','enabled','active','status','quantity','price'] as $key) {
            if (array_key_exists($key, $params)) {
                $val = $params[$key];
                if (is_array($val)) { $val = json_encode($val); }
                $parts[] = $key.'='.strval($val);
            }
        }
        if (!empty($parts)) { return implode('; ', $parts); }

        // Fallback: first few keys
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i >= 5) { break; }
            if (is_array($v)) { $v = json_encode($v); }
            $parts[] = $k.'='.strval($v);
            $i++;
        }
        return implode('; ', $parts);
    }

    /**
     * Format a value for display based on field name and type.
     * @param mixed $value The value to format
     * @param string $field The field name (used to infer formatting rules)
     * @param bool $isOldValue Whether this is the old value (affects null formatting)
     * @return string Formatted value
     */
    private function formatChangeValue($value, string $field, bool $isOldValue = false): string
    {
        // Handle null
        if (is_null($value)) {
            return $isOldValue ? 'Not set' : '';
        }

        // Price fields
        $priceFields = ['price', 'offerPrice', 'offer_price', 'bases_price', 'sizes_price', 'total', 'amount', 'subtotal', 'discount'];
        foreach ($priceFields as $pf) {
            if (stripos($field, $pf) !== false) {
                return '€' . number_format((float)$value, 2);
            }
        }

        // Boolean fields
        $boolFields = ['enabled', 'active', 'available', 'status', 'visibility', 'is_active', 'is_enabled', 'published'];
        foreach ($boolFields as $bf) {
            if (stripos($field, $bf) !== false || is_bool($value)) {
                if (is_bool($value)) {
                    return $value ? 'Yes' : 'No';
                }
                $val = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (!is_null($val)) {
                    return $val ? 'Yes' : 'No';
                }
            }
        }

        // Date/timestamp fields
        if (stripos($field, 'date') !== false || stripos($field, 'time') !== false || stripos($field, 'At') !== false) {
            try {
                $dt = Carbon::parse($value);
                return $dt->format('Y-m-d H:i');
            } catch (\Throwable $e) {
                // Not a valid date, continue
            }
        }

        // Array/object
        if (is_array($value)) {
            $count = count($value);
            if ($count === 0) {
                return 'Empty';
            }
            // Check if it's a simple list or map
            if (array_keys($value) === range(0, $count - 1)) {
                // Indexed array
                return $count . ' items';
            } else {
                // Associative array - try to provide meaningful summary
                return $count . ' entries';
            }
        }

        // Numeric fields (quantity, count, etc.)
        if (is_numeric($value) && (stripos($field, 'quantity') !== false || stripos($field, 'count') !== false)) {
            return number_format((float)$value, 0);
        }

        // Default: convert to string
        return (string)$value;
    }

    /**
     * Determine a display label for a field. Maps boolean-like fields to 'Availability'.
     */
    private function displayLabelForField(string $field, $oldVal = null, $newVal = null): string
    {
        $normalized = strtolower($field);
        $availabilityFields = ['enabled', 'active', 'available', 'is_active', 'is_enabled'];
        if (in_array($normalized, $availabilityFields, true)) {
            return 'Availability';
        }
        return ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Compute a list of field-level changes from before/after maps. If before/after are not provided,
     * provide a best-effort summary based on params and HTTP method.
     * @return array<int, array{field:string, label:string, from:mixed, to:mixed, formatted_from:string, formatted_to:string, summary:string}>
     */
    private function buildChanges(array $before, array $after, array $params, ?string $method): array
    {
        $changes = [];
        if (!empty($before) || !empty($after)) {
            $keys = array_unique(array_merge(array_keys($before ?? []), array_keys($after ?? [])));
            foreach ($keys as $k) {
                $old = $before[$k] ?? null;
                $new = $after[$k] ?? null;
                if ($old !== $new) {
                    $change = [
                        'field' => (string)$k,
                        'label' => $this->displayLabelForField((string)$k, $old, $new),
                        'from' => $old,
                        'to' => $new,
                        'formatted_from' => '',
                        'formatted_to' => '',
                        'summary' => ''
                    ];

                    // Handle nested structures (bases, sizes, etc.)
                    if (in_array($k, ['bases', 'sizes', 'toppings', 'extras'])) {
                        $change['summary'] = $this->summarizeNestedChange($k, $old, $new);
                    } elseif (in_array($k, ['bases_price', 'sizes_price', 'toppings_price', 'extras_price'])) {
                        $change['summary'] = $this->summarizePriceMapChange($k, $old, $new);
                    } else {
                        // Regular field change with formatting
                        $change['formatted_from'] = $this->formatChangeValue($old, $k, true);
                        $change['formatted_to'] = $this->formatChangeValue($new, $k, false);
                        $change['summary'] = $this->buildChangeSummary($k, $old, $new, $change['formatted_from'], $change['formatted_to']);
                    }

                    $changes[] = $change;
                }
            }
            return $changes;
        }

        // No before/after snapshots: do not infer; return empty to hide non-confirmed changes
        return [];
    }

    /**
     * Summarize a change to a nested structure (e.g. bases, sizes)
     */
    private function summarizeNestedChange(string $field, $old, $new): string
    {
        $oldArray = is_array($old) ? $old : [];
        $newArray = is_array($new) ? $new : [];

        if (empty($oldArray) && !empty($newArray)) {
            $count = count($newArray);
            return ucfirst($field) . ': ' . $count . ' ' . ($count === 1 ? 'item' : 'items') . ' added';
        }

        if (!empty($oldArray) && empty($newArray)) {
            return ucfirst($field) . ': All items removed';
        }

        // Compare keys to find additions/removals/changes
        $oldKeys = array_keys($oldArray);
        $newKeys = array_keys($newArray);
        $added = array_diff($newKeys, $oldKeys);
        $removed = array_diff($oldKeys, $newKeys);
        $common = array_intersect($oldKeys, $newKeys);

        $parts = [];
        if (!empty($added)) { $parts[] = count($added) . ' added'; }
        if (!empty($removed)) { $parts[] = count($removed) . ' removed'; }

        // Check for value changes in common keys
        $changed = 0;
        foreach ($common as $key) {
            if ($oldArray[$key] !== $newArray[$key]) {
                $changed++;
            }
        }
        if ($changed > 0) { $parts[] = $changed . ' modified'; }

        if (empty($parts)) {
            return ucfirst($field) . ': No changes';
        }

        return ucfirst($field) . ': ' . implode(', ', $parts);
    }

    /**
     * Summarize a change to a price map (e.g. bases_price, sizes_price)
     */
    private function summarizePriceMapChange(string $field, $old, $new): string
    {
        $oldArray = is_array($old) ? $old : [];
        $newArray = is_array($new) ? $new : [];

        if (empty($oldArray) && !empty($newArray)) {
            return ucfirst(str_replace('_', ' ', $field)) . ': Prices set for ' . count($newArray) . ' items';
        }

        if (!empty($oldArray) && empty($newArray)) {
            return ucfirst(str_replace('_', ' ', $field)) . ': All prices removed';
        }

        // Find items with changed prices
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($oldArray), array_keys($newArray)));
        foreach ($allKeys as $key) {
            $oldPrice = $oldArray[$key] ?? null;
            $newPrice = $newArray[$key] ?? null;
            if ($oldPrice !== $newPrice) {
                $changes[] = $key;
            }
        }

        if (empty($changes)) {
            return ucfirst(str_replace('_', ' ', $field)) . ': No changes';
        }

        return ucfirst(str_replace('_', ' ', $field)) . ': ' . count($changes) . ' price' . (count($changes) === 1 ? '' : 's') . ' updated';
    }

    /**
     * Build a human-readable summary for a single field change
     */
    private function buildChangeSummary(string $field, $oldVal, $newVal, string $formattedOld, string $formattedNew): string
    {
        $fieldLabel = $this->displayLabelForField($field, $oldVal, $newVal);

        // New field (was null/empty, now has value)
        if ((is_null($oldVal) || $oldVal === '') && !is_null($newVal) && $newVal !== '') {
            return $fieldLabel . ': Set to ' . $formattedNew;
        }

        // Field cleared (had value, now null/empty)
        if (!is_null($oldVal) && $oldVal !== '' && (is_null($newVal) || $newVal === '')) {
            return $fieldLabel . ': Cleared (was ' . $formattedOld . ')';
        }

        // Value changed
        return $fieldLabel . ': ' . $formattedOld . ' → ' . $formattedNew;
    }

    /**
     * Derive a context value (e.g., item name or number) to show above changes.
     * Only uses 'name' or 'number' when unchanged between before and after.
     */
    private function deriveChangeContext(array $before, array $after, array $params): ?string
    {
        $getIfUnchanged = function(string $key) use ($before, $after) {
            $b = array_key_exists($key, $before) ? $before[$key] : null;
            $a = array_key_exists($key, $after) ? $after[$key] : null;
            if (!is_null($b) && $b === $a && !is_array($b)) {
                $val = trim((string)$b);
                return $val !== '' ? $val : null;
            }
            return null;
        };

        // Prefer exact matches first
        if ($val = $getIfUnchanged('name')) { return $val; }
        if ($val = $getIfUnchanged('number')) { return $val; }

        // Then any key containing 'name' or 'number' (case-insensitive)
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        // Name-like keys first
        foreach ($allKeys as $k) {
            if (strcasecmp($k, 'name') === 0) { continue; }
            if (stripos($k, 'name') !== false) {
                if ($val = $getIfUnchanged($k)) { return $val; }
            }
        }
        // Number-like keys next
        foreach ($allKeys as $k) {
            if (strcasecmp($k, 'number') === 0) { continue; }
            if (stripos($k, 'number') !== false) {
                if ($val = $getIfUnchanged($k)) { return $val; }
            }
        }

        return null;
    }

    /**
     * Derive both name and number contexts from unchanged before/after fields.
     */
    private function deriveContextPair(array $before, array $after): array
    {
        $getIfUnchanged = function(string $key) use ($before, $after) {
            $b = array_key_exists($key, $before) ? $before[$key] : null;
            $a = array_key_exists($key, $after) ? $after[$key] : null;
            if (!is_null($b) && $b === $a && !is_array($b)) {
                $val = trim((string)$b);
                return $val !== '' ? $val : null;
            }
            return null;
        };

        $name = null;
        $number = null;
        $numberKey = null;

        // Prefer exact matches first
        $name = $getIfUnchanged('name') ?? $name;
        if (is_null($number)) {
            $val = $getIfUnchanged('number');
            if (!is_null($val)) { $number = $val; $numberKey = 'number'; }
        }

        // Then any key containing 'name' or 'number' (case-insensitive)
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        if (is_null($name)) {
            foreach ($allKeys as $k) {
                if (strcasecmp($k, 'name') === 0) { continue; }
                if (stripos($k, 'name') !== false) {
                    $val = $getIfUnchanged($k);
                    if (!is_null($val)) { $name = $val; break; }
                }
            }
        }
        if (is_null($number)) {
            foreach ($allKeys as $k) {
                if (strcasecmp($k, 'number') === 0) { continue; }
                if (stripos($k, 'number') !== false) {
                    $val = $getIfUnchanged($k);
                    if (!is_null($val)) { $number = $val; $numberKey = $k; break; }
                }
            }
        }

        return [$name, $number, $numberKey];
    }
}
