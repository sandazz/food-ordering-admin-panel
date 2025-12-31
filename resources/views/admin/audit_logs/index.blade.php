@php($role = $role ?? session('role'))
@extends('layouts.admin')
@section('content')
<div class="page-header">
    <h1>Audit Logs</h1>
</div>
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 mb-3">
            @if($role === 'admin')
            <div class="col-md-3">
                <label class="form-label">Restaurant</label>
                <select name="restaurantId" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($restaurants as $r)
                        <option value="{{ $r['id'] }}" {{ ($qRestaurant ?? '') === $r['id'] ? 'selected' : '' }}>{{ $r['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Branch</label>
                <select name="branchId" class="form-select">
                    <option value="">All</option>
                    @foreach($branches as $b)
                        <option value="{{ $b['id'] }}" {{ ($qBranch ?? '') === $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @elseif($role === 'restaurant_admin')
            <div class="col-md-3">
                <label class="form-label">Branch</label>
                <select name="branchId" class="form-select">
                    <option value="">All</option>
                    @foreach($branches as $b)
                        <option value="{{ $b['id'] }}" {{ ($qBranch ?? '') === $b['id'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <label class="form-label">User</label>
                <input type="text" class="form-control" name="user" value="{{ $qUser ?? '' }}"/>
            </div>
            <div class="col-md-2">
                <label class="form-label">Method</label>
                <select name="method" class="form-select">
                    <option value="">All</option>
                    @foreach(['POST','PUT','PATCH','DELETE'] as $m)
                        <option value="{{ $m }}" {{ ($qMethod ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-md-12">
                <button class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Role</th>
                        @if(($role ?? null) === 'admin')
                        <th>Restaurant</th>
                        @endif
                        <th>Branch</th>
                        <th>Route</th>
                        <th>IP</th>
                        <th class="audit-changes" style="min-width:260px;">Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td style="white-space:nowrap">
                                {{ $log['createdAtFormatted'] ?? $log['createdAt'] }}
                                @if(!empty($log['createdAtAgo']))
                                    <div class="text-muted small">{{ $log['createdAtAgo'] }}</div>
                                @endif
                            </td>
                            <td>{{ $log['userEmail'] ?: $log['uid'] }}</td>
                            <td>{{ $log['role'] }}</td>
                            @if(($role ?? null) === 'admin')
                            <td>{{ $log['restaurantName'] ?? $log['restaurantId'] }}</td>
                            @endif
                            <td style="white-space:nowrap; overflow-x:auto;">{{ $log['branchName'] ?? $log['branchId'] }}</td>
                            <td>{{ $log['route'] }}</td>
                            <td>{{ $log['ip'] }}</td>
                            <td class="audit-changes" style="white-space:normal; word-break:break-word;">
                                @if(!empty($log['changes']))
                                    <div class="mb-0 ps-0">
                                        @if(!empty($log['changeContext']))
                                            <div class="text-muted" style="margin-bottom:4px;">'{{ $log['changeContext'] }}'</div>
                                        @endif
                                        @if(!empty($log['changeContextNumber']))
                                            <div class="text-muted" style="margin-bottom:4px;">{{ $log['changeContextNumberLabel'] ?? 'number' }} : {{ $log['changeContextNumber'] }}</div>
                                        @endif
                                        @foreach($log['changes'] as $ch)
                                            <div class="change-row" style="margin-bottom:6px;">
                                                @if(!empty($ch['summary']))
                                                    {{-- Use pre-computed summary for complex changes --}}
                                                    <div class="text-dark">{{ $ch['summary'] }}</div>
                                                @else
                                                    {{-- Fallback to formatted values if no summary --}}
                                                    @php(
                                                        $fromVal = !empty($ch['formatted_from']) ? $ch['formatted_from'] : (is_array($ch['from']) ? json_encode($ch['from']) : $ch['from'])
                                                    )
                                                    @php(
                                                        $toVal = !empty($ch['formatted_to']) ? $ch['formatted_to'] : (is_array($ch['to']) ? json_encode($ch['to']) : $ch['to'])
                                                    )
                                                    <div class="text-dark">
                                                        <strong>{{ $ch['label'] ?? ucfirst(str_replace('_', ' ', $ch['field'])) }}</strong>:
                                                        @if(is_null($ch['from']) && !is_null($ch['to']))
                                                            Set to <span class="text-success">{{ $toVal }}</span>
                                                        @elseif(!is_null($ch['from']) && is_null($ch['to']))
                                                            Cleared <span class="text-muted small">(was {{ $fromVal }})</span>
                                                        @else
                                                            <span class="text-muted">{{ is_null($ch['from']) ? '—' : $fromVal }}</span>
                                                            →
                                                            <span class="text-primary">{{ is_null($ch['to']) ? '—' : $toVal }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ (($role ?? null) === 'admin') ? 8 : 7 }}" class="text-center">No logs found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($logs, 'links'))
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3 gap-2">
                <div class="text-muted small">
                    @php(
                        $from = ($logs->currentPage() - 1) * $logs->perPage() + 1
                    )
                    @php(
                        $to = min($logs->currentPage() * $logs->perPage(), $logs->total())
                    )
                    Showing {{ $logs->total() ? $from : 0 }}–{{ $to }} of {{ $logs->total() }}
                </div>

                <div class="d-flex align-items-center gap-3">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        @foreach(request()->except(['page','per_page']) as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}"/>
                        @endforeach
                        <label class="form-label mb-0 small text-muted">Rows per page</label>
                        <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                            @foreach([10,25,50,100] as $pp)
                                <option value="{{ $pp }}" {{ $logs->perPage() == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                            @endforeach
                        </select>
                    </form>

                    @php($current = $logs->currentPage())
                    @php($last = $logs->lastPage())
                    @php($start = max(1, $current - 2))
                    @php($end = min($last, $current + 2))

                    <nav aria-label="Audit log pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $logs->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $logs->url(1) }}" aria-label="First">«</a>
                            </li>
                            <li class="page-item {{ $logs->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $logs->previousPageUrl() ?? '#' }}" aria-label="Previous">‹</a>
                            </li>

                            @if($start > 1)
                                <li class="page-item disabled"><span class="page-link">…</span></li>
                            @endif
                            @for($p = $start; $p <= $end; $p++)
                                <li class="page-item {{ $p == $current ? 'active' : '' }}">
                                    <a class="page-link" href="{{ $logs->url($p) }}">{{ $p }}</a>
                                </li>
                            @endfor
                            @if($end < $last)
                                <li class="page-item disabled"><span class="page-link">…</span></li>
                            @endif

                            <li class="page-item {{ $current >= $last ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $logs->nextPageUrl() ?? '#' }}" aria-label="Next">›</a>
                            </li>
                            <li class="page-item {{ $current >= $last ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $logs->url($last) }}" aria-label="Last">»</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
