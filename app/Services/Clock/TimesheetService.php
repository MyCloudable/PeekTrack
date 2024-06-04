<?php
namespace App\Services\Clock;

use Carbon\Carbon;
use App\Models\Crew;
use App\Models\User;
use App\Models\Timesheet;
use App\Models\TravelTime;
use Illuminate\Support\Facades\DB;
use App\Services\Clock\CrewService;

class TimesheetService {
    
    public function getCrewMembers()
    {   
        $crew = Crew::where('superintendentId', auth()->id())
                ->select('id', 'superintendentId', 'last_verified_date', 'crew_members', 'updated_at');

        
        $isAlreadyVerified = $crew->where('modified_by', auth()->id())->whereDate('last_verified_date', date('Y-m-d'))->first();
       
        if($isAlreadyVerified){ // crew members already verified by superintendent
            $crew = Crew::where('superintendentId', auth()->id())->select('id', 'superintendentId', 'crew_members')->first();
            
            $timesheet = DB::table('timesheets')->where('crew_id', $crew->id)->whereDate('created_at', date('Y-m-d'))
            ->select('timesheets.*', 
            DB::raw('TIMESTAMPDIFF(minute,clockin_time,NOW()) as total_time'),
            )
            ->get();
            $timesheetWithNullClockouttime = Timesheet::where('crew_id', $crew->id)->whereDate('created_at', date('Y-m-d'))->whereNull('clockout_time')->count();

            if($timesheet->isNotEmpty()){

                if($timesheetWithNullClockouttime == 0){
                    $isAlreadyClockedin = true;
                    $isAlreadyClockedout = true;
                }else{
                    $isAlreadyClockedin = true;
                    $isAlreadyClockedout = false;
                }
            }else{
                $isAlreadyClockedin = false;
                $isAlreadyClockedout = false;
            }

            $crewMembersArray = $crew->crew_members;
            array_push($crewMembersArray, $crew->superintendentId);
            
            return [
                'isAlreadyVerified' => true,
                'isAlreadyClockedin' => $isAlreadyClockedin,
                'isAlreadyClockedout' => $isAlreadyClockedout,
                'crewId' => $crew->id,
                'crewMembers' => User::whereIn('id', $crewMembersArray)->select('id', 'name', 'email')->get(),
                'timesheet' => $timesheet,
                'travelTime' => TravelTime::where('crew_id', $crew->id)->whereDate('created_at', date('Y-m-d'))->orderBy('id', 'desc')->first(),
            ];
        }else{  // needs to verify first
            $crew = Crew::where('superintendentId', auth()->id())->select('id', 'crew_members')->first();
            return [
                'isAlreadyVerified' => false,
                'isAlreadyClockedin' => false,
                'isAlreadyClockedout' => false,
                'crewId' => $crew->id,
                'crewMembers' => User::whereIn('id', $crew->crew_members)->select('id', 'name', 'email')->get(),
                'timesheet' => [],
                'travelTime' => ''
            ];
        }
    }

    public function verifyCrewMembers($data)
    {
        return Crew::where('id', $data['crewId'])->where('superintendentId', auth()->id())
                ->update([
                    'crew_members' => $data['crewMembers'],
                    'last_verified_date' => Carbon::now(),
                    'modified_by' => auth()->id(),
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

        $crewMembersArray = $crew->crew_members;
        array_push($crewMembersArray, $crew->superintendentId);

        if($data['type'] == 'clockin'){
            foreach ($crewMembersArray as $member) {
                $timesheet = Timesheet::create([
                    'crew_id' => $crew->id,
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
                ->whereDate('updated_at', date('Y-m-d'))
                ->update([
                    'clockout_time' => Carbon::now(),
                    'modified_by' => auth()->id(),
                ]);
                
            }
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
        $isAlreadyClockedin = Timesheet::where('crew_id', $data['crewId'])->where('user_id', $data['createNewCrewForm']['crew_member_id'])
        ->whereDate('clockin_time', date('Y-m-d'))->first();

        if(!$isAlreadyClockedin){ // only create crew and clock in if its not clocked in for today
            $crew = Crew::where('id', $data['crewId'])->first();
            $crewMembers = $crew->crew_members;
            array_push($crewMembers, $data['createNewCrewForm']['crew_member_id']);
            $crew->crew_members = $crewMembers;
            $crew->save();

            $timesheet = Timesheet::create([
                'crew_id' => $crew->id,
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
            ->whereDate('clockin_time', date('Y-m-d'))->delete();
        }

        return true;
    }

    public function hfPerDiem($data)
    {
        Timesheet::where('id', $data['timesheetId'])->update([
            'per_diem' => $data['perDiem']
        ]);

        return true;
    }
}