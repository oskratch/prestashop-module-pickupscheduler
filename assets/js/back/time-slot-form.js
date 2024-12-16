$(document).ready(function() {
    $('input[type=radio]').change(function() {
        var day = $(this).attr('name').split('_')[2].toLowerCase();
        if ($(this).val() == '0') {
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_START_TIME"]').closest('.form-group').hide();
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_END_TIME"]').closest('.form-group').hide();
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_INTERVAL_MINUTES"]').closest('.form-group').hide();
        } else {
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_START_TIME"]').closest('.form-group').show();
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_END_TIME"]').closest('.form-group').show();
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_INTERVAL_MINUTES"]').closest('.form-group').show();
        }
    });

    $('input[type=radio]:checked').each(function() {
        var day = $(this).attr('name').split('_')[2].toLowerCase();
        if ($(this).val() == '0') {
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_START_TIME"]').closest('.form-group').hide();
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_END_TIME"]').closest('.form-group').hide();
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_INTERVAL_MINUTES"]').closest('.form-group').hide();
        }
    });

    $('input[type=radio]').change(function() {
        var day = $(this).attr('name').split('_')[2].toLowerCase();
        if ($(this).val() == '0') {
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_START_TIME"]').prop('required', false);
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_END_TIME"]').prop('required', false);
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_INTERVAL_MINUTES"]').prop('required', false);
        } else {
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_START_TIME"]').prop('required', true);
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_END_TIME"]').prop('required', true);
            $('input[name="PICKUP_SCHEDULER_' + day.toUpperCase() + '_INTERVAL_MINUTES"]').prop('required', true);
        }
    });

});