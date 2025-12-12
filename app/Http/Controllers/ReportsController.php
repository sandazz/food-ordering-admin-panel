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
            'period' => 'required|string|in:daily,weekly,monthly,annual',
            'branchId' => 'nullable|string',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        $period = $request->input('period');
        $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $period, $dateFrom, $dateTo);
        $buckets = $this->bucketOrdersByPeriod($orders, $period);
        return response()->json($buckets);
    }

    public function topItems(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'period' => 'required|string|in:daily,weekly,monthly,annual',
            'limit' => 'nullable|integer|min:1|max:100',
            'branchId' => 'nullable|string',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $request->input('period'), $dateFrom, $dateTo);
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
            'period' => 'required|string|in:daily,weekly,monthly,annual',
            'branchId' => 'nullable|string',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $request->input('period'), $dateFrom, $dateTo);
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
            'period' => 'required|string|in:daily,weekly,monthly,annual',
            'type' => 'required|string|in:csv,xlsx,pdf',
            'branchId' => 'nullable|string',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ]);
        $restaurantId = $request->session()->get('restaurantId');
        if (!$restaurantId) { return response()->json(['error' => 'No restaurant selected'], 400); }
        $branchId = $request->input('branchId') ?: $request->session()->get('branchId');

        $report = $request->input('report');
        $period = $request->input('period');
        $type = $request->input('type');
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');

        if ($report === 'sales') {
            $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $period, $dateFrom, $dateTo);
            $data = $this->bucketOrdersByPeriod($orders, $period);
            $headers = ['period','orders','total'];
            $rows = array_map(fn($r) => [$r['period'], $r['orders'], number_format($r['total'], 2, '.', '')], $data);
        } elseif ($report === 'top-items') {
            $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $period, $dateFrom, $dateTo);
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
            $orders = $this->fetchOrders($firebase, $restaurantId, $branchId, $period, $dateFrom, $dateTo);
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

            // metadata header rows
            $meta = [
                [strtoupper('Report: ' . $report)],
                ['Period: ' . $period],
                ['Branch: ' . ($branchId ?: 'All')],
                ['Generated: ' . date('c')],
                []
            ];

            // summary footer rows (sales/top-items/busy-slots)
            $footer = [];
            if ($report === 'sales') {
                $totalOrders = array_sum(array_column($rows, 1));
                $totalRevenue = array_sum(array_column($rows, 2));
                $avgOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
                $footer[] = [];
                $footer[] = ['Totals', $totalOrders, number_format($totalRevenue, 2, '.', '')];
                $footer[] = ['Average Order Value', '', number_format($avgOrder, 2, '.', '')];
            } elseif ($report === 'top-items') {
                $totalQty = array_sum(array_column($rows, 1));
                $footer[] = [];
                $footer[] = ['Total Items Listed', count($rows)];
                $footer[] = ['Total Quantity Sold', $totalQty];
            } elseif ($report === 'busy-slots') {
                $total = array_sum(array_column($rows, 1));
                $footer[] = [];
                $footer[] = ['Total Orders', $total];
            }

            $response = new StreamedResponse(function() use ($meta, $headers, $rows, $footer) {
                $handle = fopen('php://output', 'w');
                foreach ($meta as $m) { fputcsv($handle, $m); }
                fputcsv($handle, $headers);
                foreach ($rows as $r) { fputcsv($handle, $r); }
                foreach ($footer as $f) { fputcsv($handle, $f); }
                fclose($handle);
            });
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
            return $response;
        }

        if ($type === 'xlsx' && class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Title and metadata
            $title = strtoupper('Report: ' . $report);
            $sheet->mergeCells('A1:D1');
            $sheet->setCellValue('A1', $title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $sheet->setCellValue('A2', 'Period:');
            $sheet->setCellValue('B2', $period);
            $sheet->setCellValue('A3', 'Branch:');
            $sheet->setCellValue('B3', $branchId ?: 'All');
            $sheet->setCellValue('C2', 'Generated:');
            $sheet->setCellValue('D2', date('c'));

            // Table header starting at row 5
            $startRow = 5;
            $col = 1; foreach ($headers as $h) { 
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $startRow;
                $sheet->setCellValue($cell, $h);
                $col++;
            }
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle("A{$startRow}:{$lastCol}{$startRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$startRow}:{$lastCol}{$startRow}")->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $rowNum = $startRow + 1;
            foreach ($rows as $r) {
                $col = 1;
                foreach ($r as $c) {
                    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $rowNum;
                    $sheet->setCellValue($cell, $c);
                    $col++;
                }
                $rowNum++;
            }

            // Footer summaries for sales
            if ($report === 'sales') {
                $totalOrders = array_sum(array_column($rows, 1));
                $totalRevenue = array_sum(array_column($rows, 2));
                $sheet->setCellValue("A{$rowNum}", 'Totals');
                $sheet->setCellValue("B{$rowNum}", $totalOrders);
                $sheet->setCellValue("C{$rowNum}", number_format($totalRevenue, 2, '.', ''));
                $rowNum++;
                $avg = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
                $sheet->setCellValue("A{$rowNum}", 'Average Order Value');
                $sheet->setCellValue("C{$rowNum}", number_format($avg, 2, '.', ''));
            }

            // Header/Footer for print
            $sheet->getHeaderFooter()->setOddHeader('&C&B' . $title);
            $sheet->getHeaderFooter()->setOddFooter('&LGenerated: ' . date('Y-m-d H:i') . '&RPage &P of &N');

            for ($i = 1; $i <= count($headers); $i++) { $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true); }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = $report.'_'.$period.'_'.date('Ymd_His').'.xlsx';
            return response()->streamDownload(function() use ($writer) { $writer->save('php://output'); }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        if ($type === 'pdf' && class_exists('\\Dompdf\\Dompdf')) {
            $generated = date('Y-m-d H:i');
            $branchLabel = $branchId ?: 'All';
            $html = '<!doctype html><html><head><meta charset="utf-8"><style>';
            $html .= 'body{font-family: Arial, Helvetica, sans-serif; color:#111}';
            $html .= '.header{border-bottom:1px solid #ddd;padding-bottom:10px;margin-bottom:10px}';
            $html .= '.meta{margin-bottom:12px} .meta td{padding:4px 8px}';
            $html .= 'table.report{width:100%;border-collapse:collapse}';
            $html .= 'table.report th, table.report td{border:1px solid #ccc;padding:8px;text-align:left}';
            $html .= 'table.report th{background:#f3f4f6;font-weight:700}';
            $html .= '.footer{border-top:1px solid #ddd;margin-top:12px;padding-top:8px;font-size:12px;color:#666;text-align:center}';
            $html .= '</style></head><body>';
            $html .= '<div class="header"><h2 style="margin:0">' . htmlspecialchars(ucfirst($report) . ' Report') . '</h2></div>';
            $html .= '<table class="meta"><tr><td><strong>Period:</strong> ' . htmlspecialchars($period) . '</td><td><strong>Branch:</strong> ' . htmlspecialchars($branchLabel) . '</td><td><strong>Generated:</strong> ' . htmlspecialchars($generated) . '</td></tr></table>';
            $html .= '<table class="report"><thead><tr>';
            foreach ($headers as $h) { $html .= '<th>' . htmlspecialchars($h) . '</th>'; }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $r) { $html .= '<tr>'; foreach ($r as $c) { $html .= '<td>' . htmlspecialchars((string)$c) . '</td>'; } $html .= '</tr>'; }
            $html .= '</tbody></table>';

            if ($report === 'sales') {
                $totalOrders = array_sum(array_column($rows, 1));
                $totalRevenue = array_sum(array_column($rows, 2));
                $avg = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
                $html .= '<div style="margin-top:12px"><strong>Summary</strong><br/>';
                $html .= 'Total Orders: ' . number_format($totalOrders) . ' &nbsp;|&nbsp; Total Revenue: ' . number_format($totalRevenue,2) . ' &nbsp;|&nbsp; Avg Order Value: ' . number_format($avg,2);
                $html .= '</div>';
            }

            $html .= '<div class="footer">Generated by Admin Panel &nbsp;|&nbsp; ' . htmlspecialchars($generated) . '</div>';
            $html .= '</body></html>';

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

    private function fetchOrders(FirebaseService $firebase, string $restaurantId, ?string $branchId = null, ?string $period = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $orders = [];

        // Prefer querying the top-level `orders` collection with server-side filters
        // to avoid pulling all documents client-side. This matches OrdersController
        // and AdminController behavior where orders are stored at root.
        try {
            $fieldFilters = [['fieldPath' => 'restaurantId', 'op' => 'EQUAL', 'value' => $restaurantId]];
            if ($branchId) {
                $fieldFilters[] = ['fieldPath' => 'branchId', 'op' => 'EQUAL', 'value' => $branchId];
            }

            $resp = $firebase->runStructuredQuery('orders', $fieldFilters);
            $orders = $resp['documents'] ?? [];
        } catch (\Exception $e) {
            // If structured query is not supported or fails, fallback to branch-level queries
            \Log::warning('ReportsController::fetchOrders - structured query failed, falling back to branch scan', ['error' => $e->getMessage()]);
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
        }

        // Prefer explicit date range when provided
        if ($dateFrom && $dateTo) {
            try {
                $start = Carbon::parse($dateFrom)->startOfDay();
                $end = Carbon::parse($dateTo)->endOfDay();
            } catch (\Exception $e) {
                $start = null; $end = null;
            }
            if ($start && $end) {
                $orders = array_values(array_filter($orders, function($o) use ($start, $end) {
                    $ts = $this->parseTimestamp($o['fields']['createdAt'] ?? []);
                    return $ts && $ts->betweenIncluded($start, $end);
                }));
            }
        } elseif ($period) {
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
        if ($period === 'daily') {
            $start = $end->copy()->startOfDay();
        } elseif ($period === 'weekly') {
            $start = $end->copy()->startOfWeek();
        } elseif ($period === 'monthly') {
            $start = $end->copy()->startOfMonth();
        } else { // annual
            $start = $end->copy()->startOfYear();
        }
        return ['start' => $start, 'end' => $end];
    }

    private function bucketOrdersByPeriod(array $orders, string $period): array
    {
        $buckets = [];
        foreach ($orders as $o) {
            $ts = $this->parseTimestamp($o['fields']['createdAt'] ?? []);
            if (!$ts) { continue; }
            if ($period === 'daily') {
                $key = $ts->format('Y-m-d');
            } elseif ($period === 'weekly') {
                $key = $ts->format('o-\WW');
            } elseif ($period === 'monthly') {
                $key = $ts->format('Y-m');
            } else { // annual
                $key = $ts->format('Y');
            }
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
