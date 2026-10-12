<div class="psch"
     data-i18n-slot="{l s='franja' mod='pickupscheduler'}"
     data-i18n-slots="{l s='franjas' mod='pickupscheduler'}"
     data-i18n-invalid="{l s='La hora de cierre debe ser posterior a la de apertura' mod='pickupscheduler'}"
     data-i18n-off="{l s='Sin recogida este día' mod='pickupscheduler'}">

    <div class="psch-hero">
        <div class="psch-hero__icon"><i class="material-icons">storefront</i></div>
        <div class="psch-hero__text">
            <h2>{l s='Pickup Scheduler' mod='pickupscheduler'}</h2>
            <p>{l s='Tus clientes eligen el día y la hora en que recogerán su pedido en la tienda.' mod='pickupscheduler'}</p>
        </div>
        <span class="psch-badge">v{$psch_version|escape:'html':'UTF-8'}</span>
    </div>

    {if !$psch_carrier_configured}
        <div class="psch-notice psch-notice--warn">
            <i class="material-icons">warning</i>
            <span>{l s='Selecciona el transportista de recogida en tienda: hasta entonces el calendario no se mostrará en el checkout.' mod='pickupscheduler'}</span>
        </div>
    {/if}
    {if $psch_stats.active_days == 0}
        <div class="psch-notice psch-notice--warn">
            <i class="material-icons">event_busy</i>
            <span>{l s='No hay ningún día con recogida activada. Activa al menos un día en el horario semanal.' mod='pickupscheduler'}</span>
        </div>
    {/if}

    <div class="psch-stats">
        <a class="psch-stat psch-stat--link" href="{$psch_pickups_link|escape:'html':'UTF-8'}">
            <span class="psch-stat__icon psch-stat__icon--blue"><i class="material-icons">shopping_bag</i></span>
            <span class="psch-stat__body">
                <span class="psch-stat__value">{$psch_stats.upcoming_pickups|intval}</span>
                <span class="psch-stat__label">{l s='Recogidas próximas' mod='pickupscheduler'}</span>
            </span>
            <i class="material-icons psch-stat__arrow">chevron_right</i>
        </a>
        <div class="psch-stat">
            <span class="psch-stat__icon psch-stat__icon--green"><i class="material-icons">date_range</i></span>
            <span class="psch-stat__body">
                <span class="psch-stat__value">{$psch_stats.active_days|intval}<small>/7</small></span>
                <span class="psch-stat__label">{l s='Días con recogida' mod='pickupscheduler'}</span>
            </span>
        </div>
        <div class="psch-stat">
            <span class="psch-stat__icon psch-stat__icon--purple"><i class="material-icons">schedule</i></span>
            <span class="psch-stat__body">
                <span class="psch-stat__value">{$psch_stats.free_slots|intval}</span>
                <span class="psch-stat__label">{l s='Franjas libres generadas' mod='pickupscheduler'}</span>
            </span>
        </div>
    </div>

    <form method="post" action="{$psch_form_action|escape:'html':'UTF-8'}" class="psch-form" novalidate>

        <section class="psch-card">
            <header class="psch-card__header">
                <span class="psch-card__icon"><i class="material-icons">tune</i></span>
                <div>
                    <h3>{l s='Ajustes generales' mod='pickupscheduler'}</h3>
                    <p>{l s='Cómo y cuándo pueden reservar los clientes.' mod='pickupscheduler'}</p>
                </div>
            </header>

            <div class="psch-fields">
                <div class="psch-field psch-field--wide">
                    <label for="psch-carrier">{l s='Transportista de recogida en tienda' mod='pickupscheduler'}</label>
                    <select id="psch-carrier" name="PICKUP_SCHEDULER_CARRIER_ID" class="psch-input">
                        <option value="0">{l s='Seleccionar transportista…' mod='pickupscheduler'}</option>
                        {foreach from=$psch_carriers item=carrier}
                            <option value="{$carrier.id_carrier|intval}" {if $carrier.id_carrier == $psch_settings.CARRIER_ID}selected{/if}>
                                {$carrier.name|escape:'html':'UTF-8'}{if !$carrier.active} ({l s='inactivo' mod='pickupscheduler'}){/if}
                            </option>
                        {/foreach}
                    </select>
                    <p class="psch-help">{l s='Cuando el cliente elija este transportista verá el calendario de franjas disponibles.' mod='pickupscheduler'}</p>
                </div>

                <div class="psch-field">
                    <label for="psch-preparation">{l s='Tiempo de preparación' mod='pickupscheduler'}</label>
                    <div class="psch-affix">
                        <input id="psch-preparation" type="number" min="0" step="1" name="PICKUP_SCHEDULER_PREPARATION_DAYS" value="{$psch_settings.PREPARATION_DAYS|intval}" class="psch-input">
                        <span>{l s='días' mod='pickupscheduler'}</span>
                    </div>
                    <p class="psch-help">{l s='Días mínimos antes de que un pedido se pueda recoger.' mod='pickupscheduler'}</p>
                </div>

                <div class="psch-field">
                    <label for="psch-reservation">{l s='Reserva temporal' mod='pickupscheduler'}</label>
                    <div class="psch-affix">
                        <input id="psch-reservation" type="number" min="1" step="1" name="PICKUP_SCHEDULER_RESERVATION_MINUTES" value="{$psch_settings.RESERVATION_MINUTES|intval}" class="psch-input">
                        <span>{l s='min' mod='pickupscheduler'}</span>
                    </div>
                    <p class="psch-help">{l s='Tiempo que se guarda la franja mientras el cliente termina la compra.' mod='pickupscheduler'}</p>
                </div>

                <div class="psch-field">
                    <label for="psch-available">{l s='Días con franjas disponibles' mod='pickupscheduler'}</label>
                    <div class="psch-affix">
                        <input id="psch-available" type="number" min="1" max="10" step="1" name="PICKUP_SCHEDULER_AVAILABLE_DAYS" value="{$psch_settings.AVAILABLE_DAYS|intval}" class="psch-input">
                        <span>{l s='días' mod='pickupscheduler'}</span>
                    </div>
                    <p class="psch-help">{l s='Cuántos días por delante se pueden reservar (máximo 10).' mod='pickupscheduler'}</p>
                </div>
            </div>
        </section>

        <section class="psch-card">
            <header class="psch-card__header">
                <span class="psch-card__icon"><i class="material-icons">calendar_month</i></span>
                <div>
                    <h3>{l s='Horario semanal' mod='pickupscheduler'}</h3>
                    <p>{l s='Activa los días con recogida y define su horario y la duración de cada franja.' mod='pickupscheduler'}</p>
                </div>
            </header>

            <div class="psch-week">
                {foreach from=$psch_days item=day}
                    <div class="psch-day{if !$day.active} is-off{/if}" data-psch-day>
                        <label class="psch-switch" title="{l s='Activar / desactivar' mod='pickupscheduler'}">
                            <input type="checkbox" name="{$day.key|escape:'html':'UTF-8'}_ACTIVE" value="1" {if $day.active}checked{/if} data-psch-toggle>
                            <span class="psch-switch__track"><span class="psch-switch__thumb"></span></span>
                        </label>

                        <div class="psch-day__name">
                            <strong>{$day.label|escape:'html':'UTF-8'}</strong>
                            <span class="psch-day__state psch-day__state--on">{l s='Abierto' mod='pickupscheduler'}</span>
                            <span class="psch-day__state psch-day__state--off">{l s='Sin recogida' mod='pickupscheduler'}</span>
                        </div>

                        <div class="psch-day__fields">
                            <label>
                                <span>{l s='Apertura' mod='pickupscheduler'}</span>
                                <input type="time" name="{$day.key|escape:'html':'UTF-8'}_START_TIME" value="{$day.start_time|escape:'html':'UTF-8'}" class="psch-input" data-psch-start>
                            </label>
                            <label>
                                <span>{l s='Cierre' mod='pickupscheduler'}</span>
                                <input type="time" name="{$day.key|escape:'html':'UTF-8'}_END_TIME" value="{$day.end_time|escape:'html':'UTF-8'}" class="psch-input" data-psch-end>
                            </label>
                            <label>
                                <span>{l s='Franja' mod='pickupscheduler'}</span>
                                <span class="psch-affix">
                                    <input type="number" min="4" step="1" name="{$day.key|escape:'html':'UTF-8'}_INTERVAL_MINUTES" value="{$day.interval_minutes|intval}" class="psch-input" data-psch-interval>
                                    <span>{l s='min' mod='pickupscheduler'}</span>
                                </span>
                            </label>
                        </div>

                        <div class="psch-day__preview">
                            <div class="psch-timeline"><span class="psch-timeline__range" data-psch-range></span></div>
                            <span class="psch-day__summary" data-psch-summary></span>
                        </div>
                    </div>
                {/foreach}
            </div>
        </section>

        <div class="psch-actions">
            <button type="submit" name="submitPickupSchedulerConfig" value="1" class="btn btn-primary psch-save">
                <i class="material-icons">save</i> {l s='Guardar configuración' mod='pickupscheduler'}
            </button>
        </div>
    </form>
</div>
