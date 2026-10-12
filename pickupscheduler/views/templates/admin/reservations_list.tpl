<div class="panel col-md-12">
    <div class="panel-heading">
        <i class="icon-calendar"></i> {l s='Recogidas en tienda' mod='pickupscheduler'}
        <span class="badge">{$reservations_total|intval}</span>
        <span class="panel-heading-action">
            <span class="btn-group">
                <a href="{$link_upcoming|escape:'html':'UTF-8'}" class="btn btn-default{if !$show_all} active{/if}">{l s='Próximas' mod='pickupscheduler'}</a>
                <a href="{$link_all|escape:'html':'UTF-8'}" class="btn btn-default{if $show_all} active{/if}">{l s='Todas' mod='pickupscheduler'}</a>
            </span>
        </span>
    </div>
    <div class="table-responsive-row clearfix">
        {if $reservations}
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Fecha y hora de recogida' mod='pickupscheduler'}</th>
                        <th>{l s='Cliente' mod='pickupscheduler'}</th>
                        <th>{l s='Pedido' mod='pickupscheduler'}</th>
                        <th>{l s='Estado' mod='pickupscheduler'}</th>
                        <th class="text-right">{l s='Acciones' mod='pickupscheduler'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$reservations item=reservation}
                        <tr data-reservation-id="{$reservation.time_slot_id|intval}">
                            <td><strong>{$reservation.date|date_format:'%d/%m/%Y'}</strong> {l s='de' mod='pickupscheduler'} {$reservation.start_time|escape:'html':'UTF-8'} {l s='a' mod='pickupscheduler'} {$reservation.end_time|escape:'html':'UTF-8'}</td>
                            <td>
                                {$reservation.firstname|escape:'html':'UTF-8'} {$reservation.lastname|escape:'html':'UTF-8'}<br>
                                <small class="text-muted">{$reservation.email|escape:'html':'UTF-8'}</small>
                            </td>
                            <td>#{$reservation.order_id|intval} <small class="text-muted">{$reservation.reference|escape:'html':'UTF-8'}</small></td>
                            <td>
                                {if $reservation.state_name}
                                    <span class="label" style="background-color: {$reservation.state_color|escape:'html':'UTF-8'}; color: #fff;">{$reservation.state_name|escape:'html':'UTF-8'}</span>
                                {/if}
                            </td>
                            <td class="text-right">
                                <a href="{$reservation.link_order|escape:'html':'UTF-8'}" class="btn btn-default btn-sm"><i class="icon-search-plus"></i> {l s='Ver pedido' mod='pickupscheduler'}</a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}
            <div class="alert alert-info">
                {if $show_all}
                    {l s='Todavía no hay ninguna recogida.' mod='pickupscheduler'}
                {else}
                    {l s='No hay ninguna recogida prevista.' mod='pickupscheduler'}
                {/if}
            </div>
        {/if}
    </div>
</div>
