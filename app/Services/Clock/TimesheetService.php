<?php
namespace App\Services\Clock;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\Crew;
use App\Models\User;
use App\Models\CrewType;
use App\Models\TimeType;
use App\Models\Timesheet;
use App\Models\TravelTime;
use Illuminate\Support\Facades\DB;
use App\Services\Clock\CrewService;
use Illuminate\Support\Facades\Log;
use App\Services\Clock\DepartService;
use Illuminate\Validation\ValidationException;

class TimesheetService {
    
    public function getCrewMembers()
    {   
        $crew = Crew::where('superintendentId', auth()->id())
                    // ->where('modified_by', auth()->id())
                    ->select('id', 'superintendentId', 'last_verified_date', 'crew_members', 
                    'is_ready_for_verification', 'updated_at', 'crew_type_id')
                    ->first();

        $crewMembersArray = $this->getCrewMembersArray($crew->crew_members, $crew->superintendentId);

        $timesheet = ($crew->last_verified_date) ? 
            Timesheet::where('crew_id', $crew->id)
            ->whereIn('user_id', $crewMembersArray)->where('created_at', '>=', $crew->last_verified_date)
            ->select('timesheets.*', 
            DB::raw('IF(clockout_time, TIMESTAMPDIFF(minute,clockin_time,clockout_time), TIMESTAMPDIFF(minute,clockin_time,NOW()))as total_time'),
            )
            ->get() :
            '';

        return $this->checkStatus($crew, $crewMembersArray, $timesheet);

        
                
    }

    private function getCrewMembersArray($crewMembers, $superintendentId)
    {
        $crewMembersArray = $crewMembers;
        array_push($crewMembersArray, $superintendentId);
        return $crewMembersArray;
    }

    private function checkStatus($crew, $crewMembersArray, $timesheet)
    {

        $isAlreadyVerified = false;
        $isAlreadyClockedin = false;
        $isAlreadyClockedout = false;
        $crewId = $crew->id;
        $crewMembers;
        $timesheet = $timesheet ? $timesheet : [];
        $travelTime = '';

        if($crew->last_verified_date){ // crew is updated by superintendent after created by admin
            if($timesheet->isNotEmpty()){
                $ifNullClockout = false;
                $timesheet->each(function ($item, $key) use (&$ifNullClockout){
                    if(!$item->clockout_time){
                        $ifNullClockout = true;
                        return false;
                    }
                });
                
                if($ifNullClockout){ // crew members are not clocked out yet
                    $isAlreadyVerified = true;
                    $isAlreadyClockedin = true;
                    $crewMembers = User::whereIn('id', $crewMembersArray)->select('id', 'name', 'email')->get();
                    $travelTime = TravelTime::where('crew_id', $crew->id)->where('created_at', '>=', $crew->last_verified_date)
                                    ->orderBy('id', 'desc')
                                    ->first();
                }else{  // all crew members are clocked out
                    if($crew->is_ready_for_verification){   // ready for verification, please verify crews
                        $crewMembers = User::whereIn('id', $crew->crew_members)->select('id', 'name', 'email')->get();
                    }else{  // not ready for verification, show current timesheet
                        $isAlreadyVerified = true;
                        $isAlreadyClockedin = true;
                        $isAlreadyClockedout = true;
                        $crewMembers = User::whereIn('id', $crewMembersArray)->select('id', 'name', 'email')->get();
                    }
                }
            }else{  // crew is verified by superintendent , not clockedin yet
                $isAlreadyVerified = true;
                $crewMembers = User::whereIn('id', $crewMembersArray)->select('id', 'name', 'email')->get();
            }
        }else{ // crew just created by admin and superintendent did't do anything yet
            $crewMembers = User::whereIn('id', $crew->crew_members)->select('id', 'name', 'email')->get();
        }

        //get status outline
        $crewType = CrewType::find($crew->crew_type_id)->name;
        $currentNode = '';
        if($isAlreadyVerified)
            $currentNode = 'Verified';
        
        if($isAlreadyClockedin)
            $currentNode = 'Clocked In';

        if($isAlreadyClockedout)
            $currentNode = 'Clocked Out';

        if($travelTime){
            $lastTravelEntry = $travelTime;
            // dd($lastTravelEntry);
            if($lastTravelEntry->type =='depart_for_job'){
                if($lastTravelEntry->arrive)
                    $currentNode = 'Arrived at Job # ' . Job::where('id', $lastTravelEntry->job_id)->first()->job_number;
                else
                $currentNode = 'Departing to Job # ' . Job::where('id', $lastTravelEntry->job_id)->first()->job_number;
            }
            if($lastTravelEntry->type =='depart_for_office'){
                if($lastTravelEntry->arrive)
                    $currentNode = 'Arrive at office';
                else
                $currentNode = 'Departing to Office';
            }
        }

        $status = $crewType . ': ' . $currentNode;


        //end get status outline

        return [
            'isAlreadyVerified' =>$isAlreadyVerified,
            'isAlreadyClockedin' =>$isAlreadyClockedin,
            'isAlreadyClockedout' => $isAlreadyClockedout,
            'crewId' => $crewId,
            'crewMembers' => $crewMembers,
            'timesheet' => $timesheet,
            'travelTime' => $travelTime,
            'status' => $status,
            'crewTypes' => CrewType::select('id', 'name', 'value')->get(),
            'crewTypeId' => $crew->crew_type_id,
        ];
    }

    public function verifyCrewMembers($data)
    {
        return Crew::where('id', $data['crewId'])->where('superintendentId', auth()->id())
                ->update([
                    'crew_members' => $data['crewMembers'],
                    'last_verified_date' => Carbon::now(),
                    'modified_by' => auth()->id(),
                    'is_ready_for_verification' => 0,
                    'crew_type_id' => $data['crewTypeId']
                ]);
    }

    public function clockinoutCrewMembers($data)
    {

        if($data['isMenual']){
            $this->menualClock($data);
        }else{
            $this->allClock($data);
        }

        return true;
        
    }

    private function allClock($data)
    {
        $crew = Crew::find($data['crewId']);

        $crewMembersArray = $this->getCrewMembersArray($crew->crew_members, $crew->superintendentId);

        if($data['type'] == 'clockin'){
            foreach ($crewMembersArray as $member) {
                $timesheet = Timesheet::create([
                    'crew_id' => $crew->id,
                    'crew_type_id' => $crew->crew_type_id,
                    'user_id' => $member,
                    'clockin_time' => Carbon::now(),
                    'job_id' => Job::where('job_number', '9-99-9998')->first()->id,
                    'time_type_id' => TimeType::where('name', 'Shop')->first()->id,
                    'created_by' => auth()->id(),
                    'modified_by' => auth()->id(),
                ]);
                
            }
        }

        if($data['type'] == 'clockout'){

            //set a session for already clocked out members before end of day.
            session(['alreadyClockedOutMembers' => Timesheet::where('crew_id', $data['crewId'])
                ->whereNotNull('clockout_time')
                ->where('created_at', '>=', $crew->last_verified_date)
                ->select('id', 'crew_id', 'user_id', 'clockout_time')
                ->get()
            ]);


            
            // dd(session('alreadyClockedOutMembers'));

            foreach ($crewMembersArray as $member) {
                $timesheet = Timesheet::where('crew_id', $data['crewId'])
                ->whereNull('clockout_time')
                ->where('created_at', '>=', $crew->last_verified_date)
                ->update([
                    'clockout_time' => Carbon::now(),
                    'modified_by' => auth()->id(),
                ]);
                
            }

            // uncomment after implementation
            (new DepartService())->calculateIndirectTime([
                'crewId' => $crew->id,
            ], true);

            

            // commend this for now, as we are going to save all entries with every click rather than at end
            // $this->saveAllDepartEntries($crew, $crewMembersArray);

        }
    }

    private function menualClock($data)
    {
            $timesheet = Timesheet::where('id', $data['timesheetId'])->first();
    
            if($data['type'] == 'clockin'){
                $timesheet->clockin_time = $data['time'];
                $this->validateClockInOut($data['time'], $timesheet->clockout_time);
            }
            
    
            if($data['type'] == 'clockout'){
                $timesheet->clockout_time = $data['time'];
                $this->validateClockInOut($timesheet->clockin_time, $data['time']);
            }
            
            $timesheet->save();
    }

    public function getAllUsers()
    {
        return (new CrewService())->getUsers();
    }

    public function addNewCrewMember($data)
    {
        $crew = Crew::where('id', $data['crewId'])->first();

        $isAlreadyClockedin = Timesheet::where('crew_id', $data['crewId'])->where('user_id', $data['createNewCrewForm']['crew_member_id'])
        ->where('created_at', '>=', $crew->last_verified_date)->first();

        if(!$isAlreadyClockedin){ // only create crew member and clock in if its not clocked in

            $this->validateClockInOut($data['createNewCrewForm']['clockin_time'], null);

            $crewMembers = $this->getCrewMembersArray($crew->crew_members, $data['createNewCrewForm']['crew_member_id']);;
            $crew->crew_members = $crewMembers;
            $crew->save();

            $timesheet = Timesheet::create([
                'crew_id' => $crew->id,
                'crew_type_id' => $crew->crew_type_id,
                'user_id' => $data['createNewCrewForm']['crew_member_id'],
                'clockin_time' => $data['createNewCrewForm']['clockin_time'],
                'job_id' => Job::where('job_number', '9-99-9998')->first()->id,
                'time_type_id' => TimeType::where('name', 'Shop')->first()->id,
                'created_by' => auth()->id(),
                'modified_by' => auth()->id(),
            ]);
        }

        return true;

    }

    public function deleteCrewMember($data)
    {
        $crew = Crew::where('id', $data['crewId'])->first();

        if($crew->superintendentId !== $data['crewMemberId']){
            $crewMembers = $crew->crew_members;
            unset( $crewMembers[array_search( $data['crewMemberId'], $crewMembers )] );
            $crew->crew_members = array_values($crewMembers);
            $crew->save();
    
            Timesheet::where('crew_id', $data['crewId'])->where('user_id', $data['crewMemberId'])
            ->where('created_at', '>=', $crew->last_verified_date)->delete();
        }

        return true;
    }

    public function hfPerDiem($data)
    {   
        $has_array = is_array($data['timesheetId']);

        Timesheet::
        // where('id', $data['timesheetId'])
        when(!$has_array, function ($query, $has_array) use($data) {
            $query->where('id', $data['timesheetId']);
        })
        ->when($has_array, function ($query, $has_array) use($data) {
            $query->whereIn('id', $data['timesheetId']);
        })
        ->update([
            'per_diem' => $data['perDiem']
        ]);

        return true;
    }

    public function readyForVerification($data)
    {
        Crew::where('id', $data['crewId'])->update([
            'is_ready_for_verification' => 1
        ]);

    }

