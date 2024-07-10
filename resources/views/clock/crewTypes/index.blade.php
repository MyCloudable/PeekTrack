<x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
<x-auth.navbars.sidebar activePage="crews" activeItem="" activeSubitem=""></x-auth.navbars.sidebar>
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <x-auth.navbars.navs.auth pageTitle="Crews"></x-auth.navbars.navs.auth>
    <div class="container-fluid py-4">
         <div class="row">
            <div class="col-md-12">
                <div class="">
                    <table class="table table-flush table-striped" id="datatable-basic">
                        <thead class="thead-light">
                            <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Name</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-9">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($crewTypes as $crewType)
                            <tr>
                                <td class="text-md font-weight-bold"><h5>{{ $crewType->name }}</h5></td>
                                <td class="text-md font-weight-bold"><h5>{{ $crewType->value }}</h5></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
                </div>
            </div>
         </div>
    </div>
</main>

<x-push-script-stack />

</x-page-template>