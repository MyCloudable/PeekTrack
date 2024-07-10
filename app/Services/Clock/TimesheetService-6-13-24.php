<?php
namespace App\Services\Clock;

use Carbon\Carbon;
use App\Models\Job;
use App\Models\Crew;
use App\Models\User;
use App\Models\CrewType;
use App\Models\Timesheet;
use App\Models\TravelTime;
use Illuminate\Support\Facades\DB;
use App\Services\Clock\CrewService;
use App\Services\Clock\DepartService;

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
            $currentNode = 'Clocked in';

        if($isAlreadyClockedout)
            $currentNode = 'Clocked out';

        if($travelTime){
            $lastTravelEntry = $travelTime;
            if($lastTravelEntry->type =='depart_for_job'){
                if($lastTravelEntry->arrive)
                    $currentNode = 'Arrive at job # ' . Job::where('id', $lastTravelEntry->job_id)->first()->job_number;
                else
                $currentNode = 'Depart for job # ' . Job::where('id', $lastTravelEntry->job_id)->first()->job_number;
            }
            if($lastTravelEntry->type =='depart_for_office'){
                if($lastTravelEntry->arrive)
                    $currentNode = 'Arrive at office';
                else
                $currentNode = 'Depart for office';
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
            'status' => $status
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
                    'created_by' => auth()->id(),
                    'modified_by' => auth()->id(),
                ]);
                
            }
        }

        if($data['type'] == 'clockout'){
            foreach ($crewMembersArray as $member) {
                $timesheet = Timesheet::where('crew_id', $data['crewId'])
                ->whereNull('clockout_time')
                ->where('created_at', '>=', $crew->last_verified_date)
                ->update([
                    'clockout_time' => Carbon::now(),
                    'modified_by' => auth()->id(),
                ]);
                
            }

            (new DepartService())->calculateIndirectTime([
                'crewId' => $crew->id,
            ], true);
        }
    }

    private function menualClock($data)
    {
        $timesheet = Timesheet::where('id', $data['timesheetId'])->first();

        if($data['type'] == 'clockin')
            $timesheet->clockin_time = $data['time'];
        

        if($data['type'] == 'clockout')
            $timesheet->clockout_time = $data['time'];
        
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
            $crewMembers = $this->getCrewMembersArray($crew->crew_members, $data['createNewCrewForm']['crew_member_id']);;
            $crew->crew_members = $crewMembers;
            $crew->save();

            $timesheet = Timesheet::create([
                'crew_id' => $crew->id,
                'crew_type_id' => $crew->crew_type_id,
                'user_id' => $data['createNewCrewForm']['crew_member_id'],
                'clockin_time' => $data['createNewCrewForm']['clockin_time'],
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
}