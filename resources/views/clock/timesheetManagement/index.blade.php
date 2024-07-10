<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<x-auth.navbars.sidebar activePage="Timesheet" activeItem="" activeSubitem=""></x-auth.navbars.sidebar>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg " id="app">
    <x-auth.navbars.navs.auth pageTitle="Timesheet"></x-auth.navbars.navs.auth>
    <div class="container-fluid py-4" id="appp">
         <timesheet :users="{{$users}}" :jobs="{{$jobs}}" :timetypes="{{$timeTypes}}" :authuser="{{$authuser}}" :crewtypes="{{$crewTypes}}" />
    </div>
</main>

<x-push-script-stack />

</x-page-template>