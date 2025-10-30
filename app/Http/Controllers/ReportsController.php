<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;

class ReportsController extends Controller
{
    public function index(Request $request, FirebaseService $firebase)
    {
        $restaurantId = $request->session()->get('restaurantId');
        $branchId = $request->session()->get('branchId');
        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

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
        // The view can render filters and export options; reports can be filtered by selected branch if provided
        return view('admin.reports', [
            'branches' => $branches,
            'currentBranchId' => $branchId,
        ]);
    }
}
