<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<x-auth.navbars.sidebar activePage="crews" activeItem="" activeSubitem=""></x-auth.navbars.sidebar>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <x-auth.navbars.navs.auth pageTitle="Crews"></x-auth.navbars.navs.auth>
    <div class="container-fluid py-4">
         <div class="row">
            <div class="col-md-12 mb-3">
                <a href="#" class="btn btn-info">Create crew</a>
            </div>
            <div class="col-md-12">
                <div class="">
                    <table class="table table-flush table-striped" id="datatable-basic">
                        <thead class="thead-light">
                            <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Action</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Crew name</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">SuperIndentend name</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Last verified date</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Created by</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Modified by</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Created at</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Updated at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($crews as $crew)
                            <tr>
                            <td class="text-sm font-weight-normal">
                                <a href="#" class="btn btn-warning">Edit</a>
                                <a href="#" class="btn btn-danger">Delete</a>
                            </td>
                            <td class="text-md font-weight-bold"><h5>{{ $crew->crew_name }}</h5></td>
                            <td class="text-sm font-weight-normal"><h5>{{ $crew->superintendentId }}</h5></td>
                            <td class="text-sm font-weight-normal"><h5>{{ $crew->last_verified_date }}</h5></td>
                            <td class="text-sm font-weight-normal"><h5>{{ $crew->created_by }}</h5></td>
                            <td class="text-sm font-weight-normal"><h5>{{ $crew->modified_by }}</h5></td>
                            <td class="text-sm font-weight-normal"><h5>{{ $crew->created_at }}</h5></td>
                            <td class="text-sm font-weight-normal"><h5>{{ $crew->updated_at }}</h5></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
                </div>
            </div>
         </div>
    </div>
</main>

@push('js')
    <link href="{{ asset('assets') }}/css/datatables.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <script src="{{ asset('assets') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('assets') }}/js/datatables.min.js"></script>

    <script>
        $(document).ready(function() {
            oTable = $('#datatable-basic').dataTable();
            /* Filter immediately */
            oTable.fnFilter( branch);
        } );
    </script>
@endpush
</x-page-template>