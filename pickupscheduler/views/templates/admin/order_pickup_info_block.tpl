<div id="pickupCard" class="pickup card">
    <div class="card-header">
        <h3 class="card-header-title">
            {l s='Día y hora de la recogida' mod='pickupscheduler'}
        </h3>
    </div>

    <div class="card-body">
        <div id="pickupInfo" class="info-block">
            {if $reservation}
                <p class="mb-1">
                    <strong>{l s='Fecha' mod='pickupscheduler'}:</strong> {$reservation.date|date_format:"%d-%m-%Y"}
                </p>
                <p class="mb-0">
                    <strong>{l s='Franja horaria' mod='pickupscheduler'}:</strong> {$reservation.start_time|escape:'html':'UTF-8'} - {$reservation.end_time|escape:'html':'UTF-8'}
                </p>
            {else}
                <p class="mb-0 text-muted">{l s='Este pedido no tiene una franja de recogida asignada.' mod='pickupscheduler'}</p>
            {/if}
        </div>
    </div>
</div>
