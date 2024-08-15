<x-page-template bodyClass='g-sidenav-show bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="jobs" activeItem="jobs" activeSubitem=""></x-auth.navbars.sidebar>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" id="app">
      
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

        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
            <!-- Navbar -->
            <x-auth.navbars.navs.auth pageTitle="Approve Time"></x-auth.navbars.navs.auth>
            <!-- End Navbar -->
            <div class="se-pre-con"></div>
            <div class="container-fluid py-4">
                <div class="form-group"></div>
                <div class="table-responsive">
                    <table class="table table-flush table-striped" id="datatable-basic">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Approve</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Name</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Date</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Total Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($query as $time)
                                @if (auth()->user()->id == $time->user_id && $time->crew_member_approval < 1)
                                    <tr>
                                        <td class="text-md font-weight-bold">
                                            <h5>
                                                <button class="btn btn-icon btn-2 btn-success" type="button" onclick="approveCrewTime('{{ $time->user_id }}', '{{ $time->day }}')">
                                                    <span class="btn-inner--icon"><i class="material-icons">check</i></span>
                                                </button>
                                            </h5>
                                        </td>
                                        <td class="text-md font-weight-bold"><h5>{{ $time->crewmember_name }}</h5></td>
                                        <td class="text-md font-weight-bold"><h5>{{ $time->day }}</h5></td>
                                        <td class="text-md font-weight-bold"><h5>{{ $time->total_hours }}:{{ $time->total_minutes }}</h5></td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                    <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
                </div>
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
                    oTable.fnFilter(branch);
                });

                function approveCrewTime(id, date) {
                    fetch('/timesheet-management/approve-crew-time', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ id: id, date: date })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Time approved successfully');
                            location.reload();
                        } else {
                            alert('Error approving time');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            </script>
        @endpush
</x-page-template>
