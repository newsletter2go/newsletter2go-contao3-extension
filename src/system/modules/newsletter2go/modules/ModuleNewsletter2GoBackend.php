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
     * Compile the current element
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
            $doiCode = \Input::post('doiCode') ? \Input::post('doiCode') : '';
            $texts = \Input::post('texts') ? \Input::post('texts') : array();
            $colors = \Input::post('colors') ? \Input::post('colors') : array();
            $fields = \Input::post('fields') ? \Input::post('fields') : array();
            $widgetSource = \Input::post('widgetSourceCode') ? \Input::postRaw('widgetSourceCode') : '';

            $model->saveConfigValue('apiKey', $apiKey);
            $model->saveConfigValue('doiCode', $doiCode);
            $model->saveConfigValue('fields', $fields, true);
            $model->saveConfigValue('colors', $colors, true);
            $model->saveConfigValue('texts', $texts, true);
            $model->saveConfigValue('widgetSource', $widgetSource);
        }

        $attributesCount = 4;
        $attributes = array();
        $tplObject->apiKey = $model->getConfigValue('apiKey');
        $tplObject->doiCode = $model->getConfigValue('doiCode');
        $tplObject->widgetSource = $model->getConfigValue('widgetSource');
        $fieldsInfo = $model->getConfigValue('fields', true);
        $tplObject->texts = $model->getConfigValue('texts', true);
        $tplObject->colors = $model->getConfigValue('colors', true);

        $attributesResponse = $model->executeN2Go('get/attributes', array('key' => $tplObject->apiKey));
        $tplObject->apiSuccess = $attributesResponse['success'];

        if ($attributesResponse['success']) {
            $attributesCount += count($attributesResponse['value']);
            foreach ($attributesResponse['value'] as $atr) {
                $tmpId = strtolower(str_replace(' ', '', $atr));
                $attributes[] = array(
                    'id' => $tmpId,
                    'label' => $atr,
                    'disabled' => '',
                );
            }
        }

        $doiResponse = $model->executeN2Go('get/form', array('key' => $tplObject->apiKey, 'doicode' => $tplObject->doiCode));
        $tplObject->doiSuccess = $doiResponse['success'];
        $tplObject->doiHost = '';
        if ($doiResponse['success']) {
            $code = rawurldecode($doiResponse['value']['code']);
            if (strpos($code, '"' . $tplObject->doiCode . '"') !== false) {
                $tplObject->doiHost = $doiResponse['value']['host'];
            }
        }

        $attributes[] = array(
            'id' => 'email',
            'label' => $tplObject->myLabels['email'],
            'disabled' => 'disabled="disabled"',
        );
        $attributes[] = array(
            'id' => 'firstname',
            'label' => $tplObject->myLabels['first_name'],
            'disabled' => '',
        );
        $attributes[] = array(
            'id' => 'lastname',
            'label' => $tplObject->myLabels['last_name'],
            'disabled' => '',
        );
        $attributes[] = array(
            'id' => 'gender',
            'label' => $tplObject->myLabels['gender'],
            'disabled' => '',
        );

        foreach ($attributes as &$attribute) {
            $id = $attribute['id'];
            $label = $attribute['label'];
            $field = isset($fieldsInfo[$id]) ? $fieldsInfo[$id] : array();
            $attribute['title'] = !empty($field['title']) ? $field['title'] : $label;
            $attribute['selected'] = !empty($field['selected']) ? 'checked="checked"' : '';
            $attribute['required'] = !empty($field['required']) ? '1' : '';
            $attribute['sort'] = !empty($field['sort']) ? $field['sort'] : $attributesCount;

            if ($id === 'email') {
                $attribute['selected'] = 'checked';
                $attribute['required'] = '1';
            }
        }

        usort($attributes, function ($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        $tplObject->fields = $attributes;
        if (empty($tplObject->texts)) {
            $tplObject->texts = $tplObject->myLabels['texts'];
        } else {
            foreach ($tplObject->myLabels['texts'] as $key => $value) {
                if (empty($tplObject->texts[$key])) {
                    $tplObject->texts[$key] = $value;
                }
            }
        }
    }
}