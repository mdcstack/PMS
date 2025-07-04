<?php
include(dirname(dirname(__FILE__)) . '/config.php');

$case = $_GET['case'];
switch($case) {
	case 'LoginProcessHandler':
		LoginProcessHandler();
		break;
	case 'AttendanceProcessHandler':
		AttendanceProcessHandler();
		break;
	case 'LoadingAttendance':
		LoadingAttendance();
		break;
	case 'LoadingMyAttendance':
		LoadingMyAttendance();
		break;
	case 'LoadingSalaries':
		LoadingSalaries();
		break;
	case 'LoadingEmployees':
		LoadingEmployees();
		break;
	case 'AssignPayheadsToEmployee':
		AssignPayheadsToEmployee();
		break;
	case 'InsertUpdateHolidays':
		InsertUpdateHolidays();
		break;
	case 'GetHolidayByID':
		GetHolidayByID();
		break;
	case 'DeleteHolidayByID':
		DeleteHolidayByID();
		break;
	case 'LoadingHolidays':
		LoadingHolidays();
		break;
	case 'InsertUpdatePayheads':
		InsertUpdatePayheads();
		break;
	case 'GetPayheadByID':
		GetPayheadByID();
		break;
	case 'DeletePayheadByID':
		DeletePayheadByID();
		break;
	case 'LoadingPayheads':
		LoadingPayheads();
		break;
	case 'GetAllPayheadsExceptEmployeeHave':
		GetAllPayheadsExceptEmployeeHave();
		break;
	case 'GetEmployeePayheadsByID':
		GetEmployeePayheadsByID();
		break;
	case 'GetEmployeeByID':
		GetEmployeeByID();
		break;
	case 'DeleteEmployeeByID':
		DeleteEmployeeByID();
		break;
	case 'EditEmployeeDetailsByID':
		EditEmployeeDetailsByID();
		break;
	case 'GeneratePaySlip':
		GeneratePaySlip();
		break;
	case 'SendPaySlipByMail':
		SendPaySlipByMail();
		break;
	case 'EditProfileByID':
		EditProfileByID();
		break;
	case 'EditLoginDataByID':
		EditLoginDataByID();
		break;
	case 'LoadingAllLeaves':
		LoadingAllLeaves();
		break;
	case 'LoadingMyLeaves':
		LoadingMyLeaves();
		break;
	case 'ApplyLeaveToAdminApproval':
		ApplyLeaveToAdminApproval();
		break;
	case 'ApproveLeaveApplication':
		ApproveLeaveApplication();
		break;
	case 'RejectLeaveApplication':
		RejectLeaveApplication();
		break;
	default:
		echo '404! Page Not Found.';
		break;
}

