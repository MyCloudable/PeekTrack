<?php
namespace App\Services\Clock;

use App\Models\Job;

class DepartService {
    
    public function getAllJobs()
    {
        return Job::where('status', 'In progress')->select('id', 'job_number')->get();
    }

    public function trackTimeTravel($data)
    {
        dd($data);
    }
}