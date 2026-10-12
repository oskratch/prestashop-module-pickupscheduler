var pickupSchedulerCarrierId = String(window.pickupSchedulerCarrierId);
var weekends = window.weekends === '1';
var minTime = window.minTime;
var maxTime = window.maxTime;
var minInterval = window.minInterval;
var getTimeSlotsUrl = window.getTimeSlotsUrl;
var confirmTimeSlotUrl = window.confirmTimeSlotUrl;
var pickupSchedulerToken = window.pickupSchedulerToken;

// The slot currently held by the customer and when the hold ends (local timestamp in ms).
var selectedEventId = window.autoSelectEventId || null;
var selectedEventDate = window.autoSelectEventDate || null;
var selectedExpiresAt = window.autoSelectEventExpiresIn ? Date.now() + window.autoSelectEventExpiresIn * 1000 : null;

// Selectors shared by the Classic and Hummingbird checkouts.
var deliveryRadioSelector = 'input[type="radio"][name^="delivery_option"]';

function continueButton() {
    return $('form#js-delivery button[name="confirmDeliveryOption"]');
}

function isPickupCarrier(radio) {
    return $(radio).val().split(',')[0] === pickupSchedulerCarrierId;
}

function togglePickupCalendar(show) {
    var hiddenDiv = $('#hiddenDiv');
    if (show) {
        hiddenDiv.show();
        continueButton().hide();
        $('#calendar').fullCalendar('render');
    } else {
        hiddenDiv.hide();
        continueButton().show();
    }
}

$(function() {
    // Delegated, so it keeps working if the theme re-renders the delivery step.
    $(document).on('change', deliveryRadioSelector, function() {
        togglePickupCalendar(isPickupCarrier(this));
    });

    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'agendaWeek,agendaDay'
        },
        defaultView: 'agendaWeek',
        defaultDate: selectedEventDate || moment().format('YYYY-MM-DD'),
        firstDay: 1,
        weekends: weekends,
        minTime: minTime,
        maxTime: maxTime,
        allDaySlot: false,
        slotDuration: minInterval,
        events: function(start, end, timezone, callback) {
            $.ajax({
                url: getTimeSlotsUrl,
                dataType: 'json',
                cache: false,
                success: function(data) {
                    var events = [];
                    $.each(data, function(i, slot) {
                        events.push({
                            title: 'Disponible',
                            start: slot.date + 'T' + slot.start_time,
                            end: slot.date + 'T' + slot.end_time,
                            id: slot.id,
                            color: '#4f2170',
                            textColor: '#FFF'
                        });
                    });
                    callback(events);
                }
            });
        },
        eventRender: function(event, eventElement) {
            if (selectedEventId && event.id == selectedEventId) {
                eventElement.addClass('event-selected');
            }
        },
        eventAfterAllRender: function(view) {
            if (!selectedEventId) {
                return;
            }
            var events = $('#calendar').fullCalendar('clientEvents', selectedEventId);
            if (events.length) {
                showInfoTimeSlotSelected(events[0], false);
            }
        },
        selectable: false,
        selectHelper: false,
        locale: 'es',
        timeFormat: 'H:mm',
        slotLabelFormat: 'H:mm',
        eventClick: function(event) {
            $.post(confirmTimeSlotUrl, {
                event_id: event.id,
                token: pickupSchedulerToken
            }, null, 'json').done(function(result) {
                if (result.success) {
                    selectedEventId = event.id;
                    selectedExpiresAt = Date.now() + result.expires_in * 1000;
                    $('#calendar .event-selected').removeClass('event-selected');
                    $('#calendar').fullCalendar('rerenderEvents');
                    showInfoTimeSlotSelected(event, true);
                } else {
                    alert('Esta franja horaria acaba de ser seleccionada por otro cliente.');
                    $('#calendar').fullCalendar('refetchEvents');
                }
            }).fail(function(error) {
                console.error('Error confirming event:', error);
            });
        },
    });

    var checked = $(deliveryRadioSelector + ':checked');
    if (checked.length && isPickupCarrier(checked[0])) {
        togglePickupCalendar(true);
    }

    // Bound once: reads the current selection instead of the one captured when a slot was clicked.
    $(document).on('click', '.confirmSchedule', function() {
        if (selectedEventId && selectedExpiresAt && selectedExpiresAt > Date.now()) {
            continueButton().click();
        } else {
            alert('El tiempo para confirmar la franja horaria seleccionada y finalizar tu compra ha expirado. Por favor, selecciona una nueva franja para continuar.');
            clearSelection();
            $('#calendar').fullCalendar('refetchEvents');
        }
    });
});

$(document).on('click', '#checkout-delivery-step .step-title, #checkout-delivery-step .js-step-title', function() {
    $('#calendar').fullCalendar('render');
});

function clearSelection() {
    selectedEventId = null;
    selectedExpiresAt = null;
    $('#timeSelectedInfo').css('display', 'none');
    $('.expires-info').css('display', 'none');
    $('.confirmSchedule').css('display', 'none');
    $('#calendar .event-selected').removeClass('event-selected');
}

function showInfoTimeSlotSelected(event, scroll) {
    var startTime = event.start.format('HH:mm');
    var endTime = event.end.format('HH:mm');
    var dayOfWeek = event.start.format('dddd');
    var formattedDate = event.start.format('DD/MM/YYYY');

    $('#timeSelectedInfo').html('<strong>Horario de recogida seleccionado:</strong> ' + dayOfWeek + ' ' + formattedDate + ', de ' + startTime + ' a ' + endTime).css('display', 'block');
    $('.expires-info').css('display', 'block');
    $('.confirmSchedule').css('display', 'block');

    if (scroll) {
        $('html, body').animate({
            scrollTop: $('#timeSelectedInfo').offset().top
        }, 1000);
    }
}
