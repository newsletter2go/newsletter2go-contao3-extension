<?php

namespace Contao;

class ModuleNewsletter2GoBackend extends \BackendModule
{
    const N2GO_INTEGRATION_URL = 'https://ui-staging.newsletter2go.com/integrations/connect/CTO/';
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_newsletter2go';

    private $version = 4000;

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
        $disconnect = \Input::post('disconnect');
        if (isset($disconnect)) {
            $baseUrl = $this->Environment->url;
            $requestUri = $this->Environment->requestUri;
            $nl2goUrl = $baseUrl . $requestUri;
            $this->disconnect();
            $this->redirect($nl2goUrl);
        }
        $model = Newsletter2GoModel::getInstance();
        $tplObject->authKey = $model->getConfigValue('auth_key');
        $tplObject->apiKey = $model->getConfigValue('apiKey');
        $queryParams['version'] = 3000;
        $queryParams['apiKey'] = $tplObject->apiKey;
        if ($queryParams['apiKey'] == '') {
            $model->saveConfigValue('apiKey', $this->generateRandomString());
            $queryParams['apiKey'] = $model->getConfigValue('apiKey');

        }

        $queryParams['language'] = current(explode("-", $GLOBALS['TL_LANGUAGE']));
        $queryParams['url'] = rtrim(\Environment::get('base'), '/') . '/';
        $queryParams['callback'] = $queryParams['url'] . 'nl2go/callback.php';

        $tplObject->apiKeyConnectUrl = self::N2GO_INTEGRATION_URL . '?' . http_build_query($queryParams);
        $tplObject->forms = $model->getForms($tplObject->authKey);

        if ((\Input::post('FORM_SUBMIT') == 'tl_nl2go_configuration') && !isset($disconnect)) {
            $formUniqueCode = \Input::post('formUniqueCode') ? \Input::post('formUniqueCode') : '';

            $widgetStyleConfig = json_decode(stripcslashes(\Input::postRaw('widgetStyleConfig')));
            if (isset($widgetStyleConfig)) {
                $model->saveConfigValue('widgetStyleConfig', \Input::postRaw('widgetStyleConfig'));
            }

            $authKey = $model->getConfigValue('auth_key');
            $forms = $model->getForms($authKey);
            $model->saveFormType($forms, $formUniqueCode);
            $model->saveConfigValue('formUniqueCode', $formUniqueCode);
        }

        $tplObject->apiKey = $model->getConfigValue('apiKey');
        $tplObject->formUniqueCode = $model->getConfigValue('formUniqueCode');
        $tplObject->nl2gStylesConfigObject = $model->getConfigValue('widgetStyleConfig');

        $response = $model->executeN2GO('get/attributes', array('key' => $tplObject->apiKey));
        $tplObject->apiSuccess = $response['success'];

        $errorMessages = array();
        if (!strlen(trim($tplObject->formUniqueCode)) > 0 || $tplObject->formUniqueCode === null) {
            $errorMessages[] = "Please connect to Newsletter2Go by clicking on \"Login or Create Account\" button.";
        }
        if (!strlen(trim($tplObject->apiKey)) > 0 || $tplObject->apiKey === null) {
            $errorMessages[] = "Please, enter the api key!";
        }

        $tplObject->errorMessages = $errorMessages;

    }

    private function disconnect()
    {

        $model = Newsletter2GoModel::getInstance();
        $model->saveConfigValue('auth_key', null);
        $model->saveConfigValue('access_token', null);
        $model->saveConfigValue('refresh_token', null);
        $model->saveConfigValue('formUniqueCode', null);
        $model->saveConfigValue('widgetStyleConfig', null);
    }

    private function generateRandomString($length = 40)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
