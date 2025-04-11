<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-auth.navbars.sidebar activePage="dashboard" activeItem="viewNotifications" activeSubitem=""></x-auth.navbars.sidebar>
        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
            <!-- Navbar -->
            <x-auth.navbars.navs.auth pageTitle="Overflow Queue"></x-auth.navbars.navs.auth>
            <!-- End Navbar -->
        <div class="container-fluid py-4">
<div class="container">
    <h1>Edit Notification</h1>

    <form action="{{ route('admin.notifications.update', $notification->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ $notification->title }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" required>{{ $notification->message }}</textarea>
        </div>

        <div class="form-check mb-3">
            <input type="hidden" name="is_active" value="0">
			<input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1">

            <label class="form-check-label" for="is_active">Active</label>
        </div>

        <button class="btn btn-primary">Update</button>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
          </div>
    </x-page-template>
