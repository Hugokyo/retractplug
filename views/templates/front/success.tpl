{**
 * 2026 Hugo BOHARD
 *}
{extends file='page.tpl'}

{block name='page_content'}
    <div class="card card-block retractplug-success-panel">
        <div class="text-center" style="margin-bottom: 25px;">
            <h2 class="h1 text-success"><i class="material-icons">check_circle</i> {l s='Demande enregistrée !' mod='retractplug'}</h2>
            <p class="text-muted">
                {l s='Votre demande de rétractation pour la commande [1]#%s[/1] a bien été prise en compte.' sprintf=['%s' => $order->reference, '[1]' => '<strong>', '[/1]' => '</strong>'] mod='retractplug'}
            </p>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h3><i class="material-icons">arrow_forward</i> {l s='Comment renvoyer votre colis ?' mod='retractplug'}</h3>
                <hr>
                
                <ol style="padding-left: 20px; line-height: 2em;">
                    <li>
                        <strong>{l s='Préparez votre colis :' mod='retractplug'}</strong> 
                        {l s='Placez les produits que vous souhaitez retourner dans leur emballage d\'origine, intacts et complets.' mod='retractplug'}
                    </li>
                    <li>
                        <strong>{l s='Glissez le bordereau :' mod='retractplug'}</strong> 
                        {l s='Téléchargez et imprimez le document ci-dessous, puis glissez-le à l\'intérieur du colis (indispensable pour que nos équipes identifient votre retour).' mod='retractplug'}
                    </li>
                    <li>
                        <strong>{l s='Affranchissez votre envoi :' mod='retractplug'}</strong> 
                        {l s='Rendez-vous sur le site de La Poste (Colissimo), Mondial Relay ou chez le transporteur de votre choix pour acheter votre étiquette de transport.' mod='retractplug'}
                    </li>
                    <li>
                        <strong>{l s='Expédiez à l\'adresse suivante :' mod='retractplug'}</strong>
                    </li>
                </ol>

                <div class="alert alert-info" style="margin: 20px 0; padding: 15px; font-size: 1.1em;">
                    <i class="material-icons">location_on</i> <strong>{l s='Adresse de retour :' mod='retractplug'}</strong><br>
                    <span style="text-transform: uppercase; letter-spacing: 0.5px;">
                        {$shop_name}<br>
                        {$shop_address}<br>
                        {$shop_postcode} {$shop_city}<br>
                        {l s='FRANCE' mod='retractplug'}
                    </span>
                </div>
            </div>
        </div>

        <hr>

        <div class="text-center style-buttons" style="margin-top: 20px;">
            <a href="{$link->getModuleLink('retractplug', 'pdf', ['id_request' => $id_request])}" class="btn btn-primary" target="_blank">
                <i class="material-icons">get_app</i> {l s='Télécharger mon bordereau de retour (PDF)' mod='retractplug'}
            </a>
            
            <a href="{$link->getPageLink('history')}" class="btn btn-link">
                {l s='Retour à mes commandes' mod='retractplug'}
            </a>
        </div>
    </div>
{/block}