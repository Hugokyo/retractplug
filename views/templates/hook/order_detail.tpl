{**
 * 2026 Hugo BOHARD
 *}

<div id="retractplug-order-block" class="box values-block card memory-card mt-3">
    <div class="card-block">
        <h3 class="card-title"><i class="material-icons">gavel</i> {l s='Droit de rétractation' mod='retractplug'}</h3>
        
        {if $retract_already_done}
            <div class="alert alert-info">
                {l s='Une demande de rétractation a déjà été enregistrée pour cette commande et est en cours de traitement.' mod='retractplug'}
            </div>
        {else}
            <p class="text-muted">
                {l s='Conformément à la législation, vous disposez de 14 jours après réception de votre colis pour changer d\'avis.' mod='retractplug'} 
                <span class="font-weight-bold text-warning">({l s='Il vous reste %d jours' sprintf=[$days_left] mod='retractplug'})</span>.
            </p>
            <a href="{$retract_url|escape:'html':'UTF-8'}" class="btn btn-primary spec-btn-retract">
                <i class="material-icons">assignment_return</i> {l s='Demander une rétractation' mod='retractplug'}
            </a>
        {/if}
    </div>
</div>