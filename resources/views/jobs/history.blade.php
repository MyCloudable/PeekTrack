
<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<x-auth.navbars.sidebar activePage="job card history" activeItem="" activeSubitem=""></x-auth.navbars.sidebar>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg " id="app">
    <x-auth.navbars.navs.auth pageTitle="Job Card History"></x-auth.navbars.navs.auth>
    <div class="container-fluid py-4" id="appp">
        <div class="table-responsive">
            <jobhistory />
        </div>
    </div>
</main>

<x-push-script-stack />

</x-page-template>