<?php
// Use sessions to provide feedback messages to the user.
// This MUST be the very first thing in the file.
session_start();

require_once('config.php'); // Load DB connection and functions
require_once 'vendor/autoload.php'; // Load Dompdf
require_once('functions.php'); // Ensure this is present to use calculate_hours_worked (though we're doing the calc inline now)

use Dompdf\Dompdf;
use Dompdf\Options;

// Get input from URL
$emp_code = $_GET['emp_code'] ?? '';
$monthNum = $_GET['month'] ?? ''; // Changed to monthNum for numeric month
$year = $_GET['year'] ?? '';
$period = $_GET['period'] ?? ''; // New: 'first-half' or 'second-half'

if (!$emp_code || !$monthNum || !$year || !$period) {
    die("Invalid request: Missing employee code, month, year, or period.");
}

// Construct the full_month string for database storage and display
$full_month_display = date('F', mktime(0, 0, 0, $monthNum, 1)) . ', ' . $year;
if ($period == 'first-half') {
    $full_month_display .= ' (1st-15th)';
} else {
    $lastDayOfMonth = date('t', strtotime("$year-$monthNum-01"));
    $full_month_display .= ' (16th-' . $lastDayOfMonth . 'th)';
}
$full_month_db = date('F', mktime(0, 0, 0, $monthNum, 1)) . ', ' . $year . ' (' . $period . ')'; // Format for DB lookup


// Get employee info
$empData = GetEmployeeDataByEmpCode($emp_code);

// ===================================================================
// === START: DATABASE INSERTION OR FETCH LOGIC ======================
// ===================================================================

$checkSql = "SELECT COUNT(*) as count FROM `wy_salaries` WHERE `emp_code` = ? AND `pay_month` = ?";
$stmt_check = mysqli_prepare($db, $checkSql);
mysqli_stmt_bind_param($stmt_check, "ss", $emp_code, $full_month_db); // Use new DB format
mysqli_stmt_execute($stmt_check);
$result = mysqli_stmt_get_result($stmt_check);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_check);

