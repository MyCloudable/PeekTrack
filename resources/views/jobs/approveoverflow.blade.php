<x-page-template bodyClass='g-sidenav-show bg-gray-200 dark-version'>
  <x-auth.navbars.sidebar activePage="estimating" activeItem="analytics" activeSubitem="" />
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg" id="app">
    <x-auth.navbars.navs.auth pageTitle="Overflow Completion Queue" />
    <div class="container-fluid py-4">
      <div class="table-responsive">
        <overflow-approval />
      </div>
    </div>
  </main>
  <x-push-script-stack />
</x-page-template>
