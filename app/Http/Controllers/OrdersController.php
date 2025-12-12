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
        $role = $request->session()->get('role');

        // Read filter inputs (GET). Fall back to session values when not provided.
        $filterRestaurantId = $request->query('restaurantId', $restaurantId);
        $filterBranchId = $request->query('branchId', $branchId);
        $filterStatus = $request->query('status', '');
        $filterOrderType = $request->query('orderType', '');

        if (!$restaurantId) {
            return redirect()->route('settings.context')->with('status', 'Select restaurant first.');
        }

        // Get all restaurants for admin role
        $restaurants = [];
        if ($role === 'admin') {
            $restaurantsResp = $firebase->getCollection('restaurants');
            $restaurantsDocs = $restaurantsResp['documents'] ?? [];
            foreach ($restaurantsDocs as $rd) {
                $rid = Str::afterLast($rd['name'], '/');
                $rf = $rd['fields'] ?? [];
                $restaurants[] = [
                    'id' => $rid,
                    'name' => $rf['name']['stringValue'] ?? $rid,
                ];
            }
        }

        // Get branches for the selected (filtered) restaurant
        $branchesResp = $firebase->getCollection("restaurants/{$filterRestaurantId}/branches");
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

        // Fetch orders from the orders collection (top-level)
        $ordersResp = $firebase->getCollection('orders');
        $ordersDocs = $ordersResp['documents'] ?? [];
        $allOrders = [];

        foreach ($ordersDocs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];

            // Parse order data
            $order = $this->parseOrder($id, $f);

            // Filter by restaurantId and branchId (use filter values)
            if ($order['restaurantId'] !== $filterRestaurantId) {
                continue;
            }

            if ($filterBranchId && $order['branchId'] !== $filterBranchId) {
                continue;
            }

            // Additional filters from form
            if (!empty($filterStatus) && ($order['status'] !== $filterStatus)) {
                continue;
            }
            if (!empty($filterOrderType) && ($order['orderType'] !== $filterOrderType)) {
                continue;
            }

            $allOrders[] = $order;
        }

        // Sort orders by createdAt descending
        usort($allOrders, function($a, $b) {
            return strtotime($b['createdAt']) - strtotime($a['createdAt']);
        });

        // Fetch customers collection to resolve customer names in the view
        $customers = [];
        try {
            $customersResp = $firebase->getCollection('customers');
            $customersDocs = $customersResp['documents'] ?? [];
            foreach ($customersDocs as $cd) {
                $cid = Str::afterLast($cd['name'] ?? '', '/');
                $cf = $cd['fields'] ?? [];
                $customers[] = [
                    'id' => $cid,
                    'name' => $cf['name']['stringValue'] ?? ($cf['displayName']['stringValue'] ?? ($cf['email']['stringValue'] ?? $cid)),
                    'email' => $cf['email']['stringValue'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // non-fatal: if customers collection can't be fetched, continue without it
            $customers = [];
        }

        return view('admin.orders', [
            'orders' => $allOrders,
            'restaurants' => $restaurants,
            'branches' => $branches,
            'customers' => $customers,
            'role' => $role,
            'currentRestaurantId' => $restaurantId,
            'currentBranchId' => $branchId,
        ]);
    }

    public function getOrdersAjax(Request $request, FirebaseService $firebase)
    {
        $restaurantId = $request->session()->get('restaurantId');
        $role = $request->session()->get('role');

        // Read filter inputs
        $filterRestaurantId = $request->input('restaurantId', $restaurantId);
        $filterBranchId = $request->input('branchId', '');
        $filterStatus = $request->input('status', '');
        $filterOrderType = $request->input('orderType', '');

        if (!$restaurantId) {
            return response()->json(['error' => 'No restaurant selected'], 400);
        }

        // Fetch orders
        $ordersResp = $firebase->getCollection('orders');
        $ordersDocs = $ordersResp['documents'] ?? [];
        $allOrders = [];

        foreach ($ordersDocs as $doc) {
            $id = Str::afterLast($doc['name'], '/');
            $f = $doc['fields'] ?? [];
            $order = $this->parseOrder($id, $f);

            // Apply filters
            if ($order['restaurantId'] !== $filterRestaurantId) {
                continue;
            }
            if ($filterBranchId && $order['branchId'] !== $filterBranchId) {
                continue;
            }
            if (!empty($filterStatus) && ($order['status'] !== $filterStatus)) {
                continue;
            }
            if (!empty($filterOrderType) && ($order['orderType'] !== $filterOrderType)) {
                continue;
            }

            $allOrders[] = $order;
        }

        // Sort by createdAt descending
        usort($allOrders, function($a, $b) {
            return strtotime($b['createdAt']) - strtotime($a['createdAt']);
        });

        // Fetch customers
        $customers = [];
        try {
            $customersResp = $firebase->getCollection('customers');
            $customersDocs = $customersResp['documents'] ?? [];
            foreach ($customersDocs as $cd) {
                $cid = Str::afterLast($cd['name'] ?? '', '/');
                $cf = $cd['fields'] ?? [];
                $customers[] = [
                    'id' => $cid,
                    'name' => $cf['name']['stringValue'] ?? ($cf['displayName']['stringValue'] ?? ($cf['email']['stringValue'] ?? $cid)),
                    'email' => $cf['email']['stringValue'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            $customers = [];
        }

        return response()->json([
            'orders' => $allOrders,
            'customers' => $customers,
        ]);
    }

    private function parseOrder($id, $fields)
    {
        // Extract all order fields
        $order = [
            'id' => $id,
            'displayId' => $fields['displayId']['stringValue'] ?? $id,
            'restaurantId' => $fields['restaurantId']['stringValue'] ?? '',
            'branchId' => $fields['branchId']['stringValue'] ?? '',
            'customerId' => $fields['customerId']['stringValue'] ?? '',
            'status' => $fields['status']['stringValue'] ?? 'pending',
            'paymentStatus' => $fields['paymentStatus']['stringValue'] ?? 'pending',
            'orderType' => $fields['orderType']['stringValue'] ?? '',
            'subtotal' => $this->getNumericValue($fields['subtotal'] ?? null),
            'deliveryFee' => $this->getNumericValue($fields['deliveryFee'] ?? null),
            'discount' => $this->getNumericValue($fields['discount'] ?? null),
            'taxAmount' => $this->getNumericValue($fields['taxAmount'] ?? null),
            'totalAmount' => $this->getNumericValue($fields['totalAmount'] ?? null),
            'createdAt' => $this->getTimestampValue($fields['createdAt'] ?? null),
            'updatedAt' => $this->getTimestampValue($fields['updatedAt'] ?? null),
            'items' => $this->parseItems($fields['items'] ?? null),
        ];

        return $order;
    }

    private function parseItems($itemsField)
    {
        if (!$itemsField || !isset($itemsField['arrayValue']['values'])) {
            return [];
        }

        $items = [];
        foreach ($itemsField['arrayValue']['values'] as $itemValue) {
            if (!isset($itemValue['mapValue']['fields'])) {
                continue;
            }

            $itemFields = $itemValue['mapValue']['fields'];
            $items[] = [
                'itemId' => $itemFields['itemId']['stringValue'] ?? '',
                'name' => $itemFields['name']['stringValue'] ?? '',
                'nameLocalized' => $itemFields['nameLocalized']['stringValue'] ?? '',
                'categoryId' => $itemFields['categoryId']['stringValue'] ?? '',
                'quantity' => $this->getNumericValue($itemFields['quantity'] ?? null),
                'price' => $this->getNumericValue($itemFields['price'] ?? null),
                'size' => $itemFields['size']['stringValue'] ?? '',
                'base' => $itemFields['base']['stringValue'] ?? '',
                'customizationExtra' => $this->getNumericValue($itemFields['customizationExtra'] ?? null),
                'imageUrl' => $itemFields['imageUrl']['stringValue'] ?? '',
                'Green' => $this->parseCustomizationArray($itemFields['Green'] ?? null),
                'Topping' => $this->parseCustomizationArray($itemFields['Topping'] ?? null),
            ];
        }

        return $items;
    }

    private function parseCustomizationArray($field)
    {
        if (!$field || !isset($field['arrayValue']['values'])) {
            return [];
        }

        $result = [];
        foreach ($field['arrayValue']['values'] as $value) {
            if (!isset($value['mapValue']['fields'])) {
                continue;
            }

            $fields = $value['mapValue']['fields'];
            $result[] = [
                'id' => $fields['id']['stringValue'] ?? '',
                'name' => $fields['name']['stringValue'] ?? '',
                'name_en' => $fields['name_en']['stringValue'] ?? '',
                'name_fi' => $fields['name_fi']['stringValue'] ?? '',
            ];
        }

        return $result;
    }

    private function getNumericValue($field)
    {
        if (!$field) {
            return 0;
        }

        if (isset($field['doubleValue'])) {
            return (float)$field['doubleValue'];
        }

        if (isset($field['integerValue'])) {
            return (int)$field['integerValue'];
        }

        return 0;
    }

    private function getTimestampValue($field)
    {
        if (!$field) {
            return null;
        }

        if (isset($field['timestampValue'])) {
            return $field['timestampValue'];
        }

        if (isset($field['stringValue'])) {
            return $field['stringValue'];
        }

        return null;
    }
}
