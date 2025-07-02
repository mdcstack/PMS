<?php
session_start();

require_once('config.php'); // Load DB connection and functions
require_once 'vendor/autoload.php'; // Load Dompdf (adjust path if needed)
require_once('functions.php'); // Assuming you have common functions here, like for employee data

use Dompdf\Dompdf;
use Dompdf\Options;

// Ensure user is logged in and authorized
if (!isset($_SESSION['Admin_ID']) || !isset($_SESSION['Login_Type'])) {
    die("Unauthorized access.");
}

// Get input from URL
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$user_type = $_GET['user_type'] ?? 'employee'; // 'admin' or 'employee'

// Validate dates (basic check)
if (empty($start_date)) {
    // If no start date, default to a reasonable past date or just fetch all until end_date
    $start_date = date('Y-m-01', strtotime('-1 month')); // Example: first day of last month
}
if (empty($end_date)) {
    // If no end date, default to today
    $end_date = date('Y-m-d');
}

// Sanitize inputs
$start_date_sanitized = mysqli_real_escape_string($db, $start_date);
$end_date_sanitized = mysqli_real_escape_string($db, $end_date);


$attendance_data = [];
$report_title = "Attendance Report";
$employee_name_for_title = '';

// Prepare the WHERE clause for date filtering
$date_filter_clause = [];
if (!empty($start_date_sanitized)) {
    $date_filter_clause[] = "attendance_date >= '$start_date_sanitized'";
}
if (!empty($end_date_sanitized)) {
    $date_filter_clause[] = "attendance_date <= '$end_date_sanitized'";
}
$date_filter_sql = " WHERE " . implode(" AND ", $date_filter_clause);

if ($user_type === 'admin' && $_SESSION['Login_Type'] === 'admin') {
    // Admin view: all employees
    $report_title = "Employee Attendance Report";

    $sql = "SELECT `emp`.`emp_code`, `emp`.`first_name`, `emp`.`last_name`, `att`.`attendance_date`, 
            GROUP_CONCAT(`att`.`action_time` ORDER BY `att`.`action_time` ASC) AS `times`, 
            GROUP_CONCAT(`att`.`emp_desc` ORDER BY `att`.`action_time` ASC) AS `descs`";
    $sql .= " FROM `" . DB_PREFIX . "employees` AS `emp`
              JOIN `" . DB_PREFIX . "attendance` AS `att` ON `emp`.`emp_code` = `att`.`emp_code`";
    $sql .= $date_filter_sql;
    $sql .= " GROUP BY `emp`.`emp_code`, `att`.`attendance_date` 
              ORDER BY `att`.`attendance_date` DESC, `emp`.`first_name` ASC";

    $query = mysqli_query($db, $sql);
    if (!$query) {
        error_log("MySQL Error in generate-attendance-pdf (admin): " . mysqli_error($db));
        die("Error fetching attendance data for PDF.");
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $times = explode(',', $row["times"]);
        $descs = explode(',', $row["descs"]);
        
        $punch_in = isset($times[0]) ? date('h:i:s A', strtotime($times[0])) : '';
        $punch_in_msg = isset($descs[0]) ? htmlspecialchars($descs[0]) : '';
        $punch_out = isset($times[1]) ? date('h:i:s A', strtotime($times[1])) : '';
        $punch_out_msg = isset($descs[1]) ? htmlspecialchars($descs[1]) : '';
        
        $work_hours = '';
        if (isset($times[0]) && isset($times[1])) {
            try {
                $datetime1 = new DateTime($row['attendance_date'] . ' ' . $times[0]);
                $datetime2 = new DateTime($row['attendance_date'] . ' ' . $times[1]);
                $interval = $datetime1->diff($datetime2);
                $work_hours = $interval->format('%h') . " Hrs | " . $interval->format('%i') . " Min";
            } catch (Exception $e) {
                $work_hours = 'Invalid Time';
            }
        }

        $attendance_data[] = [
            'date' => date('d-m-Y', strtotime($row['attendance_date'])),
            'emp_code' => $row['emp_code'],
            'name' => htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']),
            'punch_in' => $punch_in,
            'punch_in_msg' => $punch_in_msg,
            'punch_out' => $punch_out,
            'punch_out_msg' => $punch_out_msg,
            'work_hours' => $work_hours
        ];
    }

} else if ($user_type === 'employee' && $_SESSION['Login_Type'] === 'employee') {
    // Employee view: own attendance
    $emp_code = mysqli_real_escape_string($db, $_SESSION['Admin_ID']); // Assuming Admin_ID holds emp_code for employees
    $empData = GetEmployeeDataByEmpCode($emp_code); // Fetch employee name for title
    $employee_name_for_title = htmlspecialchars($empData['first_name'] . ' ' . $empData['last_name']);
    $report_title = "My Attendance Report - " . $employee_name_for_title;

    $sql = "SELECT attendance_date, GROUP_CONCAT(action_time ORDER BY action_time ASC) AS times, 
            GROUP_CONCAT(emp_desc ORDER BY action_time ASC) AS descs 
            FROM " . DB_PREFIX . "attendance";
    $sql .= " WHERE emp_code = '$emp_code'"; // Filter by specific employee
    if (!empty($date_filter_clause)) { // Add date filters
        $sql .= " AND " . implode(" AND ", $date_filter_clause);
    }
    $sql .= " GROUP BY attendance_date ORDER BY attendance_date DESC";

    $query = mysqli_query($db, $sql);
    if (!$query) {
        error_log("MySQL Error in generate-attendance-pdf (employee): " . mysqli_error($db));
        die("Error fetching attendance data for PDF.");
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $times = explode(',', $row['times']);
        $descs = explode(',', $row['descs']);
        
        $punch_in = isset($times[0]) ? date('h:i:s A', strtotime($times[0])) : '';
        $punch_in_msg = isset($descs[0]) ? htmlspecialchars($descs[0]) : '';
        $punch_out = isset($times[1]) ? date('h:i:s A', strtotime($times[1])) : '';
        $punch_out_msg = isset($descs[1]) ? htmlspecialchars($descs[1]) : '';
        
        $work_hours = '';
        if (isset($times[0]) && isset($times[1])) {
            try {
                $time1 = new DateTime($row['attendance_date'] . ' ' . $times[0]);
                $time2 = new DateTime($row['attendance_date'] . ' ' . $times[1]);
                $interval = $time1->diff($time2);
                $work_hours = $interval->format('%h') . " Hrs | " . $interval->format('%i') . " Min";
            } catch (Exception $e) {
                $work_hours = 'Invalid Time';
            }
        }

        $attendance_data[] = [
            'date' => date('d-m-Y', strtotime($row['attendance_date'])),
            'punch_in' => $punch_in,
            'punch_in_msg' => $punch_in_msg,
            'punch_out' => $punch_out,
            'punch_out_msg' => $punch_out_msg,
            'work_hours' => $work_hours
        ];
    }

} else {
    die("Invalid user type or insufficient permissions for PDF generation.");
}

