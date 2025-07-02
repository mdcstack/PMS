<?php require_once(dirname(__FILE__) . '/config.php');
if (!isset($_SESSION['Admin_ID']) || !isset($_SESSION['Login_Type'])) {
    header('location:' . BASE_URL);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <title>Attendance - Payroll</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>plugins/datatables/dataTables.bootstrap.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>plugins/datatables/jquery.dataTables_themeroller.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>dist/css/AdminLTE.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>dist/css/skins/_all-skins.min.css">

    <style>
        /* Custom styles for better spacing and responsiveness */
        .filter-section {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens */
            gap: 15px; /* Space between filter elements */
            align-items: flex-end; /* Aligns items to the bottom */
        }
        .filter-section .form-group {
            margin-bottom: 0; /* Remove default form-group margin */
            flex: 1; /* Allows form groups to grow and shrink */
            min-width: 150px; /* Minimum width before wrapping */
        }
        .filter-section .btn {
            height: 34px; /* Match height of input fields */
            align-self: flex-end; /* Align button to the bottom */
        }
        @media (max-width: 767px) {
            .filter-section {
                flex-direction: column; /* Stack items vertically on very small screens */
                align-items: stretch;
            }
            .filter-section .form-group,
            .filter-section .btn {
                width: 100%; /* Full width for stacked items */
            }
        }
    </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
    <div class="wrapper">

        <?php require_once(dirname(__FILE__) . '/partials/topnav.php'); ?>

        <?php require_once(dirname(__FILE__) . '/partials/sidenav.php'); ?>

        <div class="content-wrapper">
            <section class="content-header">
                <h1>
                    Attendance
                </h1>
                <ol class="breadcrumb">
                    <li><a href="<?php echo BASE_URL; ?>"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Attendance</li>
                </ol>
            </section>

            <section class="content">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">
                                <?php if ($_SESSION['Login_Type'] == 'admin') {
                                    echo 'Employee Attendance';
                                } else {
                                    echo 'My Attendance';
                                } ?>
                                </h3>
                            </div>
                            <div class="box-body">
                                <div class="filter-section">
                                    <div class="form-group">
                                        <label for="start_date">Start Date:</label>
                                        <input type="date" class="form-control" id="start_date">
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date">End Date:</label>
                                        <input type="date" class="form-control" id="end_date">
                                    </div>
                                    <button type="button" class="btn btn-primary" id="filter_button">Filter</button>
                                    <?php if ($_SESSION['Login_Type'] == 'admin') { ?>
                                        <button type="button" class="btn btn-success" id="generate_pdf_button">Generate PDF</button>
                                    <?php } ?>
                                </div>
                                <div class="table-responsive">
                                    <table id="attendance" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                            <?php if ($_SESSION['Login_Type'] == 'admin') { ?>
                                                <th>DATE</th>
                                                <th>EMP CODE</th>
                                                <th>NAME</th>
                                                <th>PUNCH-IN</th>
                                                <th>PUNCH-IN MESSAGE</th>
                                                <th>PUNCH-OUT</th>
                                                <th>PUNCH-OUT MESSAGE</th>
                                                <th>WORK HOURS</th>
                                            <?php } else { ?>
                                                <th>DATE</th>
                                                <th>PUNCH-IN</th>
                                                <th>PUNCH-IN MESSAGE</th>
                                                <th>PUNCH-OUT</th>
                                                <th>PUNCH-OUT MESSAGE</th>
                                                <th>WORK HOURS</th>
                                            <?php } ?>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        <?php // require_once(dirname(__FILE__) . '/partials/footer.php'); // Uncomment if you have a footer partial ?>
        <?php // require_once(dirname(__FILE__) . '/partials/control_sidebar.php'); // Uncomment if you have a control sidebar partial ?>
    </div>
    <script src="<?php echo BASE_URL; ?>plugins/jQuery/jquery-2.2.3.min.js"></script>
    <script src="<?php echo BASE_URL; ?>bootstrap/js/bootstrap.min.js"></script>
    <script src="<?php echo BASE_URL; ?>plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo BASE_URL; ?>plugins/datatables/dataTables.bootstrap.min.js"></script>
    <script src="<?php echo BASE_URL; ?>plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
    <script src="<?php echo BASE_URL; ?>dist/js/app.min.js"></script>
    <script type="text/javascript">var baseurl = '<?php echo BASE_URL; ?>';</script>

    <script>
    $(document).ready(function() {
        if ($('#attendance').length > 0) {
            var ajaxUrl = '';
            <?php if ($_SESSION['Login_Type'] == 'admin') { ?>
                ajaxUrl = baseurl + "ajax/?case=LoadingAttendance";
            <?php } else { ?>
                ajaxUrl = baseurl + "ajax/?case=LoadingMyAttendance";
            <?php } ?>

            // Initialize DataTable
            var attendanceTable = $('#attendance').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": ajaxUrl,
                    "type": "POST", // Use POST to send date parameters
                    "data": function (d) {
                        // Add custom parameters to the DataTables request
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                "order": [[0, 'desc']], // Default order by date descending
                "columnDefs": [
                    { "targets": 0, "className": "dt-center" }, // Apply center alignment to the first column (Date)
                    { "targets": 1, "className": "dt-center" }  // Apply center alignment to the second column (EMP CODE or PUNCH-IN)
                ]
            });

            // Handle filter button click
            $('#filter_button').on('click', function() {
                attendanceTable.ajax.reload(); // Reload the table data with new filter parameters
            });

            // Handle generate PDF button click - this will now only execute if the button is present (i.e., for admin)
            $('#generate_pdf_button').on('click', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                var pdfUrl = '';

                // This block is already conditionally rendered in PHP for admin only
                pdfUrl = baseurl + "generate-attendance-pdf.php?user_type=admin&start_date=" + startDate + "&end_date=" + endDate;
                
                window.open(pdfUrl, '_blank'); // Open the PDF in a new tab/window
            });
        }
    });
    </script>
</body>
</html>