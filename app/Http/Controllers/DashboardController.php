<?php

namespace App\Http\Controllers;
use App\Models\Job;
use App\Models\Jobentry;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;

class DashboardController extends Controller
{
    public function index(){
		$job = Job::where('status', '=', 'In progress')->orderBy('completion_date', 'asc')->get();
		$unsubmitCards = Jobentry::where('submitted', '=', 0)->where( 'name', '=', Auth::user()->name)->get();
		$rejectedJobcards = Jobentry::where('approved', '=', 2)->where( 'name', '=', Auth::user()->name)->get();
				$Jobcards = Jobentry::where( 'name', '=', Auth::user()->name)->get();
		$deadDate = date('d/m/Y', strtotime("+30 days"));
        return view('dashboard.index', compact('job','deadDate','rejectedJobcards','unsubmitCards','Jobcards'));


    }
}
