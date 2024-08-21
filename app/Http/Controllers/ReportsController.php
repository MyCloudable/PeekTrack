<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index($jobnumber)
    {
        // Log the job number to ensure it is correct
        \Log::info('Job Number: ' . $jobnumber);

        // Pass the jobnumber directly to the view
        return view('reports.index', compact('jobnumber'));
    }
}
