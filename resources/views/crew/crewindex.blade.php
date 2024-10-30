<x-page-template bodyClass='g-sidenav-show bg-gray-200 dark-version'>
    <x-auth.navbars.sidebar activePage="jobs" activeItem="jobs" activeSubitem=""></x-auth.navbars.sidebar>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" id="app">
        <style>
            .no-js #loader { display: none; }
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
            <x-auth.navbars.navs.auth pageTitle="Approve Time"></x-auth.navbars.navs.auth>
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
								<th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">PD</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Total Time Hrs:Mins</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9" style="width:5%">Total Time (Week)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeklySummary as $weekGroup)
                                @foreach ($weekGroup as $time)
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
                                            <td class="text-md font-weight-bold"><h5>{{ $time->day }} ({{ $time->day_of_week }})</h5></td>
											<td class="text-md font-weight-bold"><h5>{{ $time->per_diem }}</h5></td>
                                            <td class="text-md font-weight-bold"><h5>{{ $time->formatted_time }}</h5></td>
                                            @if ($time->weekly_total_time)
                                                <td class="text-md font-weight-bold" rowspan="{{ $time->week_rowspan }}">
                                                    <h5>{{ $time->weekly_total_time }}</h5>
                                                </td>
                                            @else
                                                <td></td>
                                            @endif
                                        </tr>
                                    @endif
                                @endforeach
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
                    $(".se-pre-con").fadeOut("slow");
                });

                $(document).ready(function() {
                    oTable = $('#datatable-basic').dataTable();
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
