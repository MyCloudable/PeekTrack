<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-auth.navbars.sidebar activePage="jobs" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Job Card"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4"> @method('PUT')
            <!-- Add input fields for editing record attributes -->
			<a href="{{ route('jobs.removeJC', ['link' => $jobcard[0]->link,'id' => $jobinfo[0]->id]) }}" class="confirm btn btn-warning" onclick="return confirm('Are you sure?');">Delete Job Card</a><br>
            <label for="job_number">Job Number: <strong>{{ $jobinfo[0]->job_number }}</strong></label> <label for="description">Description: <strong>{{ $jobinfo[0]->description }}</strong></label>
            <label for="branch">Branch: <strong>{{ $jobinfo[0]->branch }}</strong></label> <label for="location">Location: <strong>{{ $jobinfo[0]->location }}</strong></label>
            <label for="contractor">Contractor: <strong>{{ $jobinfo[0]->contractor }}</strong></label> <label for="completion_date">Est. Completion Date: <strong>{{ $jobinfo[0]->completion_date }}</strong></label><br>
            <label for="completion_date">Entry Date: <strong>{{ $jobcard[0]->created_at }}</strong></label><br>
            <div class="row mt-4">
                <div class="col-12">
                    <button type="button" class="btn btn-warning btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-uploadfile">Upload File</button>&nbsp&nbsp&nbsp&nbsp 					@if( $files->contains('doctype', '1'))
					<button type="button" class="btn btn-success btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardfiles">View Uploads</button>
				&nbsp&nbsp&nbsp&nbsp
				@endif
				@if( !$files->contains('doctype', '1'))
					<button type="button" class="btn btn-danger btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardfiles">View Uploads</button>
				&nbsp&nbsp&nbsp&nbsp
				@endif
					@if (count($pos) > 0)
					<button type="button" class="btn btn-warning btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardpos">View POs</button>
					@endif

                </div>
				<center> @foreach ($jobnotes as $jobnote) @if ($jobnote->note_type == "Rejection") <label>Rejection Note</label><br>
                        <textarea rows="2" cols="100" disabled> {{ $jobnote->username }} - {{ $jobnote->created_at }} : {{ $jobnote->note }}</textarea></br> @endif @endforeach <br><br></center>
						                            
													
													@if ($message = Session::get('errorentry'))
																									
													<div id="entryalerts" class="alert alert-info">
                									<strong>{{ $message }}</strong>
													</div>
													
													@endif    
													
										 
                <form method="POST" id="form" onSubmit="return validateForm()" action="{{ route('jobs.entryupdate') }}">
                    <input type="hidden" name="referrer" value="0">
                    
					<div class="col-12">
                        <div class="card mb-4">

                            <div class="card-header">
                                <h6 class="mt-3 mb-2 ms-3">Production</h6><button type="button" class="btn btn-warning btn-block " data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-addproduction">Add Production</button>
                            </div> @csrf 
								<input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
								<input type="hidden" name="job_number" value="{{ $jobcard[0]->job_number }}">
                                <input type="hidden" name="jobid" value="{{ $jobinfo[0]->id }}">
                                <input type="hidden" name="id" value="{{ $jobinfo[0]->id }}">
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
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Purchase Order</th>
												<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody> @foreach ($production as $jobdata) <tr>
                                                <td><h5>{{ $jobdata->phase }}</h5><input class="form-control-sm" type="hidden" name="phase[]" value="{{ $jobdata->phase }}"><input class="form-control-sm" type="hidden" name="pid[]" value="{{ $jobdata->id }}"></td>
                                                <td><h5>{{ $jobdata->description }}</h5><input class="form-control-sm" type="hidden" name="description[]" value="{{ $jobdata->description }}"></td>
												<td><input class="form-control-sm" type="number" name="qty[]" step="0.001" value="{{ $jobdata->qty }}"></td>
                                                <td><h5>{{ $jobdata->unit_of_measure }}</h5><input class="form-control-sm" type="hidden" name="unit_of_measure[]" value="{{ $jobdata->unit_of_measure }}"></td>
                                                <td><input class="form-control-sm" type="text" name="road_name[]" value="{{ $jobdata->road_name }}"></td>
												<td><input class="form-control-sm" type="text" name="surface_type[]" value="{{ $jobdata->surface_type }}"></td>
												<td><input class="form-control-sm" type="text" name="mark_mill[]" value="{{ $jobdata->mark_mill }}"></td>
                                                @if ($jobdata->phase_item_complete == 0) <td><input type="checkbox" id="{{$jobdata->id}}" onchange="changeCheckboxValue(this.id);" value="false" name="phase_item_complete[{{$jobdata->id}}]">   
                                    </td> @endif @if ($jobdata->phase_item_complete == 1) <td>
                                        <input type="checkbox" name="phase_item_complete[{{$jobdata->id}}]" id="{{$jobdata->id}}" onchange="changeCheckboxValue(this.id);" value="true" checked>
                                    </td> @endif
                                                <td><input type="button" class="btn btn-warning" value="Create PO" id="myCheck" onclick="openSig('{{$jobdata->phase}}')" name="po[]"></td>
												<td><a href="{{ route('jobs.removeLineJBRP', ['link' => $jobcard[0]->link, 'id' => $jobdata->id, 'ref' => '0']) }}" class="btn btn-danger" onclick="return confirm('Are you sure?');">Remove</a><td>
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
                                            <td><input class="form-control-sm" type="number" step="0.001" name="mqty[]" value="{{ $jobmaterial->qty }}"></td>
                                            <td><h5>{{ $jobmaterial->unit_of_measure }}</h5><input class="form-control-sm" type="hidden" name="munit[]" value="{{ $jobmaterial->unit_of_measure }}"></td>
                                            <td><input class="form-control-sm" type="text" name="msupplier[]" value="{{ $jobmaterial->supplier }}">
                                            </td>
                                            <td><input class="form-control-sm" type="text" name="mbatch[]" value="{{ $jobmaterial->batch }}"></td>
											<td><a href="{{ route('jobs.removeLineJBRM', ['link' => $jobcard[0]->link, 'id' => $jobmaterial->id, 'ref' => '0']) }}" class="btn btn-danger" onclick="return confirm('Are you sure?');">Remove</a></td>
                                        </tr> @endforeach
                                        <!-- Add more input fields for other attributes -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mt-3 mb-2 ms-3">Equipment</h6><button type="button" class="btn btn-warning btn-block " data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-addequipment">Add Equipment</button>
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
									<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
                                </tr>
                            </thead>
                            <tbody> @foreach ($equipment as $jobequipment) <tr>
                                    <td><h5>{{ $jobequipment->phase }}</h5><input class="form-control-sm" type="hidden" name="ephase[]" value="{{ $jobequipment->phase }}"><input class="form-control-sm" type="hidden" name="eid[]" value="{{ $jobequipment->id }}"></td>
                                    <td><h5>{{ $jobequipment->description }}</h5><input class="form-control-sm" type="hidden" name="edescription[]" value="{{ $jobequipment->description }}"></td>
                                    <td><select class="form-control-sm" name="etruck[]" required>
                                            <option value="">Make a selection</option> 
											@if ($jobequipment->truck == "03-99") 
												<option value="03-99" selected>Crew Cab Truck</option> 
											@else 
												<option value="03-99">Crew Cab Truck</option> 
											@endif @if ($jobequipment->truck == "10-99") 
												<option value="10-99" selected>Paint Truck</option> 
											@else <option value="10-99">Paint Truck</option> 
											@endif @if ($jobequipment->truck == "21-99") 
												<option value="21-99" selected>Haul Truck</option> 
											@else <option value="21-99">Haul Truck</option> 
											@endif @if ($jobequipment->truck == "30-99") 
												<option value="30-99" selected>Longline Truck</option> 
											@else <option value="30-99">Longline Truck</option> 
											@endif @if ($jobequipment->truck == "32-99") 
												<option value="32-99" selected>Handline Truck</option> 
											@else <option value="32-99">Handline Truck</option> 
											@endif @if ($jobequipment->truck == "37-99") 
												<option value="37-99" selected>Marker Truck</option> 
											@else <option value="37-99">Marker Truck</option> 
											@endif @if ($jobequipment->truck == "38-99") 
												<option value="38-99" selected>Sealer Truck</option> 
											@else <option value="38-99">Sealer Truck</option> 
											@endif @if ($jobequipment->truck == "39-99") 
												<option value="39-99" selected>Knock Up Truck</option> 
											@else <option value="39-99">Knock Up Truck</option> 
											@endif @if ($jobequipment->truck == "40-99") 
												<option value="40-99" selected>Removal Truck</option> 
											@else <option value="40-99">Removal Truck</option> 
											@endif @if ($jobequipment->truck == "42-96") 
												<option value="42-96" selected>Vacuum Truck</option> 
											@else <option value="42-96">Vacuum Truck</option> 
											@endif @if ($jobequipment->truck == "42-98") 
												<option value="42-98" selected>Tape Truck</option> 
											@else <option value="42-98">Tape Truck</option> 
											@endif @if ($jobequipment->truck == "42-99") 
												<option value="42-99" selected>Waterblast Truck</option> 
											@else <option value="42-99">Waterblast Truck</option> 
											@endif @if ($jobequipment->truck == "42-97") 
												<option value="42-97" selected>Epoxy Truck</option> 
											@else <option value="42-97">Epoxy Truck</option> 
											@endif
                                        </select></td>
										
                                    <td><input class="form-control-sm" type="number" step="0.001" name="ehours[]" value="{{ $jobequipment->hours }}"></td>
