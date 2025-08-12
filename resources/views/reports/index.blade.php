<x-page-template bodyClass='g-sidenav-show bg-gray-200'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Include DataTables Buttons CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.1/css/buttons.dataTables.min.css">
    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <!-- Include Date Range Picker CSS -->
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet" />
	<style>
    input, select, textarea {
        background-color: white !important;
        color: black !important;
    }
</style>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Reports"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">

            <!-- Job Number Dropdown and Report Button -->
            <div class="card">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Job Work Summary</h4>
                    <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <div class="form-group">
                        <label for="jobnumber" style="color: #000;">Choose Job Number:</label>
                        <select class="form-control" id="jobnumber" style="width: 100%; border: 1px solid #ccc;">
                            @foreach($jobnumbers as $job)
                                <option value="{{ $job->job_number }}">{{ $job->job_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <br>
                    <button id="generateReport" class="btn btn-warning">Generate Report</button>
                </div>
            </div>

            <!-- Date Range Picker and Submit Button for Payroll Summary -->
            <div class="card mt-4">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Payroll Summary</h4>
                    <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <form id="payrollSummaryForm" action="{{ route('reports.payrollsummary') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="payroll_daterange" style="color: #000;">Select Date Range:</label>
                            <input type="text" id="payroll_daterange" class="form-control" style="width: 100%; border: 1px solid #ccc;" />
                            <input type="hidden" name="date1" id="payroll_date1">
                            <input type="hidden" name="date2" id="payroll_date2">
                        </div>
                        <br>
                        <button type="submit" class="btn btn-warning">Generate Payroll Summary</button>
                    </form>
                </div>
            </div>
			
			            <!-- Date Range Picker and Submit Button for Payroll Summary -->
            <div class="card mt-4">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Weekend Out and Per-Diem</h4>
                    <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <form id="weopdSummaryForm" action="{{ route('reports.weopdsummary') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="weopd_daterange" style="color: #000;">Select Date Range:</label>
                            <input type="text" id="weopd_daterange" class="form-control" style="width: 100%; border: 1px solid #ccc;" />
                            <input type="hidden" name="date1" id="weopd_date1">
                            <input type="hidden" name="date2" id="weopd_date2">
                        </div>
                        <br>
                        <button type="submit" class="btn btn-warning">Generate WEO/PD Summary</button>
                    </form>
                </div>
            </div>
            
            <!-- Date Range Picker and Submit Button for Department Summary -->
            <div class="card mt-4">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Hours by Department</h4>
                    <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <form id="deptSummaryForm" action="{{ route('reports.deptsummary') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="dept_daterange" style="color: #000;">Select Date Range:</label>
                            <input type="text" id="dept_daterange" class="form-control" style="width: 100%; border: 1px solid #ccc;" />
                            <input type="hidden" name="date1" id="dept_date1">
                            <input type="hidden" name="date2" id="dept_date2">
                        </div>
                        <br>
                        <button type="submit" class="btn btn-warning">Generate Department Report</button>
                    </form>
                </div>
            </div>
			<div class="card mt-4">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Material Usage Report</h4>
                    <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                </div>
				
<div class="card-body border-radius-lg p-3">
    <!-- Form for department summary to generate material usage report -->
    <form id="deptSummaryForm" action="{{ route('reports.materialusage') }}" method="POST">
        @csrf
        <div class="form-group">
            <!-- Location selection dropdown (optional) -->
            <label for="location">Location:</label>
            <select id="location" name="location">
                <option value="">Select Location (optional)</option>
                @foreach($locations as $location)
                    <option value="{{ $location }}">{{ $location }}</option>
                @endforeach
            </select>
            <br><br>

            <!-- Branch selection dropdown (optional) -->
            <label for="branch">Branch:</label>
            <select id="branch" name="branch">
                <option value="">Select Branch (optional)</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch }}">{{ $branch }}</option>
                @endforeach
            </select>
            <br><br>

        <!-- Material Description Dropdown -->
        <label for="material_name">Material Description:</label>
        <select id="material_name" name="material_name">
            <option value="">Select Material Description (optional)</option>
            @foreach($materials as $description)
                <option value="{{ $description }}">{{ $description }}</option>
            @endforeach
        </select>
        <br><br>

            <!-- Start date input (required) -->
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" required>
            <br><br>

            <!-- End date input (required) -->
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" required>
            <br><br>
        </div>
        
        <!-- Submit button for generating the material report -->
        <button type="submit" class="btn btn-warning">Generate Material Report</button>
    </form>
