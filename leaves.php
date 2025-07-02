<?php require_once(dirname(__FILE__) . '/config.php');
if (!isset($_SESSION['Admin_ID']) || !isset($_SESSION['Login_Type'])) {
    header('location:' . BASE_URL);
} ?>




<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">




    <title>Leaves - Payroll</title>




    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>plugins/datatables/dataTables.bootstrap.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>plugins/datatables/jquery.dataTables_themeroller.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>dist/css/AdminLTE.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>plugins/datepicker/datepicker3.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>dist/css/skins/_all-skins.min.css">




    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>




<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">




    <?php require_once(dirname(__FILE__) . '/partials/topnav.php'); ?>
    <?php require_once(dirname(__FILE__) . '/partials/sidenav.php'); ?>




    <div class="content-wrapper">
        <section class="content-header">
            <h1>Leaves</h1>
            <ol class="breadcrumb">
                <li><a href="<?php echo BASE_URL; ?>"><i class="fa fa-dashboard"></i> Home</a></li>
                <li class="active">Leaves</li>
            </ol>
        </section>




        <section class="content">
            <div class="row">
                <?php if ($_SESSION['Login_Type'] == 'admin') { ?>
                    <div class="col-xs-12">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">All Leaves</h3>
                            </div>
                            <div class="box-body">
                                <table id="allleaves" class="table table-bordered table-stripe">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>EMP CODE</th>
                                            <th>SUBJECT</th>
                                            <th>DATES</th>
                                            <th>MESSAGE</th>
                                            <th>TYPE</th>
                                            <th>STATUS</th>
                                            <th>ACTIONS</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="col-lg-4">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">Apply for Leave</h3>
                            </div>
                            <div class="box-body">
                                <form method="post" role="form" data-toggle="validator" id="leave-form">
                                    <div class="form-group">
                                        <label for="leave_type">Leave Type</label>
                                        <select class="form-control" name="leave_type" id="leave_type" required>
                                            <option value="">Please make a choice</option>
                                            <option value="Sick Leave">Sick Leave</option>
                                            <option value="Maternity Leave">Maternity Leave</option>
                                            <option value="Bereavement Leave">Bereavement Leave</option>
                                            <option value="Vacation Leave">Vacation Leave</option>
                                            <option value="Study/Personal Leave">Study Leave</option>
                                            <option value="Special Leave">Special Leave</option>
                                            <option value="Emergency Leave">Emergency Leave</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="leave_start_date">Start Date (MM/DD/YYYY)</label>
                                        <input type="text" class="form-control datepicker" name="leave_start_date" id="leave_start_date" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="leave_end_date">End Date (MM/DD/YYYY)</label>
                                        <input type="text" class="form-control datepicker" name="leave_end_date" id="leave_end_date" required />
                                        <small id="date-limit-msg" class="text-info"></small>
                                    </div>
                                    <div class="form-group">
                                        <label for="leave_message">Leave Message</label>
                                        <textarea class="form-control" name="leave_message" id="leave_message" rows="10" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Apply for Leave</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg-8">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">My Leaves</h3>
                            </div>
                            <div class="box-body">
                                <table id="myleaves" class="table table-bordered table-stripe">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>EMPLOYEE CODE</th>
                                            <th>MESSAGE</th>
                                             <th>TYPE</th>
                                            <th>START DATE</th>
                                            <th>END DATE</th>
                                            <th>STATUS</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </section>
    </div>


</div>




<!-- Scripts -->
<script src="<?php echo BASE_URL; ?>plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="<?php echo BASE_URL; ?>bootstrap/js/bootstrap.min.js"></script>
<script src="<?php echo BASE_URL; ?>plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_URL; ?>plugins/datatables/dataTables.bootstrap.min.js"></script>
<script src="<?php echo BASE_URL; ?>plugins/jquery-validator/validator.min.js"></script>
<script src="<?php echo BASE_URL; ?>plugins/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="<?php echo BASE_URL; ?>plugins/datepicker/bootstrap-datepicker.js"></script>
<script src="<?php echo BASE_URL; ?>dist/js/app.min.js"></script>
<script type="text/javascript">var baseurl = '<?php echo BASE_URL; ?>';</script>
<script src="<?php echo BASE_URL; ?>dist/js/script.js?rand=<?php echo rand(); ?>"></script>




<!-- Date limit logic -->
<script src="<?php echo BASE_URL; ?>limit.js?rand=<?php echo rand(); ?>"></script>
</body>
</html>

