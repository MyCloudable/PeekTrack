<style>
.form-control, select, textarea {
    background-color: #fff !important;

}

/* Modal Base */
.modal {
    padding: 1rem;
}

/* Properly centered modal dialog */
.modal-dialog {
    margin: 1.75rem auto;
    max-width: 600px;
    width: 100%;
	box-shadow: none !important;


}

/* Styled modal content */
.modal-content {
    background-color: #fefefe;
    color: #222;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

/* Scrollable modal body if needed */
.modal-body {
    max-height: 70vh;
    overflow-y: auto;
    padding: 1.5rem;
}

/* Close button contrast */
.modal-header .btn-close {
    filter: invert(1);
}

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.4); /* softer dark */
    backdrop-filter: blur(2px);
}


</style>
<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-auth.navbars.sidebar activePage="jobs" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Job Card"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4"> @csrf @method('PUT')
            <!-- Add input fields for editing record attributes -->
           <label for="job_number"><h4> Number: <strong>{{ $job->job_number }}</strong></h4></label> <label for="description"><h4>Description: <strong>{{ $job->description }}</strong></h4></label>
            <label for="contractor"><h4>Contractor: <strong>{{ $job->contractor }}</strong></h4></label><br>
            <div class="row mt-4">
                <div class="col-12">

                </div>
                <form method="POST" action="{{ route('jobs.update', ['id' => $job->id]) }}" onsubmit="return validateForm()">
                    
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mt-3 mb-2 ms-3">Production</h6><button type="button" class="btn btn-warning btn-block " data-bs-toggle="modal" style="float: right;" data-bs-target="#modal-addproduction">Add Production</button>
                            </div> @csrf <input type="hidden" name="jobid" value="{{ $job->id }}">
                            <input type="hidden" name="job_number" value="{{ $job->job_number }}">
                            <input type="hidden" name="userId" value="{{Auth::user()->id}}">
                            <input type="hidden" name="username" value="{{Auth::user()->name}}">
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
                                            <td><h5>{{ $jobdata->phase }}</h5><input class="form-control-sm" type="hidden" name="phase[]" value="{{ $jobdata->phase }}"></td>
                                            <td><h5>{{ $jobdata->description }}</h5><input class="form-control-sm" type="hidden" name="description[]" value="{{ $jobdata->description }}"></td>
                                            <td><input class="form-control-sm" type="number" step="0.001" name="qty[]" placeholder="0"></td>
											<td><h5>{{ $jobdata->unit_of_measure }}</h5><input class="form-control-sm" type="hidden" name="unit_of_measure[]" value="{{ $jobdata->unit_of_measure }}"></td>
                                            <td><input class="form-control-sm" type="text" name="road_name[]" placeholder="Road"></td>
                                            <td><select class="form-control-sm" style="background-color: white;color: black !important;" name="surface_type[]">
											<option value="">NA</>
											<option value="Each">Each</>
											<option value="Milling">Milling</>
                                            <option value="MOT">MOT</>
                                            <option value="Reclamation">Reclamation</>
											<option value"Shoulder Widening">Shoulder Widening</>
											<option value="Traffic Shift">Traffic Shift</>
											<option value="Patch/Repair">Patch/Repair</>
											<option value="Leveling">Leveling</>
											<option value="Detour">Detour</>
											<option value="Final">Final</>
											<option value="Current">Current</>
											</select></td>
											<td><input class="form-control-sm" type="text" name="mark_mill[]" placeholder="Notes"></td>
											<td><input class="form-control-sm" type="checkbox" name="phase_item_complete[]" placeholder="Phase Complete"></td>
                                            <td>Unsaved</td>
											<td><input type="button" class="btn btn-danger" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>
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
                                        <td><h5>{{ $jobmaterial->phase }}</h5><input class="form-control-sm" type="hidden" name="mphase[]" value="{{ $jobmaterial->phase }}"></td>
                                        <td><h5>{{ $jobmaterial->description }}</h5><input class="form-control-sm" type="hidden" name="mdescription[]" value="{{ $jobmaterial->description }}"></td>
                                        <td><input class="form-control-sm" type="number" step="0.001" name="mqty[]" placeholder="0"></td>
                                        <td><h5>{{ $jobmaterial->unit_of_measure }}</h5><input class="form-control-sm" type="hidden" name="munit[]" value="{{ $jobmaterial->unit_of_measure }}"></td>
                                        <td><input class="form-control-sm" type="text" name="msupplier[]" placeholder="Supplier">
                                        </td>
                                        <td><input class="form-control-sm" type="text" name="mbatch[]" placeholder="Batch #"></td>
										<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>
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
                                            <td><h5>{{ $jobequipment->phase }}</h5><input class="form-control-sm" type="hidden" name="ephase[]" value="{{ $jobequipment->phase }}"></td>
                                            <td><h5>{{ $jobequipment->description }}</h5><input class="form-control-sm" type="hidden" name="edescription[]" value="{{ $jobequipment->description }}"></td>
                                            <td><select class="form-control-sm" name="etruck[]" required>
                                                    <option value="">Make a selection</option>
                                                    <option value="03-99">Crew Cab Truck</option>
                                                    <option value="10-99">Paint Truck</option>
                                                    <option value="21-99">Haul Truck</option>
                                                    <option value="30-99">Longline Truck</option>
                                                    <option value="32-99">Handline Truck</option>
                                                    <option value="37-99">Marker Truck</option>
                                                    <option value="38-99">Sealer Truck</option>
                                                    <option value="39-99">Knock Up Truck</option>
                                                    <option value="40-99">Removal Truck</option>
                                                    <option value="42-96">Vacuum Truck</option>
                                                    <option value="42-98">Tape Truck</option>
                                                    <option value="42-99">Waterblast Truck</option>
                                                    <option value="42-97">Epoxy Truck</option>
                                                </select></td>
                                            <td><input class="form-control-sm" type="number" step="0.001" name="ehours[]" required></td>
											<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>
                                        </tr> @endforeach
                                        <!-- Add more input fields for other attributes -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <center>
                        <label>Notes</label><br>
                        <textarea rows="4" style="background-color: #fff !important;" cols="100" name="notes"></textarea><br>
						<label>Work Date </label> <input type="date" name="workdate" required>
                    </center>
                    <br><br>
                    <button type="submit" class="btn btn-warning">Save Job Card</button>
                </form>
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
	@push('js') 
	<script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
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
			'<td>unsaved</td>' 
			+ 
			'<td><input type="button" class="btn btn-danger" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table.appendChild(newRow);
        }

        function insertHTML2() {
            const newRow2 = document.createElement('tr');
            newRow2.innerHTML = '<td><input class="form-control-sm" size="15" style="background-color: white;color: black !important;" type="text" name="mphase[]" value="98-19999" placeholder="Phase" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" type="text" size="25" style="background-color: white;color: black !important;" name="mdescription[]" value="Unestimated Materials - '+String(table2.rows.length)+'" placeholder="Description" required></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;"  type="number" step="0.001" name="mqty[]" value="0" placeholder="0"></td>' 
			+ 
			'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="munit[]"><option value="">Make a selection</option><option value"GAL">Gallons</option><option value"EA">Each</option><option value"TON">Tons</option><<option value"FT">Feet</option><option value"SY">Square Yards</option></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="msupplier[]" placeholder="Supplier"></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mbatch[]" placeholder="Batch"></td>' 
			+ 
			'<td><input type="button" class="btn btn-danger" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
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
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" step="0.001" name="ehours[]" required></td>' 
			+ 
			'<td><input type="button" class="btn btn-danger" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table3.appendChild(newRow3);
        }

        function addItem(id, desc, est, unit) {
            if (parseInt(id.substring(3, 8)) < 10000) {
                const production = document.createElement('tr');
                production.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="background-color: white;color: black !important;" type="text" name="phase[]" value="' + id + '" placeholder="Phase" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" type="text" size="25" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="description[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" step="0.001" name="qty[]" placeholder="0" value="0" ></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="unit_of_measure[]"  value="' + unit + '" placeholder="Unit of Measure" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="road_name[]" placeholder="Road"></td>' 
				+  
				'<td><select class="form-control-sm" style="background-color: white;color: black !important;" name="surface_type[]"><option value="NA">NA</><option value"Milling">Milling</><option value"MOT">MOT</><option value"Reclamation">Reclamation</><option value"Shoulder Widening">Shoulder Widening</><option value"Traffic Shift">Traffic Shift</><option value"Patch/Repair">Patch/Repair</><option value"Leveling">Leveling</><option value"Detour">Detour</><option value"Final">Final</><option value="Current">Current</></select></td>'
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mark_mill[]" placeholder="Notes"></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="checkbox" name="po[]" placeholder="Purchase Order"></td>' 
				+ 
				'<td><input class="form-control-sm" type="checkbox" name="phase_item_complete[]" placeholder="Phase Complete"></td>' 
				+
				'<td><input type="button" class="btn btn-danger" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
                // Append the new row to the table
                table.appendChild(production);
            }
            if (parseInt(id.substring(3, 8)) == 10000) {
                const material = document.createElement('tr');
                material.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="ephase[]" value="' + id + '" placeholder="Phase" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" type="text" size="25" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="edescription[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="etruck[]"><option value="">Make a selection</option><option value="03-99">Crew Cab Truck</option><option value="10-99">Paint Truck</option><option value="21-99">Haul Truck</option><option value="30-99">Longline Truck</option><option value="32-99">Handline Truck</option><option value="37-99">Marker Truck</option><option value="38-99">Sealer Truck</option><option value="39-99">Knock Up Truck</option><option value="40-99">Removal Truck</option><option value="42-96">Vacuum Truck</option><option value="42-98">Tape Truck</option><option value="42-99">Waterblast Truck</option><option value="42-97">Epoxy Truck</option></select></td>'
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" step="0.001" name="ehours[]" required></td>' 
				+ 
				'<td><input type="button" class="btn btn-danger" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
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
				'<td><input type="button" class="btn btn-danger" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
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
    // 1) Make sure you have both production and material if either one exists
    if (
        (table.rows.length > 1 && table2.rows.length < 2) ||
        (table.rows.length < 2 && table2.rows.length > 1)
    ) {
        alert("You must have both production and material line items on your job card. Please correct and then save again.");
        return false;
    }

    // 2) If you have both production AND material, force at least one equipment row
    if (
        table.rows.length > 1 &&
        table2.rows.length > 1 &&
        table3.rows.length < 2  // <-- fixed typo here
    ) {
        alert("You must have equipment on your job card!");
        return false;
    }

    return true; // allow submit if everything is okay
}

    </script> @endpush
</x-page-template>