<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class BaseModel extends Authenticatable
{
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid();
            }
        });
    }

    public function getIncrementing(): false
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
