<style>
    table.dataTable thead th h5 {
        color: black !important;
    }
</style>

<x-page-template bodyClass='g-sidenav-show bg-gray-600'>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.0.1/css/buttons.dataTables.min.css">

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Reports" />
        <!-- End Navbar -->

        <div class="container-fluid py-4">
            <h3 style="color: #FFF;">Overflow Items Report</h3><br>

            <div class="card">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Submitted Between ({{ $startDate }} - {{ $endDate }})</h4>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <div class="table-responsive">
                        <?php
                        try {
                            $servername = "localhost";
                            $username = "root";
                            $password = "Cl0ud@bl3!";
                            $dbname = "peektrackv2";

                            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                            $stmt = $conn->prepare("CALL GetOverflowItemsBySubmittedDate(:startDate, :endDate)");
                            $stmt->bindParam(':startDate', $startDate);
                            $stmt->bindParam(':endDate', $endDate);
                            $stmt->execute();

                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($result) > 0) {
                                echo "<table id='overflowItemsTable' class='table align-items-center table-striped mb-0' style='width:100%'>";
                                echo "<thead><tr>";
                                foreach ($result[0] as $key => $value) {
                                    echo "<th><h5>" . ucwords(str_replace('_', ' ', $key)) . "</h5></th>";
                                }
                                echo "</tr></thead><tbody>";

                                foreach ($result as $row) {
                                    echo "<tr>";
                                    foreach ($row as $value) {
                                        echo "<td><h6>" . htmlspecialchars($value) . "</h6></td>";
                                    }
                                    echo "</tr>";
                                }

                                echo "</tbody></table>";
                            } else {
                                echo "<p>No data available for the selected date range.</p>";
                            }

                        } catch (PDOException $e) {
                            echo "<p class='text-danger'>Error: " . $e->getMessage() . "</p>";
                        }

                        $conn = null;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <x-auth.footers.auth.footer />

    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.2.2/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

    <script>
        $(document).ready(function () {
            $('#overflowItemsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copyHtml5', text: 'Copy All' },
                    { extend: 'excelHtml5', text: 'Export to Excel' },
                    { extend: 'csvHtml5', text: 'Export to CSV' },
                    { extend: 'pdfHtml5', text: 'Export to PDF' },
                    { extend: 'print', text: 'Print All' }
                ],
                autoWidth: true
            });
        });
    </script>
    @endpush
</x-page-template>
