<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="jobs" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Jobs"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
			<div class="table-responsive">
              <table class="table table-flush" id="datatable-basic">
                <thead class="thead-light">
                  <tr>
				    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Action</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Job #</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Description</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Branch</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Completion Date</th>
                  </tr>
                </thead>
                <tbody>
				@foreach ($jobs as $job)
				<tr>
				<td class="text-sm font-weight-normal"><input type="button" value="Open" class="btn btn-warning" onclick="window.location.href='/jobs/{{ $job->id }}/overview';"/></td>
				<td class="text-md font-weight-bold">{{ $job->job_number }}</td>
                <td class="text-sm font-weight-normal">{{ $job->description }}</td>
                <td class="text-sm font-weight-normal">{{ $job->branch }}</td>
                <td class="text-sm font-weight-normal">{{ $job->completion_date }}</td>
				</tr>
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
