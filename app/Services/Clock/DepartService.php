<?php
namespace App\Services\Clock;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\TravelTime;

class DepartService {
    
    public function getAllJobs()
    {
        return Job::where('status', 'In progress')->select('id', 'job_number AS text')->get();
    }

    public function trackTravelTime($data)
    {
        switch ($data['departForm']['type']) {
            case 'depart_for_job':
                $travelTime = $this->createTravelTime($data['departForm']);
                break;

            case 'arrive_for_job':
                $travelTime = $this->updateTravelTime($data['departForm']);
                break;

            case 'depart_for_office':
                $travelTime = $this->createTravelTime($data['departForm']);
                break;

            case 'arrive_for_office':
                $travelTime = $this->updateTravelTime($data['departForm']);
                break;
            
            default:
                return false;
                break;
        }

        return $travelTime;
    }

    private function createTravelTime($data)
    { 
        return TravelTime::create([
                'crew_id' => $data['crewId'],
                'job_id' => ($data['type'] == 'depart_for_office') ? NULL : $data['jobId'] ,
                'type' => $data['type'],
                'depart' => Carbon::now(),
                'created_by' => auth()->id(),
                'modified_by' =>  auth()->id()
            ]);
    }

    private function updateTravelTime($data)
    {
        TravelTime::where('id', $data['travelTimeId'])->update([
            'arrive' => Carbon::now(),
            'modified_by' =>  auth()->id()
        ]);

        return TravelTime::where('id', $data['travelTimeId'])->with('job')->first();
    }
}