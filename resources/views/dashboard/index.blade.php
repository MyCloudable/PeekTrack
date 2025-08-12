
@if (auth()->user()->role_id == 6)
    <script type="text/javascript">
        window.location = "{{ url('/crewmember') }}";
		
		
    </script>
@endif


<style>
.white-background {
    background-color: white !important;
}
tr:hover {
    background-color: #f0f0f0;
    transition: background-color 0.3s ease;
}

    /* Increase row height for easier tablet clicking */
    #superintendentTaskList tbody tr {
        height: 75px; /* Adjust for better touch area */
        font-size: 1.1rem; /* Slightly larger font */
    }

    /* Increase clickable row padding */
    #superintendentTaskList tbody td {
        padding: 5 px; /* Increase spacing */
    }

    /* Ensure header text is bold and visible */
    #superintendentTaskList thead th {
        font-weight: bold;
        font-size: 1.2rem;
        padding: 12px;
    }
	    #superintendentTaskList tbody tr:hover {
        background-color: #FF5722 !important; /* Bright orange */
        color: #000 !important; /* Black text for contrast */
        transform: scale(1.02); /* Slightly enlarges the row */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Adds shadow effect */
        transition: all 0.2s ease-in-out;
    }
	
	.custom-white-select {
  border-radius: 6px;
  border: 1px solid #ccc;
  background-color: white !important;
}

.custom-white-select option {
  background-color: #ffffff;
  color: #000000;
  font-weight: bold;
}

</style>
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<x-page-template bodyClass='g-sidenav-show bg-gray-200 dark-version'>
    <x-auth.navbars.sidebar activePage="dashboard" activeItem="analytics" activeSubitem=""></x-auth.navbars.navs.sidebar>
	
	




    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" id="app">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Dashboard"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->

<div id="app">
    <urgentnotificationpopup />
</div>

        <div class="container-fluid py-4">
            <div class="col-sm-12">

        <div class="container-fluid py-4">
            <div class="col-sm-12">
                <div class="row">
