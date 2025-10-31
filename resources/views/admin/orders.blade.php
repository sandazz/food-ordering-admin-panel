@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>Order Management</h2>
  <div></div>
  <!-- filters can be added here later -->
  </div>
@if(($mode ?? 'single') === 'single')
  @if(empty($orders))
    <p>No orders found.</p>
  @else
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Payment</th>
        <th class="text-end">Total</th>
        <th>Type</th>
      </tr>
    </thead>
    <tbody>
      @foreach($orders as $o)
        <tr>
          <td>{{ $o['id'] }}</td>
          <td>{{ $o['status'] }}</td>
          <td>{{ $o['paymentStatus'] }}</td>
          <td class="text-end">${{ number_format($o['totalAmount'], 2) }}</td>
          <td>{{ $o['orderType'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  @endif
@else
  @if(empty($branchOrders))
    <p>No orders found.</p>
  @else
    @foreach($branchOrders as $bo)
      <h5 class="mt-4 mb-2">Branch: {{ $bo['branch']['name'] }}</h5>
      @if(empty($bo['orders']))
        <div class="text-muted small">No orders.</div>
      @else
      <table class="table table-sm">
        <thead>
          <tr>
            <th>ID</th>
            <th>Status</th>
            <th>Payment</th>
            <th class="text-end">Total</th>
            <th>Type</th>
          </tr>
        </thead>
        <tbody>
          @foreach($bo['orders'] as $o)
            <tr>
              <td>{{ $o['id'] }}</td>
              <td>{{ $o['status'] }}</td>
              <td>{{ $o['paymentStatus'] }}</td>
              <td class="text-end">${{ number_format($o['totalAmount'], 2) }}</td>
              <td>{{ $o['orderType'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      @endif
    @endforeach
  @endif
@endif
@endsection
