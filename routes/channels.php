<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('test', function () {
    \Log::info('Private channel access granted');
    return true;
});

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });
