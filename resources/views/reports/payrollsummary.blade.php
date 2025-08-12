<style>
    table.dataTable thead th h5 {
        color: black !important; 
    }
</style>
<x-page-template bodyClass='g-sidenav-show bg-gray-600'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Include DataTables Buttons CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.1/css/buttons.dataTables.min.css">

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Reports"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->

        <div class="container-fluid py-4">
            <h3 style="color: #FFF;">Time Summary</h3><br>

            <!-- Crew Member Time Table -->
            <div class="card">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Time Report By Week ({{ $startDate }} - {{ $endDate }})</h4>
                    <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <div class="table-responsive">
                        <?php
                        try {
                            // Database connection settings
                            $servername = "localhost";
                            $username = "root"; // Replace with your MySQL username
                            $password = "Cl0ud@bl3!"; // Replace with your MySQL password
                            $dbname = "peektrackv2"; // Replace with your MySQL database name

                            // Create connection
                            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                            // Call the stored procedure
                            $stmt = $conn->prepare("CALL GenerateWeeklyReport(:startDate, :endDate)");
                            $stmt->bindParam(':startDate', $startDate);
                            $stmt->bindParam(':endDate', $endDate);
                            $stmt->execute();

                            // Fetch the data returned by the stored procedure
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($result) > 0) {
                                echo "<table id='crewTimeTable' class='table align-items-center table-striped mb-0' style='width:100%'>";
                                // Output table header
                                echo "<thead><tr>";
                                foreach ($result[0] as $key => $value) {
                                    echo "<th><h5>" . ucfirst($key) . "</h5></th>"; // Capitalize the first letter of each column name
                                }
                                echo "<th><h5>Weekly Total</h5></th>"; // Add column for Weekly Total
                                echo "</tr></thead><tbody>";

                                // Output table data
                                foreach ($result as $row) {
                                    echo "<tr>";
                                    $weeklyTotal = 0; // Initialize weekly total for this row
                                    foreach ($row as $key => $value) {
                                        // Display the value in the table
                                        echo "<td><h6>$value</h6></td>";
                                        // Add to weekly total if the key is a date column
                                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $key)) {
                                            $weeklyTotal += (float)$value; // Sum up the daily hours
                                        }
                                    }
                                    echo "<td><h6>$weeklyTotal</h6></td>"; // Display the weekly total
                                    echo "</tr>";
                                }

                                echo "</tbody></table>";
                            } else {
                                echo "<p>No data available for the selected date range.</p>";
                            }

                        } catch (PDOException $e) {
                            echo "Error: " . $e->getMessage();
                        }

                        // Close connection
                        $conn = null;
                        ?>
                    </div>
                </div>
            </div>

        </div>
    </main>
    <x-auth.footers.auth.footer></x-auth.footers.auth.footer>

    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>

    <!-- Include DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <!-- Include DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.2.2/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            $('#crewTimeTable').DataTable({
                dom: 'Bfrtip', // Add the buttons to the DOM
                buttons: [
                    {
                        extend: 'copyHtml5',
                        text: 'Copy All'
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Export to Excel'
                    },
                    {
                        extend: 'csvHtml5',
                        text: 'Export to CSV'
                    },
                    {
                        extend: 'pdfHtml5',
                        text: 'Export to PDF'
                    },
                    {
                        extend: 'print',
                        text: 'Print All'
                    }
                ],
                autoWidth: true // Ensure that the table auto-sizes to the content
            });
        });
    </script>
    @endpush
</x-page-template>
