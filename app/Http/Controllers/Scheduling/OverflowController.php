<?php

namespace App\Http\Controllers\Scheduling;

use App\Models\OverflowItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class OverflowController extends Controller
{
    public function index($job_id)
    {
        $overflowItems = DB::table('overflow_items')
            ->join('jobs', 'overflow_items.job_id', '=', 'jobs.id')
            ->join('crew_types', 'overflow_items.crew_type_id', '=', 'crew_types.id')
            ->leftJoin('users', 'users.id', '=', 'overflow_items.superintendent_id') // Ensure NULLs are included
            ->join('branch', 'branch.id', '=', 'overflow_items.branch_id')
            ->where('overflow_items.job_id', '=', $job_id)
            ->whereNull('overflow_items.completion_date') // Only get rows where completion_date is NULL
            ->select(
                'jobs.job_number', 
                'crew_types.name as phase',
                'jobs.contractor',
                'overflow_items.traffic_shift',
                DB::raw('DATE_FORMAT(overflow_items.timein_date, "%Y-%m-%d") as timein_date'),
                DB::raw('DATE_FORMAT(overflow_items.timeout_date, "%Y-%m-%d") as timeout_date'),
                'overflow_items.notes',
                'users.name as superintendent',
                'overflow_items.job_id',
                'branch.description as branch',
                'overflow_items.id'
            )
            ->orderBy('overflow_items.timeout_date', 'ASC')
            ->get();

        return response()->json($overflowItems);
    }


    public function getBranches()
    {
        $branches = DB::table('branch')
            ->selectRaw('MIN(id) as id, MIN(department) as department, MIN(branch) as branch, description')
            ->groupBy('description')
            ->get();



        return response()->json($branches);
    }

    public function getJobPhases($job_id)
    {

        $job = DB::table('jobs')->where('id', $job_id)->select('job_number')->first();

        if (!$job) {
            return response()->json([]);
        }

        // Get unique phase prefixes from job_data
        $phases = DB::table('job_data')
        ->where('job_number', 'LIKE', $job->job_number)
        ->selectRaw('DISTINCT LEFT(phase, LOCATE("-", phase) - 1) as phase_prefix')
        ->pluck('phase_prefix')
        ->toArray();

        // dd($phases);

        // Get matching crew types
        $crewTypes = DB::table('crew_types')
        ->whereIn(DB::raw('LEFT(value, LOCATE("-", value) - 1)'), $phases)
        ->select('id', 'name')
        ->get();

        return response()->json($crewTypes);

    }

    public function getJobBranch($jobId)
    {
        // Fetch the job's branch value (e.g., "10-50 Cleveland")
        $job = DB::table('jobs')->where('id', $jobId)->select('branch')->first();

        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        // Extract only the branch code (before the first space)
        $branchCode = explode(' ', trim($job->branch))[0];

        // Find the corresponding branch description
        $branch = DB::table('branch')
            ->where('branch', $branchCode) // Match on the branch column
            ->select('id', 'description')
            ->first();

        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        return response()->json($branch);
    }




    public function store(Request $request)
    {
   

       // dd($request->all());

        $createdBy = auth()->id();

        foreach ($request->phases as $phase) {
            OverflowItem::create([
                'job_id' => $request->job_id,
                'crew_type_id' => $phase,
                'branch_id' => $request->branch_id, // Storing as plain integer
                'notes' => $request->notes,
                'traffic_shift' => $request->traffic_shift,
                'timein_date' => $request->timein_date,
                'timeout_date' => $request->timeout_date,
                'created_by' => $createdBy,
            ]);
        }

        return response()->json(['message' => 'Overflow items created successfully!'], 201);
    }

    public function show($id)
    {
        $item = DB::table('overflow_items')
            ->where('overflow_items.id', $id)
            ->select(
                'overflow_items.id',
                'overflow_items.job_id',
                'overflow_items.crew_type_id as phases',
                'overflow_items.branch_id',
                'overflow_items.notes',
                'overflow_items.timein_date',
                'overflow_items.timeout_date',
                'overflow_items.traffic_shift'
            )
            ->first();

        return response()->json($item);
    }



    public function update(Request $request, $id)
    {
        // Validate input
        $request->validate([
            'job_id' => 'required|integer',
            'phases' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!is_array($value) || count($value) !== 1) {
                        $fail("Only one phase can be selected when editing.");
                    }
                }
            ],
            'branch_id' => 'integer',
            'notes' => 'nullable|string|max:1000',
            'timein_date' => 'date',
            'timeout_date' => 'date',
            'traffic_shift' => 'boolean',
        ]);

        $updatedBy = auth()->id();

        // Find the existing overflow item
        $overflowItem = OverflowItem::findOrFail($id);

        // dd($request->phases[0]);

        // Update fields
        $overflowItem->update([
            'job_id' => $request->job_id,
            'crew_type_id' => $request->phases[0],
            'branch_id' => $request->branch_id, // Storing as integer
            'notes' => $request->notes,
            'traffic_shift' => $request->traffic_shift,
            'timein_date' => $request->timein_date,
            'timeout_date' => $request->timeout_date,
            'updated_by' => $updatedBy, // Track who updated
        ]);

        return response()->json(['message' => 'Overflow item updated successfully!'], 200);
    }
	
	public function approve(Request $request)
    {
        $request->validate([
            'overflow_id' => 'required|integer|exists:overflow_items,id',
            'decision' => 'required|in:approved,rejected',
            'note' => 'nullable|string|max:1000',
        ]);

        $overflowItem = OverflowItem::findOrFail($request->overflow_id);

        // Update the status based on approval/rejection
        if ($request->decision === 'approved') {
            $overflowItem->approved = 1; // Mark it as approved
              
        } else {
            $overflowItem->completion_date = NULL;
            $overflowItem->complete_user_id = NULL;		// Move it back
            $overflowItem->superintendent_id = NULL;
            $overflowItem->task_order = 0;
            $existingNotes = $overflowItem->notes ? $overflowItem->notes . "\n" : "";
            $overflowItem->notes = $existingNotes . "[Rejected: " . $request->note . "]";
        }

        $overflowItem->save();

    return redirect()->back()->with('success', 'Decision recorded successfully!');

    }

    public function destroy($id)
    {
        $item = OverflowItem::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Overflow item deleted successfully!'], 200);
    }

    public function copy($id)
    {
        $original = OverflowItem::findOrFail($id);

        $copy = $original->replicate();
        $copy->duplicated_from = $original->id;
        $copy->created_at = now();
        $copy->updated_at = now();
        $copy->save();

        // Fetch same structure as SchedulerController returns
        $newItem = DB::table('overflow_items')
            ->join('jobs', 'overflow_items.job_id', '=', 'jobs.id')
            ->join('crew_types', 'overflow_items.crew_type_id', '=', 'crew_types.id')
            ->join('branch', 'overflow_items.branch_id', '=', 'branch.id')
            ->where('overflow_items.id', $copy->id)
            ->select(
                'jobs.job_number',
                'crew_types.name as crew_type',
                'jobs.contractor',
                'overflow_items.id',
                'overflow_items.traffic_shift',
                'overflow_items.timein_date',
                'overflow_items.timeout_date',
                'overflow_items.notes',
                'overflow_items.superintendent_id',
                'overflow_items.task_order',
                'overflow_items.job_id',
                'overflow_items.duplicated_from',
            )
            ->first();

        return response()->json($newItem);
    }



}