</div>
</div>
 <!-- Date Range Picker and Submit Button for Payroll Summary -->
            <div class="card mt-4">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Archived Time</h4>
                    <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <form id="archiveSummaryForm" action="{{ route('reports.archivesummary') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="archive_daterange" style="color: #000;">Select Date Range:</label>
                            <input type="text" id="archive_daterange" class="form-control" style="width: 100%; border: 1px solid #ccc;" />
                            <input type="hidden" name="date1" id="archive_date1">
                            <input type="hidden" name="date2" id="archive_date2">
                        </div>
                        <br>
                        <button type="submit" class="btn btn-warning">Generate Payroll Summary</button>
                    </form>
                
            </div>
			</div>
			<div class="card mt-4">
    <div class="card-header p-3 pb-0">
        <h4 class="mb-0">Overflow Items Report</h4>
        <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
    </div>
    <div class="card-body border-radius-lg p-3">
        <form id="overflowItemsForm" action="{{ route('reports.overflowitems') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="overflow_daterange" style="color: #000;">Select Date Range:</label>
                <input type="text" id="overflow_daterange" class="form-control" style="width: 100%; border: 1px solid #ccc;" />
                <input type="hidden" name="date1" id="overflow_date1">
                <input type="hidden" name="date2" id="overflow_date2">
            </div>
            <br>
            <button type="submit" class="btn btn-warning">Generate Overflow Items Report</button>
        </form>
    </div>
</div>
		<div id="loading-screen" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(255, 255, 255, 0.8); z-index:9999; text-align:center; padding-top:20%;">
    <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h3>Loading...<br> Please note larger reports will take longer to generate.</h3>
</div>


    </main>
    <x-auth.footers.auth.footer></x-auth.footers.auth.footer>

   @push('js')
<script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>

<!-- Include DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<!-- Include DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.2.2/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

<!-- Include Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<!-- Include Date Range Picker JS -->
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
// Initialize Date Range Picker for Overflow Items Report
$('#overflow_daterange').daterangepicker({
    opens: 'left'
}, function(start, end, label) {
    $('#overflow_date1').val(start.format('YYYY-MM-DD'));
    $('#overflow_date2').val(end.format('YYYY-MM-DD'));
});

    $(document).ready(function() {
        // Initialize Select2 on the jobnumber dropdown
        $('#jobnumber').select2({
            placeholder: "Select a job number",
            allowClear: true
        });

        $('#generateReport').click(function() {
            var jobNumber = $('#jobnumber').val();
            if (jobNumber) {
                window.location.href = `https://www.peektrack.com/reports/${jobNumber}/jobsummary`;
            } else {
                alert('Please select a job number.');
            }
        });

        // Initialize Date Range Picker for Payroll Summary
        $('#payroll_daterange').daterangepicker({
            opens: 'left'
        }, function(start, end, label) {
            $('#payroll_date1').val(start.format('YYYY-MM-DD'));
            $('#payroll_date2').val(end.format('YYYY-MM-DD'));
        });
		
		// Initialize Date Range Picker for Archive Time Summary
        $('#archive_daterange').daterangepicker({
            opens: 'left'
        }, function(start, end, label) {
            $('#archive_date1').val(start.format('YYYY-MM-DD'));
            $('#archive_date2').val(end.format('YYYY-MM-DD'));
        });
		
		        // Initialize Date Range Picker for Payroll Summary
        $('#weopd_daterange').daterangepicker({
            opens: 'left'
        }, function(start, end, label) {
            $('#weopd_date1').val(start.format('YYYY-MM-DD'));
            $('#weopd_date2').val(end.format('YYYY-MM-DD'));
        });

        // Initialize Date Range Picker for Department Summary
        $('#dept_daterange').daterangepicker({
            opens: 'left'
        }, function(start, end, label) {
            $('#dept_date1').val(start.format('YYYY-MM-DD'));
            $('#dept_date2').val(end.format('YYYY-MM-DD'));
        });

        // Show the loading screen on form submission
        $('#payrollSummaryForm, #deptSummaryForm, #archiveSummaryForm').on('submit', function() {
            $('#loading-screen').show();
        });
		
		

        // Prevent loading screen from showing when navigating back
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type == 2)) {
                $('#loading-screen').hide();
            }
        });
    });
</script>
@endpush


</x-page-template>
