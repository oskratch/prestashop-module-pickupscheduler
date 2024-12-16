<form id="module_form_1" class="defaultForm form-horizontal pickupscheduler" action="index.php?controller=AdminModules&amp;configure=pickupscheduler&amp;token={$token}" method="post" enctype="multipart/form-data" novalidate="">
    <input type="hidden" name="schedule_config" value="1">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Configuración de los tramos horarios para la entrega'}
        </div>
        <div class="alert alert-info">Por cada día, se puede activar o desactivar la opción de recogida, establecer la hora de inicio y finalización, y configurar el intervalo de tiempo en minutos entre cada franja horaria disponible.</div>
        <div class="form-wrapper">
            {foreach from=$daysOfWeek item=day}
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s=$day|capitalize}
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            {assign var="active" value=$fields_value["PICKUP_SCHEDULER_"|cat:$day|upper|cat:"_ACTIVE"]}
                            <input type="radio" 
                                   name="PICKUP_SCHEDULER_{$day|upper}_ACTIVE" 
                                   id="PICKUP_SCHEDULER_{$day|upper}_ACTIVE_on" 
                                   value="1" 
                                   {if $active == 1}checked="checked"{/if} />
                            <label for="PICKUP_SCHEDULER_{$day|upper}_ACTIVE_on">
                                {l s='Sí'}
                            </label>
                            <input type="radio" 
                                   name="PICKUP_SCHEDULER_{$day|upper}_ACTIVE" 
                                   id="PICKUP_SCHEDULER_{$day|upper}_ACTIVE_off" 
                                   value="0" 
                                   {if $active == 0}checked="checked"{/if} />
                            <label for="PICKUP_SCHEDULER_{$day|upper}_ACTIVE_off">
                                {l s='No'}
                            </label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Hora de inicio'}
                    </label>
                    <div class="col-lg-9">
                        <input type="time" name="PICKUP_SCHEDULER_{$day|upper}_START_TIME" value="{$fields_value["PICKUP_SCHEDULER_"|cat:$day|upper|cat:"_START_TIME"]|date_format:"%H:%M"}" class="timepicker">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Hora de finalización'}
                    </label>
                    <div class="col-lg-9">
                        <input type="time" name="PICKUP_SCHEDULER_{$day|upper}_END_TIME" value="{$fields_value["PICKUP_SCHEDULER_"|cat:$day|upper|cat:"_END_TIME"]|date_format:"%H:%M"}" class="timepicker">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Intervalo en minutos'}
                    </label>
                    <div class="col-lg-9">
                        <input type="number" name="PICKUP_SCHEDULER_{$day|upper}_INTERVAL_MINUTES" value="{$fields_value["PICKUP_SCHEDULER_"|cat:$day|upper|cat:"_INTERVAL_MINUTES"]|default:10}">
                    </div>
                </div>
                <hr>
            {/foreach}
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitpickupscheduler" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Guardar'}
            </button>
        </div>
    </div>
</form>
<script type="text/javascript" src="{$module_dir}assets/js/back/time-slot-form.js"></script>