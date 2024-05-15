<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>

    <x-auth.navbars.sidebar activePage="dashboard" activeItem="analytics" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Dashboard"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
		<div class="col-sm-12">
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

                       <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>
    </main>
    <x-plugins></x-plugins>
    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>

    <script>


    </script>
    @endpush
</x-page-template>
