@extends('layouts.admin')
@section('content')
<h2>Dashboard</h2>
<p>Welcome, admin!</p>
<div class="row">
    <div class="col-md-6">
        <h5>Recent Orders</h5>
        <table class="table table-sm table-bordered bg-white">
            <thead><tr><th>ID</th><th>Status</th><th>Total</th><th>Created</th></tr></thead>
            <tbody>
            @forelse($recentOrders as $order)
                <tr>
                    <td>{{ $order['name'] ?? '' }}</td>
                    <td>{{ $order['fields']['status']['stringValue'] ?? '' }}</td>
                    <td>${{ $order['fields']['totalAmount']['doubleValue'] ?? $order['fields']['totalAmount']['integerValue'] ?? '' }}</td>
                    <td>{{ $order['fields']['createdAt']['timestampValue'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No orders found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <h5>Order Stats</h5>
        <ul>
            <li>Total Orders: {{ count($recentOrders) }}</li>
            <!-- Add more stats here -->
        </ul>
    </div>
</div>
@endsection
