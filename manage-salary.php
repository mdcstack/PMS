
<?php
require_once(dirname(__FILE__) . '/config.php');
if ( !isset($_SESSION['Admin_ID']) || $_SESSION['Login_Type'] != 'admin' ) {
    header('location:' . BASE_URL);
}
// Add 'period' to the required GET parameters
if ( !isset($_GET['emp_code']) || empty($_GET['emp_code']) || !isset($_GET['month']) || empty($_GET['month']) || !isset($_GET['year']) || empty($_GET['year']) || !isset($_GET['period']) || empty($_GET['period']) ) {
    header('location:' . BASE_URL);
}

$empData = GetEmployeeDataByEmpCode($_GET['emp_code']);
$monthNum = $_GET['month']; // Numeric month
$year = $_GET['year'];
$period = $_GET['period']; // 'first-half' or 'second-half'

// Construct display month string based on period
$displayMonth = date('F', mktime(0, 0, 0, $monthNum, 1)) . ', ' . $year;
if ($period == 'first-half') {
    $displayMonth .= ' (1st-15th)';
} else {
    $lastDayOfMonth = date('t', strtotime("$year-$monthNum-01")); // Get last day of the month
    $displayMonth .= ' (16th-' . $lastDayOfMonth . 'th)';
}


$empLeave = GetEmployeeLWPDataByEmpCodeAndMonth($_GET['emp_code'], date('F', mktime(0, 0, 0, $monthNum, 1)) . ', ' . $year); // empLeave might still need full month data
$flag = 0;
$totalEarnings = 0; // This will hold current period earnings for new calculations
$totalDeductions = 0;
$netSalary = 0;

// Adjust the pay_month for database check to include the period for uniqueness
$pay_month_db = date('F', mktime(0, 0, 0, $monthNum, 1)) . ', ' . $year . ' (' . $period . ')';

$checkSalarySQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "salaries` WHERE `emp_code` = '" . $empData['emp_code'] . "' AND `pay_month` = '$pay_month_db'");
if ( $checkSalarySQL ) {
    $checkSalaryROW = mysqli_num_rows($checkSalarySQL);
    if ( $checkSalaryROW > 0 ) {
        $flag = 1;
        // Need to pass the period to GetEmployeeSalaryByEmpCodeAndMonth if it's used to fetch specific period data
        // Fetch all records for the given emp_code and pay_month to reconstruct the salary
        $empSalary = []; // Clear to rebuild
        $salary_query_fetch = mysqli_query($db, "SELECT * FROM `wy_salaries` WHERE `emp_code` = '" . $empData['emp_code'] . "' AND `pay_month` = '$pay_month_db'");
        while ($salary_row_fetch = mysqli_fetch_assoc($salary_query_fetch)) {
            $empSalary[] = $salary_row_fetch;
            // Capture total earnings/deductions/net from any row
            $totalEarnings = $salary_row_fetch['earning_total'];
            $totalDeductions = $salary_row_fetch['deduction_total'];
            $netSalary = $salary_row_fetch['net_salary'];
        }

    } else {
        // Fetch assigned payheads
        $payheads = [];
        $payhead_query = mysqli_query($db, "SELECT ps.payhead_id, ph.payhead_name, ph.payhead_type FROM wy_pay_structure ps JOIN wy_payheads ph ON ph.payhead_id = ps.payhead_id WHERE ps.emp_code = '" . $empData['emp_code'] . "'");
        while ($row = mysqli_fetch_assoc($payhead_query)) {
            $payheads[] = $row;
        }

        // Define constants for calculation
        $STANDARD_DAILY_HOURS = 8; // 8 hours per day
        $OVERTIME_RATE_MULTIPLIER = 1.25; // 1.25x for overtime hours
        $HOURLY_RATE = 70; // Your base hourly rate (CONSISTENT with generate-payslip.php)

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
            WHERE emp_code = '" . $empData['emp_code'] . "' 
              AND attendance_date BETWEEN '$start_date' AND '$end_date'
            GROUP BY attendance_date
        ");
        while ($date_row = mysqli_fetch_assoc($date_query)) {
            $date = $date_row['attendance_date'];
            // Earliest punchin
            $punchin_query = mysqli_query($db, "
                SELECT action_time 
                FROM wy_attendance 
                WHERE emp_code = '" . $empData['emp_code'] . "' 
                  AND attendance_date = '$date' 
                  AND action_name = 'punchin'
                ORDER BY action_time ASC LIMIT 1
            ");
            // Latest punchout
            $punchout_query = mysqli_query($db, "
                SELECT action_time 
                FROM wy_attendance 
                WHERE emp_code = '" . $empData['emp_code'] . "' 
                  AND attendance_date = '$date' 
                  AND action_name = 'punchout'
                ORDER BY action_time DESC LIMIT 1
            ");
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
        
        // Prepare earnings and deductions arrays
        $earnings = [];
        $deductions = [];
        
        // Calculate current period earnings first to use for SSS calculation if second-half
        foreach ($payheads as $ph) {
            if ($ph['payhead_type'] == 'earnings') {
                $amount = 0; // Default amount
                if (strtolower($ph['payhead_name']) == 'basic salary') {
                    $amount = $basic_salary_amount;
                } elseif (strtolower($ph['payhead_name']) == 'overtime') {
                    $amount = $overtime_amount;
                }
                
                $earnings[] = ['payhead_name' => $ph['payhead_name'], 'amount' => $amount];
            }
        }

        // --- SUM TOTAL EARNINGS FOR THE CURRENT PERIOD ---
        $totalEarnings = 0;
        foreach ($earnings as $earning) {
            $totalEarnings += $earning['amount'];
        }
        // --- END BLOCK ---
        
        // Now calculate deductions
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
                        $first_period_earnings_query = mysqli_query($db, "SELECT earning_total FROM `wy_salaries` WHERE `emp_code` = '" . $empData['emp_code'] . "' AND `pay_month` = '$first_half_month_db' LIMIT 1");
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
                    $perc = 0; // No percentage for display
                } elseif (strtolower($ph['payhead_name']) == 'philhealth') {
                    $amount = ($HOURLY_RATE * 8 * 313) / 12 * 0.05 / 2 / 2;
                    $perc = 0; // Fixed amount, no percentage for display
                }
                $deductions[] = ['payhead_name' => $ph['payhead_name'], 'percentage' => $perc, 'amount' => $amount];
            }
        }

        // --- SUM TOTALS FROM THE ARRAYS USED FOR DISPLAY ---
        $totalDeductions = 0;
        foreach ($deductions as $deduction) {
            $totalDeductions += $deduction['amount'];
        }
        $netSalary = $totalEarnings - $totalDeductions;
        // --- END BLOCK ---
    }
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

	<title>Salary for <?php echo $displayMonth; ?> - Payroll</title>

	<link rel="stylesheet" href="<?php echo BASE_URL; ?>bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>plugins/datatables/dataTables.bootstrap.css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>plugins/datatables/jquery.dataTables_themeroller.css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>dist/css/AdminLTE.css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>dist/css/skins/_all-skins.min.css">

	</head>
<body class="hold-transition skin-blue sidebar-mini">
	<div class="wrapper">

		<?php require_once(dirname(__FILE__) . '/partials/topnav.php'); ?>

		<?php require_once(dirname(__FILE__) . '/partials/sidenav.php'); ?>

		<div class="content-wrapper">
			<section class="content-header">
				<h1>Salary for <?php echo $displayMonth; ?></h1>
				<ol class="breadcrumb">
					<li><a href="<?php echo BASE_URL; ?>"><i class="fa fa-dashboard"></i> Home</a></li>
					<li class="active">Salary for <?php echo $displayMonth; ?></li>
				</ol>
			</section>

			<section class="content">
				<div class="row">
        			<div class="col-xs-12">
						<div class="box">
							<div class="box-body">
								<?php if ( $flag == 0 ) { ?>
									<form method="POST" role="form" id="payslip-form">
										<input type="hidden" name="emp_code" value="<?php echo $empData['emp_code']; ?>" />
										<input type="hidden" name="pay_month" value="<?php echo $pay_month_db; ?>" />
										<div class="table-responsive">
											<table class="table table-bordered">
										    	<tr>
										    		<td width="20%">Employee Code</td>
										    		<td width="30%"><?php echo strtoupper($empData['emp_code']); ?></td>
										    		<td width="20%">Bank Name</td>
										    		<td width="30%"><?php echo ucwords($empData['bank_name']); ?></td>
										    	</tr>
											    <tr>
										    		<td>Employee Name</td>
										    		<td><?php echo ucwords($empData['first_name'] . ' ' . $empData['last_name']); ?></td>
										    		<td>Bank Account</td>
										    		<td><?php echo $empData['account_no']; ?></td>
										    	</tr>
											    <tr>
												    <td>Designation</td>
												    <td><?php echo ucwords($empData['designation']); ?></td>
												    <td>IFSC Code</td>
												    <td><?php echo strtoupper($empData['ifsc_code']); ?></td>
											    </tr>
											    <tr>
												    <td>Gender</td>
												    <td><?php echo ucwords($empData['gender']); ?></td>
												    <td>PAN</td>
												    <td><?php echo strtoupper($empData['pan_no']); ?></td>
											    </tr>
											    <tr>
												    <td>Location</td>
												    <td><?php echo ucwords($empData['city']); ?></td>
												    <td>PF Account</td>
												    <td><?php echo strtoupper($empData['pf_account']); ?></td>
											    </tr>
											    <tr>
												    <td>Department</td>
												    <td><?php echo ucwords($empData['department']); ?></td>
												    <td>Payable/Working Days</td>
												    <td><?php echo ($empLeave['workingDays'] - $empLeave['withoutPay']); ?>/<?php echo $empLeave['workingDays']; ?> Days</td>
											    </tr>
											    <tr>
												    <td>Date of Joining</td>
												    <td><?php echo date('d-m-Y', strtotime($empData['joining_date'])); ?></td>
												    <td>Taken/Remaining Leaves</td>
												    <td><?php echo $empLeave['payLeaves']; ?>/<?php echo ($empLeave['totalLeaves'] - $empLeave['payLeaves']); ?> Days</td>
											    </tr>
										    </table>
											<table class="table table-bordered">
												<thead>
													<tr>
														<th width="35%">Earnings</th>
														<th width="15%" class="text-right">Amount (Php.)</th>
														<th width="35%">Deductions</th>
														<th width="15%" class="text-right">Amount (Php.)</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td colspan="2" style="padding:0">
															<table class="table table-bordered table-striped" style="margin:0">
																<?php foreach ($earnings as $earning) { ?>
																<tr>
																	<td width="70%">
																		<?php echo $earning['payhead_name']; ?>
																	</td>
																	<td width="30%" class="text-right">
																		<?php echo number_format($earning['amount'], 2); ?>
																	</td>
																</tr>
																<?php } ?>
															</table>
														</td>
														<td colspan="2" style="padding:0">
															<table class="table table-bordered table-striped" style="margin:0">
																<?php foreach ($deductions as $deduction) { ?>
																<tr>
																	<td width="70%">
																		<?php echo $deduction['payhead_name']; ?><?php if ($deduction['percentage'] > 0) { ?> (<?php echo $deduction['percentage']; ?>%)<?php } ?>
																	</td>
																	<td width="30%" class="text-right">
																		<?php echo number_format($deduction['amount'], 2); ?>
																	</td>
																</tr>
																<?php } ?>
															</table>
														</td>
													</tr>
												</tbody>
												<tfoot>
													<tr>
														<td><strong>Total Earnings</strong></td>
														<td class="text-right"><strong id="totalEarnings"><?php echo number_format($totalEarnings, 2); ?></strong></td>
														<td><strong>Total Deductions</strong></td>
														<td class="text-right"><strong id="totalDeductions"><?php echo number_format($totalDeductions, 2); ?></strong></td>
													</tr>
												</tfoot>
											</table>
										</div>
										<div class="row">
											<div class="col-sm-6">
											<h3 class="text-success" style="margin-top:0">
												Net Salary Payable:
												₱<?php echo number_format($netSalary, 2, '.', ','); ?>
												
											</h3>
											</div>
											<div class="col-sm-6 text-right">
											<button type="button" class="btn btn-info"
    onclick="window.open('<?php echo BASE_URL; ?>generate-payslip.php?emp_code=<?php echo $empData['emp_code']; ?>&month=<?php echo $_GET['month']; ?>&year=<?php echo $_GET['year']; ?>&period=<?php echo $_GET['period']; ?>', '_blank'); location.reload();">
    <i class="fa fa-plus"></i> Generate PaySlip
</button>
											</div>
										</div>
									</form>
								<?php } else { ?>
									<div class="table-responsive">
										<table class="table table-bordered">
											<thead>
												<tr>
													<th width="35%">Earnings</th>
													<th width="15%" class="text-right">Amount (Php.)</th>
													<th width="35%">Deductions</th>
													<th width="15%" class="text-right">Amount (Php.)</th>
												</tr>
											</thead>
											<tbody>
												<?php if ( !empty($empSalary) ) {
                                                    // Initialize totals to ensure accurate display from fetched data
                                                    $currentEarnings = []; // To store earnings for display
                                                    $currentDeductions = []; // To store deductions for display

                                                    foreach ($empSalary as $salary) {
                                                        if ($salary['pay_type'] == 'earnings') {
                                                            $currentEarnings[] = $salary;
                                                        } elseif ($salary['pay_type'] == 'deductions') {
                                                            $currentDeductions[] = $salary;
                                                        }
                                                    }
                                                ?>
													<tr>
														<td colspan="2" style="padding:0">
															<table class="table table-bordered table-striped" style="margin:0">
																<?php foreach ( $currentEarnings as $salary ) { ?>
																		<tr>
																			<td width="70%">
																				<?php echo $salary['payhead_name']; ?>
																			</td>
																			<td width="30%" class="text-right">
																				<?php echo number_format($salary['pay_amount'], 2, '.', ','); ?>
																			</td>
																		</tr>
																<?php } ?>
															</table>
														</td>
														<td colspan="2" style="padding:0">
															<table class="table table-bordered table-striped" style="margin:0">
																<?php foreach ( $currentDeductions as $salary ) {
                                                                    // For display, fetch percentage if needed, from payheads table
                                                                    $deductionPercentage = 0;
                                                                    // SSS, Pag-IBIG, PhilHealth are fixed amounts or based on total earnings, not percentages of current pay
                                                                ?>
																		<tr>
																			<td width="70%">
																				<?php echo $salary['payhead_name']; ?><?php if ($deductionPercentage > 0) { ?> (<?php echo $deductionPercentage; ?>%)<?php } ?>
																			</td>
																			<td width="30%" class="text-right">
																				<?php echo number_format($salary['pay_amount'], 2, '.', ','); ?>
																			</td>
																		</tr>
																<?php } ?>
															</table>
														</td>
													</tr>
												<?php } else { ?>
													<tr>
														<td colspan="4">No payheads are assigned for this employee or salary not found for this period.</td>
													</tr>
												<?php } ?>
											</tbody>
											<tfoot>
												<tr>
													<td><strong>Total Earnings</strong></td>
													<td class="text-right">
														<strong id="totalEarnings">
															<?php echo number_format($totalEarnings, 2, '.', ','); ?>
														</strong>
													</td>
													<td><strong>Total Deductions</strong></td>
													<td class="text-right">
														<strong id="totalDeductions">
															<?php echo number_format($totalDeductions, 2, '.', ','); ?>
														</strong>
													</td>
												</tr>
											</tfoot>
										</table>
									</div>
									<div class="row">
										<div class="col-sm-6">
											<h3 class="text-success" style="margin-top:0">
												Net Salary Payable:
												₱<?php echo number_format($netSalary, 2, '.', ','); ?>
												
											</h3>
										</div>
										<div class="col-sm-6 text-right">
										<button type="button" class="btn btn-success"
    										onclick="window.open('<?php echo BASE_URL; ?>generate-payslip.php?emp_code=<?php echo $empData['emp_code']; ?>&month=<?php echo $_GET['month']; ?>&year=<?php echo $_GET['year']; ?>&period=<?php echo $_GET['period']; ?>', '_blank')">
    										<i class="fa fa-download"></i> Show PaySlip (PDF Version)
										</button>
											
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
	</div>

	<script src="<?php echo BASE_URL; ?>plugins/jQuery/jquery-2.2.3.min.js"></script>
	<script src="<?php echo BASE_URL; ?>bootstrap/js/bootstrap.min.js"></script>
	<script src="<?php echo BASE_URL; ?>plugins/datatables/jquery.dataTables.min.js"></script>
	<script src="<?php echo BASE_URL; ?>plugins/datatables/dataTables.bootstrap.min.js"></script>
	<script src="<?php echo BASE_URL; ?>plugins/jquery-validator/validator.min.js"></script>
	<script src="<?php echo BASE_URL; ?>plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
	<script src="<?php echo BASE_URL; ?>dist/js/app.min.js"></script>
	<script type="text/javascript">var baseurl = '<?php echo BASE_URL; ?>';</script>
	<script src="<?php echo BASE_URL; ?>dist/js/script.js?rand=<?php echo rand(); ?>"></script>
	<?php if ( isset($_SESSION['PaySlipMsg']) ) { ?>
		<script type="text/javascript">
		$.notify({
            icon: 'glyphicon glyphicon-ok-circle',
            message: '<?php echo $_SESSION['PaySlipMsg']; ?>',
        },{
            allow_dismiss: false,
            type: "success",
            placement: {
                from: "top",
                align: "right"
            },
            z_index: 9999,
        });
		</script>
	<?php } ?>
</body>
</html>
<?php unset($_SESSION['PaySlipMsg']); ?>