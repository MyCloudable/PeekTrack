
<style>
/* main.css */
body {
  margin: 2em;
  color: #fff;
  background-color: #000;
}

/* override styles when printing */
@media print {
  body, html, #page-container, .scrollable-page, .ps, .panel {
      height: 100% !important;
      width: 100% !important;
      display: inline-block;
  }


textarea {
    background-color: #fff !important;
}


</style>
<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<input type="hidden" id="csrf" value="{{ csrf_token() }}">

    <x-auth.navbars.sidebar activePage="jobs" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="View Job Card"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
		                <div class="col-12">
                    <button type="button" class="btn btn-warning btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardfiles">View Uploads</button>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
					 <button type="button" class="btn btn-success btn-block mt-4" onclick="screenshot('{{Auth::user()->email}}')">Share</button>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp
					 <input type="button" class="btn btn-info btn-block mt-4" value="Print" onClick="window.print()">
					 @if(auth()->user()->role_id < 3 && $jobcard[0]->billing_approval != 1 && $jobcard[0]->approved == 1) 
						 
                     <button type="button" class="btn btn-danger btn-block mt-4" data-bs-toggle="modal" style="top: 0%;left: 20%;" data-bs-target="#open">Re-Open</button>
                     @endif
					 
				     @if(auth()->user()->role_id == 9 && $jobcard[0]->approved == 4) 
                     <button type="button" class="btn btn-danger btn-block mt-4" data-bs-toggle="modal" style="top: 0%;left: 30%;" data-bs-target="#openest">Send Back To Review</button>
                     @endif 
                </div>
        <div class="container-fluid py-4"> 
		<div id="capture">
		@method('PUT')
		
            <!-- Add input fields for editing record attributes -->
            <label for="job_number">Job Number: <strong>{{ $jobinfo[0]->job_number }}<input type="hidden" id="link" value="{{ $jobcard[0]->link }}"></strong></label> <label for="description">Description: <strong>{{ $jobinfo[0]->description }}</strong></label>
            <label for="branch">Branch: <strong>{{ $jobinfo[0]->branch }}</strong></label> <label for="location">Location: <strong>{{ $jobinfo[0]->location }}</strong></label>
            <label for="contractor">Contractor: <strong>{{ $jobinfo[0]->contractor }}</strong></label> <label for="completion_date">Est. Completion Date: <strong>{{ $jobinfo[0]->completion_date }}</strong></label><br>
            <label for="supervisor">Superintendent: <strong>{{ $jobcard[0]->name }}</strong></label>&nbsp&nbsp<label for="workdate">Work Date: <strong>{{ $jobcard[0]->workdate }}</strong></label>&nbsp&nbsp<label for="completion_date">Job Card Created On: <strong>{{ $jobcard[0]->created_at }}</strong></label>&nbsp&nbsp<label for="completion_date">Job Card Status: <strong>@if ($jobcard[0]->approved == 1) Approved @elseif ($jobcard[0]->approved == 2) Rejected @ELSE Pending @ENDIF</strong></label>@if ($jobcard[0]->approved == 1) &nbsp&nbsp<label for="approved_by"> Approved By: <strong>{{ $jobcard[0]->approvedBy }}</strong></label> @endif <br>

            <div class="row mt-4">




                    
					<div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mt-3 mb-2 ms-3">Production</h6>
                            </div> @csrf 
                                <div  class="table-responsive">
                                    <table class="table table-flush" id="datatable-production">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Quantity</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Measurement</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Notes</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Road Name</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Complete</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Surface Type</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Purchase Order</th>
                                            </tr>
                                        </thead>
                                        <tbody> @foreach ($production as $jobdata) <tr>
                                                <td><h5>{{ $jobdata->phase }}</h5></td>
                                                <td><h5>{{ $jobdata->description }}</h5></td>
                                                <td>{{ $jobdata->qty }}</td>
                                                <td><h5>{{ $jobdata->unit_of_measure }}</h5></td>
                                                <td>{{ $jobdata->mark_mill }}</td>
                                                <td>{{ $jobdata->road_name }}</td>
                                                <td>@if ($jobdata->phase_item_complete == 1) C @else NC @endif </td>
                                                <td>{{ $jobdata->surface_type }}</td>
                                                <td></td>
                                            </tr> @endforeach
                                            <!-- Add more input fields for other attributes -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mt-3 mb-2 ms-3">Material</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-flush" id="datatable-material">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Quantity</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Measurement</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Supplier</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Batch #</th>
                                        </tr>
                                    </thead>
                                    <tbody> @foreach ($material as $jobmaterial) <tr>
                                            <td><h5>{{ $jobmaterial->phase }}</h5></td>
                                            <td><h5>{{ $jobmaterial->description }}</h5></td>
                                            <td>{{ $jobmaterial->qty }}</td>
                                            <td><h5>{{ $jobmaterial->unit_of_measure }}</h5></td>
                                            <td><h5>{{ $jobmaterial->supplier }}</h5></td>
                                            <td><h5>{{ $jobmaterial->batch }}</h5></td>
                                        </tr> @endforeach
                                        <!-- Add more input fields for other attributes -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>
			</div>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mt-3 mb-2 ms-3">Equipment</h6>
                </div>
                <div class="card-body mb-4">
                    <div class="table-responsive">
                        <table class="table table-flush" id="datatable-equipment">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Truck</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Hours</th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($equipment as $jobequipment) <tr>
                                    <td><h5>{{ $jobequipment->phase }}</h5></td>
                                    <td><h5>{{ $jobequipment->description }}</h5></td>
                                    <td><h5>{{ $jobequipment->truck }}</h5></td>
                                    <td><h5>{{ $jobequipment->hours }}</h5></td> @endforeach
                                    <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <center>
                <label>Notes</label><br> @foreach ($jobnotes as $jobnote) <textarea rows="2" style="background-color: #fff !important;" cols="100" disabled> {{ $jobnote->note_type }} -  {{ $jobnote->username }} - {{ $jobnote->created_at }} : {{ $jobnote->note }}</textarea></br> @endforeach <br><br>
                <br></br>
               
            </center>
		</div>
        <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>
    </main>
    <x-plugins></x-plugins>
    <div class="modal fade" id="modal-jobcardfiles" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($files as $file) @if( $file->doctype == "1" ) <tr>
                                    <td>{{ $file->type }}</td>
                                    <td>{{ $file->description }}</td>
                                    <td>{{ $file->created_at }}</td>
                                    <td><input type="button" value="View" class="btn btn-warning" onclick="window.open('/uploads/{{ $file->name }}');"></td>
                                </tr> @endif @endforeach
                                <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> 
    <div class="modal fade" id="open" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body" style="background-color: black;">
                    <div class="py-3 text-center"> Are you sure you want to unapprove this job card? 
					<form method="post" id="unapproveForm" action="{{ route('jobs.opencard')}}">
                            <!-- We display the details entered by the user here -->
                            <input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
                            <input type="hidden" name="username" value="{{Auth::user()->name}}">
                    </div> @csrf <input type="submit" class="btn-warning" form="unapproveForm" value="Unapprove Job Card" />
                    </form>
                </div>
            </div>
        </div>
    </div> 
	 <div class="modal fade" id="openest" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body" style="background-color: black;">
                    <div class="py-3 text-center"> Are you sure you want to send this back to review? 
					<form method="post" id="reviewForm" action="{{ route('jobs.submit')}}">
                            <!-- We display the details entered by the user here -->
                            <input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
                            <input type="hidden" name="user" value="{{Auth::user()->name}}">
							<input type="hidden" name="role" value="{{auth()->user()->role_id}}">
							<textarea name="note" rows="4" cols="50" required></textarea>

                    </div> @csrf <input type="submit" class="btn-warning" form="reviewForm" value="Send to Review" />
                    </form>
                </div>
            </div>
        </div>
    </div> 
	@push('js') <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
	<script src="{{ asset('assets') }}/js/html2canvas.min.js"></script>
	<script src="{{ asset('assets') }}/js/html2canvas.js"></script>
    <script>
	function screenshot(id){
html2canvas(document.querySelector("#capture"),{
  backgroundColor: 'black'
}).then(canvas => {
    // Convert canvas to a data URL
    var dataURL = canvas.toDataURL('image/png');
	var link = document.getElementById('link').value;
	var csrf = document.getElementById('csrf').value;
    // Send dataURL to the server using fetch
    fetch('https://peektrack.com/jobs/shareJobcard', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
			'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ image: dataURL,
								link: link,
								email: id}),
    })
    .then(response => {
        if (response.ok) {
            alert('Shared successfully');
            // Handle the response from the server if needed
        } else {
            throw new Error('Error sending image to server');
        }
    })
    .catch(error => {
        console.error(error);
        // Handle errors if any
    });
});
	}
    </script> @endpush
</x-page-template>