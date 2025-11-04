@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2>{{ \App\Utils\UIStrings::t('menu.index.title') }}</h2>
  <a href="{{ route('menu.categories.create') }}" class="btn btn-primary">{{ \App\Utils\UIStrings::t('menu.category.add') }}</a>
</div>
@if(session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if(($mode ?? 'single') === 'single')
  @if(empty($categories))
    <p>{{ \App\Utils\UIStrings::t('menu.category.none') }}</p>
  @else
    @foreach($categories as $cat)
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">{{ $cat['name'] }}</h5>
              <div class="text-muted small">{{ \App\Utils\UIStrings::t('menu.category.order') }}: {{ $cat['displayOrder'] }}</div>
              <div class="text-muted small">{{ $cat['description'] }}</div>
            </div>
            <div>
              <a class="btn btn-sm btn-outline-primary" href="{{ route('menu.items.create', $cat['id']) }}">{{ \App\Utils\UIStrings::t('menu.item.add') }}</a>
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.categories.edit', $cat['id']) }}">{{ \App\Utils\UIStrings::t('common.edit') }}</a>
              @if(session('role') !== 'branch_admin')
                <a class="btn btn-sm btn-outline-success" href="{{ route('menu.categories.copy.form', $cat['id']) }}">{{ \App\Utils\UIStrings::t('menu.category.copy_to_branches') }}</a>
              @endif
              <form action="{{ route('menu.categories.destroy', $cat['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ \App\Utils\UIStrings::t('menu.category.delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ \App\Utils\UIStrings::t('common.delete') }}</button>
              </form>
            </div>
          </div>
          @if(!empty($cat['items']))
            <table class="table table-sm mt-3">
              <thead>
                <tr>
                  <th>{{ \App\Utils\UIStrings::t('common.name') }}</th>
                  <th style="width:120px">{{ \App\Utils\UIStrings::t('common.price') }}</th>
                  <th style="width:100px">{{ \App\Utils\UIStrings::t('common.available') }}</th>
                  <th style="width:220px"></th>
                </tr>
              </thead>
              <tbody>
                @foreach($cat['items'] as $item)
                  <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>${{ number_format($item['price'], 2) }}</td>
                    <td>{{ $item['available'] ? \App\Utils\UIStrings::t('common.yes') : \App\Utils\UIStrings::t('common.no') }}</td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-secondary" href="{{ route('menu.items.edit', [$cat['id'], $item['id']]) }}">{{ \App\Utils\UIStrings::t('common.edit') }}</a>
                      <form action="{{ route('menu.items.destroy', [$cat['id'], $item['id']]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ \App\Utils\UIStrings::t('menu.item.delete_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">{{ \App\Utils\UIStrings::t('common.delete') }}</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <div class="text-muted small mt-2">{{ \App\Utils\UIStrings::t('menu.item.none') }}</div>
          @endif
        </div>
      </div>
    @endforeach
  @endif
@else
  @if(empty($branchMenus))
    <p>{{ \App\Utils\UIStrings::t('menu.none') }}</p>
  @else
    @foreach($branchMenus as $bm)
      <h5 class="mt-4 mb-2">{{ \App\Utils\UIStrings::t('branch') }}: {{ $bm['branch']['name'] }}</h5>
      @forelse($bm['categories'] as $cat)
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-1">{{ $cat['name'] }}</h6>
                <div class="text-muted small">{{ \App\Utils\UIStrings::t('menu.category.order') }}: {{ $cat['displayOrder'] }}</div>
                <div class="text-muted small">{{ $cat['description'] }}</div>
              </div>
            </div>
            @if(!empty($cat['items']))
              <table class="table table-sm mt-3">
                <thead>
                  <tr>
                    <th>{{ \App\Utils\UIStrings::t('common.name') }}</th>
                    <th style="width:120px">{{ \App\Utils\UIStrings::t('common.price') }}</th>
                    <th style="width:100px">{{ \App\Utils\UIStrings::t('common.available') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($cat['items'] as $item)
                    <tr>
                      <td>{{ $item['name'] }}</td>
                      <td>${{ number_format($item['price'], 2) }}</td>
                      <td>{{ $item['available'] ? \App\Utils\UIStrings::t('common.yes') : \App\Utils\UIStrings::t('common.no') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @else
              <div class="text-muted small mt-2">{{ \App\Utils\UIStrings::t('menu.item.none') }}</div>
            @endif
          </div>
        </div>
      @empty
        <p class="text-muted">{{ \App\Utils\UIStrings::t('menu.categories.none') }}</p>
      @endforelse
    @endforeach
  @endif
@endif
@endsection
