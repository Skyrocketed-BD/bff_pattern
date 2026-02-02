<?php

namespace App\Events;

use App\Helpers\PushyAPI;
use App\Models\main\Event;
use App\Models\main\User;
use App\Models\main\UserTokens;

class KontraktorEventTriggered
{
    public static function trigger($event, $id_kontraktor)
    {
        $check_event = Event::with(['toEventUsers'])->where('event', $event)->first();

        $users = User::where('id_kontraktor', $id_kontraktor)->get();

        $id_users = [];
        foreach ($users as $key => $value) {
            $id_users[] = $value->id_users;
        }

        if ($check_event) {
            if ($check_event->toEventUsers->count() != 0) {
                $event_users = $check_event->toEventUsers->whereIn('id_users', $id_users);

                $token = [];
                foreach ($event_users as $key => $value) {
                    $user_notification = UserTokens::where('id_users', $value->id_users)->get();

                    if ($user_notification->count() != 0) {
                        foreach ($user_notification as $key => $row) {
                            $token[] = $row->token;
                        }
                    }
                }

                if (count($token) > 0) {
                    $message = $check_event->message . ' ' . $message;
                    PushyAPI::sendNotification($token, $check_event->title, $message, $check_event->url, $check_event->image);
                }
            }
        }
    }
}
