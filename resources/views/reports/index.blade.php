<x-page-template bodyClass='g-sidenav-show  bg-gray-600'>
    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Include DataTables Buttons CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.1/css/buttons.dataTables.min.css">


    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        <!-- Navbar -->
        <x-auth.navbars.navs.auth pageTitle="Reports"></x-auth.navbars.navs.auth>
        <!-- End Navbar -->
        <div class="container-fluid py-4">
        <h3 style="color: #FFF;">Job Number - 3-99-0069</h3><br>
		<div class="card">
                        <div class="card-header p-3 pb-0">
                            <h4 class="mb-0">Production By Day</h4>
                            <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                        </div>
                        <div class="card-body border-radius-lg p-3">
                        <?php
// Database connection settings
$servername = "localhost";
$username = "root"; // Replace with your MySQL username
$password = "Cl0ud@bl3!"; // Replace with your MySQL password
$dbname = "peektrack"; // Replace with your MySQL database name

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Call the stored procedure RoadNameList('3-05-1698')
    $p_job_number = '3-99-0069';
    
    $stmt = $conn->prepare("CALL BillingProductionByDay(:p_job_number)");
    $stmt->bindParam(':p_job_number', $p_job_number);
    $stmt->execute();

    // Fetch the data returned by the stored procedure
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Display the result in an HTML table with DataTables and Buttons
    echo "<table id='table3' class='table align-items-center table-striped mb-0' style='width:100%'>";
    // Output table header
    echo "<thead><tr>";
    foreach ($result[0] as $key => $value) {
        echo "<th><h5>" . ucfirst($key) . "</h5></th>"; // Capitalize the first letter of each column name
    }
    echo "</tr></thead><tbody>";
    // Output table data
    foreach ($result as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td><h6>$value</h6></td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$conn = null;
?>
                        </div>
                    </div>

                    <br>
                    <div class="card">
                        <div class="card-header p-3 pb-0">
                            <h4 class="mb-0">Material By Day</h4>
                            <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                        </div>
                        <div class="card-body border-radius-lg p-3">
                        <?php
// Database connection settings

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Call the stored procedure RoadNameList('3-05-1698')
    
    
    $stmt = $conn->prepare("CALL BillingMaterialByDay(:p_job_number)");
    $stmt->bindParam(':p_job_number', $p_job_number);
    $stmt->execute();

    // Fetch the data returned by the stored procedure
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Display the result in an HTML table with DataTables and Buttons
    echo "<table id='table3' class='table align-items-center table-striped mb-0' style='width:100%'>";
    // Output table header
    echo "<thead><tr>";
    foreach ($result[0] as $key => $value) {
        echo "<th><h5>" . ucfirst($key) . "</h5></th>"; // Capitalize the first letter of each column name
    }
    echo "</tr></thead><tbody>";
    // Output table data
    foreach ($result as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td><h6>$value</h6></td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$conn = null;
?>

                        </div>
                    </div>
                    <br>
                    <div class="card">
                        <div class="card-header p-3 pb-0">
                            <h4 class="mb-0">Equiupment By Day</h4>
                            <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                        </div>
                        <div class="card-body border-radius-lg p-3">
                        <?php
// Database connection settings

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Call the stored procedure RoadNameList('3-05-1698')
   
    
    $stmt = $conn->prepare("CALL BillingEquipmentByDay(:p_job_number)");
    $stmt->bindParam(':p_job_number', $p_job_number);
    $stmt->execute();

    // Fetch the data returned by the stored procedure
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Display the result in an HTML table with DataTables and Buttons
    echo "<table id='table3' class='table align-items-center table-striped mb-0' style='width:100%'>";
    // Output table header
    echo "<thead><tr>";
    foreach ($result[0] as $key => $value) {
        echo "<th><h5>" . ucfirst($key) . "</h5></th>"; // Capitalize the first letter of each column name
    }
    echo "</tr></thead><tbody>";
    // Output table data
    foreach ($result as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td><h6>$value</h6></td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$conn = null;
?>

                        </div>
                    </div>
                    <br>
                    <div class="card">
                        <div class="card-header p-3 pb-0">
                            <h4 class="mb-0">Notes</h4>
                            <p class="text-sm mb-0 text-capitalize font-weight-normal"></p>
                        </div>
                        <div class="card-body border-radius-lg p-3">
                        <?php
// Database connection settings

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Call the stored procedure RoadNameList('3-05-1698')
   
    
    $stmt = $conn->prepare("select workdate, note from jobentries, job_notes where jobentries.link = job_notes.link and job_number = :p_job_number");
    $stmt->bindParam(':p_job_number', $p_job_number);
    $stmt->execute();

    // Fetch the data returned by the stored procedure
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Display the result in an HTML table with DataTables and Buttons
    echo "<table id='table3' class='table align-items-center table-striped mb-0' style='width:100%'>";
    // Output table header
    echo "<thead><tr>";
    foreach ($result[0] as $key => $value) {
        echo "<th><h5>" . ucfirst($key) . "</h5></th>"; // Capitalize the first letter of each column name
    }
    echo "</tr></thead><tbody>";
    // Output table data
    foreach ($result as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td><h6>$value</h6></td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}


// Close connection
$conn = null;
?>

                        </div>
                    </div>
                </div>
                       <x-auth.footers.auth.footer></x-auth.footers.auth.footer>
        </div>
    </main>
    <x-plugins></x-plugins>
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
    $('#table').DataTable({
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
        ]
    });
});

        

    </script>
    @endpush
</x-page-template>
