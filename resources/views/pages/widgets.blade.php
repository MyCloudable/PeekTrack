
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-page-template bodyClass='g-sidenav-show  bg-gray-200 dark-version'>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets') }}/css/datepicker.css" />
    <x-auth.navbars.sidebar activePage='pages' activeItem='widgets' activeSubitem=''></x-auth.navbars.sidebar>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle='Export Data'></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                <div class="card">
                        <div class="card-header p-3 pb-0">
                            <h3 class="mb-0">File Export</h3>
            <form method="POST" action="{{ route('jobs.exportFile') }}">
                @csrf
                
  <h6>Export Type</h6>
  <label>
    <input type="checkbox" class="radio" value="1" name="check" onclick="onlyOne(this)" />Production</label>
  <label>
    <input type="checkbox" class="radio" value="2" name="check" onclick="onlyOne(this)" />Material</label>
  <label>
    <input type="checkbox" class="radio" value="3" name="check" onclick="onlyOne(this)"/>Equipment</label>&nbsp;&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp     Work Date:  <input type="text" name="daterange" value="01/01/2018 - 01/15/2018" /><br>
            <input type="submit" value="Export"/>
            </form>
</div>
</div><br></div></div>
                    
                
                </div><br>

            </div>



            <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>
    </main>
    <x-plugins></x-plugins>
    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/fullcalendar.min.js"></script>
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>

    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>

    <!-- Kanban scripts -->

    <script src="{{ asset('assets') }}/js/plugins/chartjs.min.js"></script>
    <!-- DataTables JavaScript initialization -->
<!-- DataTables JavaScript initialization -->
<script>
function onlyOne(checkbox) {
    var checkboxes = document.getElementsByName('check')
    checkboxes.forEach((item) => {
        if (item !== checkbox) item.checked = false
    })
}

$(function() {
  $('input[name="daterange"]').daterangepicker({
    opens: 'left',
    minDate: '01/01/2024'
  }, function(start, end, label) {
    console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
  });
});

    $(document).ready(function() {
        $('#example').DataTable({
            dom: 'Bfrtip', // Add the buttons to the DOM
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
                'print'
            ]
        });
    });
</script>
    @endpush
</x-page-template>
