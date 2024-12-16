<script type="text/javascript">
    window.pickupSchedulerCarrierId = '{$pickup_scheduler_carrier_id}';

    window.weekends = '{$weekends}';

    window.minTime = '{$minTime}'.slice(0, -3);
    window.maxTime = '{$maxTime}'.slice(0, -3);

    window.formatMinutes = function(minutes) {
        var hours = Math.floor(minutes / 60);
        var mins = minutes % 60;
        return (hours < 10 ? '0' : '') + hours + ':' + (mins < 10 ? '0' : '') + mins;
    };
    window.minInterval = window.formatMinutes('{$minInterval}');
    
    window.customer_id = {$customer_id};

    {if $calendar_event_id_selected}
    window.autoSelectEventId = {$calendar_event_id_selected};
    window.autoSelectEventDate = '{$calendar_event_date_selected}';
    window.autoSelectEventExpires = '{$calendar_event_selected_expires}';
    {/if}
</script>

<div id="hiddenDiv" style="display: none;">
    <label for="calendar">{l s='Selecciona un tramo horario para la recogida de tu pedido.'}</label>
    <p>{l s='Ten en cuenta que los tramos horarios estarán disponibles a partir de %s necesarios para preparar tu pedido.' sprintf=[$pickup_scheduler_preparation_days]}</p>
    <div id="calendar"></div>
    <div id="timeSelectedInfo"></div>
    <div class="alert alert-info expires-info">{l s='Tienes %s minutos para completar tu compra. Pasado este tiempo, la franja horaria seleccionada se liberará y podrá ser elegida por otro cliente.' sprintf=[$pickup_scheduler_reservation_minutes]}</div>
    <button type="button" class="confirmSchedule btn btn-primary" value="1" style="display: none;">
        Confirmar
    </button>
</div>