<td><a href="{{ route('jobs.removeLineJBRE', ['link' => $jobcard[0]->link, 'id' => $jobequipment->id, 'ref' => '0']) }}" class="btn btn-danger" onclick="return confirm('Are you sure?');">Remove</a></td>									@endforeach
                                    <!-- Add more input fields for other attributes -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <center>
							<div class="col-md-12">
                            
                            
    
                <label>Field Notes</label><br> @foreach ($jobnotes as $jobnote) @if ($jobnote->note_type == "JobCardNote") <textarea rows="2" cols="100" readonly> {{ $jobnote->username }} - {{ $jobnote->created_at }} : {{ $jobnote->note }}</textarea> @endif @endforeach <br><br>
                <label>Add Note</label><br>

                <textarea rows="4" cols="100" name="notes"></textarea>
                <div class="col-12">
                    <button type="button" class="btn btn-warning btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-uploadfile">Upload File</button>&nbsp&nbsp&nbsp&nbsp 
					@if( $files->contains('doctype', '1'))
					<button type="button" class="btn btn-success btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardfiles">View Uploads</button>
				&nbsp&nbsp&nbsp&nbsp
				@endif
				@if( !$files->contains('doctype', '1'))
					<button type="button" class="btn btn-danger btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardfiles">View Uploads</button>
				&nbsp&nbsp&nbsp&nbsp
				@endif
					@if (count($pos) > 0)
					
				<button type="button" class="btn btn-warning btn-block mt-4" data-bs-toggle="modal" data-bs-target="#modal-jobcardpos">View POs</button>
					@endif

                </div><br><label>Work Date </label> <input type="date" value="{{ $jobcard[0]->workdate }}" name="workdate">
            </center>
            <br><br>
            <button type="submit" class="btn btn-warning">Save Job Card</button>
			@if( $files->contains('doctype', '1'))
			<button type="submit" class="btn btn-success" form="submit" style="float: right;">Submit Job Card</button>
		@endif

       
		
		 </form>
		 
        <form method="post" id="submit" onSubmit="return validateForm()" action="{{ route('jobs.submit') }}"> @csrf 
	
	<input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
            <input type="hidden" name="jobid" value="{{ $jobinfo[0]->id }}">
            
        </form>
		
        <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>
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
                        <form action="{{route('jobcardUpload')}}" method="post" enctype="multipart/form-data"> @csrf @if ($message = Session::get('success')) <div id="filesuccess" class="alert alert-success">
                                <strong>{{ $message }}</strong>
                            </div> @endif @if (count($errors) > 0) <div id="fileerror" class="alert alert-danger">
                                <ul> @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach </ul>
                            </div> @endif <div class="custom-file"></div>
                            <input type="hidden" name="link" value="{{ $jobcard[0]->link }}">
                            <input type="hidden" name="jobnumber" value="{{ $jobinfo[0]->job_number }}">
                            <input type="file" name="file" class="custom-file-input" id="chooseFile">
                            <br><br><br>
                            <h4 class="text-gradient text-warning mt-4">Document Type:</h4>
                            <select class="form-control" name="doctype" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: white;width: 100%;margin-bottom: 1rem;vertical-align: top;border-color: #f0f2f5;" required>
                                <option value="" readonly selected>Click here to select type</option>
                                <option value="Green Book">Green Book</option>
                                <option value="Label">Label</option>
                                <option value="Meter Picture">Meter Reading</option>
                                <option value="JobCardOther">Other</option>
                            </select>
                            <input class="form-control" type="text" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: white;width: 100%;margin-bottom: 1rem;vertical-align: top;border-color: #f0f2f5;" name="docdesc" placeholder="Description" required>
                            <button type="submit" name="submit" class="btn btn-warning btn-block mt-4" style="float: left;"> Upload New File </button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-jobcardfiles" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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
	    <div class="modal fade" id="modal-signature" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body" style="background-color: black;">
                    <div class="py-3 text-center">
                            <div class="col-md-12">
                            <div style="background:gray;"> 
							<canvas id="signature-pad" id="signature-pad" class="signature-pad" width=800 height=200></canvas>
							</div></br>
							<input type="hidden" id="link" name="link" value="{{ $jobcard[0]->link }}">
							<input type="hidden" id="userId" name="userId" value="{{Auth::user()->id}}">
                            <input type="hidden" id="job_number" name="job_number" value="{{ $jobinfo[0]->job_number }}">
							<input type="text" id="signer_name" name="signer_name" placeholder="Signer's Name"></br></br>
							<input type="text" id="ponumber" name="ponumber" placeholder="PO Number"></br></br>
							<input type="hidden" id="csrf-token" name="csrf-token" value="{{ csrf_token() }}">
							<textarea rows="4" cols="50" id="ponote" name="ponote" placeholder="Notes"></textarea>
							<div>
							<button class=" btn btn-warning" id="save">Save</button>
							<button class=" btn btn-warning" id="clear">Clear</button>
							</div>

							</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <div class="modal fade" id="modal-signature" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body" style="background-color: gray-200;">
                    <div class="py-3 text-center">
                                       <div style=" text-align: center">
            <x-creagia-signature-pad />
        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>	@push('js') 
	<script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
 <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script> 
    <link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/south-street/jquery-ui.css" rel="stylesheet"> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    

 
