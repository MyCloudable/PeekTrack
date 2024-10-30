
    
<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
     <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-auth.navbars.sidebar activePage="jobs" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="{{ $job[0]->job_number }} - Job Card Review"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
		
        <div class="container-fluid py-4"> @csrf @method('PUT')
            <!-- Add input fields for editing record attributes -->
			<button type="button" class="btn btn-warning btn-block " data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-changeJob">Change Job Number</button>
            <label for="job_number">Job Number: <strong>{{ $job[0]->job_number }}</strong></label> <label for="description">Description: <strong>{{ $job[0]->description }}</strong></label>
            <label for="branch">Branch: <strong>{{ $job[0]->branch }}</strong></label> <label for="location">Location: <strong>{{ $job[0]->location }}</strong></label><label for="county">County: <strong>{{ $job[0]->county }}</strong></label>
            <label for="contractor">Contractor: <strong>{{ $job[0]->contractor }}</strong></label> <label for="superintendent">Superintendent: <strong>{{ $jobcard[0]->name }}</strong></label><br>
            <br>
			<div class="col-12 mt-3" style="overflow-y:scroll;max-height: 400px;">
                <div class="card mt-4">
                    <div class="card-header p-3 pt-2">
						@if ($message = Session::get('errorentry'))
																									
													<div id="entryalerts" class="alert alert-info">
                									<strong>{{ $message }}</strong>
													</div>
													
													@endif  
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl mt-n4 float-start">
                            <i class="material-icons opacity-10">folder</i>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-0">Job Documents</h6>
                            </div>
                        </div>
                        <table class="table table-flush" id="datatable-basic">
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Document Type</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Date Uploaded</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($files as $file) @if( $file->active == "0" && $file->type != "Work Order" ) <tr>
                                    <td class="gray-100">
                                        <h5>{{ $file->type }}</h5>
                                    </td>
                                    <td class="gray-100">
                                        <h5>{{ $file->description }}</h5>
                                    </td>
                                    <td class="gray-100">
                                        <h5>{{ $file->created_at }}</h5>
                                    </td>
                                    <td><input type="button" value="View" class="btn btn-warning" onclick="window.open('/uploads/{{ $file->name }}');"></td>
                                </tr> @endif @endforeach
                                <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
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
                            </tr>
                        </thead>
                        <tbody> @foreach ($files as $file) @if( $file->active == "0" && $file->type == "Work Order" ) <tr>
                                <td class="gray-100">
                                    <h5>{{ $file->type }}</h5>
                                </td>
                                <td class="gray-100">
                                    <h5>{{ $file->description }}</h5>
                                </td>
                                <td class="gray-100">
                                    <h5>{{ $file->created_at }}</h5>
                                </td>
                                <td><input type="button" value="View" class="btn btn-warning" onclick="window.open('/uploads/{{ $file->name }}');"></td>
                            </tr> @endif @endforeach
                            <!-- Add more input fields for other attributes -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header p-3 pt-2">
                    <div class="icon icon-lg icon-shape bg-gradient-warning shadow text-center border-radius-xl mt-n4 float-start">
                        <i class="material-icons opacity-10">folder</i>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                        <h6 class="mb-0">Job Card Uploads</h6>
                    </div>
                </div>
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
                    <tbody> @foreach ($jobfiles as $file) @if( $file->doctype == "1" ) <tr>
                            <td class="gray-100">
                                <h5>{{ $file->type }}</h5>
                            </td>
                            <td class="font-weight-bolder opacity-9 ps-2">
                                <h5>{{ $file->description }}</h5>
                            </td>
                            <td class="gray-100">
                                <h5>{{ $file->created_at }}</h5>
                            </td>
                            <td><input type="button" value="View" class="btn btn-warning" onclick="window.open('/uploads/{{ $file->name }}');"></td>
                        </tr> @endif @endforeach
                        <!-- Add more input fields for other attributes -->
                    </tbody>
                </table>
                 </div>
                </div>
            </div>
        <div class="row mt-4">
            <div class="col-12">
					@if (count($pos) > 0)
					<button type="button" class="btn btn-warning btn-lg mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardpos">View PO(s)</button>
					@endif

            </div>
				<form method="POST" id="form" action="{{ route('jobs.entryupdate') }}">
                    <input type="hidden" name="referrer" value="1">
                    <label>Work Date </label> <input type="date" value="{{ $jobcard[0]->workdate }}" name="workdate">
					<div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mt-3 mb-2 ms-3">Production</h6><button type="button" class="btn btn-warning btn-block " data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-addproduction">Add Production</button>
                            </div> @csrf 
								<input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
								<input type="hidden" name="job_number" value="{{ $jobcard[0]->job_number }}">
                                <input type="hidden" name="jobid" value="{{ $job[0]->id }}">
                                <input type="hidden" name="userId" value="{{Auth::user()->id}}">
                                <input type="hidden" name="user" value="{{Auth::user()->name}}">
                                <div class="table-responsive">
                                    <table class="table table-flush" id="datatable-production">
                                        <thead class="thead-light">
                                            <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Quantity</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Measurement</th>
									<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Road Name</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Surface Type</th>
									<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Notes</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Complete</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($production as $jobdata) <tr>
                                    <td><input type="hidden" name="phase[]" value="{{ $jobdata->phase}}"><input class="form-control-sm" type="hidden" name="pid[]" value="{{ $jobdata->id }}">
                                        <h5>{{ $jobdata->phase }}</h5>
                                    </td>
                                    <td><input type="hidden" name="description[]" value="{{ $jobdata->description }}">
                                        <h5>{{ $jobdata->description }}</h5>
                                    </td>
                                    <td>
                                        <h5><input type="number" name="qty[]" step="0.001" style="width: 100px;" value="{{ $jobdata->qty }}"></h5>
                                    </td>
                                    <td>
                                        <h5><input class="form-control-sm" type="hidden" name="unit_of_measure[]" value="{{ substr($jobdata->unit_of_measure, 0, strlen($jobdata->unit_of_measure)) }}"/>{{ substr($jobdata->unit_of_measure, 0, strlen($jobdata->unit_of_measure)) }}</h5>
                                    </td>
									<td>
                                        <h5><input type="text" name="road_name[]" style="width: 175px;" value="{{ $jobdata->road_name }}"></h5>
                                    </td>
									 <td>
                                        <h5><input type="text" name="surface_type[]" style="width: 150px;" value="{{ $jobdata->surface_type }}"></h5>
                                    </td>
                                    <td>
                                        <h5><input type="text" name="mark_mill[]" style="width: 200px;" value="{{ $jobdata->mark_mill }}"></h5>
                                    </td>
									@if ($jobdata->phase_item_complete == 0) <td><input type="checkbox" id="{{$jobdata->id}}" onchange="changeCheckboxValue(this.id);" value="false" name="phase_item_complete[{{$jobdata->id}}]">
                                        
                                    </td> @endif @if ($jobdata->phase_item_complete == 1) <td>
                                        <input type="checkbox" name="phase_item_complete[{{$jobdata->id}}]" id="{{$jobdata->id}}" onchange="changeCheckboxValue(this.id);" value="true" checked>
                                    </td> @endif
                                    <td><a href="{{ route('jobs.removeLineJBRP', ['link' => $jobcard[0]->link, 'id' => $jobdata->id, 'ref' => '1']) }}" class="btn btn-danger" onclick="return confirm('Are you sure?');">Remove</a></td>
                                </tr> @endforeach
                                <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
            </div>
			</div>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mt-3 mb-2 ms-3">Material</h6><button type="button" class="btn btn-warning btn-block " data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-addmaterial">Add Material</button>
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
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                            </tr>
                        </thead>
                        <tbody> @foreach ($material as $jobmaterial) <tr>
                                <td><h5>{{ $jobmaterial->phase }}</h5><input class="form-control-sm" type="hidden" name="mphase[]" value="{{ $jobmaterial->phase }}"><input class="form-control-sm" type="hidden" name="mid[]" value="{{ $jobmaterial->id }}"></td>
                                <td><h5>{{ $jobmaterial->description }}</h5><input class="form-control-sm" type="hidden" name="mdescription[]" value="{{ $jobmaterial->description }}"></td>
                                <td><input class="form-control-sm" type="number" name="mqty[]" step="0.001" style="width: 75px;" value="{{ $jobmaterial->qty }}"></td>
                                <td><h5>{{ $jobmaterial->unit_of_measure }}</h5><input class="form-control-sm" type="hidden" name="munit[]" value="{{ $jobmaterial->unit_of_measure }}"></td>
                                <td><input class="form-control-sm" type="text" name="msupplier[]" value="{{ $jobmaterial->supplier }}">
                                </td>
                                <td><input class="form-control-sm" type="text" name="mbatch[]" value="{{ $jobmaterial->batch }}"></td>
                                <td><a href="{{ route('jobs.removeLineJBRM', ['link' => $jobcard[0]->link, 'id' => $jobmaterial->id, 'ref' => '1']) }}" class="btn btn-danger" onclick="return confirm('Are you sure?');">Remove</a></td>
                            </tr> @endforeach
                            <!-- Add more input fields for other attributes -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mt-3 mb-2 ms-3">Equipment</h6><button type="button" class="btn btn-warning btn-block " data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-addequipment">Add Equipment</button>
                <div class="card-body mb-4">
                    <div class="table-responsive">
                        <table class="table table-flush" id="datatable-equipment">
                            <thead class="thead-light">
                                <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Truck</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Hours</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                            </tr>
                        </thead>
                        <tbody> @foreach ($equipment as $jobequipment) <tr>
                                <td><input type="hidden" name="ephase[]" value="{{ $jobequipment->phase }}"><input class="form-control-sm" type="hidden" name="eid[]" value="{{ $jobequipment->id }}">
                                    <h5>{{ $jobequipment->phase }}</h5>
                                </td>
                                <td><input type="hidden" name="edescription[]" value="{{ $jobequipment->description }}">
                                    <h5>{{ $jobequipment->description }}</h5>
                                </td>
                                <td>
                                    <h5><select class="form-control-sm" name="etruck[]" required>
                                            <option value="">Make a selection</option> @if ($jobequipment->truck == "03-99") <option value="03-99" selected>Crew Cab Truck</option> @else <option value="03-99">Crew Cab Truck</option> @endif @if ($jobequipment->truck == "10-99") <option value="10-99" selected>Paint Truck</option> @else <option value="10-99">Paint Truck</option> @endif @if ($jobequipment->truck == "21-99") <option value="21-99" selected>Haul Truck</option> @else <option value="21-99">Haul Truck</option> @endif @if ($jobequipment->truck == "30-99") <option value="30-99" selected>Longline Truck</option> @else <option value="30-99">Longline Truck</option> @endif @if ($jobequipment->truck == "32-99") <option value="32-99" selected>Handline Truck</option> @else <option value="32-99">Handline Truck</option> @endif @if ($jobequipment->truck == "37-99") <option value="37-99" selected>Marker Truck</option> @else <option value="37-99">Marker Truck</option> @endif @if ($jobequipment->truck == "38-99") <option value="38-99" selected>Sealer Truck</option> @else <option value="38-99">Sealer Truck</option> @endif @if ($jobequipment->truck == "39-99") <option value="39-99" selected>Knock Up Truck</option> @else <option value="39-99">Knock Up Truck</option> @endif @if ($jobequipment->truck == "40-99") <option value="40-99" selected>Removal Truck</option> @else <option value="40-99">Removal Truck</option> @endif @if ($jobequipment->truck == "42-96") <option value="42-96" selected>Vacuum Truck</option> @else <option value="42-96">Vacuum Truck</option> @endif @if ($jobequipment->truck == "42-98") <option value="42-98" selected>Tape Truck</option> @else <option value="42-98">Tape Truck</option> @endif @if ($jobequipment->truck == "42-99") <option value="42-99" selected>Waterblast Truck</option> @else <option value="42-99">Waterblast Truck</option> @endif @if ($jobequipment->truck == "42-97") <option value="42-97" selected>Epoxy Truck</option> @else <option value="42-97">Epoxy Truck</option> @endif
                                        </select></h5>
                                </td>
                                <td>
                                    <h5><input type="number" name="ehours[]" step="0.001" style="width: 75px;" value="{{ $jobequipment->hours }}"></h5>
                                </td>
                                <td><a href="{{ route('jobs.removeLineJBRE', ['link' => $jobcard[0]->link, 'id' => $jobequipment->id, 'ref' => '1']) }}" class="btn btn-danger" onclick="return confirm('Are you sure?');">Remove</a></td>
                            </tr>
                            </tr> @endforeach
                            <!-- Add more input fields for other attributes -->
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
                <center>
                    <label>
                        <h2>Field Notes</h2>
                    </label><br> @foreach ($jobnotes as $jobnote) @if ($jobnote->note_type == "JobCardNote") <textarea rows="4" cols="100" disabled> {{ $jobnote->username }} - {{ $jobnote->created_at }} : {{ $jobnote->note }}</textarea> @endif @endforeach <br><br>
                    <label>
                        <h2>Review Notes</h2>
                    </label><br> @foreach ($jobnotes as $jobnote) @if ($jobnote->note_type == "ReviewNote") <textarea rows="4" cols="100" disabled> {{ $jobnote->username }} - {{ $jobnote->created_at }} : {{ $jobnote->note }}</textarea> <br>@endif @endforeach <textarea rows="4" cols="100" name="review_notes"></textarea>
                </center>
                <br><br>
                <span><button type="button" class="btn btn-success btn-block mt-4" data-bs-toggle="modal" style="top: 0%;left: 20%;" data-bs-target="#approve">Approve</button>
                    <button type="submit" class="btn btn-warning btn-block mt-4" style="top: %;left: 40%;">Update</button>
                <button type="button" class="btn btn-danger btn-block mt-4" data-bs-toggle="modal" style="top: 0%;left: 60%;" data-bs-target="#reject-submit">Reject</button></span>
                </form>
                <br>
                <br>
                <br>
                <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
            </div>
			
    </main>
    <x-plugins></x-plugins>
