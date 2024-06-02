@push('js')

    <link href="{{ asset('assets') }}/css/datatables.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <script src="{{ asset('assets') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('assets') }}/js/datatables.min.js"></script>
    <script>
        $(document).ready(function() {
            oTable = $('#datatable-basic').dataTable();
            /* Filter immediately */
            oTable.fnFilter( branch);
        } );
    </script>

@endpush