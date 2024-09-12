<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait SoftDeletesWithDeletedBy
{
    public static function bootSoftDeletesWithDeletedBy()
    {
        static::deleting(function ($model) {
            //  Check if the model uses soft deletes and is not being force deleted
            if ($model->usesSoftDelete() && !$model->isForceDeleting()) {
                $model->deleted_by = Auth::id();
                
                $model->save();
            }
        });
    }

    /**
     * Determine if the model uses soft deletes.
     *
     * @return bool
     */
    public function usesSoftDelete()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this));
    }
}
