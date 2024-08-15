<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="jobs review" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Jobs Review"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
		<input type="button" class="btn btn-warning btn-block mt-4" value="Hide Branches" onClick="window.location.href='{{ route('jobs.review') }}'">
			<div class="table-responsive">
              <table class="table table-flush" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Action</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Job #</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Description</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Branch</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Work Date</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Submitted On</th>
					<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Submitted By</th>
                  </tr>
                </thead>
                <tbody>
				@foreach ($jobentries as $job)
				@if ($job->approved == 3)
				<tr>
				<td class="text-sm font-weight-normal"><input type="button" value="Open" class="btn btn-warning" onclick="window.location.href='/jobs/{{ $job->link }}/jobreview';"/></td>
				<td class="text-md font-weight-bold"><h5>{{ $job->job_number }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $job->description }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $job->branch }}<h5></td>
				<td class="text-sm font-weight-normal"><h5>{{ $job->workdate }}</h5></td>
                <td class="text-sm font-weight-normal"><h5>{{ $job->submitted_on }}</h5></td>
				<td class="text-sm font-weight-normal"><h5>{{ $job->name }}</h5></td>
				</tr>
				@endif
				@endforeach
				
				</tbody>
				</table>
                       <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>

    </main>
    <x-plugins></x-plugins>
    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
	<script src="{{ asset('assets') }}/js/plugins/datatables.js"></script>
    <script>

      const dataTableBasic = new simpleDatatables.DataTable("#datatable-basic", {
        searchable: true,
        fixedHeight: true
      });

    </script>
    @endpush
</x-page-template>