function LoginProcessHandler() {
	$result = array();
	global $db;

	$code = addslashes($_POST['code']);
    $password = addslashes($_POST['password']);
    if ( !empty($code) && !empty($password) ) {
	    $adminCheck = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "admin` WHERE `admin_code` = '$code' AND `admin_password` = '" . sha1($password) . "' LIMIT 0, 1");
	    if ( $adminCheck ) {
	        if ( mysqli_num_rows($adminCheck) == 1 ) {
	            $adminData = mysqli_fetch_assoc($adminCheck);
	            $_SESSION['Admin_ID'] = $adminData['admin_id'];
	            $_SESSION['Login_Type'] = 'admin';
	            $result['result'] = BASE_URL . 'attendance/';
			    		$result['code'] = 0;
	        } else {
	        	$empCheck = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "employees` WHERE `emp_code` = '$code' AND `emp_password` = '" . sha1($password) . "' LIMIT 0, 1");
			    if ( $empCheck ) {
			        if ( mysqli_num_rows($empCheck) == 1 ) {
			        	$empData = mysqli_fetch_assoc($empCheck);
			            $_SESSION['Admin_ID'] = $empData['emp_id'];
			            $_SESSION['Login_Type'] = 'emp';
			            $result['result'] = BASE_URL . 'profile/';
			    		$result['code'] = 0;
			        } else {
			        	$result['result'] = 'Invalid Login Details.';
			        	$result['code'] = 1;
			        }
			    } else {
			    	$result['result'] = 'Something went wrong, please try again.';
		    		$result['code'] = 2;
			    }
	        }
	    } else {
	    	$result['result'] = 'Something went wrong, please try again.';
		    $result['code'] = 2;
	    }
	} else {
		$result['result'] = 'Login Details should not be blank.';
		$result['code'] = 3;
	}

    echo json_encode($result);
}
//punch in& out
function AttendanceProcessHandler() {
    global $userData, $db;
    $result = array();

    $emp_code = $userData['emp_code'];
    $attendance_date = date('Y-m-d');
    $action_time = date('H:i:s');
    $emp_desc = addslashes($_POST['desc']);

    // Determine action type
    $attendanceSQL = mysqli_query($db, "SELECT * FROM wy_attendance WHERE emp_code = '$emp_code' AND attendance_date = '$attendance_date' ORDER BY attendance_id ASC");
    $attendanceROW = mysqli_num_rows($attendanceSQL);
    $action_name = 'punchin';

    if ($attendanceROW > 0) {
        // Get last action
        $lastSQL = mysqli_query($db, "SELECT * FROM wy_attendance WHERE emp_code = '$emp_code' AND attendance_date = '$attendance_date' ORDER BY attendance_id DESC LIMIT 1");
        $lastRow = mysqli_fetch_assoc($lastSQL);

        if ($lastRow['action_name'] == 'punchin') {
            $action_name = 'punchout';

            // ✅ Confirm punchout regardless of hours
            if (!isset($_POST['confirm_punchout']) || $_POST['confirm_punchout'] != 'yes') {
                $result['result'] = 'Are you sure you want to punch out?';
                $result['code'] = 2; // ask confirmation
                echo json_encode($result);
                return;
            }
        } else {
            $action_name = 'punchin';
        }
    }

    // Insert attendance
    $insertSQL = mysqli_query($db, "INSERT INTO wy_attendance(emp_code, attendance_date, action_name, action_time, emp_desc) VALUES ('$emp_code', '$attendance_date', '$action_name', '$action_time', '$emp_desc')");

    if ($insertSQL) {
        $result['next'] = ($action_name == 'punchin' ? 'Punch Out' : 'Punch In');
        $result['complete'] = $attendanceROW + 1;
        $result['result'] = ($action_name == 'punchin') ? 'You have successfully punched in.' : 'You have successfully punched out.';
        $result['code'] = 0;
    } else {
        $result['result'] = 'Something went wrong. Please try again.';
        $result['code'] = 1;
    }

    echo json_encode($result);
}
//END punch in& out
// Function to load all employee attendance (for admin)
function LoadingAttendance() {
    global $db; // Assuming $db is your mysqli connection
    $requestData = $_REQUEST;

    // Retrieve date filters from the request
    $start_date = isset($requestData['start_date']) ? mysqli_real_escape_string($db, $requestData['start_date']) : null;
    $end_date = isset($requestData['end_date']) ? mysqli_real_escape_string($db, $requestData['end_date']) : null;

    $columns = array(
        0 => 'attendance_date',
        1 => 'emp_code',
        2 => 'first_name',
        3 => 'last_name',
        4 => 'action_time', // This is punch-in time, index based on frontend structure
        5 => 'emp_desc' // This is punch-in message, index based on frontend structure
    );

    // Build common WHERE clause for date filtering
    $date_filter_conditions = [];
    if (!empty($start_date)) {
        $date_filter_conditions[] = "`att`.`attendance_date` >= '$start_date'";
    }
    if (!empty($end_date)) {
        $date_filter_conditions[] = "`att`.`attendance_date` <= '$end_date'";
    }
    $date_filter_clause = '';
    if (!empty($date_filter_conditions)) {
        $date_filter_clause = " AND " . implode(" AND ", $date_filter_conditions);
    }

    // --- Query for total records (without pagination and main search filter) ---
    // This query needs to reflect the date filters for accurate total count
    $sql_total = "SELECT COUNT(DISTINCT CONCAT(`att`.`emp_code`, `att`.`attendance_date`)) AS total_count";
    $sql_total .= " FROM `" . DB_PREFIX . "employees` AS `emp`, `" . DB_PREFIX . "attendance` AS `att`";
    $sql_total .= " WHERE `emp`.`emp_code` = `att`.`emp_code`";
    $sql_total .= $date_filter_clause; // Apply date filter here for total count

    $query_total = mysqli_query($db, $sql_total);
    if (!$query_total) {
        error_log("MySQL Error in LoadingAttendance (total count): " . mysqli_error($db));
        // Handle error gracefully, perhaps return an empty dataset or error message
        echo json_encode(["draw" => intval($requestData['draw']), "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []]);
        return;
    }
    $row_total = mysqli_fetch_assoc($query_total);
    $totalData = $row_total['total_count'];
    $totalFiltered = $totalData; // Initially, total filtered is total data

    // --- Main SQL query for filtered and paginated data ---
    $sql = "SELECT `emp`.`emp_code`, `emp`.`first_name`, `emp`.`last_name`, `att`.`attendance_date`, GROUP_CONCAT(`att`.`action_time` ORDER BY `att`.`action_time` ASC) AS `times`, GROUP_CONCAT(`att`.`emp_desc` ORDER BY `att`.`action_time` ASC) AS `descs`";
    $sql .= " FROM `" . DB_PREFIX . "employees` AS `emp`, `" . DB_PREFIX . "attendance` AS `att`";
    $sql .= " WHERE `emp`.`emp_code` = `att`.`emp_code`";
    $sql .= $date_filter_clause; // Apply date filter here

    // Apply global search filter from DataTables (if any)
    if ( !empty($requestData['search']['value']) ) {
        $search_value = mysqli_real_escape_string($db, $requestData['search']['value']);
        $sql .= " AND ( `att`.`attendance_date` LIKE '%" . $search_value . "%'";
        $sql .= " OR CONCAT(TRIM(`emp`.`first_name`), ' ', TRIM(`emp`.`last_name`)) LIKE '%" . $search_value . "%'";
        // Note: Filtering on GROUP_CONCAT results directly in SQL can be inefficient for large datasets.
        // Consider client-side filtering if performance is an issue for these columns.
        $sql .= " OR GROUP_CONCAT(`att`.`action_time`) LIKE '%" . $search_value . "%'";
        $sql .= " OR GROUP_CONCAT(`att`.`emp_desc`) LIKE '%" . $search_value . "%' )";
    }

    $sql .= " GROUP BY `emp`.`emp_code`, `att`.`attendance_date`";

    // --- Query for total filtered records (after global search and date filters, before pagination) ---
    $query_filtered = mysqli_query($db, $sql);
    if (!$query_filtered) {
        error_log("MySQL Error in LoadingAttendance (filtered count): " . mysqli_error($db));
        echo json_encode(["draw" => intval($requestData['draw']), "recordsTotal" => $totalData, "recordsFiltered" => 0, "data" => []]);
        return;
    }
    $totalFiltered = mysqli_num_rows($query_filtered);

    // Apply ordering and limit for pagination
    if (isset($requestData['order'][0]['column'])) {
        $order_column = $columns[$requestData['order'][0]['column']];
        $order_dir = $requestData['order'][0]['dir'];
        $sql .= " ORDER BY " . $order_column . " " . $order_dir;
    } else {
        $sql .= " ORDER BY `att`.`attendance_date` DESC"; // Default order if not specified
    }

    $sql .= " LIMIT " . intval($requestData['start']) . " ," . intval($requestData['length']);

    $query = mysqli_query($db, $sql);
    if (!$query) {
        error_log("MySQL Error in LoadingAttendance (main query): " . mysqli_error($db));
        echo json_encode(["draw" => intval($requestData['draw']), "recordsTotal" => $totalData, "recordsFiltered" => $totalFiltered, "data" => []]);
        return;
    }

    $data = array();
    while ( $row = mysqli_fetch_assoc($query) ) {
        $nestedData = array();
        $nestedData[] = date('d-m-Y', strtotime($row['attendance_date']));
        $nestedData[] = $row["emp_code"];
        $nestedData[] = '<a target="_blank" href="' . REG_URL . 'reports/' . $row["emp_code"] . '/">' . htmlspecialchars($row["first_name"]) . ' ' . htmlspecialchars($row["last_name"]) . '</a>'; // Use htmlspecialchars for output
        
        $times = explode(',', $row["times"]);
        $descs = explode(',', $row["descs"]);

        // Punch-in
        $nestedData[] = isset($times[0]) ? date('h:i:s A', strtotime($times[0])) : '';
        $nestedData[] = isset($descs[0]) ? htmlspecialchars($descs[0]) : '';

        // Punch-out
        $nestedData[] = isset($times[1]) ? date('h:i:s A', strtotime($times[1])) : '';
        $nestedData[] = isset($descs[1]) ? htmlspecialchars($descs[1]) : '';

        // Work Hours calculation
        if (isset($times[0]) && isset($times[1])) {
            try {
                $datetime1 = new DateTime($times[0]);
                $datetime2 = new DateTime($times[1]);
                $interval = $datetime1->diff($datetime2);
                $nestedData[] = $interval->format('%h') . " Hrs | " . $interval->format('%i') . " Min";
            } catch (Exception $e) {
                $nestedData[] = 'Invalid Time'; // Handle potential date/time parsing errors
            }
        } else {
            $nestedData[] = ''; // Or '0 Hrs | 0 Min' if preferred
        }

        $data[] = $nestedData;
    }

    $json_data = array(
        "draw"            => intval($requestData['draw']),
        "recordsTotal"    => intval($totalData),
        "recordsFiltered" => intval($totalFiltered),
        "data"            => $data
    );

    header('Content-Type: application/json'); // Ensure JSON header is sent
    echo json_encode($json_data);
    exit; // Exit after sending JSON response
}


// Function to load individual employee attendance (for employee)
function LoadingMyAttendance() {
    global $db, $userData; // Assuming $db and $userData are available
    $requestData = $_REQUEST;
    $emp_code = mysqli_real_escape_string($db, $userData['emp_code']); // Sanitize emp_code

    // Retrieve date filters from the request
    $start_date = isset($requestData['start_date']) ? mysqli_real_escape_string($db, $requestData['start_date']) : null;
    $end_date = isset($requestData['end_date']) ? mysqli_real_escape_string($db, $requestData['end_date']) : null;

    $columns = array(
        0 => 'attendance_date',
        1 => 'action_time', // This is punch-in time, index based on frontend structure
        2 => 'emp_desc' // This is punch-in message, index based on frontend structure
    );

    // Build common WHERE clause for date filtering
    $date_filter_conditions = [];
    $date_filter_conditions[] = "emp_code = '$emp_code'"; // Always filter by emp_code
    if (!empty($start_date)) {
        $date_filter_conditions[] = "attendance_date >= '$start_date'";
    }
    if (!empty($end_date)) {
        $date_filter_conditions[] = "attendance_date <= '$end_date'";
    }
    $date_filter_clause = " WHERE " . implode(" AND ", $date_filter_conditions);


    // --- Query for total records (without pagination and main search filter) ---
    // This query needs to reflect the date filters for accurate total count
    $sql_total = "SELECT COUNT(DISTINCT attendance_date) AS total_count FROM " . DB_PREFIX . "attendance" . $date_filter_clause;
    $query_total = mysqli_query($db, $sql_total);
    if (!$query_total) {
        error_log("MySQL Error in LoadingMyAttendance (total count): " . mysqli_error($db));
        echo json_encode(["draw" => intval($requestData['draw']), "recordsTotal" => 0, "recordsFiltered" => 0, "data" => []]);
        return;
    }
    $row_total = mysqli_fetch_assoc($query_total);
    $totalData = $row_total['total_count'];
    $totalFiltered = $totalData; // Initially, total filtered is total data

    // --- Main SQL query for filtered and paginated data ---
    $sql = "SELECT attendance_date, GROUP_CONCAT(action_time ORDER BY action_time ASC) AS times, GROUP_CONCAT(emp_desc ORDER BY action_time ASC) AS descs FROM " . DB_PREFIX . "attendance";
    $sql .= $date_filter_clause; // Apply date filter here

    // Apply global search filter from DataTables (if any)
    if (!empty($requestData['search']['value'])) {
        $search_value = mysqli_real_escape_string($db, $requestData['search']['value']);
        $sql .= " AND (attendance_date LIKE '%" . $search_value . "%'";
        // Note: Filtering on GROUP_CONCAT results directly in SQL can be inefficient for large datasets.
        $sql .= " OR GROUP_CONCAT(action_time) LIKE '%" . $search_value . "%'";
        $sql .= " OR GROUP_CONCAT(emp_desc) LIKE '%" . $search_value . "%')";
    }
    $sql .= " GROUP BY attendance_date";

    // --- Query for total filtered records (after global search and date filters, before pagination) ---
    $query_filtered = mysqli_query($db, $sql);
    if (!$query_filtered) {
        error_log("MySQL Error in LoadingMyAttendance (filtered count): " . mysqli_error($db));
        echo json_encode(["draw" => intval($requestData['draw']), "recordsTotal" => $totalData, "recordsFiltered" => 0, "data" => []]);
        return;
    }
    $totalFiltered = mysqli_num_rows($query_filtered);

    // Apply ordering and limit for pagination
    if (isset($requestData['order'][0]['column'])) {
        $order_column = $columns[$requestData['order'][0]['column']];
        $order_dir = $requestData['order'][0]['dir'];
        $sql .= " ORDER BY " . $order_column . " " . $order_dir;
    } else {
        $sql .= " ORDER BY attendance_date DESC"; // Default order if not specified
    }

    $sql .= " LIMIT " . intval($requestData['start']) . " ," . intval($requestData['length']);
    $query = mysqli_query($db, $sql);
    if (!$query) {
        error_log("MySQL Error in LoadingMyAttendance (main query): " . mysqli_error($db));
        echo json_encode(["draw" => intval($requestData['draw']), "recordsTotal" => $totalData, "recordsFiltered" => $totalFiltered, "data" => []]);
        return;
    }

    $data = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $nestedData = array();
        $nestedData[] = date('d-m-Y', strtotime($row['attendance_date']));
        $times = explode(',', $row['times']);
        $descs = explode(',', $row['descs']);

        // Punch-in
        $nestedData[] = isset($times[0]) ? date('h:i:s A', strtotime($times[0])) : '';
        $nestedData[] = isset($descs[0]) ? htmlspecialchars($descs[0]) : '';

        // Punch-out
        $nestedData[] = isset($times[1]) ? date('h:i:s A', strtotime($times[1])) : '';
        $nestedData[] = isset($descs[1]) ? htmlspecialchars($descs[1]) : '';

        // Work Hours calculation
        if (isset($times[0]) && isset($times[1])) {
            try {
                $time1 = new DateTime($times[0]);
                $time2 = new DateTime($times[1]);
                $interval = $time1->diff($time2);
                $nestedData[] = $interval->format('%h') . " Hrs | " . $interval->format('%i') . " Min";
            } catch (Exception $e) {
                $nestedData[] = 'Invalid Time'; // Handle potential date/time parsing errors
            }
        } else {
            $nestedData[] = '';
        }

        $data[] = $nestedData;
    }

    $json_data = array(
        "draw" => intval($requestData['draw']),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $data
    );

    header('Content-Type: application/json'); // Force JSON response
    echo json_encode($json_data);
    exit; // Exit after sending JSON response
}


function LoadingSalaries() {
    global $db;
    $requestData = $_REQUEST;

    if ( $_SESSION['Login_Type'] == 'admin' ) {
        $columns = array(
            0 => 'emp_code',
            1 => 'first_name',
            2 => 'last_name',
            3 => 'pay_month',
            4 => 'earning_total',
            5 => 'deduction_total',
            6 => 'net_salary'
        );

        $sql  = "SELECT * FROM `" . DB_PREFIX . "salaries` GROUP BY `emp_code`, `pay_month`";
        $query = mysqli_query($db, $sql);
        $totalData = mysqli_num_rows($query);
        $totalFiltered = $totalData;

        $sql  = "SELECT `emp`.`emp_code`, `emp`.`first_name`, `emp`.`last_name`, `salary`.*";
        $sql .= " FROM `" . DB_PREFIX . "salaries` AS `salary`, `" . DB_PREFIX . "employees` AS `emp` WHERE `emp`.`emp_code` = `salary`.`emp_code`";
        if ( !empty($requestData['search']['value']) ) {
            $sql .= " AND (`salary`.`emp_code` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR CONCAT(TRIM(`emp`.`first_name`), ' ', TRIM(`emp`.`last_name`)) LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`pay_month` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`earning_total` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`deduction_total` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`net_salary` LIKE '" . $requestData['search']['value'] . "%')";
        }
        $sql .= " GROUP BY `salary`.`emp_code`, `salary`.`pay_month`";

        $query = mysqli_query($db, $sql);
        $totalFiltered = mysqli_num_rows($query);

        $data = array();
        $i = 1 + $requestData['start'];
        while ( $row = mysqli_fetch_assoc($query) ) {
            $nestedData = array();
            $nestedData[] = $row['emp_code'];
            $nestedData[] = '<a target="_blank" href="' . REG_URL . 'reports/' . $row["emp_code"] . '/">' . $row["first_name"] . ' ' . $row["last_name"] . '</a>';
            $nestedData[] = $row['pay_month'];
            $nestedData[] = number_format($row['earning_total'], 2, '.', ',');
            $nestedData[] = number_format($row['deduction_total'], 2, '.', ',');
            $nestedData[] = number_format($row['net_salary'], 2, '.', ',');

            // Extract month, year, and period from the pay_month string for URL parameters
            // Expected format: "MonthName, Year (period-half)" e.g., "June, 2024 (first-half)"
            preg_match('/^(\w+), (\d{4}) \((\w+)-half\)$/', $row['pay_month'], $matches);
            $monthName = $matches[1] ?? '';
            $year = $matches[2] ?? '';
            $period = $matches[3] ?? '';

            // Convert month name to numeric month
            $monthNum = date('m', strtotime($monthName));

            $nestedData[] = '<button type="button" class="btn btn-success btn-xs" onclick="window.open(\'' . BASE_URL . 'generate-payslip.php?emp_code=' . $row['emp_code'] . '&month=' . urlencode($monthNum) . '&year=' . urlencode($year) . '&period=' . urlencode($period . '-half') . '\', \'_blank\')"><i class="fa fa-download"></i></button>';

            $data[] = $nestedData;
            $i++;
        }
    } else {
        $columns = array(
            0 => 'pay_month',
            1 => 'earning_total',
            2 => 'deduction_total',
            3 => 'net_salary'
        );
        $empData = GetDataByIDAndType($_SESSION['Admin_ID'], $_SESSION['Login_Type']);
        $sql  = "SELECT * FROM `" . DB_PREFIX . "salaries` WHERE `emp_code` = '" . $empData['emp_code'] . "' GROUP BY `pay_month`";
        $query = mysqli_query($db, $sql);
        $totalData = mysqli_num_rows($query);
        $totalFiltered = $totalData;

        $sql  = "SELECT `emp`.`emp_code`, `emp`.`first_name`, `emp`.`last_name`, `salary`.*";
        $sql .= " FROM `" . DB_PREFIX . "salaries` AS `salary`, `" . DB_PREFIX . "employees` AS `emp` WHERE `emp`.`emp_code` = `salary`.`emp_code` AND `salary`.`emp_code` = '" . $empData['emp_code'] . "'";
        if ( !empty($requestData['search']['value']) ) {
            $sql .= " AND (`salary`.`emp_code` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR CONCAT(TRIM(`emp`.`first_name`), ' ', TRIM(`emp`.`last_name`)) LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`pay_month` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`earning_total` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`deduction_total` LIKE '" . $requestData['search']['value'] . "%'";
            $sql .= " OR `salary`.`net_salary` LIKE '" . $requestData['search']['value'] . "%')";
        }
        $sql .= " GROUP BY `salary`.`emp_code`, `salary`.`pay_month`";

        $query = mysqli_query($db, $sql);
        $totalFiltered = mysqli_num_rows($query);

        $data = array();
        $i = 1 + $requestData['start'];
        while ( $row = mysqli_fetch_assoc($query) ) {
            $nestedData = array();
            $nestedData[] = $row['pay_month'];
            $nestedData[] = number_format($row['earning_total'], 2, '.', ',');
            $nestedData[] = number_format($row['deduction_total'], 2, '.', ',');
            $nestedData[] = number_format($row['net_salary'], 2, '.', ',');
            
            // Extract month, year, and period from the pay_month string for URL parameters
            // Expected format: "MonthName, Year (period-half)" e.g., "June, 2024 (first-half)"
            preg_match('/^(\w+), (\d{4}) \((\w+)-half\)$/', $row['pay_month'], $matches);
            $monthName = $matches[1] ?? '';
            $year = $matches[2] ?? '';
            $period = $matches[3] ?? '';

            // Convert month name to numeric month
            $monthNum = date('m', strtotime($monthName));

            $nestedData[] = '<button type="button" class="btn btn-success btn-xs" onclick="window.open(\'' . BASE_URL . 'generate-payslip.php?emp_code=' . $empData['emp_code'] . '&month=' . urlencode($monthNum) . '&year=' . urlencode($year) . '&period=' . urlencode($period . '-half') . '\', \'_blank\')"><i class="fa fa-download"></i></button>';

            $data[] = $nestedData;
            $i++;
        }
    }
    $json_data = array(
        "draw"            => intval($requestData['draw']),
        "recordsTotal"    => intval($totalData),
        "recordsFiltered" => intval($totalFiltered),
        "data"            => $data
    );

    echo json_encode($json_data);
}

function LoadingEmployees() {
	global $db;
	$requestData = $_REQUEST;
	$columns = array(
		0 => 'emp_code',
		1 => 'photo',
		2 => 'first_name',
		3 => 'last_name',
		4 => 'email',
		5 => 'mobile',
		6 => 'identity_doc',
		7 => 'identity_no',
		8 => 'dob',
		9 => 'joining_date',
		10 => 'blood_group',
		11 => 'emp_type'
	);

	$sql  = "SELECT `emp_id` ";
	$sql .= " FROM `" . DB_PREFIX . "employees`";
	$query = mysqli_query($db, $sql);
	$totalData = mysqli_num_rows($query);
	$totalFiltered = $totalData;

	$sql  = "SELECT *";
	$sql .= " FROM `" . DB_PREFIX . "employees` WHERE 1 = 1";
	if ( !empty($requestData['search']['value']) ) {
		$sql .= " AND (`emp_id` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR CONCAT(TRIM(`first_name`), ' ', TRIM(`last_name`)) LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `email` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `mobile` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR CONCAT(TRIM(`identity_doc`), ' - ', TRIM(`identity_no`)) LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `dob` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `joining_date` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `blood_group` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `emp_type` LIKE '" . $requestData['search']['value'] . "%')";
	}
	$query = mysqli_query($db, $sql);
	$totalFiltered = mysqli_num_rows($query);
	$sql .= " ORDER BY " . $columns[$requestData['order'][0]['column']] . " " . $requestData['order'][0]['dir'] . " LIMIT " . $requestData['start'] . " ," . $requestData['length'] . "";
	$query = mysqli_query($db, $sql);

	$data = array();
	$i = 1 + $requestData['start'];
	while ( $row = mysqli_fetch_assoc($query) ) {
		$nestedData = array();
		$nestedData[] = $row["emp_code"];
		$nestedData[] = '<img width="50" src="' . REG_URL . 'photos/' . $row["photo"] . '" alt="' . $row["emp_code"] . '" />';
		$nestedData[] = '<a target="_blank" href="' . REG_URL . 'reports/' . $row["emp_code"] . '/">' . $row["first_name"] . ' ' . $row["last_name"] . '</a>';
		$nestedData[] = $row["email"];
		$nestedData[] = $row["mobile"];
		$nestedData[] = $row["identity_doc"] . ' - ' . $row["identity_no"];
		$nestedData[] = $row["dob"];
		$nestedData[] = $row["joining_date"];
		$nestedData[] = strtoupper($row["blood_group"]);
		$nestedData[] = ucwords($row["emp_type"]);
		$data[] = $nestedData;
		$i++;
	}
	$json_data = array(
		"draw"            => intval($requestData['draw']),
		"recordsTotal"    => intval($totalData),
		"recordsFiltered" => intval($totalFiltered),
		"data"            => $data
	);

	echo json_encode($json_data);
}

function AssignPayheadsToEmployee() {
	$result = array();
	global $db;

	$payheads = $_POST['selected_payheads'];
	$default_salary = $_POST['pay_amounts'];
	$emp_code = $_POST['empcode'];
	$checkSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "pay_structure` WHERE `emp_code` = '$emp_code'");
	if ( $checkSQL ) {
		if ( !empty($payheads) && !empty($emp_code) ) {
			if ( mysqli_num_rows($checkSQL) == 0 ) {
				foreach ( $payheads as $payhead ) {
					mysqli_query($db, "INSERT INTO `" . DB_PREFIX . "pay_structure`(`emp_code`, `payhead_id`, `default_salary`) VALUES ('$emp_code', $payhead, " . (!empty($default_salary[$payhead]) ? $default_salary[$payhead] : 0) . ")");
				}
				$result['result'] = 'Payheads are successfully assigned to employee.';
				$result['code'] = 0;
			} else {
				mysqli_query($db, "DELETE FROM `" . DB_PREFIX . "pay_structure` WHERE `emp_code` = '$emp_code'");
				foreach ( $payheads as $payhead ) {
					mysqli_query($db, "INSERT INTO `" . DB_PREFIX . "pay_structure`(`emp_code`, `payhead_id`, `default_salary`) VALUES ('$emp_code', $payhead, " . (!empty($default_salary[$payhead]) ? $default_salary[$payhead] : 0) . ")");
				}
				$result['result'] = 'Payheads are successfully re-assigned to employee.';
				$result['code'] = 0;
			}
		} else {
			$result['result'] = 'Please select payheads and employee to assign.';
			$result['code'] = 2;
		}
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 1;
	}

	echo json_encode($result);
}

