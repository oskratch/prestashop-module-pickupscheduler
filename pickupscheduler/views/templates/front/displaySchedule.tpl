<script type="text/javascript">
    window.pickupSchedulerCarrierId = '{$pickup_scheduler_carrier_id|intval}';

    window.weekends = '{$weekends|intval}';

    window.minTime = '{$minTime|escape:'javascript':'UTF-8'}'.slice(0, 5) || '00:00';
    window.maxTime = '{$maxTime|escape:'javascript':'UTF-8'}'.slice(0, 5) || '24:00';

    window.formatMinutes = function(minutes) {
        var hours = Math.floor(minutes / 60);
        var mins = minutes % 60;
        return (hours < 10 ? '0' : '') + hours + ':' + (mins < 10 ? '0' : '') + mins;
    };
    window.minInterval = window.formatMinutes({$minInterval|intval} || 30);

    window.getTimeSlotsUrl = '{$get_time_slots_url|escape:'javascript':'UTF-8'}';
    window.confirmTimeSlotUrl = '{$confirm_time_slot_url|escape:'javascript':'UTF-8'}';
    window.pickupSchedulerToken = '{$pickup_scheduler_token|escape:'javascript':'UTF-8'}';

    {if $calendar_event_id_selected}
    window.autoSelectEventId = {$calendar_event_id_selected|intval};
    window.autoSelectEventDate = '{$calendar_event_date_selected|escape:'javascript':'UTF-8'}';
    window.autoSelectEventExpiresIn = {$calendar_event_selected_expires_in|intval};
    {/if}
</script>

<div id="hiddenDiv" style="display: none;">
    <label for="calendar">{l s='Selecciona un tramo horario para la recogida de tu pedido.' mod='pickupscheduler'}</label>
    <p>{l s='Ten en cuenta que los tramos horarios estarán disponibles a partir de %s necesarios para preparar tu pedido.' sprintf=[$pickup_scheduler_preparation_days] mod='pickupscheduler'}</p>
    <div id="calendar"></div>
    <div id="timeSelectedInfo"></div>
    <div class="alert alert-info expires-info">{l s='Tienes %s minutos para completar tu compra. Pasado este tiempo, la franja horaria seleccionada se liberará y podrá ser elegida por otro cliente.' sprintf=[$pickup_scheduler_reservation_minutes] mod='pickupscheduler'}</div>
    <button type="button" class="confirmSchedule btn btn-primary" value="1" style="display: none;">
        {l s='Confirmar' mod='pickupscheduler'}
    </button>
</div>
