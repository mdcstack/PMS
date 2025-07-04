/* Login Form Submit Script Start */
if ( $('#login-form').length > 0 ) {
    $(document).on('submit', '#login-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        $.ajax({
            type     : "POST",
            dataType : "json",
            async    : true,
            cache    : false,
            url      : baseurl + "ajax/?case=LoginProcessHandler",
            data     : form.serialize(),
            success  : function(result) {
                if ( result.code == 0 ) {
                    window.location.href = result.result;
                } else {
                    $.notify({
                        icon: 'glyphicon glyphicon-remove-circle',
                        message: result.result,
                    },{
                        allow_dismiss: false,
                        type: "danger",
                        placement: {
                            from: "top",
                            align: "right"
                        },
                        z_index: 9999,
                    });
                }
            }
        });
    });
}
/* End of Script */

/* Attendance Form Submit Script Start */
if ( $('#attendance-form').length > 0 ) {
    $(document).on('submit', '#attendance-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        $.ajax({
            type     : "POST",
            dataType : "json",
            async    : true,
            cache    : false,
            url      : baseurl + "ajax/?case=AttendanceProcessHandler",
            data     : form.serialize(),
            success  : function(result) {
                if ( result.code == 0 ) {
                    form[0].reset();
                    $('#action_btn').text(result.next);
                    if ( result.complete == 2 ) {
                        form.remove();
                    }
                    $.notify({
                        icon: 'glyphicon glyphicon-ok-circle',
                        message: result.result,
                    },{
                        allow_dismiss: false,
                        type: "success",
                        placement: {
                            from: "bottom",
                            align: "right"
                        },
                        z_index: 9999,
                    });
                } else {
                    $.notify({
                        icon: 'glyphicon glyphicon-remove-circle',
                        message: result.result,
                    },{
                        allow_dismiss: false,
                        type: "danger",
                        placement: {
                            from: "bottom",
                            align: "right"
                        },
                        z_index: 9999,
                    });
                }
            }
        });
    });
}
/* End of Script */

