<?php

namespace App\Http\Controllers\Clock;

use App\Models\Crew;
use Illuminate\Http\Request;
use App\Services\Clock\CrewService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clock\StoreCrewRequest;

class CrewsController extends Controller
{
    private CrewService $crewService;

    public function __construct(CrewService $crewService)
    {
        $this->crewService = $crewService;
    }

    public function index()
    {   
        return view('clock.crews.index')->with('crews', $this->crewService->index());
    }

    public function create()
    {
        return view('clock.crews.create')->with('users', $this->crewService->create());
    }

    public function store(StoreCrewRequest $request)
    {   
        $this->crewService->store($request->validated());
        return redirect()->route('crews.index')->with('message',"Crew have been created successfully");
    }

    public function show(Crew $crew)
    {
        return response()->json($this->crewService->show($crew), 200);
    }

    public function edit(Crew $crew)
    {
        $crew = Crew::where('id', $crew->id)->first();
        return view('clock.crews.edit')->with(['crew'=> $crew, 'users' => $this->crewService->edit()]);
    }

    public function update(StoreCrewRequest $request, Crew $crew)
    {   
        $this->crewService->update($request->validated(), $crew);
        return redirect()->route('crews.index')->with('message',"Crew have been updated successfully");
    }
    
    public function destroy(Crew $crew)
    {
        $this->crewService->destroy($crew);
        return back()->with('message', 'Crew have been deleted successfully');
    }
}
