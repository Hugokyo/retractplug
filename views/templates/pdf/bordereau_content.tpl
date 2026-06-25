{*
 * 2026 Hugo BOHARD
 *}
<style>
    /* Structure & Typographie globale */
    table { width: 100%; border-collapse: collapse; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .uppercase { text-transform: uppercase; }
    
    /* En-tête très aéré */
    .header-table td { border: none; padding-bottom: 40px; }
    .shop-logo { max-height: 65px; }
    .doc-title { font-size: 24px; font-weight: bold; color: #1e293b; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
    .doc-meta { font-size: 11px; color: #64748b; line-height: 1.5; margin-top: 8px; }

    /* Blocs d'informations (Deux Colonnes) - Marges augmentées */
    .info-table { margin-top: 40px; }
    .info-th { background-color: #f1f5f9; font-weight: bold; font-size: 12px; border: 1px solid #cbd5e1; padding: 12px; color: #1e293b; text-transform: uppercase; }
    .info-td { border: 1px solid #cbd5e1; padding: 18px; font-size: 11px; line-height: 1.7; color: #334155; vertical-align: top; }
    .reason-box { font-style: italic; color: #475569; background-color: #f8fafc; padding: 10px; border-left: 3px solid #94a3b8; margin-top: 8px; }

    /* Tableau des produits - Plus grand espacement au-dessus */
    .product-table { margin-top: 55px; }
    .product-th { background-color: #1e293b; color: #ffffff; font-weight: bold; font-size: 12px; padding: 12px; border: 1px solid #1e293b; text-transform: uppercase; }
    .product-td { border: 1px solid #e2e8f0; padding: 16px 12px; font-size: 11px; color: #334155; }
    .product-id { font-size: 9px; color: #94a3b8; margin-top: 5px; }
    .qty-badge { font-size: 15px; color: #1e293b; font-weight: bold; }
    .control-box { color: #64748b; font-size: 11px; font-weight: bold; }

    /* Zone d'instructions & adresse - Poussée vers le bas */
    .instruction-table { margin-top: 60px; background-color: #f0fdf4; border: 1px solid #bbf7d0; }
    .instruction-td { padding: 22px; font-size: 11px; line-height: 1.7; color: #166534; vertical-align: top; }
    .address-td { padding: 22px; font-size: 11px; line-height: 1.7; color: #14532d; border-left: 1px dashed #bbf7d0; vertical-align: top; }
</style>

<!-- Section de l'en-tête de Marque -->
<table class="header-table">
    <tr>
        <td style="width: 45%; text-align: left; vertical-align: middle;">
            {if $logo}
                <img src="{$logo}" class="shop-logo" />
            {else}
                <span style="font-size: 24px; font-weight: bold; color: #1e293b;">{$shop_name}</span>
            {/if}
        </td>
        <td style="width: 55%; text-align: right; vertical-align: middle;">
            <div class="doc-title">{l s='Bordereau de Retour' mod='retractplug'}</div>
            <div class="doc-meta">
                {l s='Demande #%1$d' sprintf=['%1$d' => $request->id] mod='retractplug'}<br/>
                {l s='Généré le %s' sprintf=[$request->date_add|date_format:"%d/%m/%Y à %H:%M"] mod='retractplug'}
            </div>
        </td>
    </tr>
</table>

<br/><br/>

<!-- Bloc d'informations d'identification -->
<table class="info-table">
    <tr>
        <th class="info-th" style="width: 50%;">{l s='Détails de la commande' mod='retractplug'}</th>
        <th class="info-th" style="width: 50%;">{l s='Informations de l\'expéditeur' mod='retractplug'}</th>
    </tr>
    <tr>
        <td class="info-td">
            <span class="bold">{l s='Référence Commande :' mod='retractplug'}</span> #{$order->reference}<br/>
            <span class="bold">{l s='Date d\'achat :' mod='retractplug'}</span> {$order->date_add|date_format:"%d/%m/%Y"}<br/>
            <div style="margin-top: 10px;"><span class="bold">{l s='Motif indiqué :' mod='retractplug'}</span></div>
            <div class="reason-box">"{$request->reason|escape:'html':'UTF-8'}"</div>
        </td>
        <td class="info-td">
            <span class="bold">{l s='Nom du client :' mod='retractplug'}</span> {$customer->firstname} {$customer->lastname}<br/>
            <span class="bold">{l s='Adresse e-mail :' mod='retractplug'}</span> {$customer->email}<br/>
            <span class="bold">{l s='Numéro Client :' mod='retractplug'}</span> #{$customer->id}
        </td>
    </tr>
</table>

<br/><br/>

<!-- Tableau des articles retournés -->
<table class="product-table">
    <thead>
        <tr>
            <th class="product-th" style="width: 55%;">{l s='Désignation du produit' mod='retractplug'}</th>
            <th class="product-th text-center" style="width: 22%;">{l s='Qté retournée' mod='retractplug'}</th>
            <th class="product-th text-center" style="width: 23%;">{l s='Contrôle dépôt' mod='retractplug'}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$returned_products item=product}
        <tr>
            <td class="product-td">
                <span class="bold">{$product.name}</span>
                <div class="product-id">{l s='ID Produit : #%d' sprintf=[$product.id_product] mod='retractplug'}</div>
            </td>
            <td class="product-td text-center qty-badge">
                {$product.quantity}
            </td>
            <td class="product-td text-center control-box">
                [ &nbsp; ] {l s='Conforme' mod='retractplug'}
            </td>
        </tr>
        {/foreach}
    </tbody>
</table>

<br/><br/>

<!-- Consignes logistiques et adresse de l'atelier -->
<table class="instruction-table">
    <tr>
        <td class="instruction-td" style="width: 62%;">
            <span class="bold uppercase" style="font-size: 12px; display: block; margin-bottom: 8px;">{l s='Instructions importantes :' mod='retractplug'}</span><br/>
            1. {l s='Placez obligatoirement ce document imprimé [1]à l\'intérieur[/1] de votre colis.' tags=['<strong>'] mod='retractplug'}<br/>
            2. {l s='Protégez et emballez l\'article dans son conditionnement d\'origine.' mod='retractplug'}<br/>
            3. {l s='Apposez votre bordereau de transport affranchi sur l\'extérieur de la boîte.' mod='retractplug'}<br/>
            4. {l s='Remettez le colis au transporteur sélectionné.' mod='retractplug'}
        </td>
        <td class="address-td" style="width: 38%;">
            <span class="bold uppercase" style="font-size: 12px; display: block; margin-bottom: 8px;">{l s='Adresse de renvoi :' mod='retractplug'}</span>
            <span class="bold uppercase" style="letter-spacing: 0.3px; color: #166534; font-size: 10px; line-height: 1.5;">
                {$shop_name}<br/>
                {$shop_address}<br/>
                {$shop_postcode} {$shop_city}<br/>
                FRANCE
            </span>
        </td>
    </tr>
</table>