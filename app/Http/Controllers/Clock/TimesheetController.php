<?php

namespace App\Http\Controllers\Clock;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Clock\TimesheetService;

class TimesheetController extends Controller
{
    private TimesheetService $timesheetService;
    
    public function __construct(TimesheetService $timesheetService) {
        $this->timesheetService = $timesheetService;
    }

    public function getCrewMembers()
    {
        return response()->json($this->timesheetService->getCrewMembers(), 200);
    }

    public function verifyCrewMembers(Request $request)
    {   
        // $validated = $request->validate([
        //     'crewMembers' => 'required',
        // ]);
        
        return response()->json($this->timesheetService->verifyCrewMembers($request->all()), 200);
    }

    public function clockinoutCrewMembers(Request $request)
    {
        return response()->json($this->timesheetService->clockinoutCrewMembers($request->all()), 200);
    }

    public function getAllUsers()
    {
        return response()->json($this->timesheetService->getAllUsers(), 200);
    }

    public function addNewCrewMember(Request $request)
    {
        // dd($request->all());
        return response()->json($this->timesheetService->addNewCrewMember($request->all()), 200);
    }

    public function deleteCrewMember(Request $request)
    {
        return response()->json($this->timesheetService->deleteCrewMember($request->all()), 200);
    }

    public function hfPerDiem(Request $request)
    {
        return response()->json($this->timesheetService->hfPerDiem($request->all()), 200);
    }

    public function readyForVerification(Request $request)
    {
        return response()->json($this->timesheetService->readyForVerification($request->all()), 200);
    }

    public function weatherEntry(Request $request)
    {
        return response()->json($this->timesheetService->weatherEntry($request->all()), 200);
    }
}
