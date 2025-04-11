<style scoped>
/* Ensure sidebar and nav are under modals */
.g-sidenav-show .sidenav {
    z-index: 1030 !important; /* lower than Bootstrap modal */
}

/* Ensure the modal is on top */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

    /* Improve card styling */
    .card {
        border-radius: 10px;
    }

    /* Table Styling */
    #superintendentTaskList tbody tr {
        height: 55px;
        transition: all 0.3s ease-in-out;
    }

    #superintendentTaskList tbody td {
        padding: 15px;
        font-size: 1.1rem;
    }

    #superintendentTaskList thead th {
        font-weight: bold;
        font-size: 1.2rem;
        padding: 12px;
        background-color: #343a40 !important; /* Dark background */
        color: white !important;
    }

    /* Dramatic Hover Effect */
    #superintendentTaskList tbody tr:hover {
        background-color: #FF8C00 !important; /* Dark Orange */
        color: #000 !important;
        transform: scale(1.02);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease-in-out;
    }

    /* Smooth Scroll for Overflow Section */
    .card-body {
        scrollbar-width: thin;
        scrollbar-color: #FFA500 #2A2A2A;
    }

    .card-body::-webkit-scrollbar {
        width: 8px;
    }

    .card-body::-webkit-scrollbar-thumb {
        background-color: #FFA500;
        border-radius: 4px;
    }
	.modal,
	.modal-content,
	.modal-body {
		overflow: visible !important;
		position: relative !important;
		z-index:99999 !important;
	}
