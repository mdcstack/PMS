$(document).ready(function () {
    // Define leave type rules
    const leaveRules = {
        "Vacation Leave": { startOffset: 5, endOffset: 1460 },
        "Sick Leave": { startOffset: -30, endOffset: -1 },
        "Bereavement Leave": { startOffset: -30, endOffset: -1 },
        "Emergency Leave": { startOffset: -30, endOffset: -1 },
        // Maternity Leave: start date can be any date up to yesterday, end date is 105 days after start
        "Maternity Leave": { special: true },
        "Study Leave": { startOffset: -30, endOffset: 19 }
    };

    // Initialize datepickers
    $('.datepicker').datepicker({
        autoclose: true,
        format: 'mm/dd/yyyy'
    });

    // Helper to format date
    function formatDate(date) {
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        return date.toLocaleDateString(undefined, options);
    }

    // On leave type change
    $('#leave_type').on('change', function () {
        const selected = $(this).val();
        const today = new Date();
        const rule = leaveRules[selected];

        // Reset end date field
        $('#leave_end_date').val("").prop('readonly', false);

        if (rule && rule.special) {
            // Maternity Leave: start date can be any date up to yesterday
            let yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            $('#leave_start_date').datepicker('setStartDate', null);
            $('#leave_start_date').datepicker('setEndDate', yesterday);
            $('#leave_end_date').datepicker('setStartDate', null);
            $('#leave_end_date').datepicker('setEndDate', null);
            $('#date-limit-msg').text('Select your start date (up to yesterday). End date will be set to 105 days after.');
        } else if (rule) {
            // Calculate new min and max dates
            const minDate = new Date(today);
            minDate.setDate(minDate.getDate() + rule.startOffset);
            const maxDate = new Date(today);
            maxDate.setDate(maxDate.getDate() + rule.endOffset);
            // Update datepickers
            $('#leave_start_date').datepicker('setStartDate', minDate);
            $('#leave_start_date').datepicker('setEndDate', maxDate);
            $('#leave_end_date').datepicker('setStartDate', minDate);
            $('#leave_end_date').datepicker('setEndDate', maxDate);
            // Show a helpful message
            const message = `You may select dates between ${formatDate(minDate)} and ${formatDate(maxDate)}.`;
            $('#date-limit-msg').text(message);
        } else {
            // Clear limits if no valid leave selected
            $('#leave_start_date').datepicker('setStartDate', null);
            $('#leave_start_date').datepicker('setEndDate', null);
            $('#leave_end_date').datepicker('setStartDate', null);
            $('#leave_end_date').datepicker('setEndDate', null);
            $('#date-limit-msg').text('');
        }
    });

    // On start date change (for Maternity Leave)
    $('#leave_start_date').on('change', function () {
        const selectedType = $('#leave_type').val();
        const rule = leaveRules[selectedType];
        if (rule && rule.special) {
            const startDateStr = $(this).val();
            if (startDateStr) {
                const parts = startDateStr.split('/');
                // mm/dd/yyyy
                const startDate = new Date(parts[2], parts[0] - 1, parts[1]);
                const endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 105);
                // Format as mm/dd/yyyy
                const endDateStr = (endDate.getMonth() + 1).toString().padStart(2, '0') + '/' + endDate.getDate().toString().padStart(2, '0') + '/' + endDate.getFullYear();
                $('#leave_end_date').val(endDateStr).prop('readonly', true);
                $('#date-limit-msg').text(`End date is automatically set to ${formatDate(endDate)} (105 days after start).`);
            } else {
                $('#leave_end_date').val("").prop('readonly', true);
                $('#date-limit-msg').text('Select your start date (up to yesterday). End date will be set to 105 days after.');
            }
        } else {
            $('#leave_end_date').prop('readonly', false);
        }
    });
});