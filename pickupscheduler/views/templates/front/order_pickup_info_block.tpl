<div class="box">
    <h3>{l s='Día y hora de la recogida'}</h3>
    <ul>
        <li><strong>{l s='Fecha'}:</strong> {if isset($reservation['date'])}{$reservation['date']|date_format:"%d-%m-%Y"}{else}N/A{/if}</li>
        <li><strong>{l s='Franja horaria'}:</strong> {$reservation['start_time']} - {$reservation['end_time']}</li>
    </ul>
</div>