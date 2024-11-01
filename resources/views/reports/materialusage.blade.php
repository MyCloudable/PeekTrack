<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Usage Report</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.1/css/buttons.dataTables.min.css">
</head>
<body class="g-sidenav-show bg-gray-600">

<x-page-template bodyClass="g-sidenav-show bg-gray-600">
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Reports"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->

        <div class="container-fluid py-4">
            <h3 style="color: #FFF;">Material Usage Report</h3><br>

            <!-- Material Usage Report Table -->
            <div class="card">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Materials Used ({{ $_POST['start_date'] }} - {{ $_POST['end_date'] }})</h4>
                </div>
                <div class="card-body border-radius-lg p-3">
                    <div class="table-responsive">
                        <?php
                        // Database connection parameters
                        $host = 'localhost';
                        $dbname = 'peektrackv2';
                        $username = 'root';
                        $password = 'Cl0ud@bl3!';

                        try {
                            // Connect to MariaDB
                            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                            // Collect input values from form submission
                            $location = !empty($_POST['location']) ? $_POST['location'] : null;
                            $branch = !empty($_POST['branch']) ? $_POST['branch'] : null;
                            $material_name = $_POST['material_name'];
                            $start_date = $_POST['start_date'];
                            $end_date = $_POST['end_date'];

                            // Prepare the call to the stored procedure
                            $stmt = $pdo->prepare("CALL GetConsolidatedMaterialUsage(:location, :branch, :material_name, :start_date, :end_date)");
                            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
                            $stmt->bindParam(':branch', $branch, PDO::PARAM_STR);
                            $stmt->bindParam(':material_name', $material_name, PDO::PARAM_STR);
                            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
                            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);

                            // Execute the stored procedure
                            $stmt->execute();
                            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Display results in a table format if data exists
                            if (count($results) > 0) {
                                echo "<table id='materialUsageTable' class='table align-items-center table-striped mb-0' style='width:100%'>";
                                echo "<thead><tr>
                                    <th>Location</th>
                                    <th>Branch</th>
                                    <th>Job Number</th>
									<th>Date</th>
                                    <th>Material Name</th>
                                    <th>Total Quantity</th>
                                    <th>Unit of Measure</th>
                                    <th>Entered By</th>
                                </tr></thead><tbody>";
                                
                                // Populate table rows
                                foreach ($results as $row) {
                                    echo "<tr>";
                                    echo "<td>{$row['location']}</td>";
                                    echo "<td>{$row['branch']}</td>";
                                    echo "<td>{$row['job_number']}</td>";
									echo "<td>{$row['workdate']}</td>";
                                    echo "<td>{$row['material_name']}</td>";
                                    echo "<td>{$row['total_quantity']}</td>";
                                    echo "<td>{$row['unit_of_measure']}</td>";
                                    echo "<td>{$row['entered_by_user']}</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody></table>";
                            } else {
                                echo "<p>No records found for the specified criteria.</p>";
                            }

                        } catch (PDOException $e) {
                            echo "Error: " . $e->getMessage();
                        }

                        // Close connection
                        $pdo = null;
                        ?>
                    </div>
                </div>
            </div>

        </div>
    </main>
    <x-auth.footers.auth.footer></x-auth.footers.auth.footer>

    @push('js')
    <script src="{{ asset('assets') }}/js/plugins/perfect-scrollbar.min.js"></script>

    <!-- Include DataTables and Buttons -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.0.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.2.2/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            $('#materialUsageTable').DataTable({
                paging: true,
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    'pdfHtml5',
                    'print'
                ],
                autoWidth: true
            });
        });
    </script>
    @endpush
</x-page-template>

</body>
</html>
