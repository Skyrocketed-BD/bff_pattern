<?php

namespace App\Helpers;

use App\Models\main\UserActivityLog;

class ActivityLogHelper
{
    public static function log($action, $status, $details = [])
    {
        if (auth()->id()) {
            $log             = new UserActivityLog();
            $log->id_users   = auth()->id();
            $log->action     = $action;
            $log->ip_address = request()->ip();
            $log->user_agent = request()->userAgent();
            $log->details    = $details;
            $log->status     = $status;
            $log->save();
        }
    }
}
