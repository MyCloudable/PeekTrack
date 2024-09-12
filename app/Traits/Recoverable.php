<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Recoverable
{
    /**
     * Recover the soft-deleted model.
     *
     * @param int $id
     * @return bool
     */
    public static function recoverDeleted(int $id): bool
    {
        $item = static::onlyTrashed()->find($id);

        if ($item) {

            $item->deleted_by = null; // Clear the deleted_by field
            $item->restore();
            $item->save(); // Ensure the deleted_by field is cleared
            return true;

        }

        return false;
    }

}
