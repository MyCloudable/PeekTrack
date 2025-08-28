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

    // Get all time types for crew entry. This method returns all time types except "Production"
    public function getTimeTypes()
    {
        return response()->json($this->timesheetService->getTimeTypes(), 200);
    }

    // Loop through time types dropdown and switch time type for a crew member
    public function switchTimeType(Request $request)
    {
        $validated = $request->validate([
            'crewId'       => 'required|integer|exists:crews,id',
            'timeTypeId'   => 'required|integer|exists:time_types,id',
            'lateEntryTime'=> 'nullable|date',
        ]);

        return response()->json($this->timesheetService->switchTimeType($validated), 200);
    }
}
