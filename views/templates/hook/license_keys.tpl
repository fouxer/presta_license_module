
<h3>LICENSE KEYS:</h3>

<div class="table_block">
    <table class="detail_step_by_step table table-bordered">
        <thead>
            <tr>
                <th class="first_item">Product</th>
                <th class="last_item">License</th>
                <th class="last_item">Image</th>
            </tr>
        </thead>
        <tbody>

        {foreach from=$licenseKeys item=key name="licenseKeys"}
            <tr class="{if $smarty.foreach.licenseKeys.first}first_item{elseif $smarty.foreach.licenseKeys.last}last_item{/if} {if $smarty.foreach.licenseKeys.index % 2}alternate_item{else}item{/if}">
                <td>#{$key.id_product}. {$key.product}</td>
                <td>{$key.key}</td>
                <td>{$key.htmlImage}</td>
            </tr>
        {/foreach}

        </tbody>
    </table>
</div>