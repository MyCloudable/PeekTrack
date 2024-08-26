@props(['activePage', 'activeItem', 'activeSubitem'])
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark" id="sidenav-main">

    <div class="sidenav-header bg-gradient-dark">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0 d-flex align-items-center text-wrap" href="{{ route('dashboard') }}">
            <img src="{{ asset('assets') }}/img/logo-ct.png" class="navbar-brand-img h-100" alt="main_logo">
            <span class="ms-2 font-weight-bold text-white">PeekTrack</span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto h-auto bg-gradient-dark" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item mb-2 mt-0">
                <a data-bs-toggle="collapse" href="#ProfileNav" class="nav-link text-white" aria-controls="ProfileNav"
                    role="button" aria-expanded="false">
                   
                    <span class="nav-link-text ms-2 ps-1">{{ auth()->user()->name }}</span>
                </a>
				 
                <div class="collapse" id="ProfileNav" style="">
                    <ul class="nav ">
					@if ( auth()->user()->role_id < 6)
                        <li class="nav-item">
                            <a class="nav-link text-white " href="{{ route('user-profile') }}">
                                <span class="sidenav-mini-icon"> P </span>
                                <span class="sidenav-normal  ms-3  ps-1"> Profile </span>
                            </a>
                        </li>
						<li class="nav-item">
                            <a class="nav-link text-white " href="{{ route('crews.index') }}">
                                <span class="sidenav-mini-icon"> C </span>
                                <span class="sidenav-normal  ms-3  ps-1"> Manage Crews </span>
                            </a>
                        </li>
						@if ( auth()->user()->role_id != 6)
						<li class="nav-item">
                            <a class="nav-link text-white " href="{{ route('widgets') }}">
                                <span class="sidenav-mini-icon"> E </span>
                                <span class="sidenav-normal  ms-3  ps-1"> Export Data </span>
                            </a>
                        </li>
						@endif
						@if ( auth()->user()->role_id != 6)
						<li class="nav-item"{{ $activeItem == 'reports' ? ' active ' : '' }}  ">
							<a class="nav-link text-white "
								href="https://peektrack.com/reportico">
								<span class="sidenav-mini-icon"> R </span>
                                <span class="sidenav-normal  ms-3  ps-1"> Reports</span>
							</a>
                        </li>   
												<li class="nav-item"{{ $activeItem == 'newreports' ? ' active ' : '' }}  ">
							<a class="nav-link text-white "
								href="https://peektrack.com/reports">
								<span class="sidenav-mini-icon"> R </span>
                                <span class="sidenav-normal  ms-3  ps-1"> New Reports</span>
							</a>
                        </li>   
						@endif
						@endif
						
                        <form method="POST" action="{{ route('logout') }}" class="d-none" id="logout-form">
                            @csrf
                        </form>
						
                        <li class="nav-item">
                            <a class="nav-link text-white " href="{{ route('logout') }}"
                                onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal  ms-3  ps-1"> Logout </span>
                            </a>
                        </li>
                    </ul>
                </div>
				
            </li>
            <hr class="horizontal light mt-0">
			@if ( auth()->user()->role_id != 6)
                        <li class="nav-item {{ $activeItem == 'dashboard' ? ' active ' : '' }}  ">
                            <a class="nav-link text-white {{ $activeItem == 'dashboard' ? ' active' : '' }}  "
                                href="{{ route('dashboard') }}">
                                <span class="sidenav-normal  ms-2  ps-1"> <h5>Dashboard</h5> </span>
                            </a>
                        </li>            
						<!--<li class="nav-item"{{ $activeItem == 'schedule' ? ' active ' : '' }}  ">
                            <a class="nav-link text-white {{ $activeItem == 'schedule' ? ' active' : '' }}  "
                                href="{{ route('schedule') }}">
                                <span class="sidenav-normal  ms-2  ps-1"> Schedule </span>
                            </a>
                        </li>-->
						<li class="nav-item"{{ $activeItem == 'jobs' ? ' active ' : '' }}  ">
							<a class="nav-link text-white {{ $activeItem == 'jobs' ? ' active' : '' }}  "
								href="{{ route('jobs') }}">
								<span class="sidenav-normal  ms-2  ps-1"> <h5>Jobs</h5> </span>
							</a>
                        </li>
						@if ( auth()->user()->role_id == 2)
						<li class="nav-item"{{ $activeItem == 'review' ? ' active ' : '' }}  ">
							<a class="nav-link text-white {{ $activeItem == 'review' ? ' active' : '' }}  "
								href="{{ route('jobs.review') }}">
								<span class="sidenav-normal  ms-2  ps-1"> <h5>Review Job Cards</h5> 
							</a>
                        </li>
						@endif
						@if ( auth()->user()->role_id != 6)
						<li class="nav-item"{{ $activeItem == 'history' ? ' active ' : '' }}  ">
							<a class="nav-link text-white {{ $activeItem == 'history' ? ' active' : '' }}  "
								href="{{ route('jobs.history') }}">
								<span class="sidenav-normal  ms-2  ps-1"> <h5>Job Card History</h5>
							</a>
                        </li>
						@endif
                        <!---<li class="nav-item"{{ $activeItem == 'crews' ? ' active ' : '' }}  ">
							<a class="nav-link text-white {{ $activeItem == 'crews' ? ' active' : '' }}  "
								href="{{ route('crews.index') }}">
								<span class="sidenav-normal  ms-2  ps-1"> <h5>Crews </h5></span>
							</a>
                        </li>-->
                        <!---<li class="nav-item"{{ $activeItem == 'crewTypes' ? ' active ' : '' }}  ">
							<a class="nav-link text-white {{ $activeItem == 'crewTypes' ? ' active' : '' }}  "
								href="{{ route('crewTypes.index') }}">
								<span class="sidenav-normal  ms-2  ps-1"> <h5>Crews types </h5></span>
							</a>
                        </li>-->
                        <li class="nav-item"{{ $activeItem == 'timesheet-management' ? ' active ' : '' }}  ">
							<a class="nav-link text-white {{ $activeItem == 'timesheet-management' ? ' active' : '' }}  "
								href="{{ route('timesheet-management.index') }}">
								<span class="sidenav-normal  ms-2  ps-1"> <h5>Time</h5></span>
							</a>
                        </li>
                        @endif
						@if ( auth()->user()->role_id == 6 || auth()->user()->role_id == 3)
						<li class="nav-item"{{ $activeItem == 'reports' ? ' active ' : '' }}  ">
							<a class="nav-link text-white "
								href="https://peektrack.com/crewmember">
								<span class="sidenav-mini-icon"> A </span>
                                <span class="sidenav-normal  ms-3  ps-1">Approve Time</span>
							</a>
                        </li>   
						@endif
						@if ( auth()->user()->role_id == 6 || auth()->user()->role_id == 3)
						<li class="nav-item"{{ $activeItem == 'reports' ? ' active ' : '' }}  ">
							<a class="nav-link text-white "
								href="https://peektrack.com/crewsummary">
								<span class="sidenav-mini-icon"> H </span>
                                <span class="sidenav-normal  ms-3  ps-1">History</span>
							</a>
                        </li>   
						@endif
                    </div>
</aside>
