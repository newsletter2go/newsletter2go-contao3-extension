<?php

namespace Contao;

class ModuleNewsletter2GoBackend extends \BackendModule
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_newsletter2go';

    /**
     * Compile the current element.
     */
    protected function compile()
    {
        \System::loadLanguageFile('tl_newsletter2go');
        $tplObject = $this->Template;

        $tplObject->content = '';
        $tplObject->href = $this->getReferer(true);
        $tplObject->title = specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $tplObject->button = $GLOBALS['TL_LANG']['MSC']['backBT'];
        $tplObject->myLabels = $GLOBALS['TL_LANG']['tl_newsletter2go'];
        $tplObject->action = ampersand(\Environment::get('request'));
        if (!function_exists('curl_version')) {
            $tplObject->curlMissing = 1;

            return;
        }

        $model = Newsletter2GoModel::getInstance();

        if ((\Input::post('FORM_SUBMIT') == 'tl_nl2go_configuration')) {
            $apiKey = \Input::post('apiKey') ? \Input::post('apiKey') : '';
            $formUniqueCode = \Input::post('formUniqueCode') ? \Input::post('formUniqueCode') : '';
            $widgetStyleConfig = \Input::post('widgetStyleConfig') ? \Input::postRaw('widgetStyleConfig') : '';

            $model->saveConfigValue('apiKey', $apiKey);
            $model->saveConfigValue('formUniqueCode', $formUniqueCode);
            $model->saveConfigValue('widgetStyleConfig', $widgetStyleConfig);
        }

        $tplObject->apiKey = $model->getConfigValue('apiKey');
        $tplObject->formUniqueCode = $model->getConfigValue('formUniqueCode');
        $tplObject->nl2gStylesConfigObject = $model->getConfigValue('widgetStyleConfig');


        $errorMessages = array();
        if (!strlen(trim($tplObject->formUniqueCode)) > 0 || $tplObject->formUniqueCode === null) {
            $errorMessages[] = "Please, enter the form unique code!";
        }
        if (!strlen(trim($tplObject->apiKey)) > 0 || $tplObject->apiKey === null) {
            $errorMessages[] = "Please, enter the api key!";
         }

        $tplObject->errorMessages = $errorMessages;

    }
}