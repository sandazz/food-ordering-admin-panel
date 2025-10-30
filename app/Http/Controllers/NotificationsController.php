<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class NotificationsController extends Controller
{
    public function index()
    {
        return view('admin.notifications');
    }

    public function send(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:500',
            'topic' => 'nullable|string',
            'token' => 'nullable|string',
        ]);

        $messaging = $firebase->getMessaging();

        if (!empty($data['topic'])) {
            $message = CloudMessage::withTarget('topic', $data['topic'])
                ->withNotification(FcmNotification::create($data['title'], $data['body']));
            $messaging->send($message);
        } elseif (!empty($data['token'])) {
            $message = CloudMessage::withTarget('token', $data['token'])
                ->withNotification(FcmNotification::create($data['title'], $data['body']));
            $messaging->send($message);
        }

        return back()->with('status', 'Notification sent');
    }
}
