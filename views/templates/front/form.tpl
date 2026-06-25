{extends file='page.tpl'}

{block name='page_content'}
    <div class="card card-block">
        <h1 class="h1 page-title">
            <i class="material-icons">assignment_return</i> 
            {l s='Demande de rétractation pour la commande #' mod='retractplug'}{$order->reference}
        </h1>

        {if isset($errors) && $errors}
            <div class="alert alert-danger">
                <ul>
                    {foreach from=$errors item=error}
                        <li>{$error}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        <form action="{$smarty.server.REQUEST_URI}" method="post" id="retractplug-form">
            <p class="text-muted">
                {l s='Sélectionnez les articles que vous souhaitez retourner ainsi que leur quantité :' mod='retractplug'}
            </p>

            <table class="table table-striped table-bordered dynamic-table">
                <thead>
                    <tr>
                        <th width="5%">{l s='Sélection' mod='retractplug'}</th>
                        <th>{l s='Produit' mod='retractplug'}</th>
                        <th width="20%">{l s='Quantité achetée' mod='retractplug'}</th>
                        <th width="20%">{l s='Quantité à retourner' mod='retractplug'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$products item=product}
                        <tr>
                            <td class="text-center align-middle">
                                <input type="checkbox" name="returned_products[]" value="{$product.id_order_detail}" class="retract-check">
                            </td>
                            <td class="align-middle">
                                <strong>{$product.product_name}</strong><br>
                                <small class="text-muted">{l s='Référence :' mod='retractplug'} {$product.product_reference}</small>
                            </td>
                            <td class="text-center align-middle">{$product.product_quantity}</td>
                            <td class="align-middle">
                                <input type="number" 
                                       name="returned_quantities[{$product.id_order_detail}]" 
                                       value="{$product.product_quantity}" 
                                       min="1" 
                                       max="{$product.product_quantity}" 
                                       class="form-control retract-qty" 
                                       disabled>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>

            <div class="form-group mt-4">
                <label for="retract_reason"><strong>{l s='Raison du retour / Motif' mod='retractplug'}</strong></label>
                <textarea name="retract_reason" id="retract_reason" rows="4" class="form-control" placeholder="{l s='Ex: L\'article ne correspond pas à mes attentes, erreur de taille...' mod='retractplug'}" required></textarea>
            </div>

            <div class="form-footer text-right mt-4">
                <a href="{$link->getPageLink('history')}" class="btn btn-secondary">{l s='Annuler' mod='retractplug'}</a>
                <button type="submit" name="submitRetraction" class="btn btn-primary">
                    <i class="material-icons">send</i> {l s='Valider et générer le retour' mod='retractplug'}
                </button>
            </div>
        </form>
    </div>

    {* Petit script JS d'UI pour activer/désactiver le champ quantité selon la case cochée *}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.retract-check');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const qtyInput = this.closest('tr').querySelector('.retract-qty');
                    qtyInput.disabled = !this.checked;
                });
            });
        });
    </script>
{/block}