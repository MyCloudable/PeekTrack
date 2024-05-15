<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="jobs review" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
	
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
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
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Jobs Review"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
		<div class="se-pre-con"></div>
        <div class="container-fluid py-4">
			<div class="table-responsive">
              <table class="table table-flush" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">View</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Work Date</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Job #</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Name</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Branch</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Submission Status</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Approval Status</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Approved By</th>
                  </tr>
                </thead>
                <tbody>
				@foreach ($jobentries as $job)
				
				<tr>
				<td class="text-sm font-weight-normal"><input type="button" value="View" class="btn btn-warning" onclick="window.location.href='/jobs/{{ $job->link }}/view';"/></td>
				<td class="text-sm font-weight-normal"><h5>{{ $job->workdate }}</h5></td>
				<td class="text-md font-weight-bold"><h5>{{ $job->job_number }}</h5></td>
				<td class="text-sm font-weight-normal"><h5>{{ $job->name }}</h5></td>
				<td class="text-sm font-weight-normal"><h5>{{ $job->branch }}<h5></td>
                <td class="text-sm font-weight-normal"><h5>@if ($job->submitted == 1) Submitted @else Not Submitted @endif</h5></td>
				<td class="text-sm font-weight-normal"><h5>@if ($job->approved == 1) Approved @elseif ($job->approved == 2) Rejected @else Pending @endif</h5></td>
                <td class="text-sm font-weight-normal"><h5>@if ($job->approved == 1) {{ $job->ApprovedBy }} @endif<h5></td>

				</tr>
				
				@endforeach
				
				</tbody>
				</table>
                       <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>

    </main>
    <x-plugins></x-plugins>
    @push('js')
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
	<script src="{{ asset('assets') }}/js/plugins/datatables.js"></script>
    <script>
	$(window).load(function() {
		// Animate loader off screen
		$(".se-pre-con").fadeOut("slow");;
	});
	
      const dataTableBasic = new simpleDatatables.DataTable("#datatable-basic", {
        searchable: true,
        fixedHeight: true
      });

    </script>
    @endpush
</x-page-template>
