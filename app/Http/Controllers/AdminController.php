<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;

class AdminController extends Controller
{
    public function dashboard(Request $request, FirebaseService $firebase)
    {
        // Example: fetch recent orders (global orders collection, limit 10)
        $orders = $firebase->getCollection('orders');
        $recentOrders = $orders['documents'] ?? [];
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