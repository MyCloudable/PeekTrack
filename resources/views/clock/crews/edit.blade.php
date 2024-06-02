<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<x-auth.navbars.sidebar activePage="crews" activeItem="" activeSubitem=""></x-auth.navbars.sidebar>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <x-auth.navbars.navs.auth pageTitle="Update crew"></x-auth.navbars.navs.auth>
    <div class="container-fluid py-4">
         <div class="row">
            <div class="col-md-12">
                <div class="card-body pt-0">
                    <form method="POST" action="{{ route('crews.update', $crew) }}">
                        @csrf
                        @method('PUT')
                        <div class="input-group input-group-outline">
                            <label class="form-label">Crew name</label>
                            <input type="text" name='crew_name' class="form-control" value="{{$crew->crew_name}}"
                            {{(auth()->user()->role_id !== 1 && auth()->user()->role_id !== 2) ? 'readonly' : ''}}>
                        </div>
                        @error('crew_name')
                        <p class='text-danger inputerror'>{{ $message }} </p>
                        @enderror

                        <div class="input-group input-group-outline mt-4">
                            <select name="superintendentId" class="form-control" id="exampleFormControlSelect1"
                            style="{{(auth()->user()->role_id !== 1 && auth()->user()->role_id !== 2) ? 'pointer-events: none;' : ''}}"
                            
                            >
                                <option value="">Select superintendent</option>
                                @foreach ($users as $user)
                                <option value="{{$user->id}}" {{ $user->id === $crew->superintendentId ? 'selected' : '' }}>{{$user->email}}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('superintendentId')
                        <p class='text-danger inputerror'>{{ $message }} </p>
                        @enderror

                        <div class="input-group input-group-outline mt-4 position-relative">
                            <select class="selectpicker multiselect" multiple data-live-search="true" name="crew_members[]">
                                @foreach ($users as $user)
                                <option value="{{$user->id}}" {{in_array($user->id, $crew->crew_members) ? 'selected' : '' }}>{{$user->email}}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('crew_members')
                        <p class='text-danger inputerror'>{{ $message }} </p>
                        @enderror

                        <button class="btn bg-gradient-dark btn-sm mt-6 mb-0">Update</button>
                    </form>
                </div>
            </div>
         </div>
    </div>
</main>

@push('css')
<style>
.dropdown-toggle{
    width: 800px !important;
}
.dropdown-menu.show{
    top: 0px !important;
}
.dropdown-item{
    color: white !important;
}
.dropdown-item:hover{
    background: black !important;
}
</style>

@endpush

@push('js')
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        $('.multiselect').selectpicker();
    });
    </script>

@endpush

</x-page-template>