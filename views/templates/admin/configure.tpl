{**
 * 2026 Hugo BOHARD
 *}

<ul class="nav nav-tabs" id="retractplug-tabs" style="margin-bottom: 20px;">
    <li class="active"><a href="#tab-requests" data-toggle="tab"><i class="icon-list"></i> Demandes de rétractation</a></li>
    <li><a href="#tab-config" data-toggle="tab"><i class="icon-cogs"></i> Paramètres</a></li>
    <li><a href="#tab-logs" data-toggle="tab"><i class="icon-terminal"></i> Logs</a></li>
    <li><a href="#tab-debug" data-toggle="tab"><i class="icon-bug"></i> Debug / Tests</a></li>
</ul>

<div class="tab-content" style="background: transparent; border: none; padding: 0;">
    
    <div class="tab-pane active" id="tab-requests">
        </div>
    
    <div class="tab-pane" id="tab-config">
        {$form_html}
    </div>
    
    <div class="tab-pane" id="tab-logs">
        <div class="panel">
            <div class="panel-heading"><i class="icon-terminal"></i> Journal technique des requêtes</div>
            <textarea class="form-control" rows="15" style="font-family: monospace; background-color: #272822; color: #F8F8F2; padding: 15px;" readonly>{$log_content}</textarea>
        </div>
    </div>
    
    <div class="tab-pane" id="tab-debug">
        <div class="panel">
            <div class="panel-heading"><i class="icon-bug"></i> Outils de diagnostic</div>
            <p class="text-muted">Exécutez des tests manuels pour valider le comportement de la liaison avec Dolibarr.</p>
            <hr />
            
            <form action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" method="post">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="well text-center">
                            <h4>Ping Dolibarr</h4>
                            <p class="small text-muted">Vérifie si le serveur et la clé API répondent.</p>
                            <button type="submit" name="submitTestDolibarrApi" class="btn btn-info btn-block">
                                <i class="icon-refresh"></i> Tester la connexion
                            </button>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="well text-center">
                            <h4>Avoir fictif</h4>
                            <p class="small text-muted">Envoie un avoir de simulation sur Dolibarr.</p>
                            <button type="submit" name="submitCreateTestInvoice" class="btn btn-primary btn-block">
                                <i class="icon-file-text"></i> Créer un avoir de test
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="well text-center">
                            <h4>Vider les logs</h4>
                            <p class="small text-muted">Réinitialise le fichier de log local du module.</p>
                            <button type="submit" name="submitClearLogs" class="btn btn-warning btn-block">
                                <i class="icon-trash"></i> Purger le fichier
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
</div>