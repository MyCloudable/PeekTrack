<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ProductionReportExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class ProductionReportController extends Controller
{
    public function index()
    {
        $jobs = DB::table('jobs')
            ->select('job_number', 'description')
            ->orderBy('job_number')
            ->get();

        return view('reports.production.index', compact('jobs'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'job_number' => 'required',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        return Excel::download(
            new ProductionReportExport(
                $request->job_number,
                $request->start_date,
                $request->end_date
            ),
            'production_report_' . $request->job_number . '.xlsx'
        );
    }
}