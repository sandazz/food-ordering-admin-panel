@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h1>Promotions</h1>
    <div class="actions">
        <a href="{{ route('promotions.create') }}" class="btn btn-primary">Add Promotion</a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if(isset($mode) && $mode === 'single')
    

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Discount</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $p)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if(!empty($p['imageUrl']))
                                            <img src="{{ $p['imageUrl'] }}" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;margin-right:8px;">
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $p['name'] }}</div>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($p['description'], 60) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-{{ $p['status'] === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($p['status']) }}</span></td>
                                <td>{{ $p['discount']['type'] === 'percent' ? $p['discount']['value'] . '%' : ('₹' . number_format($p['discount']['value'], 2)) }}</td>
                                <td>{{ $p['startsAt'] ? \Carbon\Carbon::parse($p['startsAt'])->format('Y-m-d') : '-' }}</td>
                                <td>{{ $p['endsAt'] ? \Carbon\Carbon::parse($p['endsAt'])->format('Y-m-d') : '-' }}</td>
                                <td>
                                    <a href="{{ route('promotions.edit', [$restaurantId, $currentBranchId, $p['id']]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('promotions.destroy', [$restaurantId, $currentBranchId, $p['id']]) }}" method="post" style="display:inline-block" onsubmit="return confirm('Delete this promotion?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No promotions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@elseif(isset($mode) && $mode === 'all')
    

    @foreach($branchPromotions as $bp)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Branch: {{ $bp['branch']['name'] }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Discount</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bp['promotions'] as $p)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if(!empty($p['imageUrl']))
                                                <img src="{{ $p['imageUrl'] }}" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;margin-right:8px;">
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ $p['name'] }}</div>
                                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($p['description'], 60) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-{{ $p['status'] === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($p['status']) }}</span></td>
                                    <td>{{ $p['discount']['type'] === 'percent' ? $p['discount']['value'] . '%' : ('₹' . number_format($p['discount']['value'], 2)) }}</td>
                                    <td>{{ $p['startsAt'] ? \Carbon\Carbon::parse($p['startsAt'])->format('Y-m-d') : '-' }}</td>
                                    <td>{{ $p['endsAt'] ? \Carbon\Carbon::parse($p['endsAt'])->format('Y-m-d') : '-' }}</td>
                                    <td>
                                        <a href="{{ route('promotions.edit', [$restaurantId, $bp['branch']['id'], $p['id']]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="{{ route('promotions.destroy', [$restaurantId, $bp['branch']['id'], $p['id']]) }}" method="post" style="display:inline-block" onsubmit="return confirm('Delete this promotion?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No promotions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
