<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard(Request $request, FirebaseService $firebase)
    {
        $rawResponse = $firebase->getCollection('orders');
        $rawDocuments = $rawResponse['documents'] ?? [];
        $recentOrders = [];

        foreach ($rawDocuments as $document) {
            $documentId = Str::afterLast($document['name'], '/');
            $recentOrders[$documentId] = $document;
        }

        return view('admin.dashboard', [
            'recentOrders' => $recentOrders,
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