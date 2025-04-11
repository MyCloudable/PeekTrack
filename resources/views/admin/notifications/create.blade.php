<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-auth.navbars.sidebar activePage="dashboard" activeItem="viewNotifications" activeSubitem=""></x-auth.navbars.sidebar>
        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
            <!-- Navbar -->
            <x-auth.navbars.navs.auth pageTitle="Create"></x-auth.navbars.navs.auth>
            <!-- End Navbar -->
        <div class="container-fluid py-4">
<div class="container">
    <h1>New Notification</h1>

   <form method="POST" action="{{ route('admin.notifications.store') }}">
    @csrf

    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>

    <div class="mb-3">
        <label for="message" class="form-label">Message</label>
        <textarea class="form-control" id="message" name="message" required></textarea>
    </div>

    <div class="mb-3 form-check">
       <input type="hidden" name="is_active" value="0">
			<input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1">
        <label class="form-check-label" for="is_active">Active</label>
    </div>

    <button type="submit" class="btn btn-primary">Create</button>
</form>

</div>
          </div>
    </x-page-template>