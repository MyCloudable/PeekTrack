<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-auth.navbars.sidebar activePage="dashboard" activeItem="viewNotifications" activeSubitem=""></x-auth.navbars.sidebar>
        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
            <!-- Navbar -->
            <x-auth.navbars.navs.auth pageTitle="Notifications"></x-auth.navbars.navs.auth>
            <!-- End Navbar -->
        <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1></h1>
        <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">+ Create</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

 <table class="table table-striped">
    <thead>
        <tr>
            <th>Title</th>
            <th>Message</th>
            <th>Active</th>
            <th>Created</th>
			<th>Acknowledged By</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($notifications as $notification)
            <tr>
                <td>{{ $notification->title }}</td>
                <td>{{ $notification->message }}</td>
                <td>
                    @if ($notification->is_active)
                        <span class="badge bg-success">Yes</span>
                    @else
                        <span class="badge bg-secondary">No</span>
                    @endif
                </td>
                <td>{{ $notification->created_at->format('Y-m-d H:i') }}</td>
				<td>
    @if ($notification->acknowledgedByUsers->count())
        <div class="position-relative d-inline-block">
<span
    class="badge bg-info text-white"
    data-bs-toggle="tooltip"
    data-bs-html="true"
    data-bs-placement="top"
    title="{!! $notification->acknowledgedByUsers->map(fn($user) => $user->name . ' - ' . \Carbon\Carbon::parse($user->pivot->acknowledged_at)->format('Y-m-d H:i'))->implode('<br>') !!}">
    {{ $notification->acknowledgedByUsers->count() }} user(s)
</span>

        </div>
    @else
        <span class="badge bg-warning text-dark">None</span>
    @endif
</td>

                <td>
                    <a href="{{ route('admin.notifications.edit', $notification->id) }}" class="btn btn-sm btn-primary">Edit</a>

                    <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this notification?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>


    {{ $notifications->links() }}
</div>

          </div>
    </x-page-template>
	@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
