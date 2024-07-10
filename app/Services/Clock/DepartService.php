<?php
namespace App\Services\Clock;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\Crew;
use App\Models\TimeType;
use App\Models\Timesheet;
use App\Models\TravelTime;
use Illuminate\Support\Facades\DB;

class DepartService {
    
    public function getAllJobs()
    {
        return Job::where('status', 'In progress')
        ->where('job_number', '!=', '9-99-9998')
        ->select('id', DB::raw("CONCAT(job_number,' (',county, ')') as text"))->get();
    }

    public function trackTravelTime($data)
    {
        $timeType = TimeType::where('name', 'Mobilization')->first();

        switch ($data['departForm']['type']) {
            case 'depart_for_job':
                $data['departForm']['time_type_id'] = $timeType->id;
                $this->calculateIndirectTime($data['departForm']);
                $travelTime = $this->createTravelTime($data['departForm']);
                break;

            case 'arrive_for_job':
                $data['departForm']['time_type_id'] = $timeType->id;
                $travelTime = $this->updateTravelTime($data['departForm']);
                break;

            case 'depart_for_office':
                $data['departForm']['jobId'] = Job::where('job_number', '9-99-9998')->first()->id;
                $data['departForm']['time_type_id'] = $timeType->id;
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
        $crew = Crew::find($data['crewId']); 
        return TravelTime::create([
                'crew_id' => $crew->id,
                'job_id' => $data['jobId'] ,
                'crew_type_id' => $crew->crew_type_id,
                'time_type_id' => (isset($data['time_type_id'])) ? $data['time_type_id'] : NULL,
                'type' => $data['type'],
                'depart' => (isset($data['depart'])) ? $data['depart'] : Carbon::now(),
                'arrive' => (isset($data['arrive'])) ? $data['arrive'] : NULL,
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

    public function calculateIndirectTime($data, $isClockout = false)
    {
        $crew = Crew::find($data['crewId']); 
        $time = TravelTime::where('crew_id', $crew->id)->where('created_at', '>=', $crew->last_verified_date)
        ->orderBy('id', 'desc')->first();
        
        $record = $data;

        $record['jobId'] = Job::where('job_number', '9-99-9998')->first()->id;
        $record['type'] = 'indirect_time';
        $record['time_type_id'] = TimeType::where('name', 'Shop')->first()->id;
        $record['arrive'] = Carbon::now();

        if(!$time){ // when first time depart for job
            $record['depart'] = Timesheet::where('crew_id', $crew->id)->where('created_at', '>=', $crew->last_verified_date)->first()->clockin_time;
            $this->createTravelTime($record);
        }
        
        if($isClockout){ // going to clock out
            $record['depart'] = $time->arrive;
            $this->createTravelTime($record);
        }
            
    }
}