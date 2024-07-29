<?php

namespace App\Http\Controllers\Clock;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Clock\DepartService;

class DepartController extends Controller
{
    private DepartService $departService;
    
    public function __construct(DepartService $departService) {
        $this->departService = $departService;
    }

    public function getAllJobs()
    {
        return response()->json($this->departService->getAllJobs(), 200);
    }

    public function trackTravelTime(Request $request)
    {
            return response()->json($this->departService->trackTravelTime($request->all()), 200);
        
    }
}
