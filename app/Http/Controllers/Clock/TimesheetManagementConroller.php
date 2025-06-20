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
        $users = User::select('id', 'name AS text', 'role_id', 'location')->where('active', 1)->get();
        $jobs = Job::where('status', 'In progress')->select('id', DB::raw("CONCAT(job_number,' (',county, ')') as text"))->get();
        $timeTypes = TimeType::select('id', 'name', 'value', DB::raw('name as text'))->get();
        $authuser = Auth::user();
        $crewTypes = CrewType::select('id', 'name as text')->get();
        
        // get this to show in super intendent dropdown while creating a new entry.So that we can grab crew_id from this
        $uniqueSuperintendents = User::where('active', 1)->whereIn('id', Crew::select('superintendentId')->distinct())->select('id', 'name as text')->get();

        return view('clock.timesheetManagement.index', compact('users', 'jobs', 'timeTypes', 'authuser', 'crewTypes', 'uniqueSuperintendents'));
    }
	
public function crewindex()
{
    $query = DB::table(DB::raw('(
        SELECT
            DATE(clockin_time) as day,
            user_id,
            crew_member_approval,
            per_diem,
            TIMESTAMPDIFF(MINUTE, clockin_time, LEAST(clockout_time, CONCAT(DATE(clockin_time), " 23:59:59"))) as total_minutes
        FROM timesheets
        WHERE DATE(clockin_time) = DATE(clockout_time) and deleted_at IS NULL

        UNION ALL

        SELECT
            DATE(clockin_time) as day,
            user_id,
            crew_member_approval,
            per_diem,
            TIMESTAMPDIFF(MINUTE, clockin_time, CONCAT(DATE(clockin_time), " 23:59:59")) as total_minutes
        FROM timesheets
        WHERE DATE(clockout_time) > DATE(clockin_time)
          AND DATE(clockin_time) <> DATE(clockout_time) and deleted_at IS NULL

        UNION ALL

        SELECT
            DATE(clockout_time) as day,
            user_id,
            crew_member_approval,
            per_diem,
            TIMESTAMPDIFF(MINUTE, CONCAT(DATE(clockout_time), " 00:00:00"), clockout_time) as total_minutes
        FROM timesheets
        WHERE DATE(clockout_time) > DATE(clockin_time)
          AND DATE(clockin_time) <> DATE(clockout_time) and deleted_at IS NULL
    ) as daily_totals'))
    ->join('users as crewmembers', 'daily_totals.user_id', '=', 'crewmembers.id')
    ->selectRaw('
        daily_totals.day,
        crewmembers.name as crewmember_name,
        crewmembers.id as user_id,
        MIN(daily_totals.crew_member_approval) AS crew_member_approval,
        SUM(daily_totals.total_minutes) AS total_minutes,
        COALESCE(MAX(daily_totals.per_diem), "NA") AS per_diem
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
            per_diem,
            TIMESTAMPDIFF(MINUTE, clockin_time, LEAST(clockout_time, CONCAT(DATE(clockin_time), " 23:59:59"))) as total_minutes
        FROM timesheets
        WHERE DATE(clockin_time) = DATE(clockout_time) and deleted_at IS NULL

        UNION ALL

        SELECT
            DATE(clockin_time) as day,
            user_id,
            crew_member_approval,
            per_diem,
            TIMESTAMPDIFF(MINUTE, clockin_time, CONCAT(DATE(clockin_time), " 23:59:59")) as total_minutes
        FROM timesheets
        WHERE DATE(clockout_time) > DATE(clockin_time)
          AND DATE(clockin_time) <> DATE(clockout_time) and deleted_at IS NULL

        UNION ALL

        SELECT
            DATE(clockout_time) as day,
            user_id,
            crew_member_approval,
            per_diem,
            TIMESTAMPDIFF(MINUTE, CONCAT(DATE(clockout_time), " 00:00:00"), clockout_time) as total_minutes
        FROM timesheets
        WHERE DATE(clockout_time) > DATE(clockin_time)
          AND DATE(clockin_time) <> DATE(clockout_time) and deleted_at IS NULL
    ) as daily_totals'))
    ->join('users as crewmembers', 'daily_totals.user_id', '=', 'crewmembers.id')
    ->selectRaw('
        daily_totals.day,
        crewmembers.name as crewmember_name,
        crewmembers.id as user_id,
        MIN(daily_totals.crew_member_approval) AS crew_member_approval,
        SUM(daily_totals.total_minutes) AS total_minutes,
        COALESCE(MAX(daily_totals.per_diem), "NA") AS per_diem
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

    // Group by week (starting on Sunday) and user
    $weeklySummary = $query->groupBy(function ($item) {
        $timestamp = strtotime($item->day);
        $sundayTimestamp = strtotime('last Sunday', $timestamp + 86400); // Adjust to previous Sunday
        return $item->user_id . '-' . date('oW', $sundayTimestamp); // ISO-8601 week number
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
    ->leftJoin('users as creators', 'timesheets.created_by', '=', 'creators.id')  // Self-join to get the creator's name
    ->select(
        'timesheets.*',
        DB::raw("DATE_FORMAT(clockin_time, '%Y-%m-%d %H:%i') as clockin_time"), 
        DB::raw("DATE_FORMAT(clockout_time, '%Y-%m-%d %H:%i') as clockout_time"), 
        DB::raw("TIMESTAMPDIFF(minute, clockin_time, clockout_time) as total_time"),
		DB::raw("CONCAT('(',crewmembers.id, ') ', crewmembers.name) as crewmember_name"),		
        'crewmembers.location as crewmember_location',
        DB::raw("CONCAT(jobs.job_number, ' ', jobs.county) as job_number_county"), 
        'superintendents.id', 
        'superintendents.name as superintendent_name', 
        'superintendents.location as superintendent_location',
        'time_types.name as time_type_name',
        'timesheets.id as timesheet_id',
        'creators.name as created_by',
        'crewmembers.role_id as crewmember_role'
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

        // don't show deleted items to other users if they are not admin , role 1 is admin here
        if(Auth::user()->role_id !== 11){
            $query->whereNull('timesheets.deleted_at');
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
	
	public function bulkTimeApproval(Request $request)
	{
    $twoDates = $request->daterange2;
    $date1 = date('Y-m-d', strtotime(substr($twoDates, 0, 10)));
    $date2 = date('Y-m-d', strtotime(substr($twoDates, 13, 21)));

    DB::table('timesheets')
        ->whereDate('clockin_time', '>=', $date1)
        ->whereDate('clockin_time', '<=', $date2)
        ->update(['payroll_approval' => 1]);

    
	return view("pages.widgets",['success' => true]);
	}
	
    public function updateCheckboxApprovalBulk(Request $request)
    {
        // dd($request->all());

        $request->validate([
            'selectedIds' => 'required|array',
            'selectedIds.*.id' => 'required|integer',
            'approved' => 'required|boolean',
            'type' => 'required|in:payroll_approval,reviewer_approval',
        ]);

        // If validation passes, proceed with updating records
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $selectedId) {
            $id = $selectedId['id'];

            $timesheet = Timesheet::find($id);
            
            switch($request->type){
                case 'payroll_approval':
                    $timesheet->payroll_approval = $request->approved;
                    break;
                case 'reviewer_approval':
                    $timesheet->reviewer_approval = $request->approved;
                    break;
            }

            $timesheet->save();
        }

        return response()->json(['success' => true]);
    }

    public function updateTimes(Request $request)
    {
        // dd($request->all());

        // $request->validate([
        //     'id' => 'required|exists:timesheets,id',
        //     'clockin_time' => 'required',
        //     'clockout_time' => 'required',
        //     'job_number' => 'required|exists:jobs,id',
        // ]);


        // // Retrieve inputs from the request
        // $id = $request->id;
        // $clockinTime = $request->clockin_time;
        // $clockoutTime = $request->clockout_time;
        // $jobNumber = $request->job_number;
        // $timeType = $request->time_type;
        // $perDiem = $request->per_diem;

        // try {
           
        //     $timesheet = Timesheet::findOrFail($id);

        //     // validate clockin and clockout
        //     (new TimesheetService())->validateClockInOut($request->clockin_time, $request->clockout_time);

        //     // Validate overlap using custom method
        //     $this->validateTimesheetOverlap(
        //         $timesheet->user_id,
        //         $request->clockin_time,
        //         $request->clockout_time,
        //         $timesheet->id // Exclude current timesheet ID from overlap check
        //     );


        //     // Update the timesheet data
        //     $timesheet->clockin_time = $clockinTime;
        //     $timesheet->clockout_time = $clockoutTime;
        //     $timesheet->job_id = $jobNumber;
        //     $timesheet->time_type_id = $timeType;
		// 	$timesheet->per_diem = $perDiem;

        //     $timesheet->save();

        //     return response()->json(['success' => true]);
        // } catch (\Exception $e) {
            
        //     return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        // }

        // dd($request->all());


        $request->validate([
            // 'rows' => 'required|array',
            'rows.*.id' => 'required|exists:timesheets,id',
            'rows.*.clockin_time' => 'required',
            'rows.*.clockout_time' => 'required',
            'rows.*.job_number' => 'required|exists:jobs,id',
        ]);


        // to manage errors for all rows, if error comes for a specific row then skip exucution and go to next, throws errors at then end
        // but save all of those rows without erros

        $errors = []; 

        try{

            // dd($request->all());

            foreach ($request->rows as $row) {

                // dd('dd0');
    
                $timesheet = Timesheet::findOrFail($row['id']);

                // Validate clockin and clockout
                $error = (new TimesheetService())->validateClockInOut($row['clockin_time'], $row['clockout_time']);
                if($error){
                    // $errors[] = $error;
                    $errors[] = [
                        'id' => $row['id'],
                        'message' => $error,
                    ];
                    // dd('dd1');
                    continue; // Skip further processing for this row
                }

                // Validate overlap using custom method
                $overlapError = $this->validateTimesheetOverlap(
                    $timesheet->user_id,
                    $row['clockin_time'],
                    $row['clockout_time'],
                    $timesheet->id // Exclude current timesheet ID from overlap check
                );

                if ($overlapError) {
                    // $errors[] = $overlapError;
                    $errors[] = [
                        'id' => $row['id'],
                        'message' => $overlapError,
                    ];
                    // dd('dd2');
                    continue; // Skip further processing for this row
                }

                // dd('dd3');

                $timesheetBeforeUpdate = $timesheet;

                // Update the timesheet data
                $timesheet->clockin_time = $row['clockin_time'];
                $timesheet->clockout_time = $row['clockout_time'];
                $timesheet->job_id = $row['job_number'];
                $timesheet->time_type_id = $row['time_type'];
                $timesheet->per_diem = $row['per_diem'];

                // dd($timesheet);
                $timesheet->save();

                TimesheetService::updatePdForAllEntriesOfTheDay($timesheetBeforeUpdate->user_id, $timesheetBeforeUpdate->clockin_time, $row['per_diem']);
    
            }

            // If there are any errors, return them
            if (!empty($errors)) {
                return response()->json(['success' => false, 'message' => '', 'errors' => $errors], 422);
            }
            
            // dd('dd4');

            return response()->json(['success' => true, 'message' => 'Timesheets updated successfully']);

        } catch (\Exception $e) {
            
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }


    }

    public function deleteTimesheet($id)
    {
        $user = Auth::user();
        $timesheet = Timesheet::findOrFail($id);

        // Add your role checks here
        if (($user->role_id == 2 || $user->role_id == 3 || $user->role_id == 5 || $user->role_id == 1) && !$timesheet->payroll_approval) {
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
                
                $error = $this->validateTimesheetOverlap(
                    $data['user_id'],
                    $data['clockin_time'],
                    $data['clockout_time'],
                );

                if($error){
                    throw ValidationException::withMessages([
                        'error' => $error,
                    ]);
                }
            }

            // If validation passes for all user_ids, proceed to save
            foreach ($userIds as $userId) {
                $timesheetData = array_merge($data, [
                    'user_id' => $userId,
                    // 'per_diem' => TimesheetService::checkIfPreviousEntriesOfTheDayHavePd($userId, $data['clockin_time'])
                    'per_diem' => !empty($data['per_diem']) ? $data['per_diem'] : TimesheetService::checkIfPreviousEntriesOfTheDayHavePd($userId, $data['clockin_time']),

                ]);

                // dd($timesheetData);

					
                $clockinTime = Carbon::parse($timesheetData['clockin_time']);
                $clockoutTime = Carbon::parse($timesheetData['clockout_time']);


                if ($clockinTime->isSameDay($clockoutTime)) {
                    // Same day, create one entry
                    Timesheet::create($timesheetData);
                }else{
                    // Different day, split into two entries
                    $firstEntry = $timesheetData;
                    $firstEntry['clockout_time'] = $clockinTime->copy()->endOfDay()->setTime(23, 59, 0)->toDateTimeString();
                    Timesheet::create($firstEntry);

                    $secondEntry = $timesheetData;
                    $secondEntry['clockin_time'] = $clockoutTime->copy()->startOfDay()->toDateTimeString(); // 00:00:00
                    $secondEntry['clockout_time'] = $clockoutTime->toDateTimeString();
                    Timesheet::create($secondEntry);
                }


            }

            return response()->json(['success' => true, 'message' => 'Timesheet created successfully', 200]);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
        
    }

    public function validateTimesheetOverlap($user_id, $clockin_time, $clockout_time, $exclude_id = null)
    {
        // dd($exclude_id);

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

            // dd($overlappingTimesheets);
            
            if ($overlappingTimesheets->isNotEmpty()) {
                // Prepare an array of overlapping timesheet IDs
                $overlappingIds = $overlappingTimesheets->pluck('id')->toArray();

                // Get the user name
                $userName = User::where('id', $user_id)->value('name');

                // throw ValidationException::withMessages([
                //     'error' => 'Overlapping timesheets for user ' .  $userName .  ' with these ids. ' . implode(',', $overlappingIds),
                // ]);

                // Return the error message instead of throwing it
                return 'Overlapping timesheets for user ' . $userName . ' with these ids: ' . implode(',', $overlappingIds);
            }
    
            // return $overlappingTimesheets;

            return null; // No overlap

        } catch(\Exception $e) {
            throw $e;
        }
    }


    



}