<div class="container-fluid py-4">
    <!-- Row 1: Job Summary Cards -->
	@if ( auth()->user()->role_id != 3)
    <div class="row">
        <!-- Card 1: Active Jobs -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('jobs', ['id' => Auth::user()->id]) }}" class="text-decoration-none">
                <div class="card mb-2">
                    <div class="card-header p-3 pt-2">
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize"><h4>Active Jobs</h4></p>
                            <h3 class="mb-0">{{ $job }}</h3>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-dark text-center border-radius-xl mt-n4">
                            <i class="material-icons opacity-10">engineering</i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card 2: Unsubmitted Job Cards -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card mb-2">
                <div class="card-header p-3 pt-2">
                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize"><h4>Pending Cards</h4></p>
                        <h3 class="mb-0">{{ count($unsubmitCards) }}</h3>
                    </div>
                </div>
                <hr class="dark horizontal my-0">
                <div class="card-footer p-3">
                    <div class="icon icon-lg icon-shape bg-gradient-warning shadow-primary shadow text-center border-radius-xl mt-n4">
                        <i class="material-icons opacity-10">pending</i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Rejected Job Cards -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card mb-2">
                <div class="card-header p-3 pt-2">
                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize"><h4>Rejected Job Cards</h4></p>
                        <h3 class="mb-0">{{ count($rejectedJobcards) }}</h3>
                    </div>
                </div>
                <hr class="horizontal my-0 dark">
                <div class="card-footer p-3">
                    <div class="icon icon-lg icon-shape bg-gradient-warning shadow-success text-center border-radius-xl mt-n4">
                        <i class="material-icons opacity-10">sync_problem</i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: My Job Cards -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('jobs.myjobcards', ['id' => Auth::user()->id]) }}" class="text-decoration-none">
                <div class="card mb-2">
                    <div class="card-header p-3 pt-2">
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0 text-capitalize"><h4>My Job Cards</h4></p>
                            <h3 class="mb-0">{{ count($Jobcards) }}</h3>
                        </div>
                    </div>
                    <hr class="horizontal my-0 dark">
                    <div class="card-footer p-3">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-success text-center border-radius-xl mt-n4">
                            <i class="material-icons opacity-10">work</i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Card 5: Estimating Cards -->
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card mb-2">
                <div class="card-header p-3 pt-2">
                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize"><h4>Estimating Cards</h4></p>
                        <h3 class="mb-0">{{ count($estimatingCards) }}</h3>
                    </div>
                </div>
                <hr class="dark horizontal my-0">
                <div class="card-footer p-3">
                    <div class="icon icon-lg icon-shape bg-gradient-warning shadow-primary text-center border-radius-xl mt-n4">
                        <i class="material-icons opacity-10">calculate</i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<br>
    <!-- Toggle Button for Collapsible Table -->
    <div class="row">
        <div class="col-12 text-end">
            <button class="btn btn-warning mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#jobCardsTable" aria-expanded="true" aria-controls="jobCardsTable">
                Job Card Detail
            </button>
        </div>
    </div>

    <!-- Collapsible Table with Tabs -->
    <div class="collapse" id="jobCardsTable">
        <div class="card">
            <div class="card-header">
                <h4>Job Cards</h4>
            </div>
            <div class="card-body">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="jobCardsTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link text-warning active" id="unsubmitted-tab" data-bs-toggle="tab" href="#unsubmitted" role="tab" aria-controls="unsubmitted" aria-selected="true">
                            Pending ({{ $filteredUnsubmitCards->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" id="rejected-tab" data-bs-toggle="tab" href="#rejected" role="tab" aria-controls="rejected" aria-selected="false">
                            Rejected ({{ $filteredRejectedJobcards->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" id="myjobcards-tab" data-bs-toggle="tab" href="#myjobcards" role="tab" aria-controls="myjobcards" aria-selected="false">
                            My Job Cards ({{ $filteredJobcards->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" id="estimating-tab" data-bs-toggle="tab" href="#estimating" role="tab" aria-controls="estimating" aria-selected="false">
                            Estimating ({{ $filteredEstimatingCards->count() }})
                        </a>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content mt-3" id="jobCardsTabContent">
    <!-- Unsubmitted Tab -->
    <div class="tab-pane fade show active" id="unsubmitted" role="tabpanel" aria-labelledby="unsubmitted-tab">
        <div class="table-responsive " style="max-height: 300px; overflow-y: auto;">
           <table id="sortablePendingTable" class="table table-bordered table-sm">
    <thead>
        <tr>
            <th>Work Date</th>
            <th>Job</th>
            <th>S I</th>
            <th>Status</th>
            <th>Branch</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($filteredUnsubmitCards as $jobcard)
            <tr onclick="window.location='{{ route('jobs.jobcard', ['id' => $jobcard->link]) }}'" style="cursor: pointer;">
                <td>{{ $jobcard->workdate }}</td>
                <td>{{ $jobcard->job_number }}</td>
                <td>{{ $jobcard->name ?? 'Job Card ' . $jobcard->job_number }}</td>
                <td>
                    @if (($jobcard->approved == 0 && $jobcard->submitted == 0) || ($jobcard->approved === null && $jobcard->submitted == 0))
                        Not Submitted
                    @elseif (($jobcard->approved == 3 && $jobcard->submitted == 1) || ($jobcard->approved === null && $jobcard->submitted == 1))
                        Awaiting Review
                    @elseif ($jobcard->approved == 1)
                        Approved
                    @elseif ($jobcard->approved == 2)
                        Rejected
                    @elseif ($jobcard->approved == 4)
                        Estimating Queue
                    @else
                        Unknown Status
                    @endif
                </td>
                <td>
                    @php
                        $matchingJob = $jobs->firstWhere('job_number', $jobcard->job_number);
                    @endphp
                    {{ $matchingJob->branch ?? 'Unknown Branch' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">No Unsubmitted Job Cards</td>
            </tr>
        @endforelse
    </tbody>
</table>

        </div>
    </div>

    <!-- Rejected Tab -->
    <div class="tab-pane fade" id="rejected" role="tabpanel" aria-labelledby="rejected-tab">
        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-bordered table-sm" table id="sortableRejectedTable">
                <thead>
                    <tr>
                        <th>Work Date</th>
                        <th>Job</th>
                        <th>Job Card</th>
                        <th>Status</th>
						<th>Branch</th>
						<th>S I</th>
						<th>Reviewer</th>
                    </tr>
                </thead>
<tbody>
    @forelse ($filteredRejectedJobcards as $jobcard)
        <tr onclick="window.location='@if (auth()->user()->role_id == 3 ) {{ route('jobs.jobcard', ['id' => $jobcard->link]) }} @else {{ route('jobs.view', ['id' => $jobcard->link]) }} @endif'" style="cursor: pointer;">
            <td>{{ $jobcard->workdate }}</td>
            <td>{{ $jobcard->job_number }}</td>
            <td>{{ $jobcard->name ?? 'Job Card ' . $jobcard->job_number }}</td>
            <td>
@if (($jobcard->approved == 0 && $jobcard->submitted == 0) || ($jobcard->approved === null && $jobcard->submitted == 0))
        Not Submitted
	@elseif (($jobcard->approved == 3 && $jobcard->submitted == 1) || ($jobcard->approved === null && $jobcard->submitted == 1))
        Awaiting Review
    @elseif ($jobcard->approved == 1)
        Approved
    @elseif ($jobcard->approved == 2)
        Rejected
    @elseif ($jobcard->approved == 4)
        Estimating Queue
    @else
        Unknown Status
    @endif
            </td>
            <td>
                @php
                    $matchingJob = $jobs->firstWhere('job_number', $jobcard->job_number);
                @endphp
                {{ $matchingJob->branch ?? 'Unknown Branch' }}
            </td>
			<td>{{ $jobcard->name }}</td>
			<td>{{ $jobcard->approvedBy }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center">No Rejected Job Cards</td>
        </tr>
    @endforelse
</tbody>

            </table>
        </div>
    </div>

    <!-- My Job Cards Tab -->
    <div class="tab-pane fade" id="myjobcards" role="tabpanel" aria-labelledby="myjobcards-tab">
        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-bordered table-sm" id="sortableMyCardsTable">
                <thead>
                    <tr>
                        <th>Work Date</th>
                        <th>Job</th>
                        <th>Job Card</th>
                        <th>Status</th>
						<th>Branch</ht>
                    </tr>
                </thead>
                <tbody>
    @forelse ($filteredJobcards as $jobcard)
        <tr onclick="window.location='{{ route('jobs.view', ['id' => $jobcard->link]) }}'" style="cursor: pointer;">
            <td>{{ $jobcard->workdate }}</td>
            <td>{{ $jobcard->job_number }}</td>
            <td>{{ $jobcard->name ?? 'Job Card ' . $jobcard->job_number }}</td>
            <td>
@if (($jobcard->approved == 0 && $jobcard->submitted == 0) || ($jobcard->approved === null && $jobcard->submitted == 0))
        Not Submitted
	@elseif (($jobcard->approved == 3 && $jobcard->submitted == 1) || ($jobcard->approved === null && $jobcard->submitted == 1))
        Awaiting Review
    @elseif ($jobcard->approved == 1)
        Approved
    @elseif ($jobcard->approved == 2)
        Rejected
    @elseif ($jobcard->approved == 4)
        Estimating Queue
    @else
        Unknown Status
    @endif
            </td>
            <td>
                @php
                    $matchingJob = $jobs->firstWhere('job_number', $jobcard->job_number);
                @endphp
                {{ $matchingJob->branch ?? 'Unknown Branch' }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center">No Job Cards</td>
        </tr>
    @endforelse
</tbody>

            </table>
        </div>
    </div>

    <!-- Estimating Tab -->
    <div class="tab-pane fade" id="estimating" role="tabpanel" aria-labelledby="estimating-tab">
        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-bordered table-sm" id="sortableEstimatingTable">
                <thead>
                    <tr>
                        <th>Work Date</th>
                        <th>Job</th>
                        <th>Job Card</th>
                        <th>Status</th>
						<th>Branch</th>
						<th>Sent By</th>
                    </tr>
                </thead>
                <tbody>
    @forelse ($estimatingCards as $jobcard)
        <tr onclick="window.location='{{ route('jobs.view', ['id' => $jobcard->link]) }}'" style="cursor: pointer;">
            <td>{{ $jobcard->workdate }}</td>
            <td>{{ $jobcard->job_number }}</td>
            <td>{{ $jobcard->name ?? 'Job Card ' . $jobcard->job_number }}</td>
            <td>
@if (($jobcard->approved == 0 && $jobcard->submitted == 0) || ($jobcard->approved === null && $jobcard->submitted == 0))
        Not Submitted
	@elseif (($jobcard->approved == 3 && $jobcard->submitted == 1) || ($jobcard->approved === null && $jobcard->submitted == 1))
        Awaiting Review
    @elseif ($jobcard->approved == 1)
        Approved
    @elseif ($jobcard->approved == 2)
        Rejected
    @elseif ($jobcard->approved == 4)
        Estimating Queue
    @else
        Unknown Status
    @endif
            </td>
            <td>
                @php
                    $matchingJob = $jobs->firstWhere('job_number', $jobcard->job_number);
                @endphp
                {{ $matchingJob->branch ?? 'Unknown Branch' }}
            </td>
			<td>{{ $jobcard->approvedBy }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center">No Estimating Job Cards</td>
        </tr>
    @endforelse
</tbody>

            </table>
        </div>
    </div>
</div>

            </div>
        </div>
    </div>
	
</div>

</div>
<br>
@if (auth()->user()->role_id == 3)
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0 fw-bold">Superintendent Task List</h3>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="superintendentTaskList" class="table table-hover table-striped align-middle">
                        <thead class="bg-gradient-dark text-white">
                            <tr>
								<th class="text-center fw-bold">Traffic Shift</th>
                                <th class="text-center fw-bold">Job Number</th>
                                <th class="text-center fw-bold">Phase</th>
                                <th class="text-center fw-bold">Start Date</th>
                                <th class="text-center fw-bold">Timeout Date</th>
								<th class="text-center fw-bold">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($overflowItems as $task)
                                <tr onclick="window.location='{{ route('jobs.overview', ['id' => $task->job_id]) }}'" 
                                    class="clickable-row fw-bold" 
                                    style="cursor: pointer; height: 50px;">
									@if ($task->traffic_shift == 1)
									<td class="text-center"><span class="traffic-icon text-bright-white"><i class="fa-solid fa-light-emergency"></i></span></td>
									@else
									<td class="text-center">N/A</td>
									@endif
                                    <td class="text-center">{{ $task->job_number }}</td>
                                    <td class="text-center">{{ $task->phase }}</td>
                                    <td class="text-center">
                                        {{ $task->timein_date ? \Carbon\Carbon::parse($task->timein_date)->format('Y-m-d') : 'N/A' }}
                                    </td>
                                    <td class="text-center">
                                        {{ $task->timeout_date ? \Carbon\Carbon::parse($task->timeout_date)->format('Y-m-d') : 'N/A' }}
                                    </td>
									<td>
										{{ $task->notes }}
									</td>
                                </tr>
                            @empty
                            
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- DataTable Script -->
<script>
$(document).ready(function () {
    $('#superintendentTaskList').DataTable({
        paging: true,
        searching: true,
        ordering: false,
        language: {
            emptyTable: "No tasks available",
            search: "Filter Tasks:",
            lengthMenu: "Show _MENU_ tasks per page",
            info: "Showing _START_ to _END_ of _TOTAL_ tasks",
            paginate: { previous: "<", next: ">" }
        }
    });
});
</script>
@endif
<br>
@if (auth()->user()->role_id != 3)
                                <!-- Crew Status Visualization -->
								                <!-- Location Selector -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="locationSelect" class="form-label">Select Location: &nbsp</label>
                        <select id="locationSelect" class="form-control custom-white-select" onchange="saveLocationPreference()">
    <option value="" selected>All Locations</option>
    @foreach ($crews->groupBy('location_group') as $locationGroup => $locationCrews)
        <option value="{{ $locationGroup }}">{{ $locationGroup }}</option>
    @endforeach
</select>

                    </div>
                </div>
                <div class="row mt-4">
                    <h3 class="mb-4">Current Crew Status</h3>

                    <!-- First show "Shop Time" group -->
                    <div class="accordion" id="accordionShopTime">
                        @foreach ($crews->where('branch', 'Please Select')->groupBy('location_group') as $locationGroup => $locationCrews)
                            <div class="accordion-item location-item" data-location="{{ $locationGroup }}">
                                <h2 class="accordion-header" id="heading{{ Str::slug($locationGroup) }}">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ Str::slug($locationGroup) }}" aria-expanded="false" aria-controls="collapse{{ Str::slug($locationGroup) }}">
                                        {{ $locationGroup }}
                                    </button>
                                </h2>
                                <div id="collapse{{ Str::slug($locationGroup) }}" class="accordion-collapse collapse" aria-labelledby="heading{{ Str::slug($locationGroup) }}" data-bs-parent="#accordionShopTime">
                                    <div class="accordion-body">
                                        @foreach ($locationCrews->groupBy('user_location') as $userLocationGroup => $crewsByUserLocation)
                                            <h6 class="mb-3">{{ $userLocationGroup }}</h6>
                                            <div class="row">
                                                @foreach ($crewsByUserLocation->sortBy('superintendent_name') as $crew)
                                                    @php
                                                        $nameColor = '';
                                                        switch (strtolower(trim($crew->time_type))) {
                                                            case 'shop':
                                                                $nameColor = '#dc3545'; // Red for shop
                                                                break;
                                                            case 'mobilization':
                                                                $nameColor = '#ffc107'; // Orange for mobilization
                                                                break;
                                                            case 'production':
                                                                $nameColor = '#28a745'; // Green for production
                                                                break;
                                                            default:
                                                                $nameColor = '#6c757d'; // Default color (secondary)
                                                                break;
                                                        }

                                                        $lastClockInTime = \Carbon\Carbon::parse($crew->last_clockin_time);
                                                        $currentTime = \Carbon\Carbon::now();
                                                        $timeDifference = $currentTime->diffForHumans($lastClockInTime, true);
                                                        $isWarning = $currentTime->diffInHours($lastClockInTime) > 12;
                                                    @endphp
                                                    <div class="col-lg-3 col-md-6 col-sm-6">
                                                        <div class="card mb-2">
                                                            <div class="card-header p-3 pt-2 bg-gradient-secondary text-white">
                                                                <div class="text-end pt-1">
                                                                    <h5 class="mb-0" style="color: {{ $nameColor }} !important;">{{ $crew->superintendent_name }}</h5>
                                                                    <p class="text-sm mb-0">{{ $crew->time_type }} - {{ $crew->job_number }}</p>
                                                                </div>
                                                            </div>
                                                            <div class="card-footer p-3">
                                                                <p class="mb-0">
                                                                    Started At: 
                                                                    <span style="{{ $isWarning ? 'color: #FFFF00;' : '' }}">
                                                                        {{ $lastClockInTime->format('Y-m-d H:i') }} ({{ $timeDifference }})
                                                                        @if($isWarning)
                                                                            <strong>Warning</strong>
                                                                        @endif
                                                                    </span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Other groups -->
                    <div class="accordion" id="accordionBranches">
                        @foreach ($crews->where('branch', '!=', 'Please Select')->groupBy('location_group') as $branch => $branchCrews)
                            <div class="accordion-item location-item" data-location="{{ $branch }}">
                                <h2 class="accordion-header" id="heading{{ Str::slug($branch) }}">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ Str::slug($branch) }}" aria-expanded="false" aria-controls="collapse{{ Str::slug($branch) }}">
                                        {{ $branch }}
                                    </button>
                                </h2>
                                <div id="collapse{{ Str::slug($branch) }}" class="accordion-collapse collapse" aria-labelledby="heading{{ Str::slug($branch) }}" data-bs-parent="#accordionBranches">
                                    <div class="accordion-body">
                                        <div class="row">
                                            @foreach ($branchCrews as $crew)
                                                @php
                                                    $nameColor = '';
                                                    switch (strtolower(trim($crew->time_type))) {
                                                        case 'shop':
                                                            $nameColor = '#dc3545'; // Red for shop
                                                            break;
                                                        case 'mobilization':
                                                            $nameColor = '#ffc107'; // Orange for mobilization
                                                            break;
                                                        case 'production':
                                                            $nameColor = '#28a745'; // Green for production
                                                            break;
                                                        default:
                                                            $nameColor = '#6c757d'; // Default color (secondary)
                                                            break;
                                                    }

                                                    $lastClockInTime = \Carbon\Carbon::parse($crew->last_clockin_time);
                                                    $currentTime = \Carbon\Carbon::now();
                                                    $timeDifference = $currentTime->diffForHumans($lastClockInTime, true);
                                                    $isWarning = $currentTime->diffInHours($lastClockInTime) > 12;
                                                @endphp
                                                <div class="col-lg-3 col-md-6 col-sm-6">
                                                    <div class="card mb-2">
                                                        <div class="card-header p-3 pt-2 bg-gradient-secondary text-white">
                                                            <div class="text-end pt-1">
                                                                <h5 class="mb-0" style="color: {{ $nameColor }} !important;">{{ $crew->superintendent_name }}</h5>
                                                                <p class="text-sm mb-0">{{ $crew->time_type }} - {{ $crew->job_number }}</p>
                                                            </div>
                                                        </div>
                                                        <div class="card-footer p-3">
                                                            <p class="mb-0">
                                                                Started At: 
                                                                <span style="{{ $isWarning ? 'color: #FFFF00;' : '' }}">
                                                                    {{ $lastClockInTime->format('Y-m-d H:i') }} ({{ $timeDifference }})
                                                                    @if($isWarning)
                                                                        <strong>Warning</strong>
                                                                    @endif
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- End of Crew Status Visualization -->
				@endif
            </div>
        </div>

        <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
    </main>
    <x-plugins></x-plugins>
    @push('js')
        <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
        <script>
		$('#yourTableId').DataTable({
    language: {
        emptyTable: ""
    }
});

            document.addEventListener('DOMContentLoaded', function() {
                const locationSelect = document.getElementById('locationSelect');
                const locationItems = document.querySelectorAll('.location-item');

                // Load location preference from localStorage
                const savedLocation = localStorage.getItem('preferredLocation');
                if (savedLocation) {
                    locationSelect.value = savedLocation;
                    showLocation(savedLocation);
                }

                // Function to save location preference in localStorage
                window.saveLocationPreference = function() {
                    const selectedLocation = locationSelect.value;
                    localStorage.setItem('preferredLocation', selectedLocation);
                    showLocation(selectedLocation);
                };

                // Function to show or hide location items based on selection and expand it
                function showLocation(location) {
                    locationItems.forEach(item => {
                        const collapseElement = item.querySelector('.accordion-collapse');
                        const buttonElement = item.querySelector('.accordion-button');
                        
                        if (location === '' || item.getAttribute('data-location') === location) {
                            item.style.display = 'block';
                            // If the location matches, expand the accordion
                            collapseElement.classList.add('show');
                            buttonElement.setAttribute('aria-expanded', 'true');
                        } else {
                            item.style.display = 'none';
                            collapseElement.classList.remove('show');
                            buttonElement.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            });
        </script>
		<script>
    document.addEventListener("DOMContentLoaded", function () {
        const rows = document.querySelectorAll(".clickable-row");
        rows.forEach(row => {
            row.addEventListener("click", function () {
                const href = this.dataset.href;
                if (href) {
                    window.location.href = href;
                }
            });
        });
    });
	

    $(document).ready(function () {
		$.fn.dataTable.ext.errMode = 'none';
        $('#sortablePendingTable').DataTable({
            paging: false, // Optional: Disable pagination if you only need sorting
            searching: false, // Optional: Disable search if you don't need it
            ordering: true, // Enable column sorting
        });
    });

$(document).ready(function () {
	$.fn.dataTable.ext.errMode = 'none';
    $('#sortableRejectedTable').DataTable({
        paging: false,
        searching: false,
        ordering: true,
    });
});

$(document).ready(function () {
	$.fn.dataTable.ext.errMode = 'none';
    $('#sortableMyCardsTable').DataTable({
        paging: false,
        searching: false,
        ordering: true,
    });
});

$(document).ready(function () {
	$.fn.dataTable.ext.errMode = 'none';
    $('#sortableEstimatingTable').DataTable({
        paging: false,
        searching: false,
        ordering: true,
    });
});

</script>
<!-- jQuery (must be loaded first) -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

    @endpush
</x-page-template>