<div class="modal fade" id="modal-addproduction" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="overflow-y:scroll;max-height: 600px;">
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($proditems as $file) <tr>
                                    <td>{{ $file->phase }}</td>
                                    <td>{{ $file->description }}</td>
                                    <td><button class="btn btn-warning" onclick="addItem('{{ $file->phase }}','{{ $file->description }}','{{ $file->est_qty }}','{{ $file->unit_of_measure }}');">Add</button></td>
                                </tr> @endforeach
                                <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-addmaterial" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="overflow-y:scroll;max-height: 600px;">
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($materialitems as $file) <tr>
                                    <td>{{ $file->phase }}</td>
                                    <td>{{ $file->description }}</td>
                                    <td><button class="btn btn-warning" onclick="addItem('{{ $file->phase }}','{{ $file->description }}','{{ $file->est_qty }}','{{ $file->unit_of_measure }}');">Add</button></td>
                                </tr> @endforeach
                                <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
	    <div class="modal fade" id="modal-addequipment" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="overflow-y:scroll;max-height: 600px;">
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Phase</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Description</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($equipmentitems as $file) <tr>
                                    <td>{{ $file->phase }}</td>
                                    <td>{{ $file->description }}</td>
                                    <td><button class="btn btn-warning" onclick="addItem('{{ $file->phase }}','{{ $file->description }}','{{ $file->est_qty }}','{{ $file->unit_of_measure }}');">Add</button></td>
                                </tr> @endforeach
                                <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
		<div class="modal fade" id="modal-jobcardpos" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Date</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Signer</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">PO Number</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Note</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2" >Signature</th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($pos as $po) <tr>
                                    <td>{{ $po->created_at}}</td>
                                    <td>{{ $po->signer_name }}</td>
									<td>{{ $po->po_number }}</td>
                                    <td>{{ $po->notes }}</td>
                                    <td style="background-color: #fff; !important "><img width="100" height="30" src="{{ $po->signature }}" alt="Red dot" /></td>
                                </tr>@endforeach
                                <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="reject-submit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body" style="background-color: black;">
                    <div class="py-3 text-center"> 
					Please explain the reason for the rejection? 
					<form method="post" id="rejectForm" action="{{ route('jobs.rejectcard')}}">
                            <!-- We display the details entered by the user here -->
                            <table class="table">
                                <tr>
                                    <th>Note</th>
                                    <td><textarea name="note"></textarea></td>
                                </tr>
                            </table>
                            <input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
                            <input type="hidden" name="username" value="{{Auth::user()->name}}">
							<input type="hidden" name="email" value="{{Auth::user()->email}}">
							<input type="hidden" name="userId" value="{{ $jobcard[0]->userId }}">
                    </div> @csrf <input type="submit" class="btn-warning" form="rejectForm" value="Reject Job Card" />
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="approve" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body" style="background-color: black;">
                    <div class="py-3 text-center"> Are you sure you want to approve this job card? 
					<form method="post" id="approveForm" action="{{ route('jobs.updatecard')}}">
                            <!-- We display the details entered by the user here -->
                            <input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
                            <input type="hidden" name="username" value="{{Auth::user()->name}}">
                    </div> @csrf <input type="submit" class="btn-warning" form="approveForm" value="Approve Job Card" />
                    </form>
                </div>
            </div>
        </div>
    </div> 
		        <div class="modal fade" id="modal-changeJob" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body" style="background-color: black;">
                    <div class="py-3 text-center"> 
					<form method="post" id="modal-changeJob" action="{{ route('jobs.changeJobNum') }}">
					@csrf
							<input type="hidden" id="link" name="link" value="{{ $jobcard[0]->link }}">
							<input type="hidden" id="link" name="job_number" value="{{ $jobcard[0]->job_number }}">
							<input type="hidden" id="csrf-token" name="csrf-token" value="{{ csrf_token() }}">
							<input type="hidden" id="userId" name="userId" value="{{Auth::user()->id}}">
							<select name="jobnumber">
							@foreach ($jobnumbers as $jobnumber)
							<option value="{{ $jobnumber->job_number }}">{{ $jobnumber->job_number }}</>
							@endforeach
							</select>
							<div>
							<button type="submit" class=" btn btn-warning" id="save">Save</button>
							</form>
                </div>
            </div>
        </div>
    </div> 
    </div>
	


	@push('js') <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
	<script>
