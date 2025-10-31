<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = session('firebase_user');
        return view('home', ['user' => $user]);
    }
}
