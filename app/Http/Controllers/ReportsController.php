<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
		
		$jobnumbers = \DB::table('jobentries')
			->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobentries.job_number')
			->where('jobentries.approved', 1)
			->groupBy("jobentries.job_number")
			->get();

        
        // Pass the jobnumber directly to the view
        return view('reports.index', compact('jobnumbers'));
    }

        public function jobsummary($jobnumber)
    {
        $status = \DB::table('jobs')
			->select('status')
			->where('job_number', $jobnumber)
			->get();

        // Pass the jobnumber directly to the view
        return view('reports.jobsummary', compact('jobnumber','status'));
    }
	
public function payrollSummary(Request $request)
{
    $startDate = $request->input('date1');
    $endDate = $request->input('date2');

    // Pass dates to the view
    return view('reports.payrollsummary', compact('startDate', 'endDate'));
}




}
