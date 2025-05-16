<div class="panel col-md-12">
    <div class="panel-heading">
        Recogidas en tienda			
    </div>    
    <div class="table-responsive-row clearfix">
        {if $reservations|@count > 0}
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Fecha y hora de recogida'}</th>
                        <th>{l s='Nombre'}</th>
                        <th>{l s='Apellidos'}</th>
                        <th>{l s='Número de pedido'}</th>
                        <th>{l s='Estado'}</th>
                        <th>{l s='Acciones'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$reservations item=reservation}
                        <tr data-reservation-id="{$reservation.time_slot_id}">
                            <td><strong>{$reservation.date|date_format:'%d/%m/%Y'} {l s='de'} {$reservation.start_time|date_format:'%H:%M'} {l s='a'} {$reservation.end_time|date_format:'%H:%M'}</strong></td>
                            <td>{$reservation.firstname}</td>
                            <td>{$reservation.lastname}</td>
                            <td>{$reservation.order_id}</td>
                            <td>{$reservation.state_name}</td>
                            <td>
                                <a href="{$reservation.link_order}"><i class="icon-edit"></i></a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        {else}        
            <div class="alert alert-success">
                {l s='No hay ninguna recogida prevista'}
            </div>
        {/if}
    </div>
</div>