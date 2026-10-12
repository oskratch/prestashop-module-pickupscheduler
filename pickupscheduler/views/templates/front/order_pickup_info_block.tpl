<div class="box">
    <h3>{l s='Día y hora de la recogida' mod='pickupscheduler'}</h3>
    {if $reservation}
        <ul>
            <li><strong>{l s='Fecha' mod='pickupscheduler'}:</strong> {$reservation.date|date_format:"%d-%m-%Y"}</li>
            <li><strong>{l s='Franja horaria' mod='pickupscheduler'}:</strong> {$reservation.start_time|escape:'html':'UTF-8'} - {$reservation.end_time|escape:'html':'UTF-8'}</li>
        </ul>
    {else}
        <p>{l s='Este pedido no tiene una franja de recogida asignada.' mod='pickupscheduler'}</p>
    {/if}
</div>
