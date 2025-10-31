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
            'restaurantId' => 'nullable|string',
            'region' => 'nullable|string',
            'group' => 'nullable|string',
        ]);

        $messaging = $firebase->getMessaging();

        if (!empty($data['token'])) {
            $message = CloudMessage::withTarget('token', $data['token'])
                ->withNotification(FcmNotification::create($data['title'], $data['body']));
            $messaging->send($message);
        } else {
            $topics = [];
            if (!empty($data['topic'])) { $topics[] = $data['topic']; }
            if (!empty($data['restaurantId'])) { $topics[] = 'restaurant_' . $data['restaurantId']; }
            if (!empty($data['region'])) { $topics[] = 'region_' . $data['region']; }
            if (!empty($data['group'])) { $topics[] = 'group_' . $data['group']; }
            $topics = array_values(array_unique(array_filter($topics)));
            foreach ($topics as $t) {
                $message = CloudMessage::withTarget('topic', $t)
                    ->withNotification(FcmNotification::create($data['title'], $data['body']));
                $messaging->send($message);
            }
        }

        return back()->with('status', 'Notification sent');
    }
}
