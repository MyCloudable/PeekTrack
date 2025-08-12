<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
			 // Fetch unique location names from the `locations` table
    $locations = \DB::table('locations')->select('name')->distinct()->orderBy('name')->pluck('name');

    // Fetch unique branch names from the `branch` table
    $branches = \DB::table('jobs')->distinct()->whereNotNull('branch')->orderBy('branch')->pluck('branch');
	// Fetch unique material names from the `branch` table
	 $materials = \DB::table('material')
        ->distinct()
        ->orderBy('description')
        ->pluck('description');
    // Retrieve input values from the request
		$jobnumbers = \DB::table('jobentries')
			->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobentries.job_number')
			->where('jobentries.approved', 1)
			->groupBy("jobentries.job_number")
			->get();

        
        // Pass the jobnumber directly to the view
        return view('reports.index', compact('jobnumbers','branches','locations','materials'));
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

public function archiveSummary(Request $request)
{
    $startDate = $request->input('date1');
    $endDate = $request->input('date2');

    // Pass dates to the view
    return view('reports.archivesummary', compact('startDate', 'endDate'));
}

public function overflowItemsReport(Request $request)
{
    $startDate = $request->input('date1');
    $endDate = $request->input('date2');

    // Pass dates to the view
    return view('reports.overflowitems', compact('startDate', 'endDate'));
}

public function materialUsage(Request $request)
{
	
    // Retrieve input values from the request
    $location = $request->input('location');
    $branch = $request->input('branch');
    $materialName = $request->input('material_name');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    // Pass variables to the processing view
    return view('reports.materialusage', compact('location', 'branch', 'materialName', 'startDate', 'endDate'));
}


public function deptSummary(Request $request)
{
    $startDate = $request->input('date1');
    $endDate = $request->input('date2');

    // Pass dates to the view
    return view('reports.deptreport', compact('startDate', 'endDate'));
}

public function weopdSummary(Request $request)
{
    $startDate = $request->input('date1');
    $endDate = $request->input('date2');

    // Pass dates to the view
    return view('reports.weopdsummary', compact('startDate', 'endDate'));
}


}
