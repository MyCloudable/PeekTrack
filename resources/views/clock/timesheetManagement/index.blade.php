<meta name="csrf-token" content="{{ csrf_token() }}">
@if (auth()->user()->role_id == 6)
    <script type="text/javascript">
        window.location = "{{ url('/crewmember') }}";
    </script>
@endif

			<style scoped>
			.custom-hover tbody tr:hover {
			background-color: #949494 !important; /* Lighter teal for better visibility */
			box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Optional shadow for emphasis */
			transition: background-color 0.3s ease, box-shadow 0.3s ease; /* Smooth transition */
			}
			</style>

<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<x-auth.navbars.sidebar activePage="Timesheet" activeItem="" activeSubitem=""></x-auth.navbars.sidebar>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg " id="app">
    <x-auth.navbars.navs.auth pageTitle="Timesheet"></x-auth.navbars.navs.auth>
    <div class="container-fluid py-4" id="appp">
         <timesheet :users="{{$users}}" :jobs="{{$jobs}}" :timetypes="{{$timeTypes}}" 
         :authuser="{{$authuser}}" :crewtypes="{{$crewTypes}}" :unique-superintendents="{{$uniqueSuperintendents}}" />
    </div>
</main>

<x-push-script-stack />

</x-page-template>

