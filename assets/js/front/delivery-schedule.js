var pickupSchedulerCarrierId = window.pickupSchedulerCarrierId;
var weekends = window.weekends;
weekends = weekends === '1';
var minTime = window.minTime;
var maxTime = window.maxTime;
var minInterval = window.minInterval;
var customer_id = window.customer_id;
var autoSelectEventId = window.autoSelectEventId;
var autoSelectEventDate = autoSelectEventDate;
var autoSelectEventExpires = window.autoSelectEventExpires;

var radioButtons = $('input[name="delivery_option[2]"]');
var hiddenDiv = $('#hiddenDiv');
var continueButton = $('form#js-delivery button.continue');

$(document).ready(function() {
    radioButtons.each(function() {
        if ($(this).val().split(',')[0] === pickupSchedulerCarrierId && $(this).is(':checked')) {
            hiddenDiv.show();
            continueButton.hide();
        }

        $(this).on('change', function() {
            var carrierId = $(this).val().split(',')[0];
            if (carrierId === pickupSchedulerCarrierId) {
                hiddenDiv.show();
                continueButton.hide();
            } else {
                hiddenDiv.hide();
                continueButton.show();
            }
        });
    });

    var today = autoSelectEventId ? autoSelectEventDate : new Date().toISOString().split('T')[0];
    var selectedEvent = null;

    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'agendaWeek,agendaDay'
        },
        defaultView: 'agendaWeek',
        defaultDate: today,
        firstDay: 1,
        weekends: weekends,
        minTime: minTime,
        maxTime: maxTime,
        allDaySlot: false,
        slotDuration: minInterval,
        events: function(start, end, timezone, callback) {
            $.ajax({
                url: '/index.php?fc=module&module=pickupscheduler&controller=gettimeslots',
                dataType: 'json',
                success: function(data) {
                    var events = [];
                    $(data).each(function() {
                        events.push({
                            title: 'Disponible',
                            start: $(this).attr('date') + 'T' + $(this).attr('start_time'),
                            end: $(this).attr('date') + 'T' + $(this).attr('end_time'),
                            id: $(this).attr('id'),
                            color: '#4f2170',
                            textColor: '#FFF'
                        });
                    });
                    callback(events);
                }
            });
        },
        eventRender: function(event, eventElement) {
            if (event.id == autoSelectEventId) {
                eventElement.addClass('event-selected');
                selectedEvent = eventElement;
                showInfoTimeSlotSelected(event, autoSelectEventExpires);
            }
        },
        selectable: false,
        selectHelper: false,
        locale: 'es',
        timeFormat: 'H:mm',
        slotLabelFormat: 'H:mm',
        eventClick: function(event) {
            if (selectedEvent) {
                $(selectedEvent).removeClass('event-selected');
            }
            selectedEvent = this;
            $(this).addClass('event-selected');

            $.post('/index.php?fc=module&module=pickupscheduler&controller=confirmtimeslot', {
                event_id: event.id,
                customer_id: customer_id
            }).done(function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    showInfoTimeSlotSelected(event, result.expires_at);
                } else {
                    alert('Esta franja horaria acaba de ser seleccionada por otro cliente.');
                }
            }).fail(function(error) {
                console.error('Error confirming event:', error);
            });
        },
    });
});

$(document).on('click', '#checkout-delivery-step .step-title', function() {
    $('#calendar').fullCalendar('next');
    $('#calendar').fullCalendar('prev');
});

function showInfoTimeSlotSelected(event, expires_at){
    console.log("Expires: ", expires_at);
    var startTime = event.start.format('HH:mm');
    var endTime = event.end.format('HH:mm');
    var dayOfWeek = event.start.format('dddd');
    var formattedDate = event.start.format('DD/MM/YYYY');

    $('#timeSelectedInfo').html('<strong>Horario de recogida seleccionado:</strong> ' + dayOfWeek + ' ' + formattedDate + ', de ' + startTime + ' a ' + endTime).css('display', 'block'); 
    $(".expires-info").css('display', 'block');
    $('html, body').animate({
        scrollTop: $('#timeSelectedInfo').offset().top
    }, 1000);
    $(".confirmSchedule").css('display', 'block');
    $(".confirmSchedule").on('click', function() {
        var expires = new Date(expires_at);
        if (expires > new Date()) {
            continueButton.click();
        }else{
            alert('El tiempo para confirmar la franja horaria seleccionada y finalizar tu compra ha expirado. Por favor, selecciona una nueva franja para continuar.');
            $('#timeSelectedInfo').css('display', 'none');
            $('.expires-info').css('display', 'none');
            $(".confirmSchedule").css('display', 'none');
            $('.event-selected').removeClass('event-selected');
        }
    });
}
