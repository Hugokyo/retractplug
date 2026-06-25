<?php
/**
 * 2026 Hugo BOHARD
 */

require_once dirname(__FILE__) . '/../../classes/RetractRequest.php';
require_once dirname(__FILE__) . '/../../src/Model/HTMLTemplateBordereauReturn.php';

class RetractPlugPdfModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        $id_request = (int)Tools::getValue('id_request');
        $retract_request = new RetractRequest($id_request);

        if (!Validate::isLoadedObject($retract_request) || $retract_request->id_customer != $this->context->customer->id) {
            die('Accès refusé ou document introuvable.');
        }

        $pdf_template = new \RetractPlug\Model\HTMLTemplateBordereauReturn($retract_request, $this->context->smarty);
        
        
        $pdf_generator = new PDFGenerator(false, 'P');
        
        $pdf_generator->setFontForLang($this->context->language->iso_code);
        
        $pdf_generator->createHeader($pdf_template->getHeader());
        $pdf_generator->createFooter($pdf_template->getFooter());
        $pdf_generator->createContent($pdf_template->getContent());
        
        $pdf_generator->writePage();
        
        $pdf_generator->render($pdf_template->getFilename(), 'D');
        exit;
    }
}