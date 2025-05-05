<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<x-auth.navbars.sidebar activePage="crews" activeItem="" activeSubitem=""></x-auth.navbars.sidebar>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg " id="app">
    <x-auth.navbars.navs.auth pageTitle="Crews"></x-auth.navbars.navs.auth>
    <div class="container-fluid py-4">
         <div class="row">
            @if ($auth_role == 1 || $auth_role == 2 || $auth_role == 7)
            <div class="col-md-12 mb-3">
                <a href="{{route('crews.create')}}" class="btn btn-info">Create crew</a>
                <x-alert />
            </div>
            @endif
            <div class="col-md-12">
                <div class="">
                    <table class="table table-flush table-striped" id="datatable-basic">
                        <thead class="thead-light">
                            <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Action</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Crew type</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">SuperIndentend name</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Last verified date</th>
                            @if ($auth_role == 1)
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Action</th>
                            @endif

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($crews as $crew)
                            <tr>
                                <td class="text-sm font-weight-normal">
                                    @if (!$crew->deleted_at)
                                        
                                    <button type="button" class="btn bg-gradient-primary show-crew-members" data-bs-toggle="modal" 
                                    data-bs-target="#show-crew-members"
                                    data-crew-id="{{$crew->id}}"
                                    >Show</button>
                                    <a href="{{route('crews.edit', $crew)}}" class="btn btn-warning">Edit</a>
                                    @if ($auth_role == 1 || $auth_role == 2 || $auth_role == 7)
                                    <form class="d-inline" action="{{ route('crews.destroy', $crew) }}" method="POST">
                                        @method('DELETE')
                                        @csrf
                                        <input type="submit" class="btn btn-danger" value="Delete" />
                                    </form>
                                    @endif
                                    
                                    @endif
                                </td>
                                <td class="text-md font-weight-bold"><h5>{{ $crew->crewType->name }}</h5></td>
                                <td class="text-sm font-weight-normal"><h5>{{ $crew->superintendent->name }}</h5></td>
                                <td class="text-sm font-weight-normal"><h5>{{ $crew->last_verified_date }}</h5></td>
                                @if ($auth_role == 1)
                                    
                                    <td class="text-sm font-weight-normal">
                                    @if ($crew->deleted_at)
                                    <crewindex :crew-id="{{$crew->id}}" />
                                    @endif
                                    </td>
                                @endif
                                

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @include('clock.crews.show')
                    <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
                </div>
            </div>
         </div>
    </div>
</main>

<x-push-script-stack />

</x-page-template>