    // save all depart entries for every crew member in timesheets table. so we can export csv's
    private function saveAllDepartEntries($crew, $crewcrewMembersArray)
    {

        $timesheets = Timesheet::where('crew_id', $crew->id)
                ->where('created_at', '>=', $crew->last_verified_date)->get();
        
        $travelTimes = TravelTime::where('crew_id', $crew->id)->where('created_at', '>=', $crew->last_verified_date)->get();

        $alreadyExistingTimesheet = [];
        $timesheets->each(function ($item, $key) use(&$alreadyExistingTimesheet) {
            $alreadyExistingTimesheet[$item->user_id] = $item->clockout_time;
        });

        // dd($alreadyExistingTimesheet);

        $newlyCreatedTimesheetIds = [];

        foreach ($travelTimes as $key => $travelTime) {
            // if($key==0 && $travelTime->type == 'indirect_time'){
            if($travelTime->type == 'indirect_time'){

                if($key == 0){ // if first indirect_time entry
                    //set clockout for already existing timesheets entries same as depart time (first time)
                    Timesheet::where('crew_id', $crew->id)
                    ->where('created_at', '>=', $crew->last_verified_date)
                    ->where('clockout_time', '>', $travelTime->arrive) // update only if clockout after first depart
                    ->update([
                        'clockout_time' => $travelTime->arrive,
                        'modified_by' => auth()->id(),
                    ]);
                }else{ // if last indirect_time entry
                    Timesheet::whereIn('id', $newlyCreatedTimesheetIds)
                    ->update([
                        'clockout_time' => $travelTime->arrive
                    ]);
                }
                
            }else{

                // if($key!==0 && $travelTime->type == 'indirect_time'){ // if this is last indirect_time entry from travel_times table then just update clockout for previous entries
                    

                //     break;;
                // }

                // check if some crew member clockout before depart for next job after completing depart for previous job
                // foreach ($alreadyExistingTimesheet as $key => $value) {
                    
                // }



                // add clockout_time for previous entries (created for Arrive button with time_type_id = NULL)
                Timesheet::whereIn('id', $newlyCreatedTimesheetIds)
                ->update([
                    'clockout_time' => $travelTime->depart
                ]);

                foreach ($alreadyExistingTimesheet as $key => $value) {
                    if($value >= $travelTime->depart){ // if crew member not clocked out at the time of this depart entry
                        $timesheet = Timesheet::create([
                            'crew_id' => $crew->id,
                            'crew_type_id' => $crew->crew_type_id,
                            'user_id' => $key,
                            'clockin_time' => $travelTime->depart,
                            'clockout_time' => $travelTime->arrive,
                            'job_id' => $travelTime->job_id,
                            'time_type_id' => $travelTime->time_type_id,
                            'created_by' => auth()->id(),
                            'modified_by' => auth()->id(),
                        ]);

                    }
                }


                $newlyCreatedTimesheetIds = [];

                foreach ($alreadyExistingTimesheet as $key => $value) { //foreach loop for arrive button clicked
                    if($value >= $travelTime->depart){ // if crew member not clocked out at the time of this depart entry
                        $timesheet = Timesheet::create([
                            'crew_id' => $crew->id,
                            'crew_type_id' => $crew->crew_type_id,
                            'user_id' => $key,
                            'clockin_time' => $travelTime->arrive,
                            'job_id' => $travelTime->job_id,
                            'time_type_id' => ($travelTime->type == 'depart_for_office') ? TimeType::where('name', 'Shop')->first()->id : NULL,
                            'created_by' => auth()->id(),
                            'modified_by' => auth()->id(),
                        ]);

                        $newlyCreatedTimesheetIds[] = $timesheet->id;
                    }

                }


                



            }
        }


        //  set original clock out if some crew member clockout before end of day
        foreach(session('alreadyClockedOutMembers') as $timesheet){
            Timesheet::where('crew_id', $crew->id)
                    ->where('created_at', '>=', $crew->last_verified_date)
                    ->where('user_id', $timesheet->user_id)
                    ->latest('id')->first()   
                    ->update([
                        'clockout_time' => $timesheet->clockout_time,
                        'modified_by' => auth()->id(),
                    ]);
        }

        session()->forget('alreadyClockedOutMembers');
        
    }

    public function weatherEntry($data)
    {
        try{
            $crew = Crew::find($data['crewId']);
            $crewMembersArray = $this->getCrewMembersArray($crew->crew_members, $crew->superintendentId);
            foreach ($crewMembersArray as $member) {
                $timesheet = Timesheet::create([
                    'crew_id' => $crew->id,
                    'crew_type_id' => $crew->crew_type_id,
                    'user_id' => $member,
                    'clockin_time' => Carbon::now(),
                    'clockout_time' => Carbon::now()->addMinute(),
                    'job_id' => Job::where('job_number', '9-99-9998')->first()->id,
                    'time_type_id' => TimeType::where('name', 'Weather')->first()->id,
                    'created_by' => auth()->id(),
                    'modified_by' => auth()->id(),
                ]);
                
            }
            return response()->json(['success' => true, 'message' => 'Weather Record created successfully.']);
        } catch(\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function validateClockInOut($clockin, $clockout)
    {
        // Parse dates if they are not null
        $clockin = $clockin ? Carbon::parse($clockin) : null;
        $clockout = $clockout ? Carbon::parse($clockout) : null;
        $now = Carbon::now();

        // If both clockin and clockout are provided, validate them
        if ($clockin && $clockout) {
            // Validate that clockout is not less than clockin
            if ($clockout->lessThan($clockin)) {
                // dd('1');
                throw ValidationException::withMessages([
                    'clockout' => 'Clockout time should not be less than Clockin time.',
                ]);
            }

            // Validate that clockin is not greater than clockout
            if ($clockin->greaterThan($clockout)) {
                throw ValidationException::withMessages([
                    'clockin' => 'Clockin time should not be greater than Clockout time.',
                ]);
            }
        }

        // Validate that clockin is not greater than current time
        if ($clockin && $now->lessThan($clockin)) {
            throw ValidationException::withMessages([
                'clockin' => 'Clockin time should not be greater than the current time.',
            ]);
        }

        // If no validation errors, return true or perform further actions
        return true;

    }
}