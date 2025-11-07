@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-semibold mb-4">Edit Restaurant Admin</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            <ul class="list-disc ml-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('settings.restaurant_admins.update', $admin['uid']) }}" method="POST" class="space-y-4 bg-white p-4 rounded shadow">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $admin['name']) }}" class="w-full border rounded p-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $admin['email']) }}" class="w-full border rounded p-2" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Restaurant</label>
            <select name="restaurantId" class="w-full border rounded p-2" required>
                @foreach($restaurants as $r)
                    <option value="{{ $r['id'] }}" @selected(old('restaurantId', $admin['restaurantId'])===$r['id'])>{{ $r['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">New Password (optional)</label>
            <input type="password" name="password" class="w-full border rounded p-2" placeholder="Leave blank to keep current">
        </div>
        <div class="flex space-x-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
            <a href="{{ route('settings.restaurant_admins') }}" class="px-4 py-2 bg-gray-200 rounded">Cancel</a>
        </div>
    </form>
</div>
@endsection
