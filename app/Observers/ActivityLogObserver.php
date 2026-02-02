<?php

namespace App\Observers;

use App\Helpers\ActivityLogHelper;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(Model $model)
    {
        if (method_exists($model, 'getLogPrefix')) {
            $prefix = $model->getLogPrefix();
            $data = method_exists($model, 'getLogData')
                ? $model->getLogData()
                : $model->toArray();

            ActivityLogHelper::log("{$prefix}_create", 1, $data);
        }
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model)
    {
        if (method_exists($model, 'getLogPrefix')) {
            $prefix = $model->getLogPrefix();
            $data = method_exists($model, 'getLogData')
                ? $model->getLogData()
                : $model->toArray();

            // Tambahkan info perubahan
            $data['changes'] = $model->getChanges();

            ActivityLogHelper::log("{$prefix}_update", 1, $data);
        }
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model)
    {
        if (method_exists($model, 'getLogPrefix')) {
            $prefix = $model->getLogPrefix();
            $data = method_exists($model, 'getLogData')
                ? $model->getLogData()
                : $model->toArray();

            ActivityLogHelper::log("{$prefix}_delete", 1, $data);
        }
    }

    /**
     * Handle the "failed" event (ketika ada error).
     */
    public function failed(Model $model, \Exception $e)
    {
        if (method_exists($model, 'getLogPrefix')) {
            $prefix = $model->getLogPrefix();

            ActivityLogHelper::log("{$prefix}_failed", 0, [
                'error' => $e->getMessage(),
                'model' => class_basename($model)
            ]);
        }
    }
}
