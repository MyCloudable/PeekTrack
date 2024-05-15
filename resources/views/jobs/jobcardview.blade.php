<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="Job Cards In Progress" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Jobs"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
		<div class="form-group">
</div>
<table class="table table-flush" id="datatable-basic">
										<thead class="thead-light">
										<tr>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2"></th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Job Number</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Submitted</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Status</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">Date Created</th>
										<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9 ps-2">User</th>
										</tr>
										</thead>
										<tbody>
                                        @foreach ($jobentries as $jobentry)
										
										<tr>
										<td><input type="button" class="btn btn-round bg-gradient-warning mb-3" value="Open" onclick="location.href='/jobs/{{ $jobentry->link }}/jobcard/';"></td>
										<td ><h4>{{ $jobentry->job_number }}</h4></td>
										<td><h4>No</h4></td>
										<td ><h4>@If ($jobentry->approved == 0) Unsubmitted @Else ($jobentry->approved == 2) Rejected @endif</h4></td>
										<td ><h4>{{ $jobentry->workdate }}</h4></td>
										<td><h4>{{ $jobentry->name }}</h4></td>
										</tr>
										@endforeach    
                                        
										</tbody>
										</table>
										       
        </div>
  <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>

    </main>
    <x-plugins></x-plugins>
    @push('js')
<link href="https://cdn.datatables.net/v/dt/dt-1.13.7/datatables.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/v/dt/dt-1.13.7/datatables.min.js"></script>

    <script>

      var branch = document.getElementById("branch").value;
$(document).ready(function() {
    oTable = $('#datatable-basic').dataTable();

    /* Filter immediately */
    oTable.fnFilter( branch);
} );
</script>
    @endpush
</x-page-template>
