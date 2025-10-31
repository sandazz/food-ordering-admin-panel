<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;

class FirebaseController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function test()
    {
        $this->firebase->createDocument('testing', [
            'message' => 'Firebase Integration Successful!'
        ]);

        return 'Firebase Connected and Test Data Added!';
    }
}