</style>
<script>
$(document).ready(function () {
    $('#superintendentTaskList').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        order: [[2, "asc"]],
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
<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <x-auth.navbars.sidebar activePage="dashboard" activeItem="jobs" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="{{ $jobinfo[0]->job_number }} - Job Overview"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div id="app">
		    <div class="container-fluid py-4">
	<div class="row mt-4">
	@if (auth()->user()->role_id == 10 || auth()->user()->role_id == 1)
    <div class="col-md-12"><createeditoverflow :job_id={{$jobinfo[0]->id}} /></div>
	@endif
	<div class="col-11">
	<div class="card mb-4">
	<div class="card-header">
                                    <div
                                        class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl mt-n4 position-absolute">
                                        <i class="material-icons opacity-10">work</i>
                                    </div>
                                    <div class="text-center  pt-1">
                                        <p class="text-sm mb-0 text-capitalize">Job Number</p>
                                        <h3 class="mb-0">
										{{ $jobinfo[0]->job_number }}
                                        </h3>
										
					<h2 class="mb-0">
															<table class="table table-flush" id="datatable-basic">
										<thead class="thead-light">
										<tr>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Branch</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Location</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Contractor</th>
										</tr>
										</thead>
										<tbody>
                        <tr><td><h4><strong>{{ $jobinfo[0]->description }}</strong></h4></td>
                        <td><h4><strong>{{ $jobinfo[0]->branch }}</strong></h4></td>
                        <td><h4><strong>{{ $jobinfo[0]->location }}</strong></h4></td>
                        <td><h4><strong>{{ $jobinfo[0]->contractor }}</strong></h4></td></tr>
						</tbody>
						</table>
                    </h2>
                                    </div>
                                </div>
								</div>
                                <hr class="dark horizontal my-0">

                            </div>
                        </div>
						
                            
                        
                    </div>
			@if (auth()->user()->role_id == 10 || auth()->user()->role_id == 1)
{{-- <div class="col-11 mt-4">
    <div class="card shadow-lg border-0">
        <!-- Card Header -->
        <div class="card-header bg-dark text-white d-flex align-items-center">
            <div class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl me-3">
                <i class="material-icons opacity-10">calendar_month</i>
            </div>
            <h5 class="mb-0 fw-bold">Open Overflow Items</h5>
        </div>

        <!-- Card Body with Scrollable Table -->
        <div class="card-body p-4" style="overflow-y: auto; max-height: 400px;">
            <div class="table-responsive">
                <table id="superintendentTaskList" class="table table-hover table-striped align-middle">
                    <thead class="bg-gradient-dark text-white">
                        <tr>
						<th class="text-center fw-bold">Start Date</th>
                            <th class="text-center fw-bold">Phase</th>
							<th class="text-center fw-bold">Branch</th>
                            <th class="text-center fw-bold">Timeout Date</th>
                            <th class="text-center fw-bold">Superintendent</th>
							
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overflowItems as $task)
                            <tr>
							                                <td class="text-center">
                                    {{ $task->timein_date ? \Carbon\Carbon::parse($task->timein_date)->format('Y-m-d') : 'N/A' }}
                                </td>
                                <td class="text-center">{{ $task->phase }}</td>
								<td class="text-center">{{ $task->description }}</td>
                                <td class="text-center">
                                    {{ $task->timeout_date ? \Carbon\Carbon::parse($task->timeout_date)->format('Y-m-d') : 'N/A' }}
                                </td>
                                <td class="text-center">
                                    {{ $task->name ? $task->name : 'Not Assigned' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No pending tasks</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> --}}
<overflowitems :job_id="{{$jobinfo[0]->id}}" />
@endif
</div>
		                   <div class="col-11 mt-3" style="overflow-y:scroll;max-height: 400px;">
                            <div class="card mt-4" >
                                <div class="card-header p-3 pt-2">
                                    <div class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl mt-n4 float-start">
                                        <i class="material-icons opacity-10">folder</i>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-0">Job Documents</h6>
                                        </div>
                                        <div class="col-md-6 d-flex justify-content-end align-items-center">
										            @if ($message = Session::get('successfile'))
																									
													<div id="entryalerts" class="alert alert-success">
                									<strong>{{ $message }}</strong>
													</div>
													
													@endif
                                            <small>  
												    <div class="form-group form-file-upload form-file-multiple">
		@if(auth()->user()->role_id < 3)
		<button type="button" class="btn btn-warning btn-block mt-4" data-bs-toggle="modal" style="float: left;" data-bs-target="#modal-uploadfile">Upload File</button>&nbsp&nbsp&nbsp
		
		<button type="button" class="btn btn-warning btn-block mt-4" data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-inactivefiles">View Archive</button>
        @endif
													</div>


											</small>
                                        </div>
										</div>
				<table class="table table-flush" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Document Type</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Date Uploaded</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>

                  </tr>
                </thead>
                <tbody>
		@foreach ($files as $file)
			@if( $file->active == "0" && $file->type != "Work Order")
		<tr><td class="gray-100"><h5>{{ $file->type }}</h5></td><td class="font-weight-bolder opacity-9"><h5>{{ $file->description }}</h5></td><td class="td-name"><h5>{{ $file->created_at }}</h5></td><td><input type="button" value="View" class="btn btn-warning" onclick="window.open('/uploads/{{ $file->name }}');"></td><td>@if(auth()->user()->role_id < 3) <a href="/delete-file/{{ $file->id }}" class="btn btn-danger" onclick="return confirm('Are you sure you want to archive this job document?');">ARCHIVE</a>@endif </td></tr>
		    @endif
		@endforeach
	<!-- Add more input fields for other attributes -->
				</tbody>
				</table>
                                    </div>
                                
                                
                            </div>
                        </div>
						<div class="col-11 mt-3" style="overflow-y:scroll;max-height: 400px;">
						<div class="card mt-4" >
                                <div class="card-header p-3 pt-2">
                                    <div class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl mt-n4 float-start">
                                        <i class="material-icons opacity-10">folder</i>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-0">Work Orders</h6>
                                        </div>
										</div>
				<table class="table table-flush" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Document Type</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Date Uploaded</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>


                  </tr>
                </thead>
                <tbody>
		@foreach ($files as $file)
			@if( $file->active == "0" && $file->type == "Work Order" )
		<tr><td class="gray-100"><h5>{{ $file->type }}</h5></td><td class="gray-100"><h5>{{ $file->description }}</h5></td><td class="gray-100" ><h5>{{ $file->created_at }}</h5></td><td><input type="button" value="View" class="btn btn-warning" onclick="window.open('/uploads/{{ $file->name }}');"></td><td>@if(auth()->user()->role_id < 3) <a href="/delete-file/{{ $file->id }}" class="btn btn-danger" onclick="return confirm('Are you sure you want to archive this work order?');">ARCHIVE</a> @endif </td></tr>
		    @endif
		@endforeach
	<!-- Add more input fields for other attributes -->
				</tbody>
				</table>
				
				
                                    </div>
                                
                                
                            </div>

</div>
<br>
<button type="button" class="btn btn-round bg-gradient-warning mb-1" style="margin-left: 73%;" data-bs-toggle="modal" data-bs-target="#modal-notification">New Job Card Entry</button>
                        <div class="col-11 mt-3" style="overflow-y:scroll;max-height: 400px;">
                            <div class="card mt-4" >
                                <div class="card-header p-3 pt-2">
                                    <div class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl mt-n4 float-start">
                                        <i class="material-icons opacity-10">assignment</i>
                                    </div>
									<div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-0">Job Entries</h6>
                                        </div>
                                    <div class="text-end pt-1">
                                        
                                                    @if ($message = Session::get('successentry'))
																									
													<div id="entryalerts" class="alert alert-success">
                									<strong>{{ $message }}</strong>
													</div>
													
													@endif
													
										    </div>
                                </div>
										<table class="table table-flush" id="datatable-basic">
										<thead class="thead-light">
										<tr>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Submitted</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Status</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Work Date</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">User</th>
										</tr>
										</thead>
										<tbody>
                                        @foreach ($jobentries as $jobentry) 
										@if (auth()->user()->name == $jobentry->name || auth()->user()->name = 1)
										
										<tr>
										<td>@If ($jobentry->submitted == 1)<input type="button" class="btn btn-round bg-gradient-warning mb-3" value="View" onclick="location.href='/jobs/{{ $jobentry->link }}/view/';"> </td><td><h4>Yes</h4></td>
										@else 
										<input type="button" class="btn btn-round bg-gradient-warning mb-3" value="Open" onclick="location.href='/jobs/{{ $jobentry->link }}/jobcard/';"></td><td><h4>No</h4></td>
										@endif
										<td ><h4>@If ($jobentry->approved == 0) NA @ElseIf ($jobentry->approved == 1) Approved @ElseIf ($jobentry->approved == 3) Pending @Else ($jobentry->approved == 2) Rejected @endif</h4></td>
										<td ><h4>{{ $jobentry->workdate }}</h4></td>
										
										<td><h4>{{ $jobentry->name }}</h4></td>
										</tr>
										@endif
										@endforeach    
                                        
										</tbody>
										</table>
										
                                 
                                <hr class="dark horizontal my-0">
									<div class="card-footer p-3">
									
									</div>
                                
                            </div>
                        </div> 
					</div>
                   
					

                        </div>
                    </div>
                
                <x-auth.footers.auth.footer></x-auth.footers.auth.footer>

    </main>
    <x-plugins></x-plugins>
    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="{{ asset('assets') }}/js/plugins/chartjs.min.js"></script>
    <!--  Plugin for TiltJS, full documentation here: https://gijsroge.github.io/tilt.js/ -->
    <script src="{{ asset('assets') }}/js/plugins/tilt.min.js"></script>
  

	<!-- Modal -->
<div class="modal fade" id="modal-notification" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">X</span>
            </button>
          </div>
          <div class="modal-body" style="background-color: black;">
            <div class="py-3 text-center">
              <h4 class="text-gradient text-warning mt-4">Select a crew type:</h4>
              <select class="form-control" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: white;width: 100%;margin-bottom: 1rem;vertical-align: top;border-color: #f0f2f5;" id="selectOption">
			  <option value="" disabled selected>Click here to select type</option>
			  @if ($jobitems->contains('phase', '10'))
			  <option value="10">Long Line</option>
		  @endif
			  @if ($jobitems->contains('phase', '20'))
			  <option value="20">Hand Line</option>
		  @endif
			  @if ($jobitems->contains('phase', '30'))
			  <option value="30">RPMs</option>
		  @endif
			  @if ($jobitems->contains('phase', '50'))
			  <option value="50">Grinding Removal</option>
		  @endif
			  @if ($jobitems->contains('phase', '51'))
			  <option value="51">Waterblast Removal</option>
		  @endif
			  @if ($jobitems->contains('phase', '60'))
			  <option value="60">Paint</option>
		  @endif
			  @if ($jobitems->contains('phase', '71'))
			  <option value="71">Tape</option>
		  @endif
		  	@if ($jobitems->contains('phase', '75'))
			  <option value="71">Epoxy</option>
		  @endif
			  @if ($jobitems->contains('phase', '90'))
			  <option value="90">Construction Striping</option>
			  @endif
			  </select>
            </div>
          </div>

        </div>
      </div>
    </div>
	
	<div class="modal fade" id="modal-inactivefiles" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">X</span>
            </button>
          </div>
          <div class="modal-body" style="background-color: black;">
            <div class="py-3 text-center">
				<table class="table table-flush" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Type</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Date Uploaded</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                  </tr>
                </thead>
                <tbody>
		@foreach ($files as $file)
			@if( $file->active == "1" )
		<tr><td>{{ $file->description }}</td><td>{{ $file->type }}</td><td>{{ $file->created_at }}</td><td><input type="button" value="View" class="btn btn-warning" onclick="window.open('/uploads/{{ $file->name }}');"></td></tr>
		    @endif
		@endforeach
	<!-- Add more input fields for other attributes -->
				</tbody>
				</table>


            </div>
          </div>

        </div>
      </div>
    </div>
	
	<div class="modal fade" id="modal-uploadfile" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">X</span>
            </button>
          </div>
          <div class="modal-body" style="background-color: black;">
            <div class="py-3 text-center">
              
 <form action="{{route('fileUpload')}}"  method="post" enctype="multipart/form-data">
            @csrf
            @if ($message = Session::get('success'))
            <div id="filesuccess" class="alert alert-success">
                <strong>{{ $message }}</strong>
            </div>
          @endif
          @if (count($errors) > 0)
            <div id="fileerror" class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
          @endif
            <div class="custom-file"></div>
				<input type="hidden" name="jobnumber"  value="{{ $jobinfo[0]->job_number }}" >
                <input type="file" name="file" class="custom-file-input" id="chooseFile">
				<br><br><br><h4 class="text-gradient text-warning mt-4">Document Type:</h4>
				<select class="form-control" name="doctype" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: white;width: 100%;margin-bottom: 1rem;vertical-align: top;border-color: #f0f2f5;">
				<option value="" disabled selected>Click here to select type</option>
				<option value="Job Documents">Job Documents</option>
				<option value="Work Order">Work Order</option>
				<option value="Other">Other</option>
				</select>
				<input  class="form-control" type="text" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: white;width: 100%;margin-bottom: 1rem;vertical-align: top;border-color: #f0f2f5;" name="docdesc"  placeholder="Description" required>
				<button type="submit" name="submit" class="btn btn-warning btn-block mt-4" style="float: left;">
                Upload New File
            </button>
            </div>
            
			
        </form>
            </div>
          </div>

        </div>
      </div>
    </div>
    <script>
// Get references to the dropdown and button elements

//Set onclick to alert the selected value
            
    document.getElementById("selectOption").addEventListener("change", function() {
// Get the selected option value
		var selectedOption = document.getElementById("selectOption").value;
		if (selectedOption !== "") {
// Navigate to the selected page
			
			window.location.href = "/jobs/{{ $jobinfo[0]->id }}/edit/" + selectedOption;
		}
	});
	
   document.addEventListener('DOMContentLoaded', function() {
            // Delay the hiding of the div by 5 seconds (5000 milliseconds)
            setTimeout(function() {
                var myDiv = document.getElementById('entryalerts');
                myDiv.style.display = 'none'; // Hide the div
            }, 3000); // 5000 milliseconds = 5 seconds
        });
    
	   document.addEventListener('DOMContentLoaded', function() {
            // Delay the hiding of the div by 5 seconds (5000 milliseconds)
            setTimeout(function() {
                var myDiv3 = document.getElementById('filesuccess');
                myDiv3.style.display = 'none'; // Hide the div
            }, 5000); // 5000 milliseconds = 5 seconds
        });
    
	
	   document.addEventListener('DOMContentLoaded', function() {
            // Delay the hiding of the div by 5 seconds (5000 milliseconds)
            setTimeout(function() {
                var myDiv2 = document.getElementById('fileerror');
                myDiv2.style.display = 'none'; // Hide the div
            }, 5000); // 5000 milliseconds = 5 seconds
        });

	console.log("Phases loaded:", phases.value);
console.log("Branches loaded:", branches.value);
	
	</script>
	
	
    @endpush
</x-page-template>