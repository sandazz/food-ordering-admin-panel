@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>{{ \App\Utils\UIStrings::t('orders.title') }}</h2>
  <div></div>
  <!-- filters can be added here later -->
  </div>
@if(($mode ?? 'single') === 'single')
  @if(empty($orders))
    <p>{{ \App\Utils\UIStrings::t('orders.none') }}</p>
  @else
  <table class="table table-striped">
    <thead>
      <tr>
        <th>{{ \App\Utils\UIStrings::t('table.id') }}</th>
        <th>{{ \App\Utils\UIStrings::t('table.status') }}</th>
        <th>{{ \App\Utils\UIStrings::t('orders.payment') }}</th>
        <th class="text-end">{{ \App\Utils\UIStrings::t('table.total') }}</th>
        <th>{{ \App\Utils\UIStrings::t('orders.type') }}</th>
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
    <p>{{ \App\Utils\UIStrings::t('orders.none') }}</p>
  @else
    @foreach($branchOrders as $bo)
      <h5 class="mt-4 mb-2">{{ \App\Utils\UIStrings::t('branch') }}: {{ $bo['branch']['name'] }}</h5>
      @if(empty($bo['orders']))
        <div class="text-muted small">{{ \App\Utils\UIStrings::t('orders.none') }}</div>
      @else
      <table class="table table-sm">
        <thead>
          <tr>
            <th>{{ \App\Utils\UIStrings::t('table.id') }}</th>
            <th>{{ \App\Utils\UIStrings::t('table.status') }}</th>
            <th>{{ \App\Utils\UIStrings::t('orders.payment') }}</th>
            <th class="text-end">{{ \App\Utils\UIStrings::t('table.total') }}</th>
            <th>{{ \App\Utils\UIStrings::t('orders.type') }}</th>
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
