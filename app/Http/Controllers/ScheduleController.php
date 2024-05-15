<?php

namespace App\Http\Controllers;
use App\Models\Job;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(){
		$jobs = Job::where('status','not like','closed')->orderBy('completion_date', 'desc')->get();
        return view('schedule.index', compact('jobs'));
    }
}