$(document).ready(function() {
    /* Attendance Table Script Start */
    if ( $('#attendance').length > 0 ) {
        var att_table = $('#attendance').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": baseurl + "ajax/?case=LoadingAttendance",
            "order": [0, 'desc'],
            "columnDefs": [{
                "targets": 0,
                "className": "dt-center"
            }, {
                "targets": 1,
                "className": "dt-center"
            }]
        });
    }
    /* End of Script */

    /* Salary Table Script Start */
    if ( $('#admin-salary').length > 0 ) {
        var admin_sal_table = $('#admin-salary').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": baseurl + "ajax/?case=LoadingSalaries",
            "order": [0, 'desc'],
            "columnDefs": [{
                "targets": 0,
                "className": "dt-center"
            }]
        });
    }
    if ( $('#emp-salary').length > 0 ) {
        var emp_sal_table = $('#emp-salary').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": baseurl + "ajax/?case=LoadingSalaries",
            "order": [0, 'desc']
        });
    }
    /* End of Script */

    if ( $('#employees').length > 0 ) {
        /* Employee Table Script Start */
        var emp_table = $('#employees').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": baseurl + "ajax/?case=LoadingEmployees",
            "columnDefs": [{
                "targets": 0,
                "className": "dt-center"
            }, {
                "targets": 1,
                "orderable": false,
                "className": "dt-center"
            }, {
                "targets": -1,
                "orderable": false,
                "data": null,
                "className": "dt-center",
                "defaultContent": '<button class="btn btn-warning btn-xs manageSalary"><i class="fa fa-money"></i></button> <button class="btn btn-primary btn-xs addSalary"><i class="fa fa-gratipay"></i></button> <button class="btn btn-success btn-xs editEmp"><i class="fa fa-edit"></i></button> <button class="btn btn-danger btn-xs deleteEmp"><i class="fa fa-trash"></i></button>'
            }]
        });
        /* End of Script */

        /* Pay Salary Script Start */
        $('#employees tbody').on('click', '.manageSalary', function(e) {
            e.preventDefault();
        
            var data = emp_table.row($(this).parents('tr')).data();
            var empCode = data[0]; // Assuming emp_code is the first column
        
            // Clear previous month/year links to regenerate
            $('#SalaryMonthModal .modal-body .row').empty();
        
            // Generate months and years for the modal as before
            // Let's generate a range of months and years (e.g., 6 months back, current month, 6 months forward)
            var today = new Date();
            var currentMonth = today.getMonth(); // 0-11
            var currentYear = today.getFullYear();
        
            // Loop to generate months for the modal (e.g., 6 months back to 6 months forward from current)
            for (let i = -6; i <= 6; i++) { // From 6 months ago to 6 months from now
                let d = new Date(today.getFullYear(), today.getMonth() + i, 1);
                let monthNum = d.getMonth() + 1; // 1-12
                let monthName = new Date(d.getFullYear(), d.getMonth(), 1).toLocaleString('en-us', { month: 'long' });
                let year = d.getFullYear();
        
                let monthLink = baseurl + 'manage-salary.php?emp_code=' + empCode + '&month=' + monthNum + '&year=' + year;
        
                // First half (1st to 15th)
                let firstHalfHtml = `
                    <div class="col-sm-3 month-period-link ${monthNum === (currentMonth + 1) && year === currentYear ? 'bg-danger' : ''}">
                        <a data-month="${monthNum}" data-year="${year}" data-period="first-half" href="${monthLink}&period=first-half">
                            ${monthName.toUpperCase()}<br />(1-15) ${year}
                        </a>
                    </div>
                `;
                $('#SalaryMonthModal .modal-body .row').append(firstHalfHtml);
        
                // Second half (16th to end)
                let secondHalfHtml = `
                    <div class="col-sm-3 month-period-link ${monthNum === (currentMonth + 1) && year === currentYear ? 'bg-danger' : ''}">
                        <a data-month="${monthNum}" data-year="${year}" data-period="second-half" href="${monthLink}&period=second-half">
                            ${monthName.toUpperCase()}<br />(16-${new Date(year, monthNum, 0).getDate()}) ${year}
                        </a>
                    </div>
                `;
                $('#SalaryMonthModal .modal-body .row').append(secondHalfHtml);
            }
        
            // Now, attach click handler to the dynamically generated links
            $('#SalaryMonthModal').off('click', '.month-period-link a').on('click', '.month-period-link a', function(e) {
                e.preventDefault(); // Prevent default link behavior
                window.location.href = $(this).attr('href'); // Navigate to the new URL
            });
        
            $('#SalaryMonthModal').modal('show');
        });
        /* End of Script */

        /* Add Salary Script Start */
        $('#employees tbody').on('click', '.addSalary', function(e) {
            e.preventDefault();

            var data = emp_table.row($(this).parents('tr')).data();
            $('#empcode').val(data[0]);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=GetAllPayheadsExceptEmployeeHave",
                data     : 'emp_code=' + data[0],
                success  : function(result) {
                    $('#all_payheads').html('');
                    if ( result.code == 0 ) {
                        for ( var i in result.result ) {
                            $('#all_payheads').append($("<option></option>")
                                .attr({
                                    "value": result.result[i].payhead_id
                                })
                                .text(
                                    result.result[i].payhead_name + ' (' + jsUcfirst(result.result[i].payhead_type) + ')')
                                .addClass((result.result[i].payhead_type=='earnings'?'text-success':'text-danger'))
                            ); 
                        }
                    }
                }
            });
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=GetEmployeePayheadsByID",
                data     : 'emp_code=' + data[0],
                success  : function(result) {
                    $('#selected_payheads, #selected_payamount').html('');
                    if ( result.code == 0 ) {
                        for ( var i in result.result ) {
                            var payhead = result.result[i];
                            $('#selected_payheads').append($("<option></option>")
                                .attr({
                                    "value": payhead.payhead_id,
                                    "selected": "selected"
                                })
                                .text(
                                    payhead.payhead_name + ' (' + jsUcfirst(payhead.payhead_type) + ')'
                                )
                                .addClass((payhead.payhead_type=='earnings'?'text-success':'text-danger'))
                            );
                        }
                        // Render all selected payheads and their descriptions
                        renderSelectedPayheadDescriptions();
                    }
                }
            });
            $('#ManageModal').modal('show');
        });
        /* End of Script */

        /* Delete Employee Script Start */
        $('#employees tbody').on('click', '.editEmp', function(e) {
            e.preventDefault();

            var data = emp_table.row($(this).parents('tr')).data();
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=GetEmployeeByID",
                data     : 'emp_code=' + data[0],
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $('#emp_id').val(result.result.emp_id);
                        $('#emp_code').text(result.result.emp_code);
                        $('#first_name').val(result.result.first_name);
                        $('#last_name').val(result.result.last_name);
                        $('#dob').val(result.result.dob).datepicker('update');
                        $('#gender').val(result.result.gender);
                        $('#merital_status').val(result.result.merital_status);
                        $('#nationality').val(result.result.nationality);
                        $('#address').val(result.result.address);
                        $('#city').val(result.result.city);
                        $('#country').val(result.result.country);
                        $('#email').val(result.result.email);
                        $('#mobile').val(result.result.mobile);
                        $('#telephone').val(result.result.telephone);
                        $('#identity_doc').val(result.result.identity_doc);
                        $('#identity_no').val(result.result.identity_no);
                        $('#emp_type').val(result.result.emp_type);
                        $('#joining_date').val(result.result.joining_date).datepicker('update');
                        $('#blood_group').val(result.result.blood_group);
                        $('#designation').val(result.result.designation);
                        $('#department').val(result.result.department);
                        $('#pan_no').val(result.result.pan_no);
                        $('#bank_name').val(result.result.bank_name);
                        $('#account_no').val(result.result.account_no);
                        $('#ifsc_code').val(result.result.ifsc_code);
                        $('#pf_account').val(result.result.pf_account);
                        $('#EditEmpModal').modal('show');
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
        /* End of Script */

        /* Delete Employee Script Start */
        $('#employees tbody').on('click', '.deleteEmp', function(e) {
            e.preventDefault();

            var conf = confirm('Are you sure you want to delete this employee?');
            if ( conf ) {
                var data = emp_table.row($(this).parents('tr')).data();
                $.ajax({
                    type     : "POST",
                    dataType : "json",
                    async    : true,
                    cache    : false,
                    url      : baseurl + "ajax/?case=DeleteEmployeeByID",
                    data     : 'emp_code=' + data[0],
                    success  : function(result) {
                        if ( result.code == 0 ) {
                            $.notify({
                                icon: 'glyphicon glyphicon-ok-circle',
                                message: result.result,
                            },{
                                allow_dismiss: false,
                                type: "success",
                                placement: {
                                    from: "top",
                                    align: "right"
                                },
                                z_index: 9999,
                            });
                            emp_table.ajax.reload(null, false);
                        } else {
                            $.notify({
                                icon: 'glyphicon glyphicon-remove-circle',
                                message: result.result,
                            },{
                                allow_dismiss: false,
                                type: "danger",
                                placement: {
                                    from: "top",
                                    align: "right"
                                },
                                z_index: 9999,
                            });
                        }
                    }
                });
            }
        });
        /* End of Script */

        /* Add Payhead To Employee Script Start */
        $(document).on('click', '#selectHeads', function() {
            moveItems('#all_payheads', '#selected_payheads');
            renderSelectedPayheadDescriptions();
            setTimeout(renderSelectedPayheadDescriptions, 200);
        });
        $(document).on('click', '#removeHeads', function() {
            moveItems('#selected_payheads', '#all_payheads');
            renderSelectedPayheadDescriptions();
            setTimeout(renderSelectedPayheadDescriptions, 200);
        });
        $(document).on('click', '#selectAllHeads', function() {
            $('#all_payheads option').prop('selected', true);
            moveItems('#all_payheads', '#selected_payheads');
            renderSelectedPayheadDescriptions();
            setTimeout(renderSelectedPayheadDescriptions, 200);
        });
        /* End of Script */
    }

    /* Date Picker Script Start */
    if ( $('.datepicker').length > 0 ) {
        $('.datepicker').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true
        });
    }
    if ( $('.multidatepicker').length > 0 ) {
        $('.multidatepicker').datepicker({
            format: 'mm/dd/yyyy',
            startDate : new Date(),
            multidate: true,
            autoclose: true
        });
    }
    /* End of Script */

    /* Stylish Radio Input Script Start */ 
    if ( $('input[type="radio"].minimal').length > 0 ) {
        $('input[type="radio"].minimal').iCheck({
            radioClass: 'iradio_minimal-blue'
        });
    }
    /* End of Script */

    /* Holiday Table Script Start */
    if ( $('#empholidays').length > 0 ) {
        $('#empholidays').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": baseurl + "ajax/?case=LoadingHolidays",
            "columnDefs": [{
                "targets": 0,
                "className": "dt-center"
            }, {
                "targets": 3,
                "className": "dt-center"
            }, {
                "targets": 4,
                "className": "dt-center"
            }]
        });
    }
    /* End of Script */

    if ( $('#holidays').length > 0 ) {
        /* Holiday Table Script Start */
        var holi_table = $('#holidays').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": baseurl + "ajax/?case=LoadingHolidays",
            "columnDefs": [{
                "targets": 0,
                "className": "dt-center"
            }, {
                "targets": 3,
                "className": "dt-center"
            }, {
                "targets": 4,
                "className": "dt-center"
            }, {
                "targets": -1,
                "orderable": false,
                "data": null,
                "className": "dt-center",
                "defaultContent": '<button class="btn btn-success btn-xs editHoliday"><i class="fa fa-edit"></i></button> <button class="btn btn-danger btn-xs deleteHoliday"><i class="fa fa-trash"></i></button>'
            }]
        });
        /* End of Script */

        /* Edit Holiday Script Start */
        $('#holidays tbody').on('click', '.editHoliday', function(e) {
            e.preventDefault();

            var data = holi_table.row($(this).parents('tr')).data();
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=GetHolidayByID",
                data     : 'id=' + data[0],
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $("#holiday_id").val(result.result.holiday_id);
                        $("#holiday_title").val(result.result.holiday_title);
                        $("#holiday_desc").val(result.result.holiday_desc);
                        $("#holiday_date").val(result.result.holiday_date).datepicker('update');
                        if ( result.result.holiday_type == 'compulsory' ) {
                            $("#compulsory_holiday").iCheck('check');
                        } else {
                            $("#restricted_holiday").iCheck('check');
                        }
                        $("#HolidayModal").modal('show');
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
        /* End of Script */

        /* Delete Holiday Script Start */
        $('#holidays tbody').on('click', '.deleteHoliday', function(e) {
            e.preventDefault();

            var conf = confirm('Are you sure you want to delete this holiday?');
            if ( conf ) {
                var data = holi_table.row($(this).parents('tr')).data();
                $.ajax({
                    type     : "POST",
                    dataType : "json",
                    async    : true,
                    cache    : false,
                    url      : baseurl + "ajax/?case=DeleteHolidayByID",
                    data     : 'id=' + data[0],
                    success  : function(result) {
                        if ( result.code == 0 ) {
                            $.notify({
                                icon: 'glyphicon glyphicon-ok-circle',
                                message: result.result,
                            },{
                                allow_dismiss: false,
                                type: "success",
                                placement: {
                                    from: "top",
                                    align: "right"
                                },
                                z_index: 9999,
                            });
                            holi_table.ajax.reload(null, false);
                        } else {
                            $.notify({
                                icon: 'glyphicon glyphicon-remove-circle',
                                message: result.result,
                            },{
                                allow_dismiss: false,
                                type: "danger",
                                placement: {
                                    from: "top",
                                    align: "right"
                                },
                                z_index: 9999,
                            });
                        }
                    }
                });
            }
        });
        /* End of Script */
    }

    /* Holiday Modal Close Script Start */
    if ( $('#EditEmpModal').length > 0 ) {
        $('#EditEmpModal').on('hidden.bs.modal', function () {
            $("#emp_code").empty();
            $('#edit-emp-form')[0].reset();
        });
    }
    /* End of Script */

    /* Holiday Modal Close Script Start */
    if ( $('#HolidayModal').length > 0 ) {
        $('#HolidayModal').on('hidden.bs.modal', function () {
            $("#holiday_id").val('');
            $("#compulsory_holiday").iCheck('check');
            $('#holiday-form')[0].reset();
        });
    }
    /* End of Script */

    /* Manage Modal Close Script Start */
    if ( $('#ManageModal').length > 0 ) {
        $('#ManageModal').on('hidden.bs.modal', function () {
            $("#empcode").val('');
            $('#selected_payheads').html('');
        });
    }
    /* End of Script */

    /* Assign Payhead to Employee Form Submit Script Start */
    if ( $('#assign-payhead-form').length > 0 ) {
        $('#assign-payhead-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=AssignPayheadsToEmployee",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $.notify({
                            icon: 'glyphicon glyphicon-ok-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "success",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                        $('#ManageModal').modal('hide');
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
    /* End of Script */

    /* Holiday Form Submit Script Start */
    if ( $('#holiday-form').length > 0 ) {
        $('#holiday-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=InsertUpdateHolidays",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $.notify({
                            icon: 'glyphicon glyphicon-ok-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "success",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                        holi_table.ajax.reload(null, false);
                        $('#HolidayModal').modal('hide');
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
    /* End of Script */

    /* Employee Edit Form Submit Script Start */
    if ( $('#edit-emp-form').length > 0 ) {
        $('#edit-emp-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=EditEmployeeDetailsByID",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $.notify({
                            icon: 'glyphicon glyphicon-ok-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "success",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                        emp_table.ajax.reload(null, false);
                        $('#EditEmpModal').modal('hide');
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
    /* End of Script */

    if ( $('#payheads').length > 0 ) {
        /* Payhead Table Script Start */
        var pay_table = $('#payheads').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": baseurl + "ajax/?case=LoadingPayheads",
            "columnDefs": [{
                "targets": 0,
                "className": "dt-center"
            }, {
                "targets": 2,
                "className": "dt-center"
            }, {
                "targets": 3,
                "className": "dt-center"
            }, {
                "targets": -1,
                "orderable": false,
                "data": null,
                "className": "dt-center",
                "defaultContent": '<button class="btn btn-success btn-xs editPayheads"><i class="fa fa-edit"></i></button> <button class="btn btn-danger btn-xs deletePayheads"><i class="fa fa-trash"></i></button>'
            }]
        });
        /* End of Script */

        /* Edit Payhead Script Start */
        $('#payheads tbody').on('click', '.editPayheads', function(e) {
            e.preventDefault();

            var data = pay_table.row($(this).parents('tr')).data();
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=GetPayheadByID",
                data     : 'id=' + data[0],
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $("#payhead_id").val(result.result.payhead_id);
                        $("#payhead_name").val(result.result.payhead_name);
                        $("#payhead_type").val(result.result.payhead_type);
                        $("#PayheadsModal").modal('show');
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
        /* End of Script */

        /* Delete Payhead Script Start */
        $('#payheads tbody').on('click', '.deletePayheads', function(e) {
            e.preventDefault();

            var conf = confirm('Are you sure you want to delete this payhead?');
            if ( conf ) {
                var data = pay_table.row($(this).parents('tr')).data();
                $.ajax({
                    type     : "POST",
                    dataType : "json",
                    async    : true,
                    cache    : false,
                    url      : baseurl + "ajax/?case=DeletePayheadByID",
                    data     : 'id=' + data[0],
                    success  : function(result) {
                        if ( result.code == 0 ) {
                            $.notify({
                                icon: 'glyphicon glyphicon-ok-circle',
                                message: result.result,
                            },{
                                allow_dismiss: false,
                                type: "success",
                                placement: {
                                    from: "top",
                                    align: "right"
                                },
                                z_index: 9999,
                            });
                            pay_table.ajax.reload(null, false);
                        } else {
                            $.notify({
                                icon: 'glyphicon glyphicon-remove-circle',
                                message: result.result,
                            },{
                                allow_dismiss: false,
                                type: "danger",
                                placement: {
                                    from: "top",
                                    align: "right"
                                },
                                z_index: 9999,
                            });
                        }
                    }
                });
            }
        });
        /* End of Script */
    }

    /* Payhead Modal Close Script Start */
    if ( $('#PayheadsModal').length > 0 ) {
        $('#PayheadsModal').on('hidden.bs.modal', function () {
            $("#payhead_id").val('');
            $('#payhead-form')[0].reset();
        });
    }
    /* End of Script */

    /* Payhead Form Submit Script Start */
    if ( $('#payhead-form').length > 0 ) {
        $('#payhead-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=InsertUpdatePayheads",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $.notify({
                            icon: 'glyphicon glyphicon-ok-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "success",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                        pay_table.ajax.reload(null, false);
                        $('#PayheadsModal').modal('hide');
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
    /* End of Script */

    /* Salary Form Submit Script Start */
    if ( $('#payslip-form').length > 0 ) {
        $('#payslip-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=GeneratePaySlip",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        window.location.reload();
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
    /* End of Script */

    /* Profile Edit Form Submit Script Start */
    if ( $('#profile-form').length > 0 ) {
        $('#profile-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=EditProfileByID",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        $.notify({
                            icon: 'glyphicon glyphicon-ok-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "success",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
    /* End of Script */

    /* Password Edit Form Submit Script Start */
    if ( $('#password-form').length > 0 ) {
        $('#password-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=EditLoginDataByID",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        form[0].reset();
                        $.notify({
                            icon: 'glyphicon glyphicon-ok-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "success",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
    /* End of Script */

   /* Leave Table Script Start */
if ($('#allleaves').length > 0) {
    var leave_table = $('#allleaves').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": baseurl + "ajax/?case=LoadingAllLeaves",
        "columnDefs": [{
            "targets": 0,
            "className": "dt-center"
        }, {
            "targets": -1,
            "orderable": false,
            "data": null,
            "className": "dt-center",
            "defaultContent": '<button class="btn btn-success btn-xs approveLeave"><i class="fa fa-check"></i></button> <button class="btn btn-danger btn-xs rejectLeave"><i class="fa fa-close"></i></button>'
        }]
    });

    /* Approve Leave Application Script Start */
    $('#allleaves tbody').on('click', '.approveLeave', function(e) {
        e.preventDefault();

        var data = leave_table.row($(this).parents('tr')).data();
        $.ajax({
            type: "POST",
            dataType: "json",
            async: true,
            cache: false,
            url: baseurl + "ajax/?case=ApproveLeaveApplication",
            data: 'id=' + data[0],
            success: function(result) {
                if (result.code == 0) {
                    $.notify({
                        icon: 'glyphicon glyphicon-ok-circle',
                        message: result.result,
                    }, {
                        allow_dismiss: false,
                        type: "success",
                        placement: {
                            from: "top",
                            align: "right"
                        },
                        z_index: 9999,
                    });
                    // Refresh the entire page
                    location.reload();
                } else {
                    $.notify({
                        icon: 'glyphicon glyphicon-remove-circle',
                        message: result.result,
                    }, {
                        allow_dismiss: false,
                        type: "danger",
                        placement: {
                            from: "top",
                            align: "right"
                        },
                        z_index: 9999,
                    });
                }
            }
        });
    });
    /* End of Script */

    /* Reject Leave Application Script Start */
    $('#allleaves tbody').on('click', '.rejectLeave', function(e) {
        e.preventDefault();

        var data = leave_table.row($(this).parents('tr')).data();
        $.ajax({
            type: "POST",
            dataType: "json",
            async: true,
            cache: false,
            url: baseurl + "ajax/?case=RejectLeaveApplication",
            data: 'id=' + data[0],
            success: function(result) {
                if (result.code == 0) {
                    $.notify({
                        icon: 'glyphicon glyphicon-ok-circle',
                        message: result.result,
                    }, {
                        allow_dismiss: false,
                        type: "success",
                        placement: {
                            from: "top",
                            align: "right"
                        },
                        z_index: 9999,
                    });
                    // Refresh the entire page
                    location.reload();
                } else {
                    $.notify({
                        icon: 'glyphicon glyphicon-remove-circle',
                        message: result.result,
                    }, {
                        allow_dismiss: false,
                        type: "danger",
                        placement: {
                            from: "top",
                            align: "right"
                        },
                        z_index: 9999,
                    });
                }
            }
        });
    });
    /* End of Script */
}
/* End of Script */

/* Leave Table Script Start */
if ($('#myleaves').length > 0) {
    var myleave = $('#myleaves').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": baseurl + "ajax/?case=LoadingMyLeaves",
        "columnDefs": [{
            "targets": 0,
            "className": "dt-center"
        }]
    });
}
/* End of Script */
    /* End of Script */

    /* Leave Apply Form Submit Script Start */
    if ( $('#leave-form').length > 0 ) {
        $('#leave-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            $.ajax({
                type     : "POST",
                dataType : "json",
                async    : true,
                cache    : false,
                url      : baseurl + "ajax/?case=ApplyLeaveToAdminApproval",
                data     : form.serialize(),
                success  : function(result) {
                    if ( result.code == 0 ) {
                        form[0].reset();
                        $.notify({
                            icon: 'glyphicon glyphicon-ok-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "success",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                        myleave.ajax.reload(null, false);
                    } else {
                        $.notify({
                            icon: 'glyphicon glyphicon-remove-circle',
                            message: result.result,
                        },{
                            allow_dismiss: false,
                            type: "danger",
                            placement: {
                                from: "top",
                                align: "right"
                            },
                            z_index: 9999,
                        });
                    }
                }
            });
        });
    }
});

function moveItems(origin, dest) {
    $(origin).find(':selected').appendTo(dest);
}

function jsUcfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function openInNewTab(url) {
    var win = window.open(url, '_blank');
    win.focus();
}

function sendPaySlipByMail(emp_code, month) {
    $.ajax({
        type     : "POST",
        dataType : "json",
        async    : true,
        cache    : false,
        url      : baseurl + "ajax/?case=SendPaySlipByMail",
        data     : 'emp_code=' + emp_code + '&month=' + month,
        success  : function(result) {
            if ( result.code == 0 ) {
                $.notify({
                    icon: 'glyphicon glyphicon-ok-circle',
                    message: result.result,
                },{
                    allow_dismiss: false,
                    type: "success",
                    placement: {
                        from: "top",
                        align: "right"
                    },
                    z_index: 9999,
                });
            } else {
                $.notify({
                    icon: 'glyphicon glyphicon-remove-circle',
                    message: result.result,
                },{
                    allow_dismiss: false,
                    type: "danger",
                    placement: {
                        from: "top",
                        align: "right"
                    },
                    z_index: 9999,
                });
            }
        }
    });
}

// MutationObserver to always update right column when #selected_payheads changes
if (window.MutationObserver) {
    var observer = new MutationObserver(function(mutations) {
        renderSelectedPayheadDescriptions();
    });
    var target = document.getElementById('selected_payheads');
    if (target) {
        observer.observe(target, { childList: true });
    }
}

// Add console logging to renderSelectedPayheadDescriptions
function renderSelectedPayheadDescriptions() {
    $('#selected_payamount').html('');
    $('#selected_payheads option').each(function(i, el) {
        var name = $(this).text();
        var payheadName = name.split(' (')[0].trim().toLowerCase();
        var label = name.split(' (')[0];
        var description = '';
        switch (payheadName) {
            case 'sss':
                description = '4.5%';
                break;
            case 'pag-ibig':
                description = '2%';
                break;
            case 'philhealth':
                description = '2.5%';
                break;
            default:
                description = '';
                break;
        }
        console.log('Rendering payhead:', label, '| Description:', description);
        $('#selected_payamount').append(
            '<div class="form-group">' +
                '<label>' + label + '</label>' +
                (description ? '<p class="form-control-static">' + description + '</p>' : '') +
            '</div>'
        );
    });
}

//