if ($row['count'] > 0) {
    $_SESSION['PaySlipMsg'] = "Payslip for " . htmlspecialchars($empData['first_name']) . " for " . htmlspecialchars($full_month_display) . " has already been generated.";

    $earnings = [];
    $deductions = [];
    $totalEarnings = 0;
    $totalDeductions = 0;
    $netSalary = 0; // Initialize netSalary

    // Fetch all records for the given emp_code and pay_month to reconstruct the salary
    $salary_query = mysqli_query($db, "SELECT * FROM `wy_salaries` WHERE `emp_code` = '$emp_code' AND `pay_month` = '$full_month_db'");
    
    // Use an associative array to store payheads to easily sum them up
    $temp_earnings = [];
    $temp_deductions = [];

    while ($salary_row = mysqli_fetch_assoc($salary_query)) {
        if ($salary_row['pay_type'] == 'earnings') {
            $temp_earnings[$salary_row['payhead_name']] = ($temp_earnings[$salary_row['payhead_name']] ?? 0) + $salary_row['pay_amount'];
        } elseif ($salary_row['pay_type'] == 'deductions') {
            $temp_deductions[$salary_row['payhead_name']] = ($temp_deductions[$salary_row['payhead_name']] ?? 0) + $salary_row['pay_amount'];
        }
        // Take total earnings/deductions/net from *any* row since they are repeated for each payhead for a given pay_month
        $totalEarnings = $salary_row['earning_total'];
        $totalDeductions = $salary_row['deduction_total'];
        $netSalary = $salary_row['net_salary'];
    }

    // Convert temp arrays back to indexed arrays for consistent structure with generation logic
    foreach ($temp_earnings as $name => $amount) {
        $earnings[] = [
            'payhead_name' => $name,
            'amount' => $amount
        ];
    }
    foreach ($temp_deductions as $name => $amount) {
        $percentage = 0; // Default
        // Re-calculate percentages for display if needed (e.g., for SSS/Pag-IBIG/Philhealth which have fixed amounts or are based on total gross)
        // For the payslip, we might just display 0% if it's a fixed amount, or the actual percentage if applicable.
        // For SSS, Pag-IBIG, PhilHealth, the percentages are usually calculated on a base or are fixed.
        // The `manage-salary.php` code does not store the percentage for deductions like SSS, Pag-IBIG, PhilHealth.
        // So, if you want to display percentages for these, you'd need to re-derive them or store them in the DB.
        // For now, let's keep it simple as per original, or set it to 0 if not percentage-based.
        $deductions[] = [
            'payhead_name' => $name,
            'percentage' => $percentage, // This will be 0 as percentage is not stored in DB for deductions
            'amount' => $amount
        ];
    }

} else {
    // No salary exists yet, generate new
    $payheads = [];
    $payhead_query = mysqli_query($db, "SELECT ps.payhead_id, ph.payhead_name, ph.payhead_type FROM wy_pay_structure ps JOIN wy_payheads ph ON ph.payhead_id = ps.payhead_id WHERE ps.emp_code = '$emp_code'");
    while ($row_ph = mysqli_fetch_assoc($payhead_query)) {
        $payheads[] = $row_ph;
    }

    // Define constants for calculation
    $STANDARD_DAILY_HOURS = 8; // 8 hours per day
    $OVERTIME_RATE_MULTIPLIER = 1.25; // 1.25x for overtime hours
    $HOURLY_RATE = 70; // Your base hourly rate (CONSISTENT with manage-salary.php)

    $total_regular_hours = 0;
    $total_overtime_hours = 0;

    // Determine start and end dates based on the period
    if ($period == 'first-half') {
        $start_date = date('Y-m-01', strtotime("$year-$monthNum-01"));
        $end_date = date('Y-m-15', strtotime("$year-$monthNum-01"));
    } else { // second-half
        $start_date = date('Y-m-16', strtotime("$year-$monthNum-01"));
        $end_date = date('Y-m-t', strtotime("$year-$monthNum-01")); // Last day of the month
    }

    $date_query = mysqli_query($db, "
        SELECT attendance_date 
        FROM wy_attendance 
        WHERE emp_code = '$emp_code' 
          AND attendance_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY attendance_date
    ");
    while ($date_row = mysqli_fetch_assoc($date_query)) {
        $date = $date_row['attendance_date'];
        $punchin_query = mysqli_query($db, "SELECT action_time FROM wy_attendance WHERE emp_code = '$emp_code' AND attendance_date = '$date' AND action_name = 'punchin' ORDER BY action_time ASC LIMIT 1");
        $punchout_query = mysqli_query($db, "SELECT action_time FROM wy_attendance WHERE emp_code = '$emp_code' AND attendance_date = '$date' AND action_name = 'punchout' ORDER BY action_time DESC LIMIT 1");
        $punchin = mysqli_fetch_assoc($punchin_query);
        $punchout = mysqli_fetch_assoc($punchout_query);
        if ($punchin && $punchout) {
            $in = strtotime($date . ' ' . $punchin['action_time']);
            $out = strtotime($date . ' ' . $punchout['action_time']);
            $worked_seconds = $out - $in;
            if ($worked_seconds > 0) {
                $worked_hours_today = $worked_seconds / 3600;
                
                if ($worked_hours_today > $STANDARD_DAILY_HOURS) {
                    $total_regular_hours += $STANDARD_DAILY_HOURS;
                    $total_overtime_hours += ($worked_hours_today - $STANDARD_DAILY_HOURS);
                } else {
                    $total_regular_hours += $worked_hours_today;
                }
            }
        }
    }

    $basic_salary_amount = $total_regular_hours * $HOURLY_RATE;
    $overtime_amount = $total_overtime_hours * ($HOURLY_RATE * $OVERTIME_RATE_MULTIPLIER);

    $earnings = [];
    $deductions = [];
    $totalEarnings = 0;
    $totalDeductions = 0;

    // Calculate earnings first to get totalEarnings for the period
    foreach ($payheads as $ph) {
        if ($ph['payhead_type'] == 'earnings') {
            $amount = 0; // Default amount
            if (strtolower($ph['payhead_name']) == 'basic salary') {
                $amount = $basic_salary_amount;
            } elseif (strtolower($ph['payhead_name']) == 'overtime') {
                $amount = $overtime_amount;
            }

            $earnings[] = ['payhead_name' => $ph['payhead_name'], 'amount' => $amount];
            $totalEarnings += $amount;
        }
    }

    // Now calculate deductions using the total earnings for the current period
    foreach ($payheads as $ph) {
        if ($ph['payhead_type'] == 'deductions') {
            $perc = 0;
            $amount = 0;

            if (strtolower($ph['payhead_name']) == 'sss') {
                if ($period == 'first-half') {
                    $amount = 0; // SSS is 0 for the first half
                } else { // second-half
                    // Fetch total earnings from the first half of the month
                    $first_half_month_db = date('F', mktime(0, 0, 0, $monthNum, 1)) . ', ' . $year . ' (first-half)';
                    $first_period_earnings_query = mysqli_query($db, "SELECT earning_total FROM `wy_salaries` WHERE `emp_code` = '$emp_code' AND `pay_month` = '$first_half_month_db' LIMIT 1");
                    $first_period_earnings_row = mysqli_fetch_assoc($first_period_earnings_query);
                    $first_period_earnings = $first_period_earnings_row['earning_total'] ?? 0;

                    // Calculate total monthly earnings
                    $total_monthly_earnings = $first_period_earnings + $totalEarnings; // $totalEarnings is current period's earnings

                    // Apply the SSS formula based on total monthly earnings
                    if ($total_monthly_earnings < 5250) {
                        $amount = 250;
                    } else {
                        $amount = min(1000, 250 + 25 * floor(($total_monthly_earnings - 5250) / 500));
                    }
                }
            } elseif (strtolower($ph['payhead_name']) == 'pag-ibig') {
                $amount = 100;
                $perc = 0; // No percentage for display as it's a fixed amount
            } elseif (strtolower($ph['payhead_name']) == 'philhealth') {
                // This calculation seems specific and was in manage-salary.php. Ensure consistency.
                // Assuming an annual income calculation and then dividing for bi-monthly.
                $annual_income = ($HOURLY_RATE * 8 * 313); // Assuming 313 working days in a year
                $monthly_premium = $annual_income * 0.05 / 12; // 5% of monthly income
                $amount = $monthly_premium / 2 / 2; // Divided by 2 for half-month
                $perc = 0; // Fixed amount, no percentage for display
            }
            $deductions[] = ['payhead_name' => $ph['payhead_name'], 'percentage' => $perc, 'amount' => $amount];
            $totalDeductions += $amount;
        }
    }

    $netSalary = $totalEarnings - $totalDeductions;

    // Insert salary entries
    // First, delete any previous records for this month and period for the employee
    mysqli_query($db, "DELETE FROM `wy_salaries` WHERE `emp_code` = '$emp_code' AND `pay_month` = '$full_month_db'");


    $insertSql = "INSERT INTO `wy_salaries` (`emp_code`, `payhead_name`, `pay_amount`, `earning_total`, `deduction_total`, `net_salary`, `pay_type`, `pay_month`, `generate_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_insert = mysqli_prepare($db, $insertSql);

    foreach ($earnings as $earning) {
        $pay_type = 'earnings';
        mysqli_stmt_bind_param($stmt_insert, "ssddddss", $emp_code, $earning['payhead_name'], $earning['amount'], $totalEarnings, $totalDeductions, $netSalary, $pay_type, $full_month_db); // Use new DB format
        mysqli_stmt_execute($stmt_insert);
    }

    foreach ($deductions as $deduction) {
        $pay_type = 'deductions';
        mysqli_stmt_bind_param($stmt_insert, "ssddddss", $emp_code, $deduction['payhead_name'], $deduction['amount'], $totalEarnings, $totalDeductions, $netSalary, $pay_type, $full_month_db); // Use new DB format
        mysqli_stmt_execute($stmt_insert);
    }

    mysqli_stmt_close($stmt_insert);

    $_SESSION['PaySlipMsg'] = "Payslip for " . htmlspecialchars($empData['first_name']) . " for " . htmlspecialchars($full_month_display) . " was generated and saved successfully!";
}

// ===================================================================
// === END LOGIC =====================================================
// ===================================================================


// === PDF GENERATION LOGIC ===
ob_start();
?>
<html>
<head>
<style>
body { font-family: sans-serif; font-size: 12px; }
h2 { text-align: center; }
.table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.table th, .table td { border: 1px solid #000; padding: 5px; text-align: left; }
.text-right { text-align: right; }
</style>
</head>
<body>
<h1 style="text-align: center; margin-bottom: 0;">BEAUTYPAY</h1>
<h2>Payslip - <?php echo htmlspecialchars($full_month_display); ?></h2>

<table class="table">
    <tr>
        <td>Employee Code</td><td><?php echo strtoupper($empData['emp_code']); ?></td>
        <td>Bank</td><td><?php echo ucwords($empData['bank_name']); ?></td>
    </tr>
    <tr>
        <td>Employee Name</td><td><?php echo ucwords($empData['first_name'] . ' ' . $empData['last_name']); ?></td>
        <td>Account No</td><td><?php echo $empData['account_no']; ?></td>
    </tr>
    <tr>
        <td>Designation</td><td><?php echo $empData['designation']; ?></td>
        <td>Department</td><td><?php echo $empData['department']; ?></td>
    </tr>
    <tr>
        <td>DOJ</td><td><?php echo date('d-m-Y', strtotime($empData['joining_date'])); ?></td>
        <td>City</td><td><?php echo $empData['city']; ?></td>
    </tr>
</table>

<table class="table">
<thead>
    <tr>
        <th width="50%" colspan="2">Earnings</th>
        <th width="50%" colspan="3">Deductions</th>
    </tr>
</thead>
<tbody>
    <tr>
        <td colspan="2" style="vertical-align: top; padding: 0;">
            <table class="table" style="margin: 0;">
                <?php foreach ($earnings as $earning) { ?>
                <tr>
                    <td><?php echo $earning['payhead_name']; ?></td>
                    <td class='text-right'><?php echo number_format($earning['amount'], 2); ?></td>
                </tr>
                <?php } ?>
                <tr>
                    <th>Total Earnings</th>
                    <th class="text-right"><?php echo number_format($totalEarnings, 2); ?></th>
                </tr>
            </table>
        </td>
        <td colspan="3" style="vertical-align: top; padding: 0;">
            <table class="table" style="margin: 0;">
                <?php foreach ($deductions as $deduction) { ?>
                <tr>
                    <td><?php echo $deduction['payhead_name']; ?></td>
                    <td class='text-right'><?php echo $deduction['percentage'] > 0 ? $deduction['percentage'] . '%' : ''; ?></td>
                    <td class='text-right'><?php echo number_format($deduction['amount'], 2); ?></td>
                </tr>
                <?php } ?>
                <tr>
                    <th colspan="2">Total Deductions</th>
                    <th class="text-right"><?php echo number_format($totalDeductions, 2); ?></th>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <th colspan="4">Net Salary</th>
        <td class="text-right"><strong><?php echo number_format($netSalary, 2); ?></strong></td>
    </tr>
</tbody>
</table>

</body>
</html>
<?php
$html = ob_get_clean();
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Payslip_{$emp_code}_{$monthNum}_{$year}_{$period}.pdf", ["Attachment" => false]);
?>