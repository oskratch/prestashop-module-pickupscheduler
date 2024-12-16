<div id="pickupCard" class="pickup card">
    <div class="card-header">
        <h3 class="card-header-title">
            {l s='Día y hora de la recogida'}
        </h3>
    </div>

  <div class="card-body">
    <div id="pickupInfo" class="info-block">
        <div class="row mt-3">
            <div class="col-xxl-12">
                <p class="mb-1">
                    <strong>{l s='Fecha'}:</strong> {if isset($reservation['date'])}{$reservation['date']|date_format:"%d-%m-%Y"}{else}N/A{/if}
                </p>
                <p>
                    <strong>{l s='Franja horaria'}:</strong> {$reservation['start_time']} - {$reservation['end_time']}
                </p>
            </div>
        </div>
    </div>
</div>