function getSig(signature){
	
		var w = window.open("");
        w.document.write(signature.outerHTML);
	
}


</script>
    <script>
        /* Prevent accidental back navigation click */
history.pushState(null, document.title, location.href);
window.addEventListener('popstate', function (event)
{
    const leavePage = confirm("Any unsaved changes will be lost if you continue.");
    if (leavePage) {
        history.back(); 
    } else {
        history.pushState(null, document.title, location.href);
    }  
});
        // Get references to the button and the div
        const button = document.getElementById('AddProduction');
        const table = document.getElementById('datatable-production');
        const button2 = document.getElementById('AddMaterial');
        const table2 = document.getElementById('datatable-material');
        const button3 = document.getElementById('AddEquipment');
        const table3 = document.getElementById('datatable-equipment');
        // Function to insert HTML into the div
         function insertHTML() {
            const newRow = document.createElement('tr');
            newRow.innerHTML = '<td><input class="form-control-sm" size="5" style="background-color: white;color: black !important;" type="text" name="phase[]" value="98-09000" placeholder="Phase" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" type="text" size="25" style="background-color: white;color: black !important;" name="description[]" value="Unestimated Production - '+String(table.rows.length)+'" placeholder="Description" required></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" name="qty[]" step="0.001" placeholder="0" required></td>' 
			+
			'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="unit_of_measure[]" required><option value="">Make a selection</option><option value="Each">Each</><option value="LM">Linear Miles</option><option value="LF">Linear Feet</option><option value="GM">Gross Miles</option><option value="GF">Gross Feet</option><option value="GLF">Gross Linear Feet</option><option value="SF">Square Feet</option><option value="SY">Square Yards</option></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="road_name[]" placeholder="Road"></td>' 
			+			
			'<td><select class="form-control-sm" style="background-color: white;color: black !important;" name="surface_type[]"><option value="NA">NA</><option value"Milling">Milling</><option value"Traffic Shift">Traffic Shift</><option value"Patch/Repair">Patch/Repair</><option value"Leveling">Leveling</><option value"Detour">Detour</><option value"Final">Final</><option value="Current">Current</></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mark_mill[]" placeholder="Mark Mill" value="NA"></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="checkbox" name="phase_item_complete[]" placeholder="Phase Complete"></td>' 
			+ 
			'<td></td>' 
			+ 
			'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table.appendChild(newRow);
        }

        function insertHTML2() {
            const newRow2 = document.createElement('tr');
            newRow2.innerHTML = '<td><input class="form-control-sm" size="15" style="background-color: white;color: black !important;" type="text" name="mphase[]" value="98-19999" placeholder="Phase" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" type="text" size="25" style="background-color: white;color: black !important;" name="mdescription[]" value="Unestimated Materials - '+String(table2.rows.length)+'" placeholder="Description" required></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;"  type="number" name="mqty[]" step="0.001" value="0" placeholder="0"></td>' 
			+ 
			'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="munit[]"><option value="">Make a selection</option><option value"GAL">Gallons</option><option value"EA">Each</option><option value"TON">Tons</option><<option value"FT">Feet</option><option value"SY">Square Yards</option></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="msupplier[]" placeholder="Supplier"></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mbatch[]" placeholder="Batch"></td>' 
			+ 
			'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table2.appendChild(newRow2);
        }

        function insertHTML3() {
            const newRow3 = document.createElement('tr');
            newRow3.innerHTML = '<td><input class="form-control-sm" size="5" style="background-color: white;color: black !important;" type="text" name="ephase[]" @if (isset($equipment[0]->phase)) value="{{ $equipment[0]->phase }}" @else value="10-10000" @endif placeholder="Phase" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" type="text" size="25" style="background-color: white;color: black !important;" name="edescription[]" @if (isset($equipment[0]->description)) value="{{ $equipment[0]->description }}" @else value="Added Equipment - '+String(table3.rows.length)+'" @endif  placeholder="Added Equipment" required></td>' 
			+ 
			'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="etruck[]"><option value="">Make a selection</option><option value="3-99">Crew Cab Truck</option><option value="10-99">Paint Truck</option><option value="21-99">Haul Truck</option><option value="30-99">Longline Truck</option><option value="32-99">Handline Truck</option><option value="37-99">Marker Truck</option><option value="38-99">Sealer Truck</option><option value="39-99">Knock Up Truck</option><option value="40-99">Removal Truck</option><option value="42-96">Vacuum Truck</option><option value="42-97">Tape Truck</option><option value="42-98">Waterblast Truck</option><option value="42-99">Epoxy Truck</option></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" name="ehours[]" value="0" placeholder="0"></td>' 
			+ 
			'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table3.appendChild(newRow3);
        }

        function addItem(id, desc, est, unit) {
            if (parseInt(id.substring(3, 8)) < 10000) {
                const production = document.createElement('tr');
                production.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="background-color: white;color: black !important;" type="text" name="phase[]" value="' + id + '" placeholder="Phase" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" type="text" size="50" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="description[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" name="qty[]" step="0.001" placeholder="0" value="0" ></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="unit_of_measure[]"  value="' + unit + '" placeholder="Unit of Measure" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" size="50" name="road_name[]" placeholder="Road"></td>' 
				+  
				'<td><select class="form-control-sm" style="background-color: white;color: black !important;" name="surface_type[]"><option value="">NA</><option value="Each">Each</><option value="Milling">Milling</><option value="MOT">MOT</><option value="Reclamation">Reclamation</><option value"Shoulder Widening">Shoulder Widening</><option value="Traffic Shift">Traffic Shift</><option value="Patch/Repair">Patch/Repair</><option value="Leveling">Leveling</><option value="Detour">Detour</><option value="Final">Final</><option value="Current">Current</></select></td>'
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mark_mill[]" placeholder="Notes"></td>' 
				+ 
				'<td><input class="form-control-sm" type="checkbox" name="phase_item_complete[]" placeholder="Phase Complete"></td>' 
				+ 
				'<td></td>' 
				+
				'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
                // Append the new row to the table
                table.appendChild(production);
            }
            if (parseInt(id.substring(3, 8)) == 10000) {
                const material = document.createElement('tr');
                material.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="ephase[]" value="' + id + '" placeholder="Phase" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" type="text" size="50" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="edescription[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="etruck[]"><option value="">Make a selection</option><option value="03-99">Crew Cab Truck</option><option value="10-99">Paint Truck</option><option value="21-99">Haul Truck</option><option value="30-99">Longline Truck</option><option value="32-99">Handline Truck</option><option value="37-99">Marker Truck</option><option value="38-99">Sealer Truck</option><option value="39-99">Knock Up Truck</option><option value="40-99">Removal Truck</option><option value="42-96">Vacuum Truck</option><option value="42-98">Tape Truck</option><option value="42-99">Waterblast Truck</option><option value="42-97">Epoxy Truck</option></select></td>'
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" name="ehours[]" placeholder="0" value="0"></td>' 
				+ 
				'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
                // Append the new row to the table
                table3.appendChild(material);
            }
            if (parseInt(id.substring(3, 8)) > '10000') {
                const equipment = document.createElement('tr');
                equipment.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="mphase[]" value="'+ id + '" placeholder="Phase" readonly></td>'
				+ 
				'<td><input class="form-control-sm" type="text" size="25" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="mdescription[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number"  step="0.001" name="mqty[]" placeholder="0" value="0" ></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="munit[]"  value="' + unit + '" placeholder="Unit of Measure" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="msupplier[]" placeholder="Supplier"></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mbatch[]" placeholder="Batch #"></td>' 
				+ 
				'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
                // Append the new row to the table
                table2.appendChild(equipment);
            }
        }
        // Add a click event listener to the button
        button.addEventListener('click', insertHTML);
        button2.addEventListener('click', insertHTML2);
        button3.addEventListener('click', insertHTML3);

        function SomeDeleteRowFunction(o) {
            //no clue what to put here?
            var p = o.parentNode.parentNode;
            p.parentNode.removeChild(p);
        }
		
		function validateForm() {
		if (table.rows.length > 1 && table2.rows.length < 2 || table.rows.length < 2 && table2.rows.length > 1) {

	    alert("You must have both production and material line items on your job card. Please correct and then save again.");
	
		return false;
		}
		if (table.rows.length > 1 && table2.rows.length > 1 && table3.rows.lenght < 2) {

	    alert("You must have equipment on your job card!");
	
		return false;
		}
		

			
}
		function changeCheckboxValue(checkboxId) {
    var $checkbox = $('#' + checkboxId);
    if($checkbox.is(':checked')) {
        $checkbox.val('true');
    } 
	else{
        
		$checkbox.val('false');
		$checkbox.removeAttr('checked');
    }
}
    </script> @endpush
</x-page-template>