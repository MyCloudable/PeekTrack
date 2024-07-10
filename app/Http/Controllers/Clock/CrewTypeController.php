<?php

namespace App\Http\Controllers\Clock;

use App\Models\CrewType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CrewTypeController extends Controller
{
    public function index()
    {
        return view('clock.crewTypes.index')
        ->with('crewTypes', CrewType::all());
    }
}
