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
use App\Http\Controllers\Clock\TimesheetManagementConroller;

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
                    'last_verified_date' => Carbon::now()->format('Y-m-d H:i:s'),
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
        // dd($data);

        $crew = Crew::find($data['crewId']);

        $crewMembersArray = $this->getCrewMembersArray($crew->crew_members, $crew->superintendentId);

        if($data['type'] == 'clockin'){
            foreach ($crewMembersArray as $member) {
                $timesheet = Timesheet::create([
                    'crew_id' => $crew->id,
                    'crew_type_id' => $crew->crew_type_id,
                    'user_id' => $member,
                    'clockin_time' => $data['lateEntryTime'] ? $data['lateEntryTime'] : Carbon::now()->format('Y-m-d H:i:00'),
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
                // ->update([
                //     'clockout_time' => Carbon::now()->format('Y-m-d H:i:00'),
                //     'modified_by' => auth()->id(),
                // ]);
                ->first();

                // dd($timesheet);
                //check and handle midnight split
                if($timesheet){
                    $this->handleMidnightSplit($timesheet, $data['lateEntryTime'] ? $data['lateEntryTime'] : Carbon::now()->format('Y-m-d H:i:00'));
                }
                
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

            // $timesheetManagementConroller = new TimesheetManagementConroller();
    
            if($data['type'] == 'clockin'){

                $timesheet->clockin_time = $data['time'];
                $this->validateClockInOut($data['time'], $timesheet->clockout_time);

                $this->validateTimesheetOverlap(
                    $timesheet->user_id,
                    $data['time'],
                    $timesheet->clockout_time,
                    $timesheet->id // Exclude current timesheet ID from overlap check
                );

                

            }
            
    
            if($data['type'] == 'clockout'){

                $error = $this->validateClockInOut($timesheet->clockin_time, $data['time']);

                if($error){
                    throw ValidationException::withMessages([
                        'error' => $error,
                    ]);
                }

                // $timesheet->clockout_time = $data['time']; // no need to put here, will updated in handleMidnightSplit

                //check and handle midnight split
                $this->handleMidnightSplit($timesheet, $data['time']);
                // $this->handleMidnightSplit($timesheet, '2024-09-11 02:47:00');
                return;



                // $this->validateTimesheetOverlap(
                //     $timesheet->user_id,
                //     $timesheet->clockin_time,
                //     $data['time'],
                //     $timesheet->id // Exclude current timesheet ID from overlap check
                // );

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

            $error = $this->validateIFCrewMemberAlreadyLoggedInSomewhereElse($data['createNewCrewForm']['crew_member_id']);
            if($error){
                throw ValidationException::withMessages([
                    'error' => $error,
                ]);
            }

            $this->validateClockInOut($data['createNewCrewForm']['clockin_time'], null);

            $this->validateTimesheetOverlap(
                    $data['createNewCrewForm']['crew_member_id'],
                    $data['createNewCrewForm']['clockin_time'],
                    null,
                );

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

        if(!$has_array){
            $timesheetBeforeUpdate = Timesheet::find($data['timesheetId']);
        }

        Timesheet::
        when(!$has_array, function ($query, $has_array) use($data) {
            $query->where('id', $data['timesheetId']);
        })
        ->when($has_array, function ($query, $has_array) use($data) {
            $query->whereIn('id', $data['timesheetId']);
        })
        ->update([
            'per_diem' => $data['perDiem']
        ]);


        if(!$has_array){
            $this->updatePdForAllEntriesOfTheDay($timesheetBeforeUpdate->user_id, $timesheetBeforeUpdate->clockin_time, $data['perDiem']);
        }

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
                    'clockin_time' => Carbon::now()->format('Y-m-d H:i:00'),
                    'clockout_time' => Carbon::now()->format('Y-m-d H:i:00')->addMinute(),
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

        // If no validation errors
        return '';

    }

    public function validateTimesheetOverlap($user_id, $clockin_time, $clockout_time, $exclude_id = null)
    {
        try{

            // dd($clockout_time);

            $query = Timesheet::where('user_id', $user_id);
            

            // If clockout_time is provided, add these constraints
            if ($clockout_time) {
                $query->whereDate('clockin_time', '<=', date('Y-m-d', strtotime($clockout_time)))
                    ->whereDate('clockout_time', '>=', date('Y-m-d', strtotime($clockin_time)))
                    ->where(function ($query) use ($clockin_time, $clockout_time) {
                        $query->where(function ($query) use ($clockin_time, $clockout_time) {
                            $query->where('clockin_time', '<', $clockout_time)
                                    ->where(function ($query) use ($clockin_time) {
                                        $query->where('clockout_time', '>', $clockin_time)
                                            ->orWhereNull('clockout_time'); // Handle timesheets with null clockout_time
                                    });
                        })->orWhere(function ($query) use ($clockin_time, $clockout_time) {
                            $query->where('clockin_time', '>=', $clockin_time)
                                    ->where('clockout_time', '<=', $clockout_time);
                        });
                    });
            } else {
        
                // If clockout_time is null, only check overlap based on clockin_time
                $query->whereDate('clockout_time', '>=', date('Y-m-d', strtotime($clockin_time)))
                    ->where(function ($query) use ($clockin_time) {
                        $query->where(function ($query) use ($clockin_time) {
                            $query->where('clockin_time', '<=', $clockin_time)
                                ->where(function ($query) use ($clockin_time) {
                                    $query->where('clockout_time', '>', $clockin_time)
                                            ->orWhereNull('clockout_time'); // Handle timesheets with null clockout_time
                                });
                        })->orWhere('clockin_time', '>=', $clockin_time);
                    });
            }
    
            if ($exclude_id) {
                $query->where('id', '!=', $exclude_id);
            }
    
            $overlappingTimesheets = $query->get();
    
            if ($overlappingTimesheets->isNotEmpty()) {
                // Prepare an array of overlapping timesheet IDs
                $overlappingIds = $overlappingTimesheets->pluck('id')->toArray();
    
                // Get the user name
                $userName = User::where('id', $user_id)->value('name');
    
                throw ValidationException::withMessages([
                    // 'error' => 'Overlapping timesheets for user ' .  $userName .  ' with these ids. ' . implode(',', $overlappingIds),
                    'error' => 'Overlapping timesheets for user ' .  $userName .  ' with other entries',
                ]);
            }
    
            return $overlappingTimesheets;

        } catch(\Exception $e) {
            throw $e;
        }
    }

    public function validateIFCrewMemberAlreadyLoggedInSomewhereElse($user_id)
    {
        $timesheet = Timesheet::where('user_id', $user_id)->whereNull('clockout_time')->first();

        if($timesheet){
            return 'This Crew member is already logged in somewhere else';
        }

        return '';

    }



    public static function handleMidnightSplit(Timesheet $entry, $newClockout)
    {
        $previousClockin = Carbon::parse($entry->clockin_time);
        $newClockout = Carbon::parse($newClockout);

        // Check if the previous entry crosses midnight
        if ($previousClockin->format('Y-m-d') !== $newClockout->format('Y-m-d')) {
            // Split the previous entry into two

            // Update the original entry's clockout_time to midnight (end of the day)
            $entry->update([
                'clockout_time' => $previousClockin->copy()->setTime(23, 59, 59)->toDateTimeString(), // 23:59:00
                'modified_by' => auth()->id(),
            ]);

            // Create a new entry for the next day starting at midnight
            Timesheet::create([
                'crew_id' => $entry->crew_id,
                'crew_type_id' => $entry->crew_type_id,
                'user_id' => $entry->user_id,
                'clockin_time' => $newClockout->copy()->startOfDay()->toDateTimeString(), // 00:00:00 next day
                'clockout_time' => $newClockout->toDateTimeString(),
                'job_id' => $entry->job_id,
                'time_type_id' => $entry->time_type_id,
                'created_by' => auth()->id(),
                'modified_by' => auth()->id(),
                'per_diem' => TimesheetService::checkIfPreviousEntriesOfTheDayHavePd($entry->user_id, $entry->clockin_time),
            ]);
        } else {
            // If it doesn't cross midnight, just update the existing clockout_time
            $entry->update([
                'clockout_time' => $newClockout->toDateTimeString(),
                'modified_by' => auth()->id(),
            ]);
        }
    }


    public static function updatePdForAllEntriesOfTheDay($user_id, $clockin_time, $per_diem)
    {
        $clockinDate = Carbon::parse($clockin_time)->format('Y-m-d');
        Timesheet::where('user_id', $user_id)
        ->whereDate('clockin_time', $clockinDate) // Compare only the date part
        ->update([
            'per_diem' => $per_diem,
            'modified_by' => auth()->id(),
        ]);
    }

    public static function checkIfPreviousEntriesOfTheDayHavePd($user_id, $clockin_time)
    {
        $clockinDate = Carbon::parse($clockin_time)->format('Y-m-d');
        $entryWithPd = Timesheet::where('user_id', $user_id)
        ->whereDate('clockin_time', $clockinDate) // Compare only the date part
        ->whereNotNull('per_diem')
        ->first();

        if($entryWithPd)
        {
            return $entryWithPd->per_diem;
        }else{
            return NULL;
        }

    }


}