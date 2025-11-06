@extends('layouts.admin')
@section('content')
<h2>{{ \App\Utils\UIStrings::t('dashboard.title') }}</h2>
<p>{{ \App\Utils\UIStrings::t('dashboard.welcome') }}</p>
<div class="row">
    <div class="col-md-6">
        <h5>{{ \App\Utils\UIStrings::t('dashboard.recent_orders') }}</h5>
        <table class="table table-sm table-bordered bg-white">
            <thead>
                <tr>
                    <th>{{ \App\Utils\UIStrings::t('table.id') }}</th>
                    <th>{{ \App\Utils\UIStrings::t('table.status') }}</th>
                    <th>{{ \App\Utils\UIStrings::t('table.total') }}</th>
                    <th>{{ \App\Utils\UIStrings::t('table.created') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $orderId => $order)
                    <tr>
                        <td>{{ $orderId }}</td> 
                        <td>{{ $order['fields']['status']['stringValue'] ?? '' }}</td>
                        <td>${{ $order['fields']['totalAmount']['doubleValue'] ?? $order['fields']['totalAmount']['integerValue'] ?? '' }}</td>
                        <td>{{ $order['fields']['createdAt']['timestampValue'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">{{ \App\Utils\UIStrings::t('orders.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <h5>{{ \App\Utils\UIStrings::t('dashboard.order_stats') }}</h5>
        <ul>
            <li>{{ \App\Utils\UIStrings::t('reports.table.orders') }}: {{ count($recentOrders) }}</li>
            <!-- Add more stats here -->
        </ul>
    </div>
</div>
@endsection
