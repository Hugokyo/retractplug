{*
 * 2026 Hugo BOHARD
 *}
<div class="box info-block" style="margin-top: 20px; background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 4px;">
    <div class="row" style="align-items: center;">
        <div class="col-md-8" style="color: #166534; line-height: 1.5;">
            <h3 style="color: #14532d; font-weight: bold; margin-top: 0; font-size: 1.1rem; display: flex; align-items: center;">
                <i class="material-icons" style="margin-right: 8px; color: #166534; vertical-align: middle;">assignment_return</i>
                {l s='Une demande de rétractation est en cours' mod='retractplug'}
            </h3>
            <p style="margin-bottom: 0; font-size: 0.9rem;">
                {l s='Vous avez initié une procédure de retour pour cette commande. Vous pouvez télécharger à nouveau votre document logistique pour l\'insérer dans votre colis.' mod='retractplug'}
            </p>
        </div>
        <div class="col-md-4 text-md-right text-xs-center" style="margin-top: 10px;">
            <a href="{$download_pdf_link}" class="btn btn-primary spec-pdf-btn" target="_blank" style="background-color: #166534; border-color: #14532d; display: inline-flex; align-items: center; padding: 10px 15px; color: #fff;">
                <i class="material-icons" style="margin-right: 5px; font-size: 18px;">get_app</i>
                {l s='Télécharger mon bordereau (PDF)' mod='retractplug'}
            </a>
        </div>
    </div>
</div>