function InsertUpdateHolidays() {
	$result = array();
	global $db;

	$holiday_title = stripslashes($_POST['holiday_title']);
    $holiday_desc = stripslashes($_POST['holiday_desc']);
    $holiday_date = stripslashes($_POST['holiday_date']);
    $holiday_type = stripslashes($_POST['holiday_type']);
    if ( !empty($holiday_title) && !empty($holiday_desc) && !empty($holiday_date) && !empty($holiday_type) ) {
	    if ( !empty($_POST['holiday_id']) ) {
	    	$holiday_id = addslashes($_POST['holiday_id']);
	    	$updateHoliday = mysqli_query($db, "UPDATE `" . DB_PREFIX . "holidays` SET `holiday_title` = '$holiday_title', `holiday_desc` = '$holiday_desc', `holiday_date` = '$holiday_date', `holiday_type` = '$holiday_type' WHERE `holiday_id` = $holiday_id");
		    if ( $updateHoliday ) {
		        $result['result'] = 'Holiday record has been successfully updated.';
		        $result['code'] = 0;
		    } else {
		    	$result['result'] = 'Something went wrong, please try again.';
		    	$result['code'] = 1;
		    }
	    } else {
	    	$insertHoliday = mysqli_query($db, "INSERT INTO `" . DB_PREFIX . "holidays`(`holiday_title`, `holiday_desc`, `holiday_date`, `holiday_type`) VALUES ('$holiday_title', '$holiday_desc', '$holiday_date', '$holiday_type')");
		    if ( $insertHoliday ) {
		        $result['result'] = 'Holiday record has been successfully inserted.';
		        $result['code'] = 0;
		    } else {
		    	$result['result'] = 'Something went wrong, please try again.';
		    	$result['code'] = 1;
		    }
		}
	} else {
		$result['result'] = 'Holiday details should not be blank.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function GetHolidayByID() {
	$result = array();
	global $db;

	$id = $_POST['id'];
	$holiSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "holidays` WHERE `holiday_id` = $id LIMIT 0, 1");
	if ( $holiSQL ) {
		if ( mysqli_num_rows($holiSQL) == 1 ) {
			$result['result'] = mysqli_fetch_assoc($holiSQL);
			$result['code'] = 0;
		} else {
			$result['result'] = 'Holiday record is not found.';
			$result['code'] = 1;
		}
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function DeleteHolidayByID() {
	$result = array();
	global $db;

	$id = $_POST['id'];
	$holiSQL = mysqli_query($db, "DELETE FROM `" . DB_PREFIX . "holidays` WHERE `holiday_id` = $id");
	if ( $holiSQL ) {
		$result['result'] = 'Holiday record is successfully deleted.';
		$result['code'] = 0;
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 1;
	}

	echo json_encode($result);
}

function LoadingHolidays() {
	global $db;
	$requestData = $_REQUEST;
	$columns = array(
		0 => 'holiday_id',
		1 => 'holiday_title',
		2 => 'holiday_desc',
		3 => 'holiday_date',
		4 => 'holiday_type',
	);

	$sql  = "SELECT `holiday_id` ";
	$sql .= " FROM `" . DB_PREFIX . "holidays`";
	$query = mysqli_query($db, $sql);
	$totalData = mysqli_num_rows($query);
	$totalFiltered = $totalData;

	$sql  = "SELECT *";
	$sql .= " FROM `" . DB_PREFIX . "holidays` WHERE 1 = 1";
	if ( !empty($requestData['search']['value']) ) {
		$sql .= " AND (`holiday_id` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `holiday_title` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `holiday_desc` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `holiday_date` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `holiday_type` LIKE '" . $requestData['search']['value'] . "%')";
	}
	$query = mysqli_query($db, $sql);
	$totalFiltered = mysqli_num_rows($query);
	$sql .= " ORDER BY " . $columns[$requestData['order'][0]['column']] . " " . $requestData['order'][0]['dir'] . " LIMIT " . $requestData['start'] . " ," . $requestData['length'] . "";
	$query = mysqli_query($db, $sql);

	$data = array();
	$i = 1 + $requestData['start'];
	while ( $row = mysqli_fetch_assoc($query) ) {
		$nestedData = array();
		$nestedData[] = $row["holiday_id"];
		$nestedData[] = $row["holiday_title"];
		$nestedData[] = $row["holiday_desc"];
		$nestedData[] = date('d-m-Y', strtotime($row["holiday_date"]));
		if ( $row["holiday_type"] == 'compulsory' ) {
			$nestedData[] = '<span class="label label-success">' . ucwords($row["holiday_type"]) . '</span>';
		} else {
			$nestedData[] = '<span class="label label-danger">' . ucwords($row["holiday_type"]) . '</span>';
		}
		$data[] = $nestedData;
		$i++;
	}
	$json_data = array(
		"draw"            => intval($requestData['draw']),
		"recordsTotal"    => intval($totalData),
		"recordsFiltered" => intval($totalFiltered),
		"data"            => $data
	);

	echo json_encode($json_data);
}

function InsertUpdatePayheads() {
	$result = array();
	global $db;

	$payhead_name = stripslashes($_POST['payhead_name']);
    $payhead_type = stripslashes($_POST['payhead_type']);
    if ( !empty($payhead_name) && !empty($payhead_type) ) {
	    if ( !empty($_POST['payhead_id']) ) {
	    	$payhead_id = addslashes($_POST['payhead_id']);
	    	$updatePayhead = mysqli_query($db, "UPDATE `" . DB_PREFIX . "payheads` SET `payhead_name` = '$payhead_name', `payhead_type` = '$payhead_type' WHERE `payhead_id` = $payhead_id");
		    if ( $updatePayhead ) {
		        $result['result'] = 'Payhead record has been successfully updated.';
		        $result['code'] = 0;
		    } else {
		    	$result['result'] = 'Something went wrong, please try again.';
		    	$result['code'] = 1;
		    }
	    } else {
	    	$insertPayhead = mysqli_query($db, "INSERT INTO `" . DB_PREFIX . "payheads`(`payhead_name`, `payhead_type`) VALUES ('$payhead_name', '$payhead_type')");
		    if ( $insertPayhead ) {
		        $result['result'] = 'Payhead record has been successfully inserted.';
		        $result['code'] = 0;
		    } else {
		    	$result['result'] = 'Something went wrong, please try again.';
		    	$result['code'] = 1;
		    }
		}
	} else {
		$result['result'] = 'Payhead details should not be blank.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function GetPayheadByID() {
	$result = array();
	global $db;

	$id = $_POST['id'];
	$holiSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "payheads` WHERE `payhead_id` = $id LIMIT 0, 1");
	if ( $holiSQL ) {
		if ( mysqli_num_rows($holiSQL) == 1 ) {
			$result['result'] = mysqli_fetch_assoc($holiSQL);
			$result['code'] = 0;
		} else {
			$result['result'] = 'Payhead record is not found.';
			$result['code'] = 1;
		}
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function DeletePayheadByID() {
	$result = array();
	global $db;

	$id = $_POST['id'];
	$holiSQL = mysqli_query($db, "DELETE FROM `" . DB_PREFIX . "payheads` WHERE `payhead_id` = $id");
	if ( $holiSQL ) {
		$result['result'] = 'Payhead record is successfully deleted.';
		$result['code'] = 0;
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 1;
	}

	echo json_encode($result);
}

function LoadingPayheads() {
	global $db;
	$requestData = $_REQUEST;
	$columns = array(
		0 => 'payhead_id',
		1 => 'payhead_name',
		2 => 'payhead_type'
	);

	$sql  = "SELECT `payhead_id` ";
	$sql .= " FROM `" . DB_PREFIX . "payheads`";
	$query = mysqli_query($db, $sql);
	$totalData = mysqli_num_rows($query);
	$totalFiltered = $totalData;

	$sql  = "SELECT *";
	$sql .= " FROM `" . DB_PREFIX . "payheads` WHERE 1 = 1";
	if ( !empty($requestData['search']['value']) ) {
		$sql .= " AND (`payhead_id` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `payhead_name` LIKE '" . $requestData['search']['value'] . "%'";
		$sql .= " OR `payhead_type` LIKE '" . $requestData['search']['value'] . "%')";
	}
	$query = mysqli_query($db, $sql);
	$totalFiltered = mysqli_num_rows($query);
	$sql .= " ORDER BY " . $columns[$requestData['order'][0]['column']] . " " . $requestData['order'][0]['dir'] . " LIMIT " . $requestData['start'] . " ," . $requestData['length'] . "";
	$query = mysqli_query($db, $sql);

	$data = array();
	$arr = 1;
	$i = 1 + $requestData['start'];
	while ( $row = mysqli_fetch_assoc($query) ) {
		$nestedData = array();
		$nestedData[] = $arr;
		$nestedData[] = $row["payhead_name"];
		if ( $row["payhead_type"] == 'earnings' ) {
			$nestedData[] = '<span class="label label-success">' . ucwords($row["payhead_type"]) . '</span>';
		} else {
			$nestedData[] = '<span class="label label-danger">' . ucwords($row["payhead_type"]) . '</span>';
		}
		$data[] = $nestedData;
		$i++;
		$arr++;
	}
	$json_data = array(
		"draw"            => intval($requestData['draw']),
		"recordsTotal"    => intval($totalData),
		"recordsFiltered" => intval($totalFiltered),
		"data"            => $data
	);

	echo json_encode($json_data);
}

function GetAllPayheadsExceptEmployeeHave() {
	$result = array();
	global $db;

	$emp_code = $_POST['emp_code'];
	$salarySQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "payheads` WHERE `payhead_id` NOT IN (SELECT `payhead_id` FROM `" . DB_PREFIX . "pay_structure` WHERE `emp_code` = '$emp_code')");
	if ( $salarySQL ) {
		if ( mysqli_num_rows($salarySQL) > 0 ) {
			while ( $data = mysqli_fetch_assoc($salarySQL) ) {
				$result['result'][] = $data;
			}
			$result['code'] = 0;
		} else {
			$result['result'] = 'Salary record is not found.';
			$result['code'] = 1;
		}
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function GetEmployeePayheadsByID() {
	$result = array();
	global $db;

	$emp_code = $_POST['emp_code'];
	$salarySQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "pay_structure` AS `pay`, `" . DB_PREFIX . "payheads` AS `head` WHERE `head`.`payhead_id` = `pay`.`payhead_id` AND `pay`.`emp_code` = '$emp_code'");
	if ( $salarySQL ) {
		if ( mysqli_num_rows($salarySQL) > 0 ) {
			while ( $data = mysqli_fetch_assoc($salarySQL) ) {
				$result['result'][] = $data;
			}
			$result['code'] = 0;
		} else {
			$result['result'] = 'Salary record is not found.';
			$result['code'] = 1;
		}
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function GetEmployeeByID() {
	$result = array();
	global $db;

	$emp_code = $_POST['emp_code'];
	$empSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "employees` WHERE `emp_code` = '$emp_code' LIMIT 0, 1");
	if ( $empSQL ) {
		if ( mysqli_num_rows($empSQL) == 1 ) {
			$result['result'] = mysqli_fetch_assoc($empSQL);
			$result['code'] = 0;
		} else {
			$result['result'] = 'Employee record is not found.';
			$result['code'] = 1;
		}
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function DeleteEmployeeByID() {
	$result = array();
	global $db;

	$emp_code = $_POST['emp_code'];
	$empSQL = mysqli_query($db, "DELETE FROM `" . DB_PREFIX . "employees` WHERE `emp_code` = '$emp_code'");
	if ( $empSQL ) {
		$result['result'] = 'Employee record is successfully deleted.';
		$result['code'] = 0;
	} else {
		$result['result'] = 'Something went wrong, please try again.';
		$result['code'] = 1;
	}

	echo json_encode($result);
}

function EditEmployeeDetailsByID() {
	$result = array();
	global $db;

	$emp_id = stripslashes($_POST['emp_id']);
    $first_name = stripslashes($_POST['first_name']);
    $last_name = stripslashes($_POST['last_name']);
    $dob = stripslashes($_POST['dob']);
    $gender = stripslashes($_POST['gender']);
    $merital_status = stripslashes($_POST['merital_status']);
    $nationality = stripslashes($_POST['nationality']);
    $address = stripslashes($_POST['address']);
    $city = stripslashes($_POST['city']);
    $country = stripslashes($_POST['country']);
    $email = stripslashes($_POST['email']);
    $mobile = stripslashes($_POST['mobile']);
    $telephone = stripslashes($_POST['telephone']);
    $identity_doc = stripslashes($_POST['identity_doc']);
    $identity_no = stripslashes($_POST['identity_no']);
    $emp_type = stripslashes($_POST['emp_type']);
    $joining_date = stripslashes($_POST['joining_date']);
    $blood_group = stripslashes($_POST['blood_group']);
    $designation = stripslashes($_POST['designation']);
    $department = stripslashes($_POST['department']);
    $pan_no = stripslashes($_POST['pan_no']);
    $bank_name = stripslashes($_POST['bank_name']);
    $account_no = stripslashes($_POST['account_no']);
    $ifsc_code = stripslashes($_POST['ifsc_code']);
    $pf_account = stripslashes($_POST['pf_account']);
    if ( !empty($first_name) && !empty($last_name) && !empty($dob) && !empty($gender) && !empty($merital_status) && !empty($nationality) && !empty($address) && !empty($city) && !empty($country) && !empty($email) && !empty($mobile) && !empty($identity_doc) && !empty($identity_no) && !empty($emp_type) && !empty($joining_date) && !empty($blood_group) && !empty($designation) && !empty($department) && !empty($pan_no) && !empty($bank_name) && !empty($account_no) && !empty($ifsc_code) && !empty($pf_account) ) {
    	$updateEmp = mysqli_query($db, "UPDATE `" . DB_PREFIX . "employees` SET `first_name` = '$first_name', `last_name` = '$last_name', `dob` = '$dob', `gender` = '$gender', `merital_status` = '$merital_status', `nationality` = '$nationality', `address` = '$address', `city` = '$city' = `country` = '$country', `email` = '$email', `mobile` = '$mobile', `telephone` = '$telephone', `identity_doc` = '$identity_doc', `identity_no` = '$identity_no', `emp_type` = '$emp_type', `joining_date` = '$joining_date', `blood_group` = '$blood_group', `designation` = '$designation', `department` = '$department', `pan_no` = '$pan_no', `bank_name` = '$bank_name', `account_no` = '$account_no', `ifsc_code` = '$ifsc_code', `pf_account` = '$pf_account' WHERE `emp_id` = $emp_id");
	    if ( $updateEmp ) {
	        $result['result'] = 'Employee details has been successfully updated.';
	        $result['code'] = 0;
	    } else {
	    	$result['result'] = 'Something went wrong, please try again.';
	    	$result['code'] = 1;
	    }
	} else {
		$result['result'] = 'All fields are mandatory except Telephone.';
		$result['code'] = 2;
	}

	echo json_encode($result);
}

function GeneratePaySlip() {
	global $mpdf, $db;
	$result = array();

	$emp_code = $_POST['emp_code'];
    $pay_month = $_POST['pay_month'];
    $earnings_heads = $_POST['earnings_heads'];
    $earnings_amounts = $_POST['earnings_amounts'];
    $deductions_heads = $_POST['deductions_heads'];
    $deductions_amounts = $_POST['deductions_amounts'];
    if ( !empty($emp_code) && !empty($pay_month) ) {
	    for ( $i = 0; $i < count($earnings_heads); $i++ ) {
	    	$checkSalSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "salaries` WHERE `emp_code` = '$emp_code' AND `payhead_name` = '" . $earnings_heads[$i] . "' AND `pay_month` = '$pay_month' AND `pay_type` = 'earnings' LIMIT 0, 1");
	    	if ( $checkSalSQL ) {
	    		if ( mysqli_num_rows($checkSalSQL) == 0 ) {
	    			mysqli_query($db, "INSERT INTO `" . DB_PREFIX . "salaries`(`emp_code`, `payhead_name`, `pay_amount`, `earning_total`, `deduction_total`, `net_salary`, `pay_type`, `pay_month`, `generate_date`) VALUES ('$emp_code', '" . $earnings_heads[$i] . "', " . number_format($earnings_amounts[$i], 2, '.', '') . ", " . number_format(array_sum($earnings_amounts), 2, '.', '') . ", " . number_format(array_sum($deductions_amounts), 2, '.', '') . ", " . number_format((array_sum($earnings_amounts) - array_sum($deductions_amounts)), 2, '.', '') . ", 'earnings', '$pay_month', '" . date('Y-m-d H:i:s') . "')");
	    		}
	    	}
	    }
	    for ( $i = 0; $i < count($deductions_heads); $i++ ) {
	    	$checkSalSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "salaries` WHERE `emp_code` = '$emp_code' AND `payhead_name` = '" . $deductions_heads[$i] . "' AND `pay_month` = '$pay_month' AND `pay_type` = 'deductions' LIMIT 0, 1");
	    	if ( $checkSalSQL ) {
	    		if ( mysqli_num_rows($checkSalSQL) == 0 ) {
	    			mysqli_query($db, "INSERT INTO `" . DB_PREFIX . "salaries`(`emp_code`, `payhead_name`, `pay_amount`, `earning_total`, `deduction_total`, `net_salary`, `pay_type`, `pay_month`, `generate_date`) VALUES ('$emp_code', '" . $deductions_heads[$i] . "', " . number_format($deductions_amounts[$i], 2, '.', '') . ", " . number_format(array_sum($earnings_amounts), 2, '.', '') . ", " . number_format(array_sum($deductions_amounts), 2, '.', '') . ", " . number_format((array_sum($earnings_amounts) - array_sum($deductions_amounts)), 2, '.', '') . ", 'deductions', '$pay_month', '" . date('Y-m-d H:i:s') . "')");
	    		}
	    	}
	    }
	    $empData = GetEmployeeDataByEmpCode($emp_code);
	    $empSalary = GetEmployeeSalaryByEmpCodeAndMonth($emp_code, $pay_month);
	    $empLeave = GetEmployeeLWPDataByEmpCodeAndMonth($emp_code, $pay_month);
	    $totalEarnings = 0;
		$totalDeductions = 0;
		$html = '<style>
		@page{margin:20px 20px;font-family:Arial;font-size:14px;}
    	.div_half{float:left;margin:0 0 30px 0;width:50%;}
    	.logo{width:250px;padding:0;}
    	.com_title{text-align:center;font-size:16px;margin:0;}
    	.reg_no{text-align:center;font-size:12px;margin:5px 0;}
    	.subject{text-align:center;font-size:20px;font-weight:bold;}
    	.emp_info{width:100%;margin:0 0 30px 0;}
    	.table{border:1px solid #ccc;margin:0 0 30px 0;}
    	.salary_info{width:100%;margin:0;}
    	.salary_info th,.salary_info td{border:1px solid #ccc;margin:0;padding:5px;vertical-align:middle;}
    	.net_payable{margin:0;color:#050;}
    	.in_word{text-align:right;font-size:12px;margin:5px 0;}
    	.signature{margin:0 0 30px 0;}
    	.signature strong{font-size:12px;padding:5px 0 0 0;border-top:1px solid #000;}
    	.com_info{font-size:12px;text-align:center;margin:0 0 30px 0;}
    	.noseal{text-align:center;font-size:11px;}
	    </style>';
	    $html .= '<div class="div_half">';
	    $html .= '<img class="logo" src="' . BASE_URL . 'dist/img/logo.png" alt="Wisely Online Services Private Limited" />';
	    $html .= '</div>';
	    $html .= '<div class="div_half">';
	    $html .= '<h2 class="com_title">Wisely Online Services Private Limited</h2>';
	    $html .= '<p class="reg_no">Registration Number: 063838, Bangalore</p>';
	    $html .= '</div>';

	    $html .= '<p class="subject">Salary Slip for ' . $pay_month . '</p>';

	    $html .= '<table class="emp_info">';
	    $html .= '<tr>';
	    $html .= '<td width="25%">Employee Code</td>';
	    $html .= '<td width="25%">: ' . strtoupper($emp_code) . '</td>';
	    $html .= '<td width="25%">Bank Name</td>';
	    $html .= '<td width="25%">: ' . ucwords($empData['bank_name']) . '</td>';
	    $html .= '</tr>';

	    $html .= '<tr>';
	    $html .= '<td>Employee Name</td>';
	    $html .= '<td>: ' . ucwords($empData['first_name'] . ' ' . $empData['last_name']) . '</td>';
	    $html .= '<td>Bank Account</td>';
	    $html .= '<td>: ' . $empData['account_no'] . '</td>';
	    $html .= '</tr>';

	    $html .= '<tr>';
	    $html .= '<td>Designation</td>';
	    $html .= '<td>: ' . ucwords($empData['designation']) . '</td>';
	    $html .= '<td>IFSC Code</td>';
	    $html .= '<td>: ' . strtoupper($empData['ifsc_code']) . '</td>';
	    $html .= '</tr>';

	    $html .= '<tr>';
	    $html .= '<td>Gender</td>';
	    $html .= '<td>: ' . ucwords($empData['gender']) . '</td>';
	    $html .= '<td>PAN</td>';
	    $html .= '<td>: ' . strtoupper($empData['pan_no']) . '</td>';
	    $html .= '</tr>';

	    $html .= '<tr>';
	    $html .= '<td>Location</td>';
	    $html .= '<td>: ' . ucwords($empData['city']) . '</td>';
	    $html .= '<td>PF Account</td>';
	    $html .= '<td>: ' . strtoupper($empData['pf_account']) . '</td>';
	    $html .= '</tr>';

	    $html .= '<tr>';
	    $html .= '<td>Department</td>';
	    $html .= '<td>: ' . ucwords($empData['department']) . '</td>';
	    $html .= '<td>Payable/Working Days</td>';
	    $html .= '<td>: ' . ($empLeave['workingDays'] - $empLeave['withoutPay']) . '/' . $empLeave['workingDays'] . ' Days</td>';
	    $html .= '</tr>';

	    $html .= '<tr>';
	    $html .= '<td>Date of Joining</td>';
	    $html .= '<td>: ' . date('d-m-Y', strtotime($empData['joining_date'])) . '</td>';
	    $html .= '<td>Taken/Remaining Leaves</td>';
	    $html .= '<td>: ' . $empLeave['payLeaves'] . '/' . ($empLeave['totalLeaves'] - $empLeave['payLeaves']) . ' Days</td>';
	    $html .= '</tr>';
	    $html .= '</table>';

		$html .= '<table class="table" cellspacing="0" cellpadding="0" width="100%">';
			$html .= '<thead>';
				$html .= '<tr>';
					$html .= '<th width="50%" valign="top">';
						$html .= '<table class="salary_info" cellspacing="0">';
							$html .= '<tr>';
								$html .= '<th align="left">Earnings</th>';
								$html .= '<th width="110" align="right">Amount (Rs.)</th>';
							$html .= '</tr>';
						$html .= '</table>';
					$html .= '</th>';
					$html .= '<th width="50%" valign="top">';
						$html .= '<table class="salary_info" cellspacing="0">';
							$html .= '<tr>';
								$html .= '<th align="left">Deductions</th>';
								$html .= '<th width="110" align="right">Amount (Rs.)</th>';
							$html .= '</tr>';
						$html .= '</table>';
					$html .= '</th>';
				$html .= '</tr>';
			$html .= '</thead>';

			if ( !empty($empSalary) ) {
				$html .= '<tr>';
					$html .= '<td width="50%" valign="top">';
						$html .= '<table class="salary_info" cellspacing="0">';
						foreach ( $empSalary as $salary ) {
							if ( $salary['pay_type'] == 'earnings' ) {
								$totalEarnings += $salary['pay_amount'];
								$html .= '<tr>';
									$html .= '<td align="left">';
										$html .= $salary['payhead_name'];
									$html .= '</td>';
									$html .= '<td width="110" align="right">';
										$html .= number_format($salary['pay_amount'], 2, '.', ',');
									$html .= '</td>';
								$html .= '</tr>';
							}
						}
						$html .= '</table>';
					$html .= '</td>';

					$html .= '<td width="50%" valign="top">';
						$html .= '<table class="salary_info" cellspacing="0">';
						foreach ( $empSalary as $salary ) {
							if ( $salary['pay_type'] == 'deductions' ) {
								$totalDeductions += $salary['pay_amount'];
								$html .= '<tr>';
									$html .= '<td align="left">';
										$html .= $salary['payhead_name'];
									$html .= '</td>';
									$html .= '<td width="110" align="right">';
										$html .= number_format($salary['pay_amount'], 2, '.', ',');
									$html .= '</td>';
								$html .= '</tr>';
							}
						}
						$html .= '</table>';
					$html .= '</td>';
				$html .= '</tr>';
			} else {
				$html .= '<tr>';
					$html .= '<td colspan="2" width="100%">No payheads are assigned for this employee</td>';
				$html .= '</tr>';
			}

			$html .= '<tr>';
				$html .= '<td width="50%" valign="top">';
					$html .= '<table class="salary_info" cellspacing="0">';
						$html .= '<tr>';
							$html .= '<td align="left">';
								$html .= '<strong>Total Earnings</strong>';
							$html .= '</td>';
							$html .= '<td width="110" align="right">';
								$html .= '<strong>' . number_format($totalEarnings, 2, '.', ',') . '</strong>';
							$html .= '</td>';
						$html .= '</tr>';
					$html .= '</table>';
				$html .= '</td>';
				$html .= '<td width="50%" valign="top">';
					$html .= '<table class="salary_info" cellspacing="0">';
						$html .= '<tr>';
							$html .= '<td align="left">';
								$html .= '<strong>Total Deductions</strong>';
							$html .= '</td>';
							$html .= '<td width="110" align="right">';
								$html .= '<strong>' . number_format($totalDeductions, 2, '.', ',') . '</strong>';
							$html .= '</td>';
						$html .= '</tr>';
					$html .= '</table>';
				$html .= '</td>';
			$html .= '</tr>';
		$html .= '</table>';

		$html .= '<div class="div_half">';
			$html .= '<h3 class="net_payable">';
				$html .= 'Net Salary Payable: Rs.' . number_format(($totalEarnings - $totalDeductions), 2, '.', ',');
			$html .= '</h3>';
		$html .= '</div>';
		$html .= '<div class="div_half">';
			$html .= '<h3 class="net_payable">';
				$html .= '<p class="in_word">(In words: ' . ucfirst(ConvertNumberToWords(($totalEarnings - $totalDeductions))) . ')</p>';
			$html .= '</h3>';
		$html .= '</div>';

		$html .= '<div class="signature">';
			$html .= '<table class="emp_info">';
				$html .= '<thead>';
					$html .= '<tr>';
						$html .= '<td>Date: ' . date('d-m-Y') . '</td>';
						$html .= '<th width="200">';
							$html .= '<img width="100" src="' . BASE_URL . 'dist/img/signature.png" alt="Nani Gopal Paul" /><br />';
							$html .= '<strong>Nani Gopal Paul, Director</strong>';
						$html .= '</th>';
					$html .= '</tr>';
				$html .= '</thead>';
			$html .= '</table>';
		$html .= '</div>';

		$html .= '<p class="com_info">';
			$html .= 'No. 15, 20th Main, 100 Feet Road,<br/>';
			$html .= '1st Phase, 2nd Stage, BTM Layout,<br/>';
			$html .= 'Bangalore, 560076,<br/>';
			$html .= 'Karnataka, INDIA<br/>';
			$html .= 'www.wisely.co';
		$html .= '</p>';
		$html .= '<p class="noseal"><small>Note: This is an electronically generated copy & therefore doesn’t require seal.</small></p>';

	    $mpdf->WriteHTML($html);
	    $pay_month = str_replace(', ', '-', $pay_month);
	    $payslip_path = dirname(dirname(__FILE__)) . '/payslips/';
	    if ( ! file_exists($payslip_path . $emp_code . '/') ) {
	    	mkdir($payslip_path . $emp_code, 0777);
	    }
	    if ( ! file_exists($payslip_path . $emp_code . '/' . $pay_month . '/') ) {
	    	mkdir($payslip_path . $emp_code . '/' . $pay_month, 0777);
	    }
		$mpdf->Output($payslip_path . $emp_code . '/' . $pay_month . '/' . $pay_month . '.pdf', 'F');
    	$result['code'] = 0;
    	$_SESSION['PaySlipMsg'] = $pay_month . ' PaySlip has been successfully generated for ' . $emp_code . '.';
    } else {
    	$result['code'] = 1;
    	$result['result'] = 'Something went wrong, please try again.';
    }

	echo json_encode($result);
}

function SendPaySlipByMail() {
	$result = array();
	global $db;

	$emp_code = $_POST['emp_code'];
	$month 	  = $_POST['month'];
	$empData  = GetEmployeeDataByEmpCode($emp_code);
	if ( $empData ) {
		$empName  = $empData['first_name'] . ' ' . $empData['last_name'];
		$empEmail = $empData['email'];
		$subject  = 'PaySlip for ' . $month;
		$message  = '<p>Hi ' . $empData['first_name'] . '</p>';
		$message .= '<p>Here is your attached Salary Slip for the period of ' . $month . '.</p>';
		$message .= '<hr/>';
		$message .= '<p>Thank You,<br/>Wisely Online Services Private Limited</p>';
		$attachment[0]['src'] = dirname(dirname(__FILE__)) . '/payslips/' . $emp_code . '/' . str_replace(', ', '-', $month) . '/' . str_replace(', ', '-', $month) . '.pdf';
		$attachment[0]['name'] = str_replace(', ', '-', $month);
		$send = Send_Mail($subject, $message, $empName, $empEmail, FALSE, FALSE, FALSE, FALSE, $attachment);
		if ( $send == 0 ) {
			$result['code'] = 0;
			$result['result'] = 'PaySlip for ' . $month . ' has been successfully send to ' . $empName;
		} else {
			$result['code'] = 1;
			$result['result'] = 'PaySlip is not send, please try again.';
		}
	} else {
		$result['code'] = 2;
		$result['result'] = 'No such employee found.';
	}

	echo json_encode($result);
}

function EditProfileByID() {
	$result = array();
	global $db;

	if ( $_SESSION['Login_Type'] == 'admin' ) {
		$admin_id = $_SESSION['Admin_ID'];
		$admin_name = addslashes($_POST['admin_name']);
		$admin_email = addslashes($_POST['admin_email']);
		if ( !empty($admin_name) && !empty($admin_email) ) {
			$editSQL = mysqli_query($db, "UPDATE `" . DB_PREFIX . "admin` SET `admin_name` = '$admin_name', `admin_email` = '$admin_email' WHERE `admin_id` = $admin_id");
			if ( $editSQL ) {
				$result['code'] = 0;
				$result['result'] = 'Profile data has been successfully updated.';
			} else {
				$result['code'] = 1;
				$result['result'] = 'Something went wrong, please try again.';
			}
		} else {
			$result['code'] = 2;
			$result['result'] = 'All fields are mandatory.';
		}
	} else {
		$emp_id = stripslashes($_SESSION['Admin_ID']);
	    $first_name = stripslashes($_POST['first_name']);
	    $last_name = stripslashes($_POST['last_name']);
	    $dob = stripslashes($_POST['dob']);
	    $gender = stripslashes($_POST['gender']);
	    $merital_status = stripslashes($_POST['merital_status']);
	    $nationality = stripslashes($_POST['nationality']);
	    $address = stripslashes($_POST['address']);
	    $city = stripslashes($_POST['city']);
	    $country = stripslashes($_POST['country']);
	    $email = stripslashes($_POST['email']);
	    $mobile = stripslashes($_POST['mobile']);
	    $telephone = stripslashes($_POST['telephone']);
	    $identity_doc = stripslashes($_POST['identity_doc']);
	    $identity_no = stripslashes($_POST['identity_no']);
	    $emp_type = stripslashes($_POST['emp_type']);
	    $joining_date = stripslashes($_POST['joining_date']);
	    $blood_group = stripslashes($_POST['blood_group']);
	    $designation = stripslashes($_POST['designation']);
	    $department = stripslashes($_POST['department']);
	    $pan_no = stripslashes($_POST['pan_no']);
	    $bank_name = stripslashes($_POST['bank_name']);
	    $account_no = stripslashes($_POST['account_no']);
	    $ifsc_code = stripslashes($_POST['ifsc_code']);
	    $pf_account = stripslashes($_POST['pf_account']);
	    if ( !empty($first_name) && !empty($last_name) && !empty($dob) && !empty($gender) && !empty($merital_status) && !empty($nationality) && !empty($address) && !empty($city) && !empty($country) && !empty($email) && !empty($mobile) && !empty($identity_doc) && !empty($identity_no) && !empty($emp_type) && !empty($joining_date) && !empty($blood_group) && !empty($designation) && !empty($department) && !empty($pan_no) && !empty($bank_name) && !empty($account_no) && !empty($ifsc_code) && !empty($pf_account) ) {
	    	$updateEmp = mysqli_query($db, "UPDATE `" . DB_PREFIX . "employees` SET `first_name` = '$first_name', `last_name` = '$last_name', `dob` = '$dob', `gender` = '$gender', `merital_status` = '$merital_status', `nationality` = '$nationality', `address` = '$address', `city` = '$city', `country` = '$country', `email` = '$email', `mobile` = '$mobile', `telephone` = '$telephone', `identity_doc` = '$identity_doc', `identity_no` = '$identity_no', `emp_type` = '$emp_type', `joining_date` = '$joining_date', `blood_group` = '$blood_group', `designation` = '$designation', `department` = '$department', `pan_no` = '$pan_no', `bank_name` = '$bank_name', `account_no` = '$account_no', `ifsc_code` = '$ifsc_code', `pf_account` = '$pf_account' WHERE `emp_id` = $emp_id");
		    if ( $updateEmp ) {
		        $result['result'] = 'Profile data has been successfully updated.';
		        $result['code'] = 0;
		    } else {
		    	$result['result'] = 'Something went wrong, please try again.';
		    	$result['code'] = 1;
		    }
		} else {
			$result['result'] = 'All fields are mandatory except Telephone.';
			$result['code'] = 2;
		}
	}

	echo json_encode($result);
}

function EditLoginDataByID() {
	$result = array();
	global $db;

	if ( $_SESSION['Login_Type'] == 'admin' ) {
		$admin_id = $_SESSION['Admin_ID'];
		$admin_code = addslashes($_POST['admin_code']);
		$admin_password = addslashes($_POST['admin_password']);
		$admin_password_conf = addslashes($_POST['admin_password_conf']);
		if ( !empty($admin_code) && !empty($admin_password) && !empty($admin_password_conf) ) {
			if ( $admin_password == $admin_password_conf ) {
				$editSQL = mysqli_query($db, "UPDATE `" . DB_PREFIX . "admin` SET `admin_code` = '$admin_code', `admin_password` = '" . sha1($admin_password) . "' WHERE `admin_id` = $admin_id");
				if ( $editSQL ) {
					$result['code'] = 0;
					$result['result'] = 'Login data has been successfully updated.';
				} else {
					$result['code'] = 1;
					$result['result'] = 'Something went wrong, please try again.';
				}
			} else {
				$result['code'] = 2;
				$result['result'] = 'Confirm password does not match.';
			}
		} else {
			$result['code'] = 3;
			$result['result'] = 'All fields are mandatory.';
		}
	} else {
		$emp_id = $_SESSION['Admin_ID'];
		$old_password = addslashes($_POST['old_password']);
		$new_password = addslashes($_POST['new_password']);
		$password_conf = addslashes($_POST['password_conf']);
		if ( !empty($old_password) && !empty($new_password) && !empty($password_conf) ) {
			$checkPassSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "employees` WHERE `emp_id` = $emp_id");
			if ( $checkPassSQL ) {
				if ( mysqli_num_rows($checkPassSQL) == 1 ) {
					$passData = mysqli_fetch_assoc($checkPassSQL);
					if ( sha1($old_password) == $passData['emp_password'] ) {
						if ( $new_password == $password_conf ) {
							$editSQL = mysqli_query($db, "UPDATE `" . DB_PREFIX . "employees` SET `emp_password` = '" . sha1($new_password) . "' WHERE `emp_id` = $emp_id");
							if ( $editSQL ) {
								$result['code'] = 0;
								$result['result'] = 'Password has been successfully updated.';
							} else {
								$result['code'] = 1;
								$result['result'] = 'Something went wrong, please try again.';
							}
						} else {
							$result['code'] = 2;
							$result['result'] = 'Confirm password does not match.';
						}
					} else {
						$result['code'] = 3;
						$result['result'] = 'Entered wrong existing password.';
					}
				} else {
					$result['code'] = 4;
					$result['result'] = 'No such employee found.';
				}
			} else {
				$result['code'] = 5;
				$result['result'] = 'Something went wrong, please try again.';
			}
		} else {
			$result['code'] = 6;
			$result['result'] = 'All fields are mandatory.';
		}
	}

	echo json_encode($result);
}

function LoadingAllLeaves() {
    global $db;
    $empData = GetDataByIDAndType($_SESSION['Admin_ID'], $_SESSION['Login_Type']);
    $requestData = $_REQUEST;


    $columns = array(
        0 => 'leave_id',
        1 => 'emp_code',
        2 => 'leave_message',
        3 => 'leave_type',
        4 => 'leave_start_date',
        5 => 'leave_end_date',
        6 => 'leave_status',
        7 => 'apply_date'
    );


    $sql = "SELECT `leave_id` FROM `" . DB_PREFIX . "leaves`";
    $query = mysqli_query($db, $sql);
    $totalData = mysqli_num_rows($query);
    $totalFiltered = $totalData;


    $sql = "SELECT * FROM `" . DB_PREFIX . "leaves` WHERE 1=1";


    if (!empty($requestData['search']['value'])) {
        $search = mysqli_real_escape_string($db, $requestData['search']['value']);
        $sql .= " AND (`leave_id` LIKE '$search%'
                 OR `emp_code` LIKE '$search%'
                 OR `leave_message` LIKE '$search%'
                 OR `leave_type` LIKE '$search%'
                 OR `leave_start_date` LIKE '$search%'
                 OR `leave_end_date` LIKE '$search%'
                 OR `leave_status` LIKE '$search%'
                 OR `apply_date` LIKE '$search%')";
    }


    $query = mysqli_query($db, $sql);
    $totalFiltered = mysqli_num_rows($query);


    $sql .= " ORDER BY " . $columns[$requestData['order'][0]['column']] . " " . $requestData['order'][0]['dir'] .
            " LIMIT " . $requestData['start'] . ", " . $requestData['length'];
    $query = mysqli_query($db, $sql);


    $data = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $nestedData = array();
        $nestedData[] = $row["leave_id"];
        $nestedData[] = '<a target="_blank" href="' . REG_URL . 'reports/' . $row["emp_code"] . '/">' . $row["emp_code"] . '</a>';
        $nestedData[] = $row["leave_message"];
        $nestedData[] = $row["leave_type"];
        $nestedData[] = date('M d, Y', strtotime($row["leave_start_date"]));
        $nestedData[] = date('M d, Y', strtotime($row["leave_end_date"]));
       
        if ($row["leave_status"] == 'pending') {
            $nestedData[] = '<span class="label label-warning">Pending</span>';
        } elseif ($row["leave_status"] == 'approve') {
            $nestedData[] = '<span class="label label-success">Approved</span>';
        } elseif ($row["leave_status"] == 'reject') {
            $nestedData[] = '<span class="label label-danger">Rejected</span>';
        } else {
            $nestedData[] = ucwords($row["leave_status"]);
        }


        $nestedData[] = date('M d, Y h:i A', strtotime($row["apply_date"]));


        $data[] = $nestedData;
    }


    $json_data = array(
        "draw" => intval($requestData['draw']),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $data
    );


    echo json_encode($json_data);
}


function LoadingMyLeaves() {
    global $db;
    $empData = GetDataByIDAndType($_SESSION['Admin_ID'], $_SESSION['Login_Type']);
    $requestData = $_REQUEST;


    $columns = array(
        0 => 'leave_id',
        1 => 'emp_code',
        2 => 'leave_message',
        3 => 'leave_type',
        4 => 'leave_start_date',
        5 => 'leave_end_date',
        6 => 'leave_status',
        7 => 'apply_date'
    );


    $sql = "SELECT `leave_id` FROM `" . DB_PREFIX . "leaves` WHERE `emp_code` = '" . $empData['emp_code'] . "'";
    $query = mysqli_query($db, $sql);
    $totalData = mysqli_num_rows($query);
    $totalFiltered = $totalData;


    $sql = "SELECT * FROM `" . DB_PREFIX . "leaves` WHERE `emp_code` = '" . $empData['emp_code'] . "'";


    if (!empty($requestData['search']['value'])) {
        $search = mysqli_real_escape_string($db, $requestData['search']['value']);
        $sql .= " AND (`leave_id` LIKE '$search%'
                 OR `leave_message` LIKE '$search%'
                 OR `leave_type` LIKE '$search%'
                 OR `leave_start_date` LIKE '$search%'
                 OR `leave_end_date` LIKE '$search%'
                 OR `leave_status` LIKE '$search%'
                 OR `apply_date` LIKE '$search%')";
    }


    $query = mysqli_query($db, $sql);
    $totalFiltered = mysqli_num_rows($query);


    $sql .= " ORDER BY " . $columns[$requestData['order'][0]['column']] . " " . $requestData['order'][0]['dir'] .
            " LIMIT " . $requestData['start'] . ", " . $requestData['length'];
    $query = mysqli_query($db, $sql);


    $data = array();
    while ($row = mysqli_fetch_assoc($query)) {
        $nestedData = array();
        $nestedData[] = $row["leave_id"];
        $nestedData[] = $row["emp_code"];
        $nestedData[] = $row["leave_message"];
        $nestedData[] = $row["leave_type"];
        $nestedData[] = date('M d, Y', strtotime($row["leave_start_date"]));
        $nestedData[] = date('M d, Y', strtotime($row["leave_end_date"]));
       
        if ($row["leave_status"] == 'pending') {
            $nestedData[] = '<span class="label label-warning">Pending</span>';
        } elseif ($row["leave_status"] == 'approve') {
            $nestedData[] = '<span class="label label-success">Approved</span>';
        } elseif ($row["leave_status"] == 'reject') {
            $nestedData[] = '<span class="label label-danger">Rejected</span>';
        } else {
            $nestedData[] = ucwords($row["leave_status"]);
        }


        $nestedData[] = date('M d, Y h:i A', strtotime($row["apply_date"]));


        $data[] = $nestedData;
    }


    $json_data = array(
        "draw" => intval($requestData['draw']),
        "recordsTotal" => intval($totalData),
        "recordsFiltered" => intval($totalFiltered),
        "data" => $data
    );


    echo json_encode($json_data);
}


function ApplyLeaveToAdminApproval() {
    $result = array();
    global $db;


    $adminData = GetAdminData(1);
    $empData   = GetDataByIDAndType($_SESSION['Admin_ID'], $_SESSION['Login_Type']);


    // Raw input
    $leave_start_raw = trim($_POST['leave_start_date'] ?? '');
    $leave_end_raw   = trim($_POST['leave_end_date'] ?? '');
    $leave_message   = mysqli_real_escape_string($db, trim($_POST['leave_message'] ?? ''));
    $leave_type      = mysqli_real_escape_string($db, trim($_POST['leave_type'] ?? ''));


    // Convert MM/DD/YYYY to YYYY-MM-DD
    $leave_start_date = mysqli_real_escape_string($db, date('Y-m-d', strtotime($leave_start_raw)));
    $leave_end_date   = mysqli_real_escape_string($db, date('Y-m-d', strtotime($leave_end_raw)));


    // Check if all required fields are filled
    if (!empty($leave_start_date) && !empty($leave_end_date) && !empty($leave_message) && !empty($leave_type)) {
        // Check for overlapping leave
        $checkLeaveSQL = mysqli_query($db, "
            SELECT * FROM `" . DB_PREFIX . "leaves`
            WHERE emp_code = '" . $empData['emp_code'] . "'
            AND NOT (leave_end_date < '$leave_start_date' OR leave_start_date > '$leave_end_date')
        ");


        if ($checkLeaveSQL && mysqli_num_rows($checkLeaveSQL) > 0) {
            $result['code'] = 2;
            $result['result'] = 'You have already applied for leave overlapping the selected dates. Please choose different dates.';
        } else {
            // Insert new leave
            $insertSQL = "
                INSERT INTO `" . DB_PREFIX . "leaves`
                (emp_code, leave_start_date, leave_end_date, leave_message, leave_type, apply_date)
                VALUES
                ('" . $empData['emp_code'] . "', '$leave_start_date', '$leave_end_date', '$leave_message', '$leave_type', '" . date('Y-m-d H:i:s') . "')
            ";
            $leaveSQL = mysqli_query($db, $insertSQL);


            if ($leaveSQL) {
                $insertId   = mysqli_insert_id($db);
                $empName    = $empData['first_name'] . ' ' . $empData['last_name'];
                $adminEmail = $adminData['admin_email'];
                $adminName  = $adminData['admin_name'];
                $empEmail   = $empData['email'];
                $subject    = 'Leave Application Notification';


                $message  = '<p>Employee: ' . $empName . ' (' . $empData['emp_code'] . ')</p>';
                $message .= '<p>Leave Message: ' . $leave_message . '</p>';
                $message .= '<p>Leave Dates: ' . $leave_start_date . ' to ' . $leave_end_date . '</p>';
                $message .= '<p>Leave Type: ' . $leave_type . '</p>';
                $message .= '<hr/>';
                $message .= '<p>Please click on the buttons below or log into the admin area to take action:</p>';
                $message .= '<form method="post" action="' . BASE_URL . 'ajax/?case=ApproveLeaveApplication&id=' . $insertId . '" style="display:inline;">';
                $message .= '<input type="hidden" name="id" value="' . $insertId . '" />';
                $message .= '<button type="submit" style="background:green; border:1px solid green; color:white; padding:0 5px 3px; cursor:pointer; margin-right:15px;">Approve</button>';
                $message .= '</form>';
                $message .= '<form method="post" action="' . BASE_URL . 'ajax/?case=RejectLeaveApplication&id=' . $insertId . '" style="display:inline;">';
                $message .= '<input type="hidden" name="id" value="' . $insertId . '" />';
                $message .= '<button type="submit" style="background:red; border:1px solid red; color:white; padding:0 5px 3px; cursor:pointer;">Reject</button>';
                $message .= '</form>';
                $message .= '<hr/>';
                $message .= '<p>Thank You<br/>' . $empName . '</p>';


                $send = Send_Mail($subject, $message, $adminName, $adminEmail, $empName, $empEmail);


                // Regardless of email status, treat as successful
                $result['code'] = 0;
                $result['result'] = 'Leave Application has been successfully submitted.';
            } else {
                $result['code'] = 1;
                $result['result'] = 'Something went wrong, please try again.';
            }
        }
    } else {
        $result['code'] = 5;
        $result['result'] = 'All fields are mandatory.';
    }


    echo json_encode($result);
}

function ApproveLeaveApplication() {
    $result = array();
    global $db;

    $leaveId = $_REQUEST['id'];
    $leaveSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "leaves` WHERE `leave_id` = $leaveId AND `leave_status` = 'pending' LIMIT 0, 1");
    if ( $leaveSQL ) {
        if ( mysqli_num_rows($leaveSQL) == 1 ) {
            $leaveData = mysqli_fetch_assoc($leaveSQL);
            $update = mysqli_query($db, "UPDATE `" . DB_PREFIX . "leaves` SET `leave_status` = 'approve' WHERE `leave_id` = $leaveId");
            if ( $update ) {
                $empData  = GetEmployeeDataByEmpCode($leaveData['emp_code']);
                if ( $empData ) {
                    $empName  = $empData['first_name'] . ' ' . $empData['last_name'];
                    // $empEmail = $empData['email']; // This line is commented out as email sending is not implemented
                    $subject  = 'Leave Application Approved';
                    $message  = '<p>Hi ' . $empData['first_name'] . '</p>';
                    $message .= '<p>Your leave application is approved.</p>';
                    $message .= '<p>Application Details:</p>';
                    $message .= '<p>Subject: ' . $leaveData['leave_subject'] . '</p>';
                    $message .= '<p>Leave Date(s): ' . $leaveData['leave_start_date'] . ' to ' . $leaveData['leave_end_date'] . '</p>';
                    $message .= '<p>Message: ' . $leaveData['leave_message'] . '</p>';
                    $message .= '<p>Leave Type: ' . $leaveData['leave_type'] . '</p>';
                    $message .= '<p>Status: ' . ucwords($leaveData['leave_status']) . '</p>';
                    $message .= '<hr/>';
                    $message .= '<p>Thank You,<br/>Wisely Online Services Private Limited</p>';
                    $empEmail = $empData['email']; // Still assigning email, but Send_Mail function might not be called or implemented
                    // $send = Send_Mail($subject, $message, $empName, $empEmail); // This line is commented out as email sending is not implemented
                    // if ( $send == 0 ) { // This condition is now irrelevant without Send_Mail
                        $result['code'] = 0;
                        $result['result'] = 'Leave Application is successfully approved.'; // Removed email notification phrase
                    // } else {
                        // $result['code'] = 1;
                        // $result['result'] = 'Leave Application is not approved, please try again.'; // Disabled this line
                    // }
                } else {
                    $result['code'] = 2;
                    $result['result'] = 'No such employee found.';
                }
            } else {
                $result['code'] = 1;
                $result['result'] = 'Something went wrong, please try again.';
            }
        } else {
            $result['code'] = 2;
            $result['result'] = 'This leave application is already verified.';
        }
    } else {
        $result['code'] = 3;
        $result['result'] = 'Something went wrong, please try again.';
    }

    echo json_encode($result);
}

function RejectLeaveApplication() {
    $result = array();
    global $db;

    $leaveId = $_REQUEST['id'];
    $leaveSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "leaves` WHERE `leave_id` = $leaveId AND `leave_status` = 'pending' LIMIT 0, 1");
    if ( $leaveSQL ) {
        if ( mysqli_num_rows($leaveSQL) == 1 ) {
            $leaveData = mysqli_fetch_assoc($leaveSQL);
            $update = mysqli_query($db, "UPDATE `" . DB_PREFIX . "leaves` SET `leave_status` = 'reject' WHERE `leave_id` = $leaveId");
            if ( $update ) {
                $empData  = GetEmployeeDataByEmpCode($leaveData['emp_code']);
                if ( $empData ) {
                    $empName  = $empData['first_name'] . ' ' . $empData['last_name'];
                    $empEmail = $empData['email'];
                    $subject  = 'Leave Application Rejected';
                    $message  = '<p>Hi ' . $empData['first_name'] . '</p>';
                    $message .= '<p>Your leave application is rejected.</p>';
                    $message .= '<p>Application Details:</p>';
                    $message .= '<p>Subject: ' . $leaveData['leave_subject'] . '</p>';
                    $message .= '<p>Leave Date(s): ' . $leaveData['leave_dates'] . '</p>';
                    $message .= '<p>Message: ' . $leaveData['leave_message'] . '</p>';
                    $message .= '<p>Leave Type: ' . $leaveData['leave_type'] . '</p>';
                    $message .= '<p>Status: ' . ucwords($leaveData['leave_status']) . '</p>';
                    $message .= '<hr/>';
                    $message .= '<p>Thank You,<br/>Wisely Online Services Private Limited</p>';
                    // $send = Send_Mail($subject, $message, $empName, $empEmail); // This line is commented out as email sending is not implemented
                    // if ( $send == 0 ) { // This condition is now irrelevant without Send_Mail
                        $result['code'] = 0;
                        $result['result'] = 'Leave Application is rejected.'; // Removed email notification phrase
                    // } else {
                        // $result['code'] = 1;
                        // $result['result'] = 'Leave Application is not rejected, please try again.'; // Disabled this line
                    // }
                } else {
                    $result['code'] = 2;
                    $result['result'] = 'No such employee found.';
                }
            } else {
                $result['code'] = 1;
                $result['result'] = 'Something went wrong, please try again.';
            }
        } else {
            $result['code'] = 2;
            $result['result'] = 'This leave application is already verified.';
        }
    } else {
        $result['code'] = 3;
        $result['result'] = 'Something went wrong, please try again.';
    }

    echo json_encode($result);
}