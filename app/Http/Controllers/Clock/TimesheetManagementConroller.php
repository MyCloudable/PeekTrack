<?php

namespace App\Http\Controllers\Clock;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\Crew;
use App\Models\User;
use App\Models\CrewType;
use App\Models\TimeType;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Clock\TimesheetService;
use Illuminate\Validation\ValidationException;

class TimesheetManagementConroller extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name AS text', 'role_id', 'location')->get();
        $jobs = Job::where('status', 'In progress')->select('id', DB::raw("CONCAT(job_number,' (',county, ')') as text"))->get();
        $timeTypes = TimeType::select('id', 'name', 'value', DB::raw('name as text'))->get();
        $authuser = Auth::user();
        $crewTypes = CrewType::select('id', 'name as text')->get();
        
        // get this to show in super intendent dropdown while creating a new entry.So that we can grab crew_id from this
        $uniqueSuperintendents = User::whereIn('id', Crew::select('superintendentId')->distinct())->select('id', 'name as text')->get();

        return view('clock.timesheetManagement.index', compact('users', 'jobs', 'timeTypes', 'authuser', 'crewTypes', 'uniqueSuperintendents'));
    }
	
public function crewindex()
{
    $query = DB::table(DB::raw('(
        SELECT
            DATE(clockin_time) as day,
            user_id,
            crew_member_approval,
            TIMESTAMPDIFF(MINUTE, clockin_time, LEAST(clockout_time, CONCAT(DATE(clockin_time), " 23:59:59"))) as total_minutes
        FROM timesheets
        WHERE DATE(clockin_time) = DATE(clockout_time)

        UNION ALL

        SELECT
            DATE(clockin_time) as day,
            user_id,
            crew_member_approval,
            TIMESTAMPDIFF(MINUTE, clockin_time, CONCAT(DATE(clockin_time), " 23:59:59")) as total_minutes
        FROM timesheets
        WHERE DATE(clockout_time) > DATE(clockin_time)
          AND DATE(clockin_time) <> DATE(clockout_time)

        UNION ALL

        SELECT
            DATE(clockout_time) as day,
            user_id,
            crew_member_approval,
            TIMESTAMPDIFF(MINUTE, CONCAT(DATE(clockout_time), " 00:00:00"), clockout_time) as total_minutes
    FROM timesheets
    WHERE DATE(clockout_time) > DATE(clockin_time)
      AND DATE(clockin_time) <> DATE(clockout_time)
    ) as daily_totals'))
    ->join('users as crewmembers', 'daily_totals.user_id', '=', 'crewmembers.id')
    ->selectRaw('
        daily_totals.day,
        crewmembers.name as crewmember_name,
        crewmembers.id as user_id,
        MIN(daily_totals.crew_member_approval) AS crew_member_approval,
        SUM(daily_totals.total_minutes) AS total_minutes
    ')
    ->whereRaw('daily_totals.day < CURDATE()')
    ->groupBy(
        'daily_totals.day',
        'crewmembers.name',
        'crewmembers.id'
    )
    ->havingRaw('MIN(daily_totals.crew_member_approval) = 0')
    ->orderBy('daily_totals.day', 'desc')
    ->get();

    // Group by Sunday-Saturday week and user
    $weeklySummary = $query->groupBy(function ($item) {
        // Calculate the start of the week (Sunday)
        $dayOfWeek = date('w', strtotime($item->day)); // 0 (for Sunday) through 6 (for Saturday)
        $sunday = date('Y-m-d', strtotime($item->day . ' -' . $dayOfWeek . ' days'));

        return $item->user_id . '-' . $sunday;
    });

    // Format total minutes into HH:MM and calculate weekly total
    foreach ($weeklySummary as $weekGroup) {
        $weeklyTotalMinutes = $weekGroup->sum('total_minutes');

        foreach ($weekGroup as $record) {
            $hours = floor($record->total_minutes / 60);
            $minutes = $record->total_minutes % 60;
            $record->formatted_time = sprintf('%02d:%02d', $hours, $minutes);

            // Add day of the week
            $record->day_of_week = date('l', strtotime($record->day));

            // Add weekly total to the first record of the group
            if ($record === $weekGroup->first()) {
                $record->weekly_total_time = sprintf('%02d:%02d', floor($weeklyTotalMinutes / 60), $weeklyTotalMinutes % 60);
                $record->week_rowspan = $weekGroup->count();
            } else {
                $record->weekly_total_time = null; // No display for other rows
                $record->week_rowspan = null;
            }
        }
    }

    return view('crew.crewindex', compact('weeklySummary'));
}


	
public function summary()
{
    $query = DB::table(DB::raw('(
        SELECT
            DATE(clockin_time) as day,
            user_id,
            crew_member_approval,
            TIMESTAMPDIFF(MINUTE, clockin_time, LEAST(clockout_time, CONCAT(DATE(clockin_time), " 23:59:59"))) as total_minutes
        FROM timesheets
        WHERE DATE(clockin_time) = DATE(clockout_time)

        UNION ALL

        SELECT
            DATE(clockin_time) as day,
            user_id,
            crew_member_approval,
            TIMESTAMPDIFF(MINUTE, clockin_time, CONCAT(DATE(clockin_time), " 23:59:59")) as total_minutes
        FROM timesheets
        WHERE DATE(clockout_time) > DATE(clockin_time)
          AND DATE(clockin_time) <> DATE(clockout_time)

        UNION ALL

        SELECT
            DATE(clockout_time) as day,
            user_id,
            crew_member_approval,
            TIMESTAMPDIFF(MINUTE, CONCAT(DATE(clockout_time), " 00:00:00"), clockout_time) as total_minutes
    FROM timesheets
    WHERE DATE(clockout_time) > DATE(clockin_time)
      AND DATE(clockin_time) <> DATE(clockout_time)
    ) as daily_totals'))
    ->join('users as crewmembers', 'daily_totals.user_id', '=', 'crewmembers.id')
    ->selectRaw('
        daily_totals.day,
        crewmembers.name as crewmember_name,
        crewmembers.id as user_id,
        MIN(daily_totals.crew_member_approval) AS crew_member_approval,
        SUM(daily_totals.total_minutes) AS total_minutes
    ')
    ->whereRaw('daily_totals.day < CURDATE()')
    ->groupBy(
        'daily_totals.day',
        'crewmembers.name',
        'crewmembers.id'
    )
    ->havingRaw('MIN(daily_totals.crew_member_approval) = 1')
    ->orderBy('daily_totals.day', 'desc')
    ->get();

    // Group by week and user
    $weeklySummary = $query->groupBy(function ($item) {
        return $item->user_id . '-' . date('W', strtotime($item->day));
    });

    // Format total minutes into HH:MM and calculate weekly total
    foreach ($weeklySummary as $weekGroup) {
        $weeklyTotalMinutes = $weekGroup->sum('total_minutes');

        foreach ($weekGroup as $record) {
            $hours = floor($record->total_minutes / 60);
            $minutes = $record->total_minutes % 60;
            $record->formatted_time = sprintf('%02d:%02d', $hours, $minutes);

            // Add day of the week
            $record->day_of_week = date('l', strtotime($record->day));

            // Add weekly total to the first record of the group
            if ($record === $weekGroup->first()) {
                $record->weekly_total_time = sprintf('%02d:%02d', floor($weeklyTotalMinutes / 60), $weeklyTotalMinutes % 60);
                $record->week_rowspan = $weekGroup->count();
            } else {
                $record->weekly_total_time = null; // No display for other rows
                $record->week_rowspan = null;
            }
        }
    }

    return view('crew.summary', compact('weeklySummary'));
}

    public function getAll(Request $request)
    {   
        
        
        $query = DB::table('timesheets')
                    ->join('users as crewmembers', 'timesheets.user_id', '=', 'crewmembers.id')
                    ->join('crews', 'timesheets.crew_id', '=', 'crews.id')
                    ->join('users as superintendents', 'crews.superintendentId', '=', 'superintendents.id')
                    ->join('jobs', 'timesheets.job_id', '=', 'jobs.id')
                    ->leftJoin('time_types', 'timesheets.time_type_id', '=', 'time_types.id')
                    ->select(
                        'timesheets.*',
                        DB::raw("DATE_FORMAT(clockin_time, '%Y-%m-%d %H:%i') as clockin_time"), 
                        DB::raw("DATE_FORMAT(clockout_time, '%Y-%m-%d %H:%i') as clockout_time"), 
                        DB::raw("TIMESTAMPDIFF(minute,clockin_time,clockout_time) as total_time"),
                        'crewmembers.name as crewmember_name', 
                        'crewmembers.location as crewmember_location',
                        DB::raw("CONCAT(jobs.job_number,' ',jobs.county) as job_number_county"), 
                        'superintendents.id', 
                        'superintendents.name as superintendent_name', 
                        'superintendents.location as superintendent_location',
                        'time_types.name as time_type_name',
                        'timesheets.id as timesheet_id' 
                    );

        if(is_array($request->filterData)){
            // dd($request->filterData['superIntendent']);
            if(array_key_exists('crewMember', $request->filterData))    
                $query->where('timesheets.user_id', $request->filterData['crewMember']);
                

            if(array_key_exists('superIntendent', $request->filterData))    
                $query->where('superintendents.id', $request->filterData['superIntendent']);

            if(array_key_exists('job', $request->filterData))    
                $query->where('timesheets.job_id', $request->filterData['job']);

            if(array_key_exists('from', $request->filterData))    
                $query->whereDate('timesheets.clockin_time', '>=', $request->filterData['from']);

            if(array_key_exists('to', $request->filterData))    
                $query->whereDate('timesheets.clockin_time', '<=', $request->filterData['to']);

            if(array_key_exists('location', $request->filterData))    
                $query->where('crewmembers.location', $request->filterData['location']);
                // ->orWhere('superintendents.location', $request->filterData['location'])

        }

        
        

        $query = $query->get();

        
        return DataTables::of($query)
        // ->toJson()

        // ->addColumn('crew_member_approval', function ($row) {
        //     return '<input class="form-check-input crew-member-approval-checkbox" type="checkbox" data-id="' . $row->timesheet_id . '" data-type="cma" disabled >';
        // })
        // ->addColumn('reviewer_approval', function ($row) {
        //     return '<input class="form-check-input reviewer-approval-checkbox" type="checkbox" data-id="' . $row->timesheet_id . '" data-type="ra">';
        // })
        // ->addColumn('payroll_approval', function ($row) {
        //     return '<input class="form-check-input" type="checkbox payroll-approval-checkbox" data-id="' . $row->timesheet_id . '" data-type="pa">';
        // })
        // ->addColumn('action', function ($row) {
        //     return '<i class="fa fa-pencil cursor-pointer edit-icon" data-id="' . $row->timesheet_id . '" aria-hidden="true"></i>';
        // })

        // If you need to allow HTML in the custom column, use rawColumns
        // ->rawColumns([
            // 'crew_member_approval', 
            // 'reviewer_approval', 
            // 'payroll_approval', 
            // 'action'])


        ->make(true);

    }

    public function updateCheckboxApproval(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|exists:timesheets,id',
            'approved' => 'required|boolean',
            'type' => 'required|in:payroll_approval,reviewer_approval,weekend_out',
        ]);

        // Update the timesheet record based on the provided type
        $timesheet = Timesheet::findOrFail($validatedData['id']);

        switch ($validatedData['type']) {
            case 'payroll_approval':
                $timesheet->payroll_approval = $validatedData['approved'];
                break;
            case 'weekend_out':
                $timesheet->weekend_out = $validatedData['approved'];
                break;
            case 'reviewer_approval':
                $timesheet->reviewer_approval = $validatedData['approved'];
                $timesheet->reviewer_approval_by = Auth::user()->id;
                $timesheet->reviewer_approval_at = Carbon::now();
                break;
        }

        $timesheet->save();

        return response()->json(['success' => true]);
    }
	
	public function updateCrewCheckBox(Request $request)
	{
		$id = $request->id;
		$date = $request->date;

		DB::table('timesheets')
			->where('user_id', $id)
			->where('clockin_time', 'LIKE', $date . '%')
			->update(['crew_member_approval' => 1]);

		return response()->json(['success' => true]);
	}

    public function updateCheckboxApprovalBulk(Request $request)
    {

        $request->validate([
            'selectedIds' => 'required|array',
            'selectedIds.*.id' => 'required|integer',
            'approved' => 'required|boolean',
            'type' => 'required|in:payroll_approval',
        ]);

        // If validation passes, proceed with updating records
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $selectedId) {
            $id = $selectedId['id'];

            $timesheet = Timesheet::find($id);
            $timesheet->payroll_approval = $request->approved;
            $timesheet->save();
        }

        return response()->json(['success' => true]);
    }

    public function updateTimes(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:timesheets,id',
            'clockin_time' => 'required',
            'clockout_time' => 'required',
            'job_number' => 'required|exists:jobs,id',
        ]);

        // Retrieve inputs from the request
        $id = $request->id;
        $clockinTime = $request->clockin_time;
        $clockoutTime = $request->clockout_time;
        $jobNumber = $request->job_number;
        $timeType = $request->time_type;
		$perDiem = $request->per_diem;

        try {
            // Fetch the timesheet record by ID
            $timesheet = Timesheet::findOrFail($id);
            // dd($timesheet);

            // // Validate against nearest timesheet entry with same crew and date
            // $nearestTimesheet = Timesheet::where('crew_id', $timesheet->crew_id)
            // ->where('user_id', $timesheet->user_id)
            // ->whereDate('clockin_time', '=', date('Y-m-d', strtotime($timesheet->clockin_time)))
            // ->where('id', '<>', $timesheet->id)
            // ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, clockin_time, ?))', [$clockinTime])
            // ->first();

            // if ($nearestTimesheet) {
            //     // Check if updating clock out time is not greater than nearest record clock in time
            //     if (strtotime($clockoutTime) > strtotime($nearestTimesheet->clockin_time)) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Updating clock out time cannot be greater than nearest record clock in time with id ' . $nearestTimesheet->id
            //         ], 422);
            //     }
    
            //     // Check if updating clockin time is not less than nearest record clock out time
            //     if (strtotime($clockinTime) < strtotime($nearestTimesheet->clockout_time)) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Updating clockin time cannot be less than nearest record clock out time with id ' . $nearestTimesheet->id
            //         ], 422);
            //     }
            // }


            // validate clockin and clockout
            (new TimesheetService())->validateClockInOut($request->clockin_time, $request->clockout_time);

            // Validate overlap using custom method
            $this->validateTimesheetOverlap(
                $timesheet->user_id,
                $request->clockin_time,
                $request->clockout_time,
                $timesheet->id // Exclude current timesheet ID from overlap check
            );


            // Update the timesheet data
            $timesheet->clockin_time = $clockinTime;
            $timesheet->clockout_time = $clockoutTime;
            $timesheet->job_id = $jobNumber;
            $timesheet->time_type_id = $timeType;
			$timesheet->per_diem = $perDiem;

            // Save the changes
            $timesheet->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // Handle any exceptions (e.g., database errors)
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteTimesheet($id)
    {
        $user = Auth::user();
        $timesheet = Timesheet::findOrFail($id);

        // Add your role checks here
        if (($user->role_id == 2 || $user->role_id == 3 || $user->role_id == 5) && !$timesheet->payroll_approval) {
            $timesheet->delete();
            return response()->json(['success' => true, 'message' => 'Timesheet deleted successfully', 200]);
        }else{
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }
    }

    public function storeTimesheet(Request $request)
    {

        try {

            $data = $request->formData;
            $data['crew_id'] = Crew::where('superintendentId', $data['superintendentId'])->value('id'); //get crew id from superintendent id
            $data['created_by'] = Auth::user()->id;
            $data['modified_by'] = Auth::user()->id;

            $userIds = $data['user_id']; // assuming user_id is an array

            // validate clockin and clockout
            (new TimesheetService())->validateClockInOut($data['clockin_time'], $data['clockout_time']);

            // Validate overlap using custom method (for each crew member)
            foreach ($userIds as $userId) {
                $this->validateTimesheetOverlap(
                    $data['user_id'],
                    $data['clockin_time'],
                    $data['clockout_time'],
                );
            }

            // If validation passes for all user_ids, proceed to save
            foreach ($userIds as $userId) {
                $timesheetData = array_merge($data, [
                    'user_id' => $userId
                ]);

                Timesheet::create($timesheetData);
            }

            return response()->json(['success' => true, 'message' => 'Timesheet created successfully', 200]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
        
    }

    public function validateTimesheetOverlap($user_id, $clockin_time, $clockout_time, $exclude_id = null)
    {
        // dd($clockout_time);

        try {
            $query = Timesheet::where('user_id', $user_id)
                ->whereDate('clockin_time', '<=', date('Y-m-d', strtotime($clockout_time))) // Check if existing timesheets start before or on the new timesheet's clockout_date
                ->whereDate('clockout_time', '>=', date('Y-m-d', strtotime($clockin_time))); // Check if existing timesheets end after or on the new timesheet's clockin_date
    
            // dd($query->get());
            $query->where(function ($query) use ($clockin_time, $clockout_time) {
                $query->where(function ($query) use ($clockin_time, $clockout_time) {
                    $query->where('clockin_time', '<', $clockout_time)
                        ->where('clockout_time', '>', $clockin_time);
                })->orWhere(function ($query) use ($clockin_time, $clockout_time) {
                    $query->where('clockin_time', '>=', $clockin_time)
                        ->where('clockout_time', '<=', $clockout_time);
                });
            });
    
            if ($exclude_id) {
                $query->where('id', '!=', $exclude_id);
            }
    
            $overlappingTimesheets = $query->get();
            
            if ($overlappingTimesheets->isNotEmpty()) {
                // Prepare an array of overlapping timesheet IDs
                $overlappingIds = $overlappingTimesheets->pluck('id')->toArray();

                // Get the user name
                $userName = User::where('id', $user_id)->value('name');

                throw ValidationException::withMessages([
                    'error' => 'Overlapping timesheets for user ' .  $userName .  ' with these ids. ' . implode(',', $overlappingIds),
                    // 'overlapping_ids' => $overlappingIds
                ]);
            }
    
            return $overlappingTimesheets;
        } catch(\Exception $e) {
            throw $e;
        }
    }



}
