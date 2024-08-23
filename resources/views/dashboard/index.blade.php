<x-page-template bodyClass='g-sidenav-show bg-gray-200 dark-version'>
    <x-auth.navbars.sidebar activePage="dashboard" activeItem="analytics" activeSubitem=""></x-auth.navbars.navs.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" id="app">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Dashboard"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <div class="col-sm-12">
                <div class="row">
                    <div class="row">
<a href="{{ route('jobs.jobcardview', [ 'id' => Auth::user()->id ]) }}"><div class="col-lg-3 col-md-6 col-sm-6">
<div class="card  mb-2">
<div class="card-header p-3 pt-2">
<div class="icon icon-lg icon-shape bg-gradient-warning shadow-dark shadow text-center border-radius-xl mt-n4 position-absolute">
<i class="material-icons opacity-10">engineering</i>
</div>
<div class="text-end pt-1">
<p class="text-sm mb-0 text-capitalize"><h4>Active Jobs</h4></p>
<h3 class="mb-0"><a href="{{ route('jobs') }}"> {{ $count = count($job) }}</h3>
</div>
</div>
<hr class="dark horizontal my-0">
<div class="card-footer p-3">
<p class="mb-0"></p>
</div>
</div>
</div></a>

<div class="col-lg-3 col-md-6 col-sm-6 mt-sm-0 mt-4">
<a href="{{ route('jobs.jobcardview', [ 'id' => Auth::user()->id ]) }}">
<div class="card  mb-2">
<div class="card-header p-3 pt-2">
<div class="icon icon-lg icon-shape bg-gradient-warning shadow-primary shadow text-center border-radius-xl mt-n4 position-absolute">
<i class="material-icons opacity-10">pending</i>
</div>
<div class="text-end pt-1">
<p class="text-sm mb-0 text-capitalize"><h4>Unsubmitted Job Cards</h4></p>
<h3 class="mb-0"><a href="{{ route('jobs.jobcardview', [ 'id' => Auth::user()->id ]) }}">{{ $count = count($unsubmitCards) }}</a></h3>
</div>
</div>
<hr class="dark horizontal my-0">
<div class="card-footer p-3">
<p class="mb-0"></p>
</div>
</div>
</div></a>

<div class="col-lg-3 col-md-6 col-sm-6 mt-lg-0 mt-4">
<a href="{{ route('jobs.jobcardview', [ 'id' => Auth::user()->id ]) }}">
<div class="card  mb-2">
<div class="card-header p-3 pt-2 bg-transparent">
<div class="icon icon-lg icon-shape bg-gradient-warning shadow-success text-center border-radius-xl mt-n4 position-absolute">
<i class="material-icons opacity-10">sync_problem</i>
</div>
<div class="text-end pt-1">
<p class="text-sm mb-0 text-capitalize "><h4>Rejected Job Cards</h4></p>
<h3 class="mb-0 ">{{ $count = count($rejectedJobcards) }}</h3>
</div>
</div>
<hr class="horizontal my-0 dark">
<div class="card-footer p-3">
<p class="mb-0 "></p>
</div>
</div>
</a>
</div>
<div class="col-lg-3 col-md-6 col-sm-6 mt-lg-0 mt-4">
<a href="{{ route('jobs.myjobcards', [ 'id' => Auth::user()->id ]) }}">
<div class="card  mb-2">
<div class="card-header p-3 pt-2 bg-transparent">
<div class="icon icon-lg icon-shape bg-gradient-warning shadow-success text-center border-radius-xl mt-n4 position-absolute">
<i class="material-icons opacity-10">work</i>
</div>
<div class="text-end pt-1">
<p class="text-sm mb-0 text-capitalize "><h4>My Job Cards</h4></p>
<h3 class="mb-0 ">{{ $count = count($Jobcards) }}</h3>
</div>
</div>
<hr class="horizontal my-0 dark">
<div class="card-footer p-3">
<p class="mb-0 "></p>
</div>
</div>
</a>
</div>
</div>
                </div>

                <!-- Crew Status Visualization -->
                <div class="row mt-4">
                    <h3 class="mb-4">Current Crew Status</h3>

                    @foreach ($crews->groupBy('location') as $location => $locationCrews)
                        <h4 class="mb-4">{{ $location }}</h4>
                        <div class="row">
                            @foreach ($locationCrews as $crew)
                                @php
                                    $nameColor = ''; // Default color
                                    switch (strtolower(trim($crew->time_type))) {
                                        case 'shop':
                                            $nameColor = '#dc3545'; // Red for shop
                                            break;
                                        case 'mobilization':
                                            $nameColor = '#ffc107'; // Orange for mobilization
                                            break;
                                        case 'production':
                                            $nameColor = '#28a745'; // Green for production
                                            break;
                                        default:
                                            $nameColor = '#6c757d'; // Default color (secondary)
                                            break;
                                    }
                                @endphp
                                <div class="col-lg-3 col-md-6 col-sm-6">
                                    <div class="card mb-2">
                                        <div class="card-header p-3 pt-2 bg-gradient-secondary text-white">
                                            <div class="text-end pt-1">
                                                <h5 class="mb-0" style="color: {{ $nameColor }} !important;">{{ $crew->superintendent_name }}</h5>
                                                <p class="text-sm mb-0">{{ $crew->time_type }}</p>
                                            </div>
                                        </div>
                                        <div class="card-footer p-3">
                                            <p class="mb-0">Last Clock-In: {{ \Carbon\Carbon::parse($crew->last_clockin_time)->format('H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                </div>
                <!-- End of Crew Status Visualization -->

                <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
            </div>
        </div>
    </main>
    <x-plugins></x-plugins>
    @push('js')
        <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
    @endpush
</x-page-template>
