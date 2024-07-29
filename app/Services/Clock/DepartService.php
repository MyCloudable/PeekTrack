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
                $this->createTimesheetForEveryDepartClick($travelTime, 'depart_for_job');
                break;

            case 'arrive_for_job':
                $data['departForm']['time_type_id'] = $timeType->id;
                $travelTime = $this->updateTravelTime($data['departForm']);
                $this->createTimesheetForEveryDepartClick($travelTime, 'arrive_for_job');
                break;

            case 'depart_for_office':
                $data['departForm']['jobId'] = Job::where('job_number', '9-99-9998')->first()->id;
                $data['departForm']['time_type_id'] = $timeType->id;
                $travelTime = $this->createTravelTime($data['departForm']);
                $this->createTimesheetForEveryDepartClick($travelTime, 'depart_for_office');
                break;

            case 'arrive_for_office':
                $travelTime = $this->updateTravelTime($data['departForm']);
                $this->createTimesheetForEveryDepartClick($travelTime, 'arrive_for_office');
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
        $travelTime = TravelTime::create([
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

        // $this->createTimesheetForEveryDepartClick($travelTime, 'create');

        return $travelTime;
    }

    private function updateTravelTime($data)
    {
        TravelTime::where('id', $data['travelTimeId'])->update([
            'arrive' => Carbon::now(),
            'modified_by' =>  auth()->id()
        ]);

        $travelTime = TravelTime::where('id', $data['travelTimeId'])->with('job')->first();

        // $this->createTimesheetForEveryDepartClick($travelTime, 'update');

        return $travelTime;
    }

    public function calculateIndirectTime($data, $isClockout = false)
    {
        $crew = Crew::find($data['crewId']); 
        $time = TravelTime::where('crew_id', $crew->id)->where('created_at', '>=', $crew->last_verified_date)
        ->orderBy('id', 'desc')->first();

        if($time){ // in case if they clock out without any mobilization

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

    public function createTimesheetForEveryDepartClick($travelTime, $type)
    {

            $crew = Crew::find($travelTime->crew_id);

            $crewMembers = $crew->crew_members;
            $crewMembersArray = $crewMembers;
            array_push($crewMembersArray, $crew->superintendentId);

            $clockin_time = '';
            $time_type_id = $travelTime->time_type_id;


            // only get ids for those crew members who are not clocked out yet so travel time entry create only for them
            $activeCrewMembersArray = Timesheet::where('crew_id', $crew->id)
                                    ->where('created_at', '>=', $crew->last_verified_date)
                                    ->whereIn('user_id', $crewMembersArray)
                                    ->whereNull('clockout_time')
                                    ->pluck('user_id')
                                    ->toArray(); 


            if($type == 'depart_for_job' || $type == 'depart_for_office'){
                $clockin_time = $travelTime->depart;
            }
            if($type == 'arrive_for_job' || $type == 'arrive_for_office'){
                $clockin_time = $travelTime->arrive;
            }



            if($type == 'arrive_for_job'){
                $timeType = TimeType::where('name', 'Production')->first();
                $time_type_id = $timeType->id;
            }
            if($type == 'arrive_for_office'){
                $timeType = TimeType::where('name', 'Shop')->first();
                $time_type_id = $timeType->id;
            }


            Timesheet::where('crew_id', $crew->id)
                    ->where('created_at', '>=', $crew->last_verified_date)
                    ->whereIn('user_id', $crewMembersArray)
                    ->whereNull('clockout_time') 
                    ->update([
                        'clockout_time' => $clockin_time,
                        'modified_by' => auth()->id(),
                    ]);


        // foreach ($crewMembersArray as $member) {
        //     $timesheet = Timesheet::create([
        //         'crew_id' => $crew->id,
        //         'crew_type_id' => $crew->crew_type_id,
        //         'user_id' => $member,
        //         'clockin_time' => $clockin_time,
        //         'job_id' => $travelTime->job_id,
        //         'time_type_id' => $time_type_id,
        //         'created_by' => auth()->id(),
        //         'modified_by' => auth()->id(),
        //     ]);
                
        // }


        

                            
            foreach ($activeCrewMembersArray as $member) {
                
                $timesheet = Timesheet::create([
                    'crew_id' => $crew->id,
                    'crew_type_id' => $crew->crew_type_id,
                    'user_id' => $member,
                    'clockin_time' => $clockin_time,
                    'job_id' => $travelTime->job_id,
                    'time_type_id' => $time_type_id,
                    'created_by' => auth()->id(),
                    'modified_by' => auth()->id(),
                ]);
                
        }

        

    }
}