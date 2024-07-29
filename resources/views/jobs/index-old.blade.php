<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="jobs" activeItem="jobs" activeSubitem=""></x-auth.navbars.sidebar>


    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg " id="app">
      
<style>
/* Paste this css to your style sheet file or under head tag */
/* This only works with JavaScript, 
if it's not present, don't show loader */
.no-js #loader { display: none;  }
.js #loader { display: block; position: absolute; left: 100px; top: 0; }
.se-pre-con {
	position: fixed;
	left: 0px;
	top: 0px;
	width: 100%;
	height: 100%;
	z-index: 9999;
	background: url({{ asset('assets') }}/img/loading.gif) center no-repeat #252526;
}
  </style>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Open Jobs"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="se-pre-con"></div>
        <div class="container-fluid py-4">
		<div class="form-group">
</div>
<input type="hidden" id="branch" value="@foreach ($branch as $binfo) @if ($binfo->department == Auth::user()->location){{ $binfo->branch }} @else @endif @endforeach">
			<div class="table-responsive">
              <table class="table table-flush table-striped" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Action</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Job #</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Description</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">County</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Contractor</th>
					                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Branch</th>
                  </tr>
                </thead>
                <tbody>
				@foreach ($jobs as $job)
				<tr>
				<td class="text-sm font-weight-normal"><input type="button" value="Open" class="btn btn-warning" onclick="window.location.href='/jobs/{{ $job->id }}/overview';"/></td>
				<td class="text-md font-weight-bold"><h5>{{ $job->job_number }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $job->description }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $job->county }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $job->contractor }}</h5></td>
				                <td class="text-sm font-weight-normal"><h5>{{ $job->branch }}</h5></td>
				</tr>
				@endforeach
				</tbody>
				</table>
                       <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>

    </main>
    <div class="modal"><!-- Place at bottom of page --></div>
    <x-plugins></x-plugins>
    @push('js')
<link href="{{ asset('assets') }}/css/datatables.min.css" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
<script src="{{ asset('assets') }}/js/jquery.dataTables.min.js"></script>
<script src="{{ asset('assets') }}/js/datatables.min.js"></script>

    <script>
	$(window).load(function() {
		// Animate loader off screen
		$(".se-pre-con").fadeOut("slow");;
	});

      var branch = document.getElementById("branch").value;
	  branch = branch.replace(/^\s+|\s+$/gm,'');
$(document).ready(function() {
    oTable = $('#datatable-basic').dataTable();

    /* Filter immediately */
    oTable.fnFilter( branch);
} );
</script>
    @endpush
</x-page-template>
