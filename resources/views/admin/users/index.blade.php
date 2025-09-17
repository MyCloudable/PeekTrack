<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-auth.navbars.sidebar activePage="dashboard" activeItem="users" activeSubitem=""></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Users"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <div class="row mt-4">
                <div class="col-md-12" id="app">
                    <admin-users-index :roles='@json($roles)'></admin-users-index>
                </div>
            </div>
        </div>
</x-page-template>
