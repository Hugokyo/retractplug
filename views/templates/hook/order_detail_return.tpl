{**
 * 2026 Hugo BOHARD
 *}

<div id="retractplug-order-block" class="box values-block card memory-card mt-3">
    <div class="card-block">
        <h3 class="card-title"><i class="material-icons">gavel</i> {l s='Droit de rétractation' mod='retractplug'}</h3>
        
        {if $retract_already_done}
            <!-- Bloc d'alerte propre et moderne -->
            <div class="alert alert-success d-flex align-items-start" role="alert">
                <i class="material-icons text-success mr-2">check_circle</i>
                <div>
                    <h5 class="alert-heading font-weight-bold text-success mb-1">
                        {l s='Demande de rétractation enregistrée' mod='retractplug'}
                    </h5>
                    <p class="text-muted mb-0">
                        {l s='Votre demande a bien été prise en compte. Vous pouvez télécharger votre bordereau de retour à tout moment ci-dessous.' mod='retractplug'}
                    </p>
                </div>
            </div>

            <!-- Bouton de téléchargement centré et aéré -->
            {if isset($download_url) && $download_url}
                <div class="text-center mt-3">
                    <a href="{$download_url|escape:'html':'UTF-8'}" class="btn btn-secondary spec-btn-download" target="_blank">
                        <i class="material-icons">cloud_download</i> {l s='Télécharger mon bordereau de retour (PDF)' mod='retractplug'}
                    </a>
                </div>
            {/if}
        {else}
            <p class="text-muted">
                {l s='Conformément à la législation, vous disposez de 14 jours après réception de votre colis pour changer d\'avis.' mod='retractplug'} 
                {if isset($days_left)}
                    <span class="font-weight-bold text-warning">({l s='Il vous reste %d jours' sprintf=[$days_left] mod='retractplug'})</span>.
                {/if}
            </p>
            <a href="{$retract_url|escape:'html':'UTF-8'}" class="btn btn-primary spec-btn-retract">
                <i class="material-icons">assignment_return</i> {l s='Demander une rétractation' mod='retractplug'}
            </a>
        {/if}
    </div>
</div>