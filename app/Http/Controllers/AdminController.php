<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function recoverItem($model, $id)
    {

        $modelClass = 'App\\Models\\' . ucfirst($model);

        if (!class_exists($modelClass) || !in_array('App\Traits\Recoverable', class_uses($modelClass))) {
            return response()->json(['error' => 'Model not found or not recoverable'], 404);
        }

        $recovered = $modelClass::recoverDeleted($id);

        if ($recovered) {
            return response()->json(['success' => 'Item recovered successfully']);
        } else {
            return response()->json(['error' => 'Item not found or already recovered'], 404);
        }
    }
}
