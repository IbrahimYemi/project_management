<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait to set a UUID for the model.
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Ensure the key type is a string for UUIDs.
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Ensure the primary key is of type string.
     */
    public function getKeyType()
    {
        return 'string';
    }
}
