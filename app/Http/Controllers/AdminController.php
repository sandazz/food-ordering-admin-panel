<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard(Request $request, FirebaseService $firebase)
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');

        // Load branches for selector if restaurant selected
        $branches = [];
        if ($restaurantId) {
            $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $branchesDocs = $branchesResp['documents'] ?? [];
            foreach ($branchesDocs as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $bf = $bd['fields'] ?? [];
                $branches[] = [
                    'id' => $bid,
                    'name' => $bf['name']['stringValue'] ?? $bid,
                ];
            }
        }

        $recentOrders = [];
        if ($restaurantId && $branchId) {
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/orders");
            $docs = $resp['documents'] ?? [];
            foreach ($docs as $doc) {
                $id = Str::afterLast($doc['name'], '/');
                $recentOrders[$id] = $doc;
            }
        } elseif ($restaurantId) {
            // Aggregate across all branches for the selected restaurant
            foreach ($branches as $b) {
                $bId = $b['id'];
                $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bId}/orders");
                $docs = $resp['documents'] ?? [];
                foreach ($docs as $doc) {
                    $id = Str::afterLast($doc['name'], '/');
                    $recentOrders[$id] = $doc;
                }
            }
        } else {
            // Fallback to global orders if no restaurant selected yet
            $rawResponse = $firebase->getCollection('orders');
            $rawDocuments = $rawResponse['documents'] ?? [];
            foreach ($rawDocuments as $document) {
                $documentId = Str::afterLast($document['name'], '/');
                $recentOrders[$documentId] = $document;
            }
        }

        return view('admin.dashboard', [
            'recentOrders' => $recentOrders,
            'branches' => $branches,
            'currentBranchId' => $branchId,
        ]);
    }

    // Add stubs for other admin panel pages
    public function orders() { return view('admin.orders'); }
    public function menu() { return view('admin.menu'); }
    public function staff() { return view('admin.staff'); }
    public function reports() { return view('admin.reports'); }
    public function notifications() { return view('admin.notifications'); }
    public function settings() { return view('admin.settings'); }
}