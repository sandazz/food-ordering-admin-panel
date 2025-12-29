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
    </div>
</div>
@endsection
