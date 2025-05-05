<?php

namespace App\Http\Controllers\Scheduling;

use Carbon\Carbon;
use App\Models\User;
use App\Models\OverflowItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SchedulingController extends Controller
{
    public function index()
    {
        return view('scheduling.index');
    }

    public function getManagers()
    {
        $managers = User::where('role_id', 7)->select('id', 'name')->get();
        $loggedInManagerId = auth()->user()->id;

        return response()->json([
            'managers' => $managers,
            'logged_in_manager_id' => $loggedInManagerId
        ]);
    }

    public function getTasksAndSuperintendents(Request $request)
    {
        $managerId = $request->query('manager_id');

        $manager = User::where('id', $managerId)->select('id', 'name', 'location')->first();
        if (!$manager) {
            return response()->json(['error' => 'Manager not found'], 404);
        }

        // Superintendents under the selected manager
        $superintendents = User::where('role_id', 3)
            ->where('manager_id', $managerId)
            ->select('id', 'name', 'location')
            ->get();

        // Prepend the manager as a superIntendent so they can assign to themselves
        $superintendents = $superintendents
        ->prepend($manager)
        ->unique('id')   // remove duplicate if the manager was also a superintendent
        ->values();

        
        // Map users.location to correct branch descriptions
        $branchMapping = [
            'Columbus' => range(1, 10),
            'Cartersville' => [11],
            'Locust Grove' => [12],
            'Remerton' => [20],
            'Byron' => range(30, 39),
            'Cleveland' => range(50, 59),
            'Columbia' => [40],
            'Summerville' => [60],
            'Spartanburg' => [70],
            'Richmond Hill' => [80],
        ];

        // Find matching branch descriptions based on manager's location
        $matchingBranchDescriptions = [];
        foreach ($branchMapping as $branchName => $locationRange) {
            if (in_array($manager->location, $locationRange)) {
                $matchingBranchDescriptions[] = $branchName;
            }
        }

        
        // Fetch the correct `branch_id` from the `branch` table
        $branchIds = DB::table('branch')
        ->whereIn('description', $matchingBranchDescriptions)
        ->pluck('id');
        
        // dd($branchIds);

        // Fetch overflow items that belong to those branches
        $overflowItems = DB::table('overflow_items')
            ->join('jobs', 'overflow_items.job_id', '=', 'jobs.id')
            ->join('crew_types', 'overflow_items.crew_type_id', '=', 'crew_types.id')
            ->join('branch', 'overflow_items.branch_id', '=', 'branch.id')
            // ->whereIn('branch.description', $branchDescriptions) // Filter by branch description
            ->whereIn('overflow_items.branch_id', $branchIds)
            ->whereNull('overflow_items.completion_date')
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
            ->orderBy('overflow_items.timeout_date', 'ASC') // Ensures dates are in ascending order
            ->get();

        // Separate assigned and unassigned tasks
        $assignedTasks = $overflowItems->whereNotNull('superintendent_id')->groupBy('superintendent_id');
        $unassignedTasks = $overflowItems->whereNull('superintendent_id')->values();

        // dd($unassignedTasks);

        // Attach assigned tasks to each superintendent
        $superintendents = $superintendents->map(function ($superintendent) use ($assignedTasks) {
            // $superintendent->tasks = $assignedTasks->get($superintendent->id, collect())->values();
            $superintendent->tasks = $assignedTasks->get($superintendent->id, collect())->sortBy('task_order')->values();
            return $superintendent;
        });

        return response()->json([
            'superintendents' => $superintendents,
            // 'overflowItems' => $overflowItems
            'overflowItems' => $unassignedTasks
        ]);
    }

    public function updateTaskAssignment(Request $request)
    {
        $taskId = $request->input('task_id');
        $superintendentId = $request->input('superintendent_id');
        $action = $request->input('action');

        if ($action === 'assign') {
            // Get the next order number for the new task
            $nextOrder = DB::table('overflow_items')
                ->where('superintendent_id', $superintendentId)
                ->max('task_order') + 1;

            // Assign the task to the superintendent and set the order
            DB::table('overflow_items')
                ->where('id', $taskId)
                ->update([
                    'superintendent_id' => $superintendentId,
                    'task_order' => $nextOrder,
                ]);
        } elseif ($action === 'unassign') {
            // Remove task from superintendent
            DB::table('overflow_items')
                ->where('id', $taskId)
                ->update([
                    'superintendent_id' => null,
                    'task_order' => 0, // Reset task order
                ]);

            // Reorder remaining tasks
            $tasks = DB::table('overflow_items')
                ->where('superintendent_id', $superintendentId)
                ->orderBy('task_order', 'asc')
                ->get();

            foreach ($tasks as $index => $task) {
                DB::table('overflow_items')
                    ->where('id', $task->id)
                    ->update(['task_order' => $index + 1]);
            }
        }

        return response()->json(['message' => 'Task assignment updated successfully']);
    }

    public function updateTaskOrder(Request $request)
    {
        $superintendentId = $request->input('superintendent_id');
        $tasks = $request->input('tasks');

        foreach ($tasks as $task) {
            DB::table('overflow_items')
                ->where('id', $task['task_id'])
                ->update(['task_order' => $task['task_order']]);
        }

        return response()->json(['message' => 'Task order updated successfully']);
    }

    public function completeTask(Request $request)
    {
        $taskId = $request->input('task_id');
        $note = $request->input('completion_note');
        $userId = auth()->id();

        DB::table('overflow_items')
            ->where('id', $taskId)
            ->update([
                'completion_date' => Carbon::now(),
                'complete_user_id' => $userId
            ]);

        if ($note) {
            DB::table('overflow_notes')->insert([
                'job_id' => DB::table('overflow_items')->where('id', $taskId)->value('job_id'),
                'overflow_item_id' => $taskId,
                'user_id' => $userId,
                'note' => $note,
                'created_at' => Carbon::now(),
            ]);
        }

        return response()->json(['message' => 'Task marked as completed']);
    }



}
