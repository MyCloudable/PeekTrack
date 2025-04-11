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
            <h3 style="color: #FFF;">WEO/PD Report</h3><br>

            <!-- Employee Time Summary Table -->
            <div class="card">
                <div class="card-header p-3 pb-0">
                    <h4 class="mb-0">Report for dates ({{ $startDate }} - {{ $endDate }})</h4>
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

                            // Call the stored procedure to get raw timesheet data
                            $stmt = $conn->prepare("CALL GetTimesheetsData(:startDate, :endDate)");
                            $stmt->bindParam(':startDate', $startDate);
                            $stmt->bindParam(':endDate', $endDate);
                            $stmt->execute();

                            // Fetch the data returned by the stored procedure
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($result) > 0) {
                                // Initialize arrays to store the totals for per_diem and weekend_out by user and date
                                $userTotals = [];

                                foreach ($result as $row) {
                                    $userId = $row['user_id'];
                                    $workDate = date('Y-m-d', strtotime($row['clockin_time']));
                                    $perDiem = $row['per_diem'];
                                    $weekendOut = $row['weekend_out'];
                                    $location = $row['location']; // Assuming 'location' is available in the query results

                                    // Initialize the array for this user if not already done
                                    if (!isset($userTotals[$userId])) {
                                        $userTotals[$userId] = [
                                            'name' => $row['crew_member_name'],
                                            'location' => $location, // Add location to user details
                                            'per_diem_total' => 0,
                                            'weekend_out_total' => 0,
                                            'daily' => [], // Stores per-diem and weekend-out by day
                                        ];
                                    }

                                    // If this is the first time we're seeing this date, add it to the array
                                    if (!isset($userTotals[$userId]['daily'][$workDate])) {
                                        $userTotals[$userId]['daily'][$workDate] = [
                                            'per_diem' => 0,
                                            'weekend_out' => 0,
                                        ];
                                    }

                                    // Update per diem for the day based on 'per_diem' field value
                                    if ($perDiem == 'f') {
                                        // Full per diem for the day
                                        $userTotals[$userId]['daily'][$workDate]['per_diem'] = 1;
                                    } elseif ($perDiem == 'h') {
                                        // Half per diem, accumulate up to 1
                                        if ($userTotals[$userId]['daily'][$workDate]['per_diem'] < 1) {
                                            $userTotals[$userId]['daily'][$workDate]['per_diem'] += 0.5;
                                        }
                                    }

                                    // Update weekend_out, ensuring it's only counted on weekends and has a truthy value
                                    $dayOfWeek = date('N', strtotime($workDate)); // 6 = Saturday, 7 = Sunday
                                    if (!empty($weekendOut) && in_array($dayOfWeek, [6, 7])) {
                                        $userTotals[$userId]['daily'][$workDate]['weekend_out'] = 1;
                                    }
                                }

                                // Now calculate the totals for each user
                                foreach ($userTotals as $userId => $totals) {
                                    foreach ($totals['daily'] as $day => $dayData) {
                                        $userTotals[$userId]['per_diem_total'] += $dayData['per_diem'];
                                        $userTotals[$userId]['weekend_out_total'] += $dayData['weekend_out'];
                                    }
                                }

                                // Output table
                                echo "<table id='crewTimeTable' class='table align-items-center table-striped mb-0' style='width:100%'>";
                                echo "<thead><tr><th>User</th><th>Employee ID</th><th>Location</th><th>Per Diem Total</th><th>Weekend Out Total</th></tr></thead><tbody>";

                                // Display user totals in the table
                                foreach ($userTotals as $userId => $totals) {
                                    // Display 0 instead of dash for proper sorting
                                    $weekendOutTotal = $totals['weekend_out_total'] > 0 ? $totals['weekend_out_total'] : 0;

                                    echo "<tr>";
                                    echo "<td><h6>{$totals['name']}</h6></td>";
									echo "<td><h6>{$totals['id']}</h6></td>";
                                    echo "<td><h6>{$totals['location']}</h6></td>"; // Display location
                                    // Format per_diem_total to 1 decimal place without rounding
                                    echo "<td><h6>" . number_format($totals['per_diem_total'], 1, '.', '') . "</h6></td>";
                                    echo "<td><h6>{$weekendOutTotal}</h6></td>";
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
                paging: true,      // Enable pagination
                pageLength: 25,    // Show 25 entries per page
                searching: true,   // Enable the search bar
                dom: 'Bfrtip',     // Add the buttons to the DOM
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
                autoWidth: true    // Ensure that the table auto-sizes to the content
            });
        });
    </script>
    @endpush
</x-page-template>