// === PDF GENERATION LOGIC ===
ob_start();
?>
<html>
<head>
<style>
body { font-family: sans-serif; font-size: 10px; } /* Slightly smaller font for more data */
h1 { text-align: center; margin-bottom: 5px; color: #333; font-size: 1.8em; }
h2 { text-align: center; margin-top: 0; margin-bottom: 20px; color: #555; font-size: 1.2em; }
.table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.table th, .table td { border: 1px solid #ddd; padding: 4px; text-align: left; }
.table th { background-color: #f2f2f2; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.footer { text-align: center; margin-top: 20px; font-size: 0.8em; color: #777; }
</style>
</head>
<body>
<h1>BEAUTYPAY</h1>
<h2><?php echo htmlspecialchars($report_title); ?></h2>
<?php if (!empty($start_date) || !empty($end_date)) { ?>
    <p class="text-center" style="font-size: 0.9em; color: #666;">
        Report Period: <?php echo date('d-m-Y', strtotime($start_date_sanitized)) . ' to ' . date('d-m-Y', strtotime($end_date_sanitized)); ?>
    </p>
<?php } ?>

<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <?php if ($user_type === 'admin') { ?>
                <th>Emp Code</th>
                <th>Name</th>
            <?php } ?>
            <th>Punch-in</th>
            <th>In Message</th>
            <th>Punch-out</th>
            <th>Out Message</th>
            <th>Work Hours</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($attendance_data)) { ?>
            <tr>
                <td colspan="<?php echo ($user_type === 'admin' ? '8' : '6'); ?>" class="text-center">
                    No attendance data found for the selected period.
                </td>
            </tr>
        <?php } else {
            foreach ($attendance_data as $row) { ?>
            <tr>
                <td><?php echo $row['date']; ?></td>
                <?php if ($user_type === 'admin') { ?>
                    <td><?php echo $row['emp_code']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                <?php } ?>
                <td><?php echo $row['punch_in']; ?></td>
                <td><?php echo $row['punch_in_msg']; ?></td>
                <td><?php echo $row['punch_out']; ?></td>
                <td><?php echo $row['punch_out_msg']; ?></td>
                <td><?php echo $row['work_hours']; ?></td>
            </tr>
            <?php }
        } ?>
    </tbody>
</table>

<div class="footer">
    Generated on <?php echo date('d-m-Y H:i:s'); ?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Landscape might be better for more columns
$dompdf->render();

// Output the generated PDF
$filename = "Attendance_Report_" . ($user_type === 'admin' ? "Admin" : str_replace(' ', '_', $employee_name_for_title)) . "_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit;
?>