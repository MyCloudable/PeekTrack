<?php

namespace App\Http\Controllers;
use DB;
use App\Models\PO;
use Carbon\Carbon;
use App\Mail\Share;
use App\Models\Job;
use App\Models\File;
use App\Models\User;
use App\Models\Branch;
use App\Models\Jobentry;
use App\Models\JobNotes;
use App\Models\JobsData;
use App\Models\Material;
use App\Models\Equipment;
use App\Models\Production;
use App\Models\OverflowItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;
use App\Mail\JobCardRejectionNotification;
use App\Mail\Estimating;
use App\Mail\BillingNotification;

class JobsController extends Controller
{
	
    public function index(Request $request)
    {
        if($request->ajax()){
            $jobs = Job::where('status', '=', 'In progress')
            ->get();

            return DataTables::of($jobs)
            
            ->addColumn('action', function($row) {
                return '<a class="btn btn-warning" href="/jobs/'. $row->id . '/overview">Open</a>';
            })

            ->rawColumns(['action'])
            
            ->make(true);

        }else{
            $branchName = Branch::where('department', Auth::user()->location)->value('branch');
            $branchName = ($branchName) ? preg_replace('/^\s+|\s+$/', '', $branchName) : '';
            return view("jobs.index", compact("branchName"));
        }
        
		
        
    }
    public function estimating()
    {

			$jobentries = \DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobs.job_number', 'jobs.description','jobs.branch','jobentries.name','jobentries.submitted_on','jobentries.approved','jobentries.link','jobentries.workdate')
			->where('approved',4)
			->orderBy("jobentries.updated_at", "desc")
            ->get();
	
		

        return view("jobs.estimating", compact("jobentries"));
    }
	public function overflowapproval()
    {

$overflow = DB::table('overflow_items')
    ->join('jobs', 'overflow_items.job_id', '=', 'jobs.id')
    ->join('crew_types', 'overflow_items.crew_type_id', '=', 'crew_types.id')
    ->leftJoin('users', 'users.id', '=', 'overflow_items.superintendent_id')
    ->join('branch', 'branch.id', '=', 'overflow_items.branch_id') // Ensure NULLs for superintendent are included
    ->leftJoin('overflow_notes', 'overflow_notes.overflow_item_id', '=', 'overflow_items.id') // Ensure left join to include all overflow items
    ->where('overflow_items.approved', '=', '0')
    ->whereNotNull('overflow_items.completion_date') // Only get rows where completion_date is NOT NULL
    ->select(
        'overflow_items.id',
        'jobs.job_number', 
        'crew_types.name as phase',
        'jobs.contractor',
        'overflow_items.traffic_shift',
        'overflow_items.timein_date',
        'overflow_items.timeout_date',
        'overflow_items.completion_date',
        'overflow_items.notes',
        'users.name',
        'overflow_items.job_id',
        'branch.description',
        DB::raw("(SELECT GROUP_CONCAT(overflow_notes.note SEPARATOR ', ') 
                  FROM overflow_notes 
                  WHERE overflow_notes.overflow_item_id = overflow_items.id) AS notes_list")
    )
    ->groupBy(
        'overflow_items.id',
        'jobs.job_number', 
        'crew_types.name',
        'jobs.contractor',
        'overflow_items.traffic_shift',
        'overflow_items.timein_date',
        'overflow_items.timeout_date',
        'overflow_items.completion_date',
        'overflow_items.notes',
        'users.name',
        'overflow_items.job_id',
        'branch.description'
    )
    ->get();

	
		

        return view("jobs.approveoverflow", compact("overflow"));
    }

	public function globalreview()
    {

			$jobentries = \DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobs.job_number', 'jobs.description','jobs.branch','jobentries.name','jobentries.submitted_on','jobentries.approved','jobentries.link','jobentries.workdate')
            ->orderBy("jobentries.updated_at", "desc")
            ->get();
	
		

        return view("jobs.globalreview", compact("jobentries"));
    }
    public function review()
    {
		
		if(in_array(Auth::user()->id,array(72,1022,1529))){
			$branch = array('10-10 Columbus');
			        $jobentries = \DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobs.job_number', 'jobs.description','jobs.branch','jobentries.name','jobentries.submitted_on','jobentries.approved','jobentries.link','jobentries.workdate')
			->whereIn('jobs.branch', $branch)
            ->orderBy("jobentries.workdate", "asc")
            ->get();
		}
		elseif(in_array(Auth::user()->id,array(93))){
			
			$branch = array('10-20 Remerton', '10-30 Byron', '10-80 Richmond Hill');
			        $jobentries = \DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobs.job_number', 'jobs.description','jobs.branch','jobentries.name','jobentries.submitted_on','jobentries.approved','jobentries.link','jobentries.workdate')
			->whereIn('jobs.branch', $branch)
            ->orderBy("jobentries.workdate", "asc")
            ->get();
		}
		elseif(in_array(Auth::user()->id,array(2795))){
			
						$branch = array('10-50 Cleveland', '10-11 Cartersville', '10-12 Locust Grove');
						        $jobentries = \DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobs.job_number', 'jobs.description','jobs.branch','jobentries.name','jobentries.submitted_on','jobentries.approved','jobentries.link','jobentries.workdate')
			->whereIn('jobs.branch', $branch)
            ->orderBy("jobentries.workdate", "asc")
            ->get();
		}
		elseif(in_array(Auth::user()->id,array(8138,8169))){
			
						$branch = array('10-60 Summerville', '10-40 Columbia', '10-70 Spartanburg', '10-65 Conway');
						        $jobentries = \DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobs.job_number', 'jobs.description','jobs.branch','jobentries.name','jobentries.submitted_on','jobentries.approved','jobentries.link','jobentries.workdate')
			->whereIn('jobs.branch', $branch)
            ->orderBy("jobentries.workdate", "asc")
            ->get();
		}
		else{

			$jobentries = \DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
			->select('jobs.job_number', 'jobs.description','jobs.branch','jobentries.name','jobentries.submitted_on','jobentries.approved','jobentries.link','jobentries.workdate')
            ->orderBy("jobentries.workdate", "asc")
            ->get();
		}
		

        return view("jobs.review", compact("jobentries"));
    }

	public function history(Request $request)
	{
        if($request->ajax()){
			$jobentries = DB::table('jobentries')
            ->join("jobs", "jobs.job_number", "=", "jobentries.job_number")
            ->leftJoin("users", "users.id", "=", "jobentries.billing_approval_by")
			->select(
                'jobs.job_number', 
                'jobs.description',
                'jobs.branch',
                'jobentries.name', 
                'jobentries.id', 
                'jobentries.billing_approval', 
                'users.name as billing_approval_by', 
                DB::raw("IF(jobentries.submitted = 1, 'Submitted', 'Not submitted') as submission_status"),
                'jobentries.submitted_on',
                DB::raw("CASE 
                            WHEN jobentries.approved = 1 THEN 'Approved' 
                            WHEN jobentries.approved = 2 THEN 'Rejected' 
                            ELSE 'Pending' 
                        END as approval_status"),
                'jobentries.link', 
                'jobentries.workdate',
                DB::raw("CASE 
                    WHEN jobentries.approved = 1 THEN jobentries.ApprovedBy 
                    ELSE '' 
                 END as approved_by")
            )
            ->get();

            return DataTables::of($jobentries)
            
            ->addColumn('action', function($row) {
                return '<a class="btn btn-warning" href="/jobs/'. $row->link . '/view">View</a>';
            })

            ->rawColumns(['action'])
            
            ->make(true);

        }else{
            $authuser = Auth::user();
            return view('jobs.history', compact('authuser'));
        }

		
	}

    public function billingApproval(Request $request)
    {
        $jobentry = Jobentry::find($request->id);
        $jobentry->billing_approval = $request->approved;
        $jobentry->billing_approval_by = Auth::user()->id;
        $jobentry->billing_approval_at = Carbon::now();
        $jobentry->save();

        return response()->json(['success' => 'Billing Approval updated '], 200);


    }

    public function jobreview($id)
    {
		$jobnumbers = Job::where('status', '=', 'In progress')->orderBy("job_number", "asc")->get();
        $jobcard = Jobentry::where("link", $id)->get();
        $jobnotes = JobNotes::where("link", $id)->get();
		$pos = PO::where("link", $id)->get();
        $jobnum = $jobcard[0]->job_number;
        $jobitems = JobsData::where("job_number", $jobnum)->get();
        $job = Job::where("job_number", $jobnum)->get();
        $files = File::where("job_number", $jobnum)
            ->where("doctype", 0)
            ->orderBy("type", "desc")
            ->get();
        $jobfiles = File::where("link", $id)
            ->where("doctype", 1)
            ->orderBy("type", "desc")
            ->get();
$production = Production::where("link", $id)->get();
		$proditems = JobsData::where("job_number", $jobnum)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), "<", 10000)->where(\DB::raw("RIGHT(phase, 5)"), ">", 0)->orderBy("phase", "asc")->get();
		$materialitems = JobsData::where("job_number", $jobnum)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), ">", 10000)->orderBy("phase", "asc")->get();
        $material = Material::where("link", $id)->get();
		$equipmentitems = JobsData::where("job_number", $jobnum)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), "=", 10000)->orderBy("phase", "asc")->get();
        $equipment = Equipment::where("link", $id)->get();
        return view(
            "jobs.jobreview",
            compact(
                "jobcard",
                "production",
                "material",
                "equipment",
                "job",
                "jobitems",
                "files",
                "jobfiles",
                "jobnotes",
				"pos",
				"jobnumbers",
				"proditems",
				"equipmentitems",
				"materialitems"
            ),
        );
    }

    public function overview($id)
    {
        $jobinfo = Job::where("id", $id)->get();
        $jobnum = $jobinfo[0]->job_number;
        $jobitems = JobsData::select(\DB::raw("LEFT(phase, 2) as phase"))
            ->where("job_number", $jobnum)
            ->groupBy("phase")
            ->get();
        $files = File::where("job_number", $jobnum)
            ->where("doctype", 0)
            ->orderBy("type", "desc")
            ->get();
        $jobentries = Jobentry::where("job_number", $jobnum)
            ->orderBy("submitted", "desc")
            ->get();
        $jobid = $id;
		
$overflowItems = DB::table('overflow_items')
    ->join('jobs', 'overflow_items.job_id', '=', 'jobs.id')
    ->join('crew_types', 'overflow_items.crew_type_id', '=', 'crew_types.id')
    ->leftJoin('users', 'users.id', '=', 'overflow_items.superintendent_id')
	->join('branch', 'branch.id', '=', 'overflow_items.branch_id')// Ensure NULLs for superintendent are included
    ->where('overflow_items.job_id', '=', $id)
    ->whereNull('overflow_items.completion_date') // Only get rows where completion_date is NULL
    ->select(
        'jobs.job_number', 
        'crew_types.name as phase',
        'jobs.contractor',
        'overflow_items.traffic_shift',
        'overflow_items.timein_date',
        'overflow_items.timeout_date',
        'overflow_items.notes',
        'users.name',
        'overflow_items.job_id',
		'branch.description'
		
    )
    ->get();




        return view(
            "jobs.overview",
            compact("jobinfo", "files", "jobentries", "jobid", "jobitems","overflowItems"),
        );
    }
	
	public function jobcardview($id)
    {
		
        $jobentries = Jobentry::where("userId", $id)
			->where("submitted", '0')
			->orWhere("approved", '2')
			->where("userId", $id)
			->OrderBy("approved", 'desc')
            ->get();
        return view(
            "jobs.jobcardview",
            compact("jobentries"),
        );
    }
	
		public function myjobcards($id)
    {
        $jobentries = Jobentry::where("userId", $id)
			->where("userId", $id)
			->OrderBy("approved", 'desc')
            ->get();
        return view(
            "jobs.myjobcards",
            compact("jobentries"),
        );
    }

    public function jobcard($id)
    {
		
        $jobcard = Jobentry::where("link", $id)->get();
        $jobnum = $jobcard[0]->job_number;
		$pos = PO::where("link", $id)->get();
        $jobnotes = JobNotes::where("link", $id)->get();
        $jobitems = JobsData::where("job_number", $jobnum)->where('unit_of_measure', '!=' , '%LS%')->orderBy("phase", "asc")->get();
        $jobinfo = Job::where("job_number", $jobnum)->get();
        $files = File::where("link", $id)
            ->where("doctype", 1)
            ->orderBy("type", "desc")
            ->get();
        $production = Production::where("link", $id)->get();
		$proditems = JobsData::where("job_number", $jobnum)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), "<", 10000)->where(\DB::raw("RIGHT(phase, 5)"), ">", 0)->orderBy("phase", "asc")->get();
		$materialitems = JobsData::where("job_number", $jobnum)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), ">", 10000)->orderBy("phase", "asc")->get();
        $material = Material::where("link", $id)->get();
		$equipmentitems = JobsData::where("job_number", $jobnum)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), "=", 10000)->orderBy("phase", "asc")->get();
        $equipment = Equipment::where("link", $id)->get();
        return view(
            "jobs.jobcard",
            compact(
                "jobcard",
                "production",
                "material",
                "equipment",
                "jobinfo",
                "jobitems",
                "files",
                "jobnotes",
				"pos",
				"proditems",
				"materialitems",
				"equipmentitems",
            ),
        );
    }
	
	public function view($id)
    {
        $jobcard = Jobentry::where("link", $id)->get();
        $jobnum = $jobcard[0]->job_number;
        $jobnotes = JobNotes::where("link", $id)->get();
        $jobitems = JobsData::where("job_number", $jobnum)->get();
        $jobinfo = Job::where("job_number", $jobnum)->get();
        $files = File::where("link", $id)
            ->where("doctype", 1)
            ->orderBy("type", "desc")
            ->get();
        $production = Production::where("link", $id)->get();
        $material = Material::where("link", $id)->get();
        $equipment = Equipment::where("link", $id)->get();
        return view(
            "jobs.view",
            compact(
                "jobcard",
                "production",
                "material",
                "equipment",
                "jobinfo",
                "jobitems",
                "files",
                "jobnotes",
            ),
        );
    }

    public function edit($id, $crewType)
    {
        $job = Job::find($id);
        $job_number = $job->job_number;
        $jobitems = JobsData::where("job_number", $job_number)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->orderBy("phase", "asc")->get();
		$proditems = JobsData::where("job_number", $job_number)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), "<", 10000)->where(\DB::raw("RIGHT(phase, 5)"), ">", 0)->orderBy("phase", "asc")->get();
        $production = JobsData::where("job_number", $job_number)
			->where('unit_of_measure', '!=' , 'LS')
			->where('unit_of_measure', '!=' , 'TXT')
            ->where(\DB::raw("RIGHT(phase, 5)"), "<", 10000)
			->where(\DB::raw("RIGHT(phase, 5)"), ">", 0)
            ->where(\DB::raw("LEFT(phase, 2)"), "=", $crewType)
            ->get();
		$materialitems = JobsData::where("job_number", $job_number)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), ">", 10000)->orderBy("phase", "asc")->get();
        $material = JobsData::where("job_number", $job_number)->where('unit_of_measure', '!=' , '%LS%')
            ->where(\DB::raw("RIGHT(phase, 5)"), ">", 10000)
            ->where(\DB::raw("LEFT(phase, 2)"), "=", $crewType)
            ->get();
			$equipmentitems = JobsData::where("job_number", $job_number)->where('unit_of_measure', '!=' , 'LS')->where('unit_of_measure', '!=' , 'TXT')->where(\DB::raw("RIGHT(phase, 5)"), "=", 10000)->orderBy("phase", "asc")->get();
        $equipment = JobsData::where("job_number", $job_number)
            ->where(\DB::raw("RIGHT(phase, 5)"), "=", 10000)
            ->where(\DB::raw("LEFT(phase, 2)"), "=", $crewType)
            ->get();
        return view(
            "jobs.edit",
            compact("job", "production", "material", "equipment", "jobitems","proditems","materialitems","equipmentitems"),
        );
    }

    public function update(Request $request, $id)
    {
        // Validate the form input
        $validatedData = $request->validate([
            "job_name" => "required|string|max:255",
            // Add validation rules for other attributes here
        ]);

        // Find the job record by ID
        $job = Job::findOrFail($id);

        // Update the job record with the validated data
        $job->update($validatedData);

        // Redirect to a success page or show the updated job details
        return redirect()
            ->route("jobs.show", $job->id)
            ->with("success", "Job updated successfully");
    }

    public function store(Request $request)
    {
        $uuid = Str::uuid()->toString();
        $jobid = $request->jobid;
        $jobEntry = new Jobentry();
        $jobEntry->link = $uuid;
        $jobEntry->job_number = $request->job_number;
        $jobEntry->workdate = $request->workdate;
        $jobEntry->userId = $request->userId;
        $jobEntry->name = $request->username;
        $jobEntry->save();
        if ($request->notes != "") {
            $JobNote = new JobNotes();
            $JobNote->link = $uuid;
            $JobNote->note_type = "JobCardNote";
            $JobNote->username = $request->username;
            $JobNote->note = $request->notes;
            $JobNote->save();
        }
        if (!is_null($request->phase)) {
            for ($i = 0; $i < count($request->phase); $i++) {
                if (isset($request->qty[$i])) {
                    $prod = new Production();
                    $prod->job_number = $request->job_number;
                    $prod->link = $uuid;
                    $prod->userId = $request->userId;
                    $prod->phase = $request->phase[$i];
                    $prod->description = $request->description[$i];
                    $prod->qty = $request->qty[$i];
                    $prod->unit_of_measure = $request->unit_of_measure[$i];
                    $prod->mark_mill = $request->mark_mill[$i];
                    $prod->road_name = $request->road_name[$i];
                    if (isset($request->phase_item_complete[$i])) {
                        $prod->phase_item_complete = 1;
                    } else {
                        $prod->phase_item_complete = 0;
                    }

                    $prod->surface_type = $request->surface_type[$i];

                    $prod->save();
                }
            }
        }
        if (!is_null($request->mphase)) {
            for ($i = 0; $i < count($request->mphase); $i++) {
                if (isset($request->mqty[$i])){
                    $mat = new Material();
                    $mat->job_number = $request->job_number;
                    $mat->link = $uuid;
                    $mat->userId = $request->userId;
                    $mat->phase = $request->mphase[$i];
                    $mat->description = $request->mdescription[$i];
                    $mat->qty = $request->mqty[$i];
                    $mat->unit_of_measure = $request->munit[$i];
                    $mat->supplier = $request->msupplier[$i];
                    $mat->batch = $request->mbatch[$i];
                    $mat->save();
                }
            }
        }
        if (!is_null($request->ephase)) {
			
            for ($i = 0; $i < count($request->ephase); $i++) {
			Log::info('Request for Equipment - '.$request->ephase[$i]);

                if (isset($request->ehours[$i])) {
                    $equip = new Equipment();
                    $equip->job_number = $request->job_number;
                    $equip->link = $uuid;
                    $equip->userId = $request->userId;
                    $equip->phase = $request->ephase[$i];
                    $equip->description = $request->edescription[$i];
                    $equip->truck = $request->etruck[$i];
                    $equip->hours = $request->ehours[$i];
                    $equip->save();
                }
            }
        }

        return redirect()
            ->route("jobs.jobcard", ["id" => $uuid])
            ->with("success", "Job entry saved successfully");
    }

    public function entryupdate(Request $request)
    {
		DB::enableQueryLog();
        $jobLink = $request->link;
        Jobentry::where("link", $jobLink)->update([
            "workdate" => $request->workdate,
        ]);

        if ($request->notes != "") {
            $JobNote = new JobNotes();
            $JobNote->link = $request->link;
            $JobNote->note_type = "JobCardNote";
            $JobNote->username = $request->user;
            $JobNote->note = $request->notes;
            $JobNote->save();
        }

        if ($request->review_notes != "") {
            $JobNote = new JobNotes();
            $JobNote->link = $request->link;
            $JobNote->note_type = "ReviewNote";
            $JobNote->username = $request->user;
            $JobNote->note = $request->review_notes;
            $JobNote->save();
        }
        
        if (!empty($request->phase)) {
			
            for ($i = 0; $i < count($request->phase); $i++) {
				if (isset($request->qty[$i]) && isset($request->pid[$i])) {
					
					$productionid = $request->pid[$i];
								
					
					
					
					if(Production::where("link", $jobLink)
						->where("id", $request->pid[$i])
						->count() > 0)
						{
							$complete = 0;
							$id = $request->pid[$i];
				// Check if checkbox was checked and set accordingly
							if (!empty($request->phase_item_complete) && array_key_exists($id, $request->phase_item_complete)) {
							$complete = 1;
							}	
				// Update the Production model based on the provided data
						$affectedRows = Production::where("link", $jobLink)
						->where("id", $productionid)
						->update([
                        "qty" => $request->qty[$i],
                        "mark_mill" => $request->mark_mill[$i],
                        "road_name" => $request->road_name[$i],
                        "phase_item_complete" => $complete,
                        "surface_type" => $request->surface_type[$i],
						]);

                // Check if the update was successful
							if ($affectedRows === false) {
							return redirect()
							->route("jobs.jobcard", ["id" => $request->link])
							->with("error", "Job entry updated Unsuccessfully");
							}
						}
					}
					else
					{

						if (isset($request->qty[$i])){
						$prod = new Production();
						$prod->job_number = $request->job_number;
						$prod->link = $jobLink;
						$prod->userId = $request->userId;
						$prod->phase = $request->phase[$i];
						$prod->description = $request->description[$i];
						$prod->qty = $request->qty[$i];
						$prod->unit_of_measure = $request->unit_of_measure[$i];
						$prod->mark_mill = $request->mark_mill[$i];
						$prod->road_name = $request->road_name[$i];
                        if (isset($request->phase_item_complete[$i])) {
                            $complete = 1;
                        } 
                        else {
                            $complete = 0;
                        }                   
                        $prod->phase_item_complete = $complete;
						$prod->surface_type = $request->surface_type[$i];
						$prod->save();
						}
					}
					Log::info(DB::getQueryLog());
					DB::flushQueryLog();
				
			}
        }
        if (!empty($request->mphase)) {
			
            for ($i = 0; $i < count($request->mphase); $i++) {
				if (isset($request->mqty[$i]) && isset($request->mid[$i])) {
				if(Material::where("link", $jobLink)->where("id", $request->mid[$i])->exists())
				{
					Material::where("link", $jobLink)
						->where("id", $request->mid[$i])
						->update([
							"qty" => $request->mqty[$i],
							"supplier" => $request->msupplier[$i],
							"batch" => $request->mbatch[$i],
						]);
				}}
				else{
if (isset($request->mqty[$i])){
				    $mat = new Material();
                    $mat->job_number = $request->job_number;
                    $mat->link = $jobLink;
                    $mat->userId = $request->userId;
                    $mat->phase = $request->mphase[$i];
                    $mat->description = $request->mdescription[$i];
                    $mat->qty = $request->mqty[$i];
                    $mat->unit_of_measure = $request->munit[$i];
                    $mat->supplier = $request->msupplier[$i];
                    $mat->batch = $request->mbatch[$i];
                    $mat->save();
}
				
				}
			
			}
		}
			

        if (!empty($request->ephase)) {
			
            for ($i = 0; $i < count($request->ephase); $i++) {
				
				
				if (isset($request->ehours[$i]) && isset($request->eid[$i]) && $request->ehours[$i] > 0) {
				if(Equipment::where("link", $jobLink)->where("id", $request->eid[$i])->exists())
				{
					Equipment::where("link", $jobLink)
						->where("id", $request->eid[$i])
						->update([
                        "hours" => $request->ehours[$i],
                        "truck" => $request->etruck[$i],
						]);
				}
				}
				else{
					if (isset($request->ehours[$i]) && isset($request->etruck[$i]) && $request->ehours[$i] > 0){
				    $equip = new Equipment();
                    $equip->job_number = $request->job_number;
                    $equip->link = $jobLink;
                    $equip->userId = $request->userId;
                    $equip->phase = $request->ephase[$i];
                    $equip->description = $request->edescription[$i];
                    $equip->truck = $request->etruck[$i];
                    $equip->hours = $request->ehours[$i];
                    $equip->save();
					}
					else{
						if (!isset($request->etruck[$i]) && $request->referrer == 0){
						return redirect()
                ->route("jobs.jobcard", ["id" => $request->link])
                ->with("errorentry", "You must select a truck type");
						}
						elseif (!isset($request->ehours[$i]) && $request->referrer == 0 || $request->ehours[$i] < 1 && $request->referrer == 0){
													return redirect()
                ->route("jobs.jobcard", ["id" => $request->link])
                ->with("errorentry", "You must have hours for added equipment.");
						}
												if (!isset($request->etruck[$i]) && $request->referrer == 1){
						return redirect()
                ->route("jobs.jobreview", ["id" => $request->link])
                ->with("errorentry", "You must select a truck type");
						}
						elseif (!isset($request->ehours[$i]) && $request->referrer == 1 || $request->ehours[$i] < 1 && $request->referrer == 1){
													return redirect()
                ->route("jobs.jobreview", ["id" => $request->link])
                ->with("errorentry", "You must have hours for added equipment.");
						}
					}
				}
			
			}
        }
        if ($request->referrer == 0) {
            return redirect()
                ->route("jobs.jobcard", ["id" => $request->link])
                ->with("successentry", "Job entry updated successfully");
        }

        if ($request->referrer == 1) {
            return redirect()
                ->route("jobs.jobreview", ["id" => $request->link])
                ->with("successentry", "Job entry updated successfully");
        }
    }

    public function submitjob(Request $request)
    {
        Jobentry::where("link", $request->link)->update([
            "submitted" => 1,
			"submitted_on" => date('Y-m-d'),
            "approved" => 3,
        ]);
		
		if ($request->role == 9){
		$JobNote = new JobNotes();
        $JobNote->link = $request->link;
        $JobNote->note_type = "JobCardNote";
        $JobNote->username = $request->user;
        $JobNote->note = $request->note;
        $JobNote->save();			
        return redirect()
            ->route("jobs.estimating")
            ->with("successentry", "Job card submitted successfully");
		}else{
		$JobNote = new JobNotes();
        $JobNote->link = $request->link;
        $JobNote->note_type = "JobCardNote";
        $JobNote->username = $request->user;
        $JobNote->note = "Jobcard submitted.";
        $JobNote->save();
		return redirect()
            ->route("jobs")
            ->with("successentry", "Job card submitted successfully");
		}
    }

    public function updatecard(Request $request)
    {
        Jobentry::where("link", $request->link)->update(["approved" => 1,
		"approvedBy" => $request->username,
		"approved_date" => date('Y-m-d'),
		]);
				$JobNote = new JobNotes();
        $JobNote->link = $request->link;
        $JobNote->note_type = "JobCardNote";
        $JobNote->username = $request->username;
        $JobNote->note = "Jobcard approved.";
        $JobNote->save();
        return redirect()
            ->route("jobs.review")
            ->with("successentry", "Job card approved");
    }

    public function opencard(Request $request)
    {
        Jobentry::where("link", $request->link)->update(["approved" => 3,
		"approvedBy" => $request->username,
		"approved_date" => date('Y-m-d'),
        "submitted" => 1,
		]);
		$JobNote = new JobNotes();
        $JobNote->link = $request->link;
        $JobNote->note_type = "JobCardNote";
        $JobNote->username = $request->username;
        $JobNote->note = "Jobcard reopened.";
        $JobNote->save();
		
        return redirect()
            ->route("jobs.review")
            ->with("successentry", "Job card re-opened");
    }

    public function rejectcard(Request $request)
    {
        Jobentry::where("link", $request->link)->update(["submitted" => 0]);
        Jobentry::where("link", $request->link)->update(["approved" => 2]);
        Jobentry::where("link", $request->link)->update(["approvedBy" => $request->username,]);
		$email = \DB::table('jobentries')
            ->join("users", "users.id", "=", "jobentries.userId")
			->select('users.email', 'users.name', 'jobentries.job_number')
            ->where('jobentries.userId', $request->userId)
			->where('jobentries.link', $request->link)
            ->get();
        $JobNote = new JobNotes();
        $JobNote->link = $request->link;
        $JobNote->note_type = "Rejection";
        $JobNote->username = $request->username;
        $JobNote->note = $request->note;
        $JobNote->save();
		$note = $request->note;
		$emailaddress = $email[0]->email;
		$name = $email[0]->name;
		$job_number = $email[0]->job_number;
		Mail::to($emailaddress)->send(new JobCardRejectionNotification($name, $job_number, $note));
        return redirect()
            ->route("jobs.review")
            ->with("successentry", "Job card rejected");
    }
	
	public function estqueue(Request $request)
    {
        Jobentry::where("link", $request->link)->update(["submitted" => 1]);
        Jobentry::where("link", $request->link)->update(["approved" => 4]);
        Jobentry::where("link", $request->link)->update(["approvedBy" => $request->username,]);
		$email = \DB::table('jobentries')
            ->join("users", "users.id", "=", "jobentries.userId")
			->select('users.email', 'users.name', 'jobentries.job_number')
            ->where('jobentries.userId', $request->userId)
			->where('jobentries.link', $request->link)
            ->get();
        $JobNote = new JobNotes();
        $JobNote->link = $request->link;
        $JobNote->note_type = "JobCardNote";
        $JobNote->username = $request->username;
        $JobNote->note = $request->note;
        $JobNote->save();
		$note = $request->note;
		$emailaddress = $email[0]->email;
		$name = $email[0]->name;
		$job_number = $email[0]->job_number;
		Mail::to('estimating@peeksafety.com')->send(new Estimating($name, $job_number, $note));
        return redirect()
            ->route("jobs.review")
            ->with("successentry", "Job card sent to estimating");
    }
	
	public function shareJobcard(Request $request)
	{
		$link = $request->link;
		$toemail = $request->email;
		$imageData = $request->image;
		$email = \DB::table('jobentries')
            ->join("users", "users.id", "=", "jobentries.userId")
			->select('users.email', 'jobentries.job_number')
			->where('jobentries.link', $request->link)
            ->get();
		$job_number = $email[0]->job_number;
		Mail::to($toemail)->send(new Share($link, $imageData, $job_number));
		
			
			
		
	}

    public function removeLineJBRP($link, $id, $ref)
    {
        Production::where("link", $link)
            ->where("id", $id)
            ->delete();
			if($ref == 0)
			{
			return redirect()
                ->route("jobs.jobcard", ["id" => $link])
                ->with("successentry", "Job entry updated successfully");
			}
			else{
            return redirect()
                ->route("jobs.jobreview", ["id" => $link])
                ->with("successentry", "Job entry updated successfully");
			}
	}
    public function removeLineJBRM($link, $id, $ref)
    {
        Material::where("link", $link)
            ->where("id", $id)
            ->delete();
			if($ref == 0)
			{
			return redirect()
                ->route("jobs.jobcard", ["id" => $link])
                ->with("successentry", "Job entry updated successfully");
			}
			else{
            return redirect()
                ->route("jobs.jobreview", ["id" => $link])
                ->with("successentry", "Job entry updated successfully");
			}
    }
    public function removeLineJBRE($link, $id, $ref)
    {
        Equipment::where("link", $link)
            ->where("id", $id)
            ->delete();
			if($ref == 0)
			{
			return redirect()
                ->route("jobs.jobcard", ["id" => $link])
                ->with("successentry", "Job entry updated successfully");
			}
			else{
            return redirect()
                ->route("jobs.jobreview", ["id" => $link])
                ->with("successentry", "Job entry updated successfully");
			}
    }
	
	public function removeJC($link, $id)
    {
        Production::where("link", $link)
            ->delete();
			
	
	

        Material::where("link", $link)
            ->delete();
		
   

        Equipment::where("link", $link)
            ->delete();
			
		Jobentry::where("link", $link)
            ->delete();
		
            return redirect()
                ->route("jobs.overview", ["id" => $id])
                ->with("successentry", "Job card deleted successfully");
				
			
    }
	
		public function changeJobNum(Request $request)
    {

		$link = $request->link;
		$job_number = $request->job_number;
		$newnumber = $request->jobnumber;
		
		Production::where("link", $request->link)->update(["job_number" => $newnumber,]);
	
		Material::where("link", $request->link)->update(["job_number" => $newnumber,]);
		
		Equipment::where("link", $request->link)->update(["job_number" => $newnumber,]);
			
		JobEntry::where("link", $request->link)->update(["job_number" => $newnumber,]);
		
		PO::where("link", $request->link)->update(["job_number" => $newnumber,]);
		
		File::where("job_number", $job_number)->where("doctype", '1')->update(["job_number" => $newnumber,]);
		
            return redirect()
                ->route("jobs.jobreview", ["id" => $link])
                ->with("successentry", "Job number changed successfully");
				
			
    }
	
	public function roadList(Request $request){
		$job = $request->jobnumber;
		$equipment = DB::select("CALL BillingEquipmentByDay($request->jobnumber)");
		$material = DB::select("CALL BillingMaterialByDay($request->jobnumber)");
		$production = DB::select("CALL BillingProductionByDay($request->jobnumber)");
		
		return view("jobs.roadListReport",compact("job", "production", "material", "equipment"),);
		
		
	}
	
	
    public function exportFile(Request $request)
    {

    if($request->check == 1){
    $twoDates = $request->daterange;
    $date1 = date('Y-m-d', strtotime(substr($twoDates,0,10)));
    $date2 = date('Y-m-d', strtotime(substr($twoDates,13,21)));

    $production = \DB::table('jobentries')
    ->join("production", "production.link", "=", "jobentries.link")
    ->select('production.phase','jobentries.workdate','jobentries.job_number','production.qty')
    ->whereBetween("jobentries.workdate", [$date1, $date2])->where('approved', '1')->where('qty','>', '0')->get();
    $links = \DB::table('jobentries')
    ->join('production', 'production.link', '=', 'jobentries.link')
    ->whereBetween('jobentries.workdate', [$date1, $date2])
    ->where('approved', '1')
    ->where('qty', '>', 0)
    ->pluck('production.link');

	// Step 2: Update the `billing_approval` column
	\DB::table('jobentries')
    ->whereIn('link', $links)
    ->update(['billing_approval' => 1]); 
    
    $csvFileName = 'production.txt';


    $headers = [
        'Content-Type' => 'text/csv',
        "Content-Description" => "File Transfer",
        "Cache-Control" => "public",
        'Content-Disposition' => 'attachment; filename="'.$date1."-".$date2."-".$csvFileName.'"'];

    $callback = function() use($production) {
    $handle = fopen('php://output', 'w');


    foreach ($production as $product) {
        fputcsv($handle, ["JCTMW", $product->job_number,"",$product->phase,"","2",date('m-d-Y', strtotime($product->workdate)),"",$product->qty]); // Add more fields as needed
    }

    fclose($handle);
    };
    return response()->stream($callback, 200, $headers);
    }
    

    if($request->check == 2){
        $twoDates = $request->daterange;
        $date1 = date('Y-m-d', strtotime(substr($twoDates,0,10)));
        $date2 = date('Y-m-d', strtotime(substr($twoDates,13,21)));
    
        $material = \DB::table('jobentries')
        ->join("material", "material.link", "=", "jobentries.link")
        ->select('material.phase','jobentries.workdate','jobentries.job_number','material.qty')
        ->whereBetween("jobentries.workdate", [$date1, $date2])->where('approved', '1')->where('qty','>', '0')->get();
                    
        $csvFileName = 'material.txt';
		
		$links = \DB::table('jobentries')
    ->join('material', 'material.link', '=', 'jobentries.link')
    ->whereBetween('jobentries.workdate', [$date1, $date2])
    ->where('approved', '1')
    ->where('qty', '>', 0)
    ->pluck('material.link');

	// Step 2: Update the `billing_approval` column
	\DB::table('jobentries')
    ->whereIn('link', $links)
    ->update(['billing_approval' => 1]);
    
        $headers = [
            'Content-Type' => 'text/csv',
            "Content-Description" => "File Transfer",
            "Cache-Control" => "public",
            'Content-Disposition' => 'attachment; filename="'.$date1."-".$date2."-".$csvFileName.'"'];
    
        $callback = function() use($material) {
        $handle = fopen('php://output', 'w');
    
    
        foreach ($material as $product) {
            fputcsv($handle, ["DC", $product->job_number,"",$product->phase,"M","2",date('m-d-Y', strtotime($product->workdate)),date('m-d-Y', strtotime($product->workdate)),"",$product->qty,"","","10-10-999","10-10-999","","",substr($product->phase,3,5)]); // Add more fields as needed
        }
    
        fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
        }
		
        if($request->check == 3){
            $twoDates = $request->daterange;
            $date1 = date('Y-m-d', strtotime(substr($twoDates,0,10)));
            $date2 = date('Y-m-d', strtotime(substr($twoDates,13,21)));
        
            $equipment = \DB::select('select 
    e.phase,
    je.workdate,
    je.job_number,
    e.hours,
    e.truck, 
    e.userid, 
    tc.cost 
from jobentries je
left join equipment e on e.link = je.link 
left join truckcost tc on tc.truck = e.truck
where je.workdate between ? and ? and je.approved = 1 and e.hours > 0', [$date1, $date2]);
                        
            $csvFileName = 'equipment.txt';
        
        
            $headers = [
                'Content-Type' => 'text/csv',
                "Content-Description" => "File Transfer",
                "Cache-Control" => "public",
                'Content-Disposition' => 'attachment; filename="'.$date1."-".$date2."-".$csvFileName.'"'];
        
            $callback = function() use($equipment) {
            $handle = fopen('php://output', 'w');
        
        
            foreach ($equipment as $product) {
                fputcsv($handle, ["R", $product->truck,"1",$product->hours,$product->cost,"",date('m-d-Y', strtotime($product->workdate)),"","","","","",date('m-d-Y', strtotime($product->workdate)),"10-10-999","10-10-999","",$product->job_number,"",$product->phase,"E",$product->userid]); // Add more fields as needed
            }
        
            fclose($handle);
            };
            return response()->stream($callback, 200, $headers);
            }
			
			if ($request->check == 4) {
    $twoDates = $request->daterange;
    $date1 = date('Y-m-d', strtotime(substr($twoDates, 0, 10)));
    $date2 = date('Y-m-d', strtotime(substr($twoDates, 13, 21)));

    try {
        // First query: Equipment
$equipment = \DB::select("
    SELECT 
        DATE_FORMAT(workdate, '%m-%d-%Y') AS workdate,
        user_id,
		crew_type,
        job_number,
        value,
        class,
        pay_rate,
        ROUND(SUM(hours), 2) AS hours
    FROM (
        SELECT 
            t.user_id,
			ct.value as crew_type,
            j.job_number,
            tt.value,
            u.class,
            u.pay_rate,
            -- Adjust clockin_time to 12:00 AM if it starts before Sunday 12:00 AM
            CASE 
                WHEN t.clockin_time < ? THEN CONCAT(DATE(?), ' 00:00:00')
                ELSE t.clockin_time 
            END AS clockin_time,
            -- Adjust clockout_time to 11:59 PM if it ends after Saturday 11:59 PM
            CASE 
                WHEN t.clockout_time > DATE_ADD(DATE(?), INTERVAL 6 DAY) THEN CONCAT(DATE_ADD(DATE(?), INTERVAL 6 DAY), ' 23:59:59')
                ELSE t.clockout_time 
            END AS clockout_time,
            -- Set workdate as the adjusted clockin_date
            DATE(GREATEST(
                CASE 
                    WHEN t.clockin_time < ? THEN CONCAT(DATE(?), ' 00:00:00')
                    ELSE t.clockin_time 
                END, 
                t.clockin_time
            )) AS workdate,
            -- Calculate the hours within the adjusted times
            TIMESTAMPDIFF(SECOND, 
                GREATEST(
                    CASE 
                        WHEN t.clockin_time < ? THEN CONCAT(DATE(?), ' 00:00:00')
                        ELSE t.clockin_time 
                    END, 
                    t.clockin_time
                ), 
                LEAST(
                    CASE 
                        WHEN t.clockout_time > DATE_ADD(DATE(?), INTERVAL 6 DAY) THEN CONCAT(DATE_ADD(DATE(?), INTERVAL 6 DAY), ' 23:59:59')
                        ELSE t.clockout_time 
                    END, 
                    t.clockout_time
                )
            ) / 3600 AS hours
        FROM timesheets t
        JOIN users u ON u.id = t.user_id
        JOIN jobs j ON t.job_id = j.id
        JOIN time_types tt ON t.time_type_id = tt.id
		JOIN crew_types ct ON ct.id = t.crew_type_id
        WHERE payroll_approval = 1  and t.deleted_at IS NULL
            AND (
                t.clockin_time < DATE_ADD(DATE(?), INTERVAL 7 DAY)
                AND t.clockout_time >= ?
            )
    ) AS ADJUSTED
    GROUP BY workdate, user_id, crew_type, job_number, value, class, pay_rate
    ORDER BY user_id, workdate", 
    [
        $date1, $date1, // For clockin_time adjustment
        $date1, $date1, // For clockout_time adjustment
        $date1, $date1, // For workdate calculation in GREATEST
        $date1, $date1, // For hours calculation in TIMESTAMPDIFF (GREATEST)
        $date1, $date1, // For hours calculation in TIMESTAMPDIFF (LEAST)
        $date1, $date1, // For WHERE clause conditions
    ]);


        // Second query: Weekend out
$weekendout = \DB::select("
    SELECT
        DATE_FORMAT(DATE(t.clockin_time), '%m-%d-%Y') AS workdate,
        t.user_id,
        MAX(j.job_number) AS job_number,
        MAX(tt.value) AS value,
        MAX(u.class) AS class,
        '25' as pay_rate,
        '1' AS hours,
        t.user_id
    FROM timesheets t
    JOIN users u ON u.id = t.user_id
    JOIN jobs j ON t.job_id = j.id
    JOIN time_types tt ON t.time_type_id = tt.id
    WHERE payroll_approval = 1 
        AND weekend_out = 1 AND pay_rate = 0
        AND t.clockin_time BETWEEN CONCAT(DATE(?), ' 00:00:00') AND CONCAT(DATE(?), ' 23:59:59') and deleted_at IS NULL
    GROUP BY t.user_id, workdate
    ORDER BY t.user_id, workdate", [$date1, $date2]);


        // Third query: Per Diem
        $perdiem = \DB::select("
            SELECT
                DATE_FORMAT(DATE(t.clockin_time), '%m-%d-%Y') AS workdate,
                t.user_id,
                MAX(j.job_number) AS job_number,
                MAX(tt.value) AS value,
                MAX(u.class) AS class,
                '9' as pay_rate,
                CASE
                    WHEN MIN(t.per_diem) = 'h' THEN '0.50'
                    WHEN MIN(t.per_diem) = 'f' THEN '1.00'
                END AS hours,
                t.user_id
            FROM timesheets t
            JOIN users u ON u.id = t.user_id
            JOIN jobs j ON t.job_id = j.id
            JOIN time_types tt ON t.time_type_id = tt.id
            WHERE t.payroll_approval = 1 
                AND t.per_diem IN ('h','f')
                AND t.clockin_time BETWEEN CONCAT(DATE(?), ' 00:00:00') AND CONCAT(DATE(?), ' 23:59:59') and deleted_at IS NULL
            GROUP BY t.user_id, workdate
            ORDER BY t.user_id, workdate", [$date1, $date2]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }

    // File name and headers for CSV
    $csvFileName = 'payroll_' . $date1 . '-' . $date2 . '.txt';

    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Description' => 'File Transfer',
        'Cache-Control' => 'public',
        'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
    ];

    // Callback function to write CSV
    $callback = function() use ($equipment, $weekendout, $perdiem) {
        $handle = fopen('php://output', 'w');

        // Write equipment data
        foreach ($equipment as $product) {
			if($product->value == ''){
            fputcsv($handle, [
                $product->workdate ?? '', 
                $product->user_id ?? '',
                $product->job_number ?? '',
                $product->crew_type ?? '', 
                $product->class ?? '',
                $product->pay_rate ?? '',
                $product->hours ?? '',
                '',
				'',
                '1',
                $product->user_id ?? ''
            ]);
        }
		
		else{
			            fputcsv($handle, [
                $product->workdate ?? '', 
                $product->user_id ?? '',
                $product->job_number ?? '',
                $product->value ?? '', 
                $product->class ?? '',
                $product->pay_rate ?? '',
                $product->hours ?? '',
                '',
                '',
				'2',
                $product->user_id ?? ''
            ]);
        }
			
		}

        // Write weekend out data
        foreach ($weekendout as $product) {
            fputcsv($handle, [
                $product->workdate ?? '', 
                $product->user_id ?? '',
                '', 
                '', 
                $product->class ?? '',
                '25',
                '1.00',
                '',
                '',
				'2',
                $product->user_id ?? ''
            ]);
        }

        // Write per diem data
        foreach ($perdiem as $product) {
            fputcsv($handle, [
                $product->workdate ?? '', 
                $product->user_id ?? '',
                '', 
                '', 
                $product->class ?? '',
                '9',
                $product->hours ?? '',
                '',
                '',
				'2',
                $product->user_id ?? ''
            ]);
        }

        fclose($handle);
    };
	
    return response()->stream($callback, 200, $headers);
}



			
        
        }



}