@extends('layouts.app')

@section('content')
<div class="container" style="max-width:760px;margin-top:3rem;">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Home</h4>

            @if($user)
                <p>Welcome back, <strong>{{ $user['email'] }}</strong>!</p>
                <p>Your UID: <code>{{ $user['uid'] }}</code></p>
            @else
                <p>Welcome! You are not logged in. <a href="{{ url('/login') }}">Login with Firebase</a></p>
            @endif

            <hr />

            <p>
                Quick links:
            </p>
            <ul>
                <li><a href="{{ url('/firebase-test') }}">Run Firebase test (create a document)</a></li>
                <li><a href="https://console.firebase.google.com/" target="_blank">Open Firebase Console</a></li>
            </ul>
        </div>
    </div>
</div>
@endsection