<script type="text/javascript">



   document.addEventListener('DOMContentLoaded', function() {
            // Delay the hiding of the div by 5 seconds (5000 milliseconds)
            setTimeout(function() {
                var myDiv = document.getElementById('entryalerts');
                myDiv.style.display = 'none'; // Hide the div
            }, 3000); // 5000 milliseconds = 5 seconds
        });


var signaturePad = new SignaturePad(document.getElementById('signature-pad'), {
  backgroundColor: 'rgba(211, 211, 211, 0)',
  penColor: 'rgb(0, 0, 0)'
});
var saveButton = document.getElementById('save');
var cancelButton = document.getElementById('clear');

var userId = document.getElementById("userId").value;
var job_number = document.getElementById("job_number").value;
var link = document.getElementById("link").value;
var csrf = document.getElementById("csrf-token").value;

saveButton.addEventListener('click', function (event) {
  var data = signaturePad.toDataURL('image/png');
  var signer_name = document.getElementById("signer_name").value;
var ponumber = document.getElementById("ponumber").value;
var note = document.getElementById("ponote").value;
    fetch('https://peektrack.com/uploadpo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
			'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({  signature: data,
								link: link,
								job_number: job_number,
								signer_name: signer_name,
								notes: note,
								userId: userId,
								po_number: ponumber }),
    })
    .then(response => {
        if (response.ok) {
            alert('Saved successfully');
			signaturePad.clear();
			document.getElementById("signer_name").value = "";
			document.getElementById("ponumber").value = "";
			document.getElementById("ponote").value = "";
			 $('#modal-signature').modal('toggle');
			 window.location.href = window.location.href;
            // Handle the response from the server if needed
        } else {
            throw new Error('Error sending po to server');
        }
    })
    .catch(error => {
        console.error(error);
        // Handle errors if any
    });
// Send data to server instead...
 
});

cancelButton.addEventListener('click', function (event) {
  signaturePad.clear();
});

function openSig(p) {


  // If the checkbox is checked, display the output text

						signaturePad.clear();
			document.getElementById("signer_name").value = "";
			document.getElementById("ponumber").value = "";
			document.getElementById("ponote").value = "";
         $('#modal-signature').modal('toggle');//.modal('show')/.modal('hide');
		 document.getElementById("ponote").value += p.toString() + " - ";
		
}
</script>
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
			'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="etruck[]"><option value="">Make a selection</option><option value="03-99">Crew Cab Truck</option><option value="10-99">Paint Truck</option><option value="21-99">Haul Truck</option><option value="30-99">Longline Truck</option><option value="32-99">Handline Truck</option><option value="37-99">Marker Truck</option><option value="38-99">Sealer Truck</option><option value="39-99">Knock Up Truck</option><option value="40-99">Removal Truck</option><option value="42-96">Vacuum Truck</option><option value="42-97">Tape Truck</option><option value="42-98">Waterblast Truck</option><option value="42-99">Epoxy Truck</option></select></td>' 
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