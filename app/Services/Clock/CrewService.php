<?php

namespace App\Services\Clock;

use Carbon\Carbon;
use App\Models\Crew;
use App\Models\User;

class CrewService {

    public function index()
    {
        $role = auth()->user()->role_id;

        return Crew::with(['superintendent:id,name,email', 'createdBy:id,name,email', 
                    'modifiedBy:id,name,email'])
                    ->when(($role !== 1 && $role !== 2), function ($query, $role) {
                        $query->where('superintendentId', auth()->id());
                    })
                    ->get();
    }

    public function create()
    {
        return $this->getUsers();
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
        return $this->getUsers();
    }

    public function update(array $data, Crew $crew): bool
    {
        return $crew->update($this->mergeDataBeforeSaveUpdate($data));
    }

    public function destroy(Crew $crew)
    {
        $crew->delete();
    }

    public function getUsers()
    {
        return User::select('id', 'email', 'name')->get();
    }

    private function mergeDataBeforeSaveUpdate(array $data)
    {
        // $data['last_verified_date'] = Carbon::now(); 
        $data['last_verified_date'] = NULL; 
        $data['created_by'] = auth()->id(); 
        $data['modified_by'] = auth()->id();
        return $data;
    }

}