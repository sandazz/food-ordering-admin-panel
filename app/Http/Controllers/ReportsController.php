<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function sales(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'period' => 'required|string|in:daily,weekly,monthly',
            'branchId' => 'nullable|string',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');
        $orders = $this->fetchOrders($firebase, $restaurantId, $branchId);
        $period = $request->input('period');
        $buckets = $this->bucketOrdersByPeriod($orders, $period);
        return response()->json($buckets);
    }

    public function topItems(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'period' => 'required|string|in:daily,weekly,monthly',
            'limit' => 'nullable|integer|min:1|max:100',
            'branchId' => 'nullable|string',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');
        $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $request->input('period'));
        $items = [];
        foreach ($orders as $o) {
            $arr = $o['fields']['items']['arrayValue']['values'] ?? [];
            foreach ($arr as $iv) {
                $mf = $iv['mapValue']['fields'] ?? [];
                $id = $mf['itemId']['stringValue'] ?? ($mf['id']['stringValue'] ?? Str::random(6));
                $name = $mf['name']['stringValue'] ?? ($mf['title']['stringValue'] ?? $id);
                $qty = isset($mf['qty']['integerValue']) ? (int)$mf['qty']['integerValue'] : (int)($mf['quantity']['integerValue'] ?? 1);
                $items[$id] = ($items[$id] ?? ['id' => $id, 'name' => $name, 'qty' => 0]);
                $items[$id]['qty'] += $qty;
            }
        }
        usort($items, fn($a,$b) => $b['qty'] <=> $a['qty']);
        $limit = (int)($request->input('limit') ?? 10);
        return response()->json(array_slice(array_values($items), 0, $limit));
    }

    public function busySlots(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'period' => 'required|string|in:daily,weekly,monthly',
            'branchId' => 'nullable|string',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');
        $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $request->input('period'));
        $hist = [];
        foreach ($orders as $o) {
            $ts = $this->parseTimestamp($o['fields']['createdAt'] ?? []);
            $hour = $ts ? $ts->format('H:00') : 'unknown';
            $hist[$hour] = ($hist[$hour] ?? 0) + 1;
        }
        ksort($hist);
        $data = [];
        foreach ($hist as $hour => $count) { $data[] = ['hour' => $hour, 'orders' => $count]; }
        return response()->json($data);
    }

    public function export(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'report' => 'required|string|in:sales,top-items,busy-slots',
            'period' => 'required|string|in:daily,weekly,monthly',
            'type' => 'required|string|in:csv,xlsx,pdf',
            'branchId' => 'nullable|string',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');

        $report = $request->input('report');
        $period = $request->input('period');
        $type = $request->input('type');

        if ($report === 'sales') {
            $orders = $this->fetchOrders($firebase, $restaurantId, $branchId);
            $data = $this->bucketOrdersByPeriod($orders, $period);
            $headers = ['period','orders','total'];
            $rows = array_map(fn($r) => [$r['period'], $r['orders'], number_format($r['total'], 2, '.', '')], $data);
        } elseif ($report === 'top-items') {
            $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $period);
            $acc = [];
            foreach ($orders as $o) {
                $arr = $o['fields']['items']['arrayValue']['values'] ?? [];
                foreach ($arr as $iv) {
                    $mf = $iv['mapValue']['fields'] ?? [];
                    $id = $mf['itemId']['stringValue'] ?? ($mf['id']['stringValue'] ?? Str::random(6));
                    $name = $mf['name']['stringValue'] ?? ($mf['title']['stringValue'] ?? $id);
                    $qty = isset($mf['qty']['integerValue']) ? (int)$mf['qty']['integerValue'] : (int)($mf['quantity']['integerValue'] ?? 1);
                    $acc[$id] = ($acc[$id] ?? ['name' => $name, 'qty' => 0]);
                    $acc[$id]['qty'] += $qty;
                }
            }
            uasort($acc, fn($a,$b)=> $b['qty'] <=> $a['qty']);
            $headers = ['item','qty'];
            $rows = [];
            foreach ($acc as $name=>$v) { $rows[] = [$v['name'], $v['qty']]; }
        } else { // busy-slots
            $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $period);
            $hist = [];
            foreach ($orders as $o) {
                $ts = $this->parseTimestamp($o['fields']['createdAt'] ?? []);
                $hour = $ts ? $ts->format('H:00') : 'unknown';
                $hist[$hour] = ($hist[$hour] ?? 0) + 1;
            }
            ksort($hist);
            $headers = ['hour','orders'];
            $rows = [];
            foreach ($hist as $h=>$c) { $rows[] = [$h, $c]; }
        }

        if ($type === 'csv') {
            $filename = $report.'_'.$period.'_' . date('Ymd_His') . '.csv';
            $response = new StreamedResponse(function() use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($rows as $r) { fputcsv($handle, $r); }
                fclose($handle);
            });
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
            return $response;
        }

        if ($type === 'xlsx' && class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // Write headers
            $col = 1; foreach ($headers as $h) { $sheet->setCellValueByColumnAndRow($col++, 1, $h); }
            // Write rows
            $rowNum = 2; foreach ($rows as $r) { $col = 1; foreach ($r as $c) { $sheet->setCellValueByColumnAndRow($col++, $rowNum, $c); } $rowNum++; }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = $report.'_'.$period.'_'.date('Ymd_His').'.xlsx';
            return response()->streamDownload(function() use ($writer) { $writer->save('php://output'); }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        if ($type === 'pdf' && class_exists('\\Dompdf\\Dompdf')) {
            $html = '<h3>'.htmlspecialchars($report.' ('.$period.')').'</h3><table border=\"1\" cellspacing=\"0\" cellpadding=\"4\"><thead><tr>';
            foreach ($headers as $h) { $html .= '<th>'.htmlspecialchars($h).'</th>'; }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $r) { $html .= '<tr>'; foreach ($r as $c) { $html .= '<td>'.htmlspecialchars((string)$c).'</td>'; } $html .= '</tr>'; }
            $html .= '</tbody></table>';
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $filename = $report.'_'.$period.'_'.date('Ymd_His').'.pdf';
            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"'
            ]);
        }

        return response()->json(['error' => 'Export type not supported'], 422);
    }

    private function fetchOrders(FirebaseService $firebase, string $restaurantId, ?string $branchId = null, ?string $period = null): array
    {
        $orders = [];
        if ($branchId) {
            $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$branchId}/orders");
            $orders = $resp['documents'] ?? [];
        } else {
            $branchesResp = $firebase->getCollection("restaurants/{$restaurantId}/branches");
            $branchesDocs = $branchesResp['documents'] ?? [];
            foreach ($branchesDocs as $bd) {
                $bid = Str::afterLast($bd['name'], '/');
                $resp = $firebase->getCollection("restaurants/{$restaurantId}/branches/{$bid}/orders");
                foreach (($resp['documents'] ?? []) as $doc) { $orders[] = $doc; }
            }
        }
        if ($period) {
            $window = $this->periodWindow($period);
            $orders = array_values(array_filter($orders, function($o) use ($window) {
                $ts = $this->parseTimestamp($o['fields']['createdAt'] ?? []);
                return $ts && $ts->betweenIncluded($window['start'], $window['end']);
            }));
        }
        return $orders;
    }

    private function parseTimestamp(array $field)
    {
        $val = $field['timestampValue'] ?? ($field['stringValue'] ?? null);
        if (!$val) return null;
        try { return Carbon::parse($val); } catch (\Exception $e) { return null; }
    }

    private function periodWindow(string $period): array
    {
        $end = Carbon::now();
        if ($period === 'daily') { $start = $end->copy()->startOfDay(); }
        elseif ($period === 'weekly') { $start = $end->copy()->startOfWeek(); }
        else { $start = $end->copy()->startOfMonth(); }
        return ['start' => $start, 'end' => $end];
    }

    private function bucketOrdersByPeriod(array $orders, string $period): array
    {
        $buckets = [];
        foreach ($orders as $o) {
            $ts = $this->parseTimestamp($o['fields']['createdAt'] ?? []);
            if (!$ts) { continue; }
            $key = $period === 'daily' ? $ts->format('Y-m-d') : ($period === 'weekly' ? $ts->format('o-\WW') : $ts->format('Y-m'));
            $total = $this->extractTotal($o['fields'] ?? []);
            if (!isset($buckets[$key])) { $buckets[$key] = ['period' => $key, 'orders' => 0, 'total' => 0.0]; }
            $buckets[$key]['orders'] += 1;
            $buckets[$key]['total'] += $total;
        }
        ksort($buckets);
        return array_values($buckets);
    }

    private function extractTotal(array $fields): float
    {
        // Prefer totalAmount if present (as used by OrdersController), fallback to total
        $candidates = [
            $fields['totalAmount'] ?? null,
            $fields['total'] ?? null,
        ];
        foreach ($candidates as $f) {
            if (is_array($f)) {
                if (array_key_exists('doubleValue', $f)) return (float)$f['doubleValue'];
                if (array_key_exists('integerValue', $f)) return (float)$f['integerValue'];
            }
        }
        // Try compute from parts: subtotal + tax + serviceCharge - discounts
        $subtotal = $this->numOrNull($fields['subtotal'] ?? null);
        $tax = $this->numOrNull($fields['tax'] ?? null);
        $service = $this->numOrNull($fields['serviceCharge'] ?? null);
        $delivery = $this->numOrNull($fields['deliveryFee'] ?? null);
        $discount = $this->numOrNull($fields['discount'] ?? null);
        if ($subtotal !== null) {
            $sum = $subtotal + ($tax ?? 0) + ($service ?? 0) + ($delivery ?? 0) - ($discount ?? 0);
            return (float)$sum;
        }
        // Fallback: sum items by (lineTotal) or (price*qty)
        $items = $fields['items']['arrayValue']['values'] ?? [];
        $sum = 0.0;
        foreach ($items as $iv) {
            $mf = $iv['mapValue']['fields'] ?? [];
            $line = $this->numOrNull($mf['lineTotal'] ?? null);
            if ($line !== null) { $sum += (float)$line; continue; }
            $price = $this->numOrNull($mf['price'] ?? null) ?? 0.0;
            $qty = isset($mf['qty']['integerValue']) ? (int)$mf['qty']['integerValue'] : (int)($mf['quantity']['integerValue'] ?? 1);
            $sum += $price * max(1, $qty);
        }
        return (float)$sum;
    }

    private function numOrNull($firestoreField): ?float
    {
        if (!is_array($firestoreField)) return null;
        if (array_key_exists('doubleValue', $firestoreField)) return (float)$firestoreField['doubleValue'];
        if (array_key_exists('integerValue', $firestoreField)) return (float)$firestoreField['integerValue'];
        if (array_key_exists('stringValue', $firestoreField)) {
            $v = trim($firestoreField['stringValue']);
            if ($v === '') return null;
            if (is_numeric($v)) return (float)$v;
        }
        return null;
    }
}
