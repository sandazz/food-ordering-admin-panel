<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

        // Limit to recent 500 entries for performance
        $logs = array_slice($logs, 0, 500);

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
     * Compute a list of field-level changes from before/after maps. If before/after are not provided,
     * provide a best-effort summary based on params and HTTP method.
     * @return array<int, array{field:string, from:mixed, to:mixed}>
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
                    $changes[] = ['field' => (string)$k, 'from' => $old, 'to' => $new];
                }
            }
            return $changes;
        }

        // No before/after snapshots: do not infer; return empty to hide non-confirmed changes
        return [];
    }
}
