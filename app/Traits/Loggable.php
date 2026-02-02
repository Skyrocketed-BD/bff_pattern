<?php

namespace App\Traits;

use App\Observers\ActivityLogObserver;

trait Loggable
{
    public static function bootLoggable()
    {
        static::observe(ActivityLogObserver::class);
    }

    /**
     * Tentukan prefix untuk log (override di model jika perlu)
     */
    public function getLogPrefix(): string
    {
        $reflection = new \ReflectionClass($this);
        $namespace = $reflection->getNamespaceName();

        $parts = explode('\\', $namespace);
        $module = isset($parts[2]) ? strtolower($parts[2]) : 'general';

        $modelName = snake_case(class_basename($this));

        return "{$module}:{$modelName}";
    }

    /**
     * Data yang akan di-log (override di model jika perlu custom)
     */
    public function getLogData(): array
    {
        return $this->toArray();
    }
}
