<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class OrdersController extends Controller
{
    public function index(Request $request, FirebaseService $firebase)
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

        // branches for selector
        $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
        $branchesDocs = $branchesResp['documents'] ?? [];
        $branches = [];
        foreach ($branchesDocs as $bd) {
            $bid = Str::afterLast($bd['name'], '/');
            $bf = $bd['fields'] ?? [];
            $branches[] = [
                'id' => $bid,
                'name' => $bf['name']['stringValue'] ?? $bid,
            ];
        }

        if ($branchId) {
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/orders");
            $docs = $resp['documents'] ?? [];
            $orders = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $orders[] = [
                    'id' => $id,
                    'status' => $f['status']['stringValue'] ?? '',
                    'paymentStatus' => $f['paymentStatus']['stringValue'] ?? '',
                    'totalAmount' => isset($f['totalAmount']['doubleValue']) ? (float)$f['totalAmount']['doubleValue'] : (float)($f['totalAmount']['integerValue'] ?? 0),
                    'orderType' => $f['orderType']['stringValue'] ?? '',
                ];
            }
            return view('admin.orders', [
                'mode' => 'single',
                'orders' => $orders,
                'branches' => $branches,
                'currentBranchId' => $branchId,
            ]);
        }

        $branchOrders = [];
        foreach ($branches as $b) {
            $bId = $b['id'];
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bId}/orders");
            $docs = $resp['documents'] ?? [];
            $orders = [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $f = $doc['fields'] ?? [];
                $orders[] = [
                    'id' => $id,
                    'status' => $f['status']['stringValue'] ?? '',
                    'paymentStatus' => $f['paymentStatus']['stringValue'] ?? '',
                    'totalAmount' => isset($f['totalAmount']['doubleValue']) ? (float)$f['totalAmount']['doubleValue'] : (float)($f['totalAmount']['integerValue'] ?? 0),
                    'orderType' => $f['orderType']['stringValue'] ?? '',
                ];
            }
            $branchOrders[] = [
                'branch' => $b,
                'orders' => $orders,
            ];
        }
        return view('admin.orders', [
            'mode' => 'all',
            'branchOrders' => $branchOrders,
            'branches' => $branches,
            'currentBranchId' => null,
        ]);
    }
}
