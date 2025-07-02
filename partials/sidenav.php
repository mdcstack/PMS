<?php
$page_name = basename($_SERVER["SCRIPT_FILENAME"], '.php');

global $userData;
$attendanceSQL = mysqli_query($db, "SELECT * FROM `" . DB_PREFIX . "attendance` WHERE `emp_code` = '" . $userData['emp_code'] . "' AND `attendance_date` = '" . date('Y-m-d') . "'");
if ( $attendanceSQL ) {
	$attendanceROW = mysqli_num_rows($attendanceSQL);
	if ( $attendanceROW == 0 ) {
		$action_name = 'Punch In';
	} else {
		$attendanceDATA = mysqli_fetch_assoc($attendanceSQL);
		if ( $attendanceDATA['action_name'] == 'punchin' ) {
			$action_name = 'Punch Out';
		} else {
			$action_name = 'Punch In';
		}
	}
} else {
	$attendanceROW = 0;
	$action_name = 'Punch In';
} ?>

<aside class="main-sidebar">
	<section class="sidebar">
		<div class="user-panel">
			<div class="pull-left image">
				<?php if ( $_SESSION['Login_Type'] == 'admin' ) { ?>
					<img src="<?php echo BASE_URL; ?>dist/img/adminnew.png" class="img-circle" alt="User Image">
				<?php } else { ?>
					<img src="<?php echo REG_URL; ?>photos/<?php echo $userData['photo']; ?>" class="img-circle" alt="User Image">
				<?php } ?>
			</div>
			<div class="pull-left info">
				<?php if ( $_SESSION['Login_Type'] == 'admin' ) { ?>
					<p><?php echo $userData['admin_name']; ?></p>
				<?php } else { ?>
					<p><?php echo $userData['first_name']; ?> <?php echo $userData['last_name']; ?></p>
				<?php } ?>
				<a href="#"><i class="fa fa-circle text-success"></i> Online</a>
			</div>
		</div>
		<?php if ( $_SESSION['Login_Type'] != 'admin' ) { ?>
			<?php if ( $attendanceROW < 2 ) { ?>
				<form method="POST" class="employee sidebar-form" role="form" id="attendance-form">
	                <div class="input-group">
	                    <input type="text" class="form-control" id="desc" name="desc" placeholder="Comment (if any)" />
	                    <span class="input-group-btn">
	                    	<button type="submit" id="action_btn" class="btn btn-warning"><?php echo $action_name; ?></button>
	                    </span>
	                </div>
	            </form>
	        <?php } ?>
	    <?php } ?>

		<ul class="sidebar-menu">
			<li class="header">NAVIGATION</li>
			<?php if ( $_SESSION['Login_Type'] == 'admin' ) { ?>
				<li class="<?php echo $page_name == "attendance" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>attendance/">
						<i class="fa fa-calendar"></i> <span>Attendance</span>
					</a>
				</li>
				<li class="<?php echo $page_name == "employees" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>employees/">
						<i class="fa fa-users"></i> <span>Employees Section</span>
					</a>
				</li>
				<li>
					<a href="/payroll/registration/newreg.php" target="_blank">
						<i class="fa fa-user-plus"></i> <span>Register New Employee</span>
					</a>
				</li>
				<li class="<?php echo $page_name == "salaries" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>salaries/">
						<i class="fa fa-money"></i> <span>Salary Slips</span>
					</a>
				</li>
				<li class="<?php echo $page_name == "leaves" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>leaves/">
						<i class="fa fa-sign-out"></i> <span>Leave Management</span>
					</a>
				</li>
				<li></li>
				<li class="<?php echo $page_name == "payheads" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>payheads/">
						<i class="fa fa-gratipay"></i>  <span>Pay Heads</span> 
					</a>
				</li>
				
			<?php } else { ?>
				<li class="<?php echo $page_name == "profile" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>profile/">
						<i class="fa fa-user"></i> <span>Profile</span>
					</a>
				</li>
				<li class="<?php echo $page_name == "attendance" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>attendance/">
						<i class="fa fa-calendar"></i> <span>Attendance</span>
					</a>
				</li>
				<li class="<?php echo $page_name == "salaries" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>salaries/">
						<i class="fa fa-money"></i> <span>Salary Slips</span>
					</a>
				</li>
				<li class="<?php echo $page_name == "leaves" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>leaves/">
						<i class="fa fa-sign-out"></i> <span>Leaves</span>
					</a>
				</li>
				<li class="<?php echo $page_name == "holidays" ? 'active' : ''; ?>">
					<a href="<?php echo BASE_URL; ?>holidays/">
						<i class="fa fa-calendar-check-o"></i> <span>Holidays</span>
					</a>
				</li>
			<?php } ?>
			<script>
document.addEventListener('DOMContentLoaded', function () {
	const form = document.getElementById('attendance-form');

	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();

			const desc = document.getElementById('desc').value;

			function sendRequest(confirmPunchOut = false) {
				const formData = new FormData();
				formData.append('desc', desc);
				if (confirmPunchOut) {
					formData.append('confirm_punchout', 'yes'); // ✅ Updated key
				}

				fetch('<?= BASE_URL ?>ajax/?case=AttendanceProcessHandler', {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.code === 2) {
						// Always confirm when punching out
						if (confirm(data.result)) {
							sendRequest(true);
						}
					} else {
						alert(data.result);
						location.reload();
					}
				})
				.catch(error => {
					alert('Error: ' + error.message);
				});
			}

			sendRequest();
		});
	}
});
</script>


		</ul>
	</section>
</aside>
