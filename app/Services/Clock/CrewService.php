<?php

namespace App\Services\Clock;

use Carbon\Carbon;
use App\Models\Crew;
use App\Models\User;
use App\Models\CrewType;
use Illuminate\Support\Facades\DB;

class CrewService {

    public function index()
    {
        $role = auth()->user()->role_id;

        $query =  Crew::with(['superintendent:id,name,email', 'createdBy:id,name,email', 
                    'modifiedBy:id,name,email', 'crewType:id,name,value'])
                    ->when(($role !== 1 && $role !== 2), function ($query, $role) {
                        $query->where('superintendentId', auth()->id());
                    });

        if($role == 1){
            $query->withTrashed();
        }
                    
                    
        return $query->get();
    }

    public function create()
    {
        return [
            'users' => $this->getUsers(),
            'crewTypes' => $this->getCrewTypes()
        ];
    }

    public function store(array $data): Crew
    {
        return Crew::create($this->mergeDataBeforeSaveUpdate($data));
    }

    public function show($crew) // get crew members against a crew
    {
        return User::whereIn('id', $crew->crew_members)->select('name', 'email')->get();
    }

    public function edit()
    {
        return [
            'users' => $this->getUsers(),
            'crewTypes' => $this->getCrewTypes()
        ];
    }

    public function update(array $data, Crew $crew): bool
    {
        return $crew->update($this->mergeDataBeforeSaveUpdate($data, true));
    }

    public function destroy(Crew $crew)
    {
        $crew->delete();
    }

    public function getUsers()
    {
        return User::select('id', 'email', 'name', 'role_id', 'active', DB::raw("name as text"))->where('active', 1)->get();
    }
    public function getCrewTypes()
    {
        return CrewType::select('id', 'name', 'value', DB::raw("name as text"))->get();
    }

    private function mergeDataBeforeSaveUpdate(array $data, $isUpdate = false)
    {


        // save empty array if none crew members coming
        if(!isset($data['crew_members'])){
            $data['crew_members'] = [];
        } 
            

        (!$isUpdate) ? $data['last_verified_date'] = NULL : '';
        $data['created_by'] = auth()->id(); 
        $data['modified_by'] = auth()->id();

        return $data;
    }

}