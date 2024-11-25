<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Jobentry;
use App\Models\User;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use DB;

class DashboardController extends Controller
{
    public function index()
    {
        $job = Job::where('status', '=', 'In progress')->orderBy('completion_date', 'asc')->get();
        $unsubmitCards = Jobentry::where('submitted', '=', 0)->where('name', '=', Auth::user()->name)->get();
        $rejectedJobcards = Jobentry::where('approved', '=', 2)->where('name', '=', Auth::user()->name)->get();
		$estimatingCards = Jobentry::where('approved', '=', 4)->get();
        $Jobcards = Jobentry::where('name', '=', Auth::user()->name)->get();
 $userLocation = auth()->user()->location; // Get the logged-in user's location
 
        // Fetch the most recent crew status for each superintendent and their location
$crews = Timesheet::select(
        'crews.superintendentId', 
        'users.name as superintendent_name', 
        'locations.name as user_location', 
        'time_types.name as time_type', 
        'time_types.value as time_value', 
        DB::raw('MAX(timesheets.clockin_time) as last_clockin_time'),
        'jobs.job_number',
        'locations.name as location_group',
        'jobs.branch'
    )
    ->join('crews', 'timesheets.crew_id', '=', 'crews.id')
    ->join('time_types', 'timesheets.time_type_id', '=', 'time_types.id')
    ->join('users', 'crews.superintendentId', '=', 'users.id')
    ->leftJoin('jobs', 'jobs.id', '=', 'timesheets.job_id')
    ->leftJoin('locations', 'users.location', '=', 'locations.id') // Assuming users table has location_id
    ->whereNull('timesheets.clockout_time') // Only consider those who are clocked in
    ->groupBy(
        'crews.superintendentId', 
        'users.name', 
        'user_location', 
        'time_types.name', 
        'time_types.value',
        'jobs.job_number',
        'location_group',
        'jobs.branch'
    )
    ->orderBy('location_group')
    ->orderBy('last_clockin_time', 'desc')
    ->orderBy('time_types.id', 'desc')
    ->get();



        $deadDate = date('d/m/Y', strtotime("+30 days"));

        return view('dashboard.index', compact('job', 'deadDate', 'rejectedJobcards', 'unsubmitCards', 'Jobcards', 'crews','userLocation','estimatingCards'));
    }
}
