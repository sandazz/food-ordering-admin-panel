@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Restaurant Admins</h1>
        <a href="{{ route('settings.restaurant_admins.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded">Create</a>
    </div>

    @if (session('status'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
    @endif

    <div class="overflow-x-auto bg-white shadow rounded">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-3">Name</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Restaurant</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $a)
                    <tr class="border-t">
                        <td class="p-3">{{ $a['name'] }}</td>
                        <td class="p-3">{{ $a['email'] }}</td>
                        <td class="p-3">
                            @php
                                $r = collect($restaurants)->firstWhere('id', $a['restaurantId']);
                            @endphp
                            {{ $r['name'] ?? $a['restaurantId'] }}
                        </td>
                        <td class="p-3 space-x-2">
                            <a href="{{ route('settings.restaurant_admins.edit', $a['uid']) }}" class="px-3 py-1 bg-gray-200 rounded">Edit</a>
                            <form action="{{ route('settings.restaurant_admins.destroy', $a['uid']) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this admin?')" class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-3 text-center text-gray-500">No restaurant admins found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
