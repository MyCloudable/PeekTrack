@if(session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <span class="alert-icon align-middle">
        <span class="material-icons text-md">
        thumb_up_off_alt
        </span>
        </span>
        <span class="alert-text">{{ session()->get('message') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif