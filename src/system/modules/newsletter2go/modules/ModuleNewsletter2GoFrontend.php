<?php

namespace Contao;


class ModuleNewsletter2GoFrontend extends \Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_newsletter2go';

    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST" && \Environment::get('isAjaxRequest')) {
            $this->myGenerateAjax();
            exit;
        }

        if (TL_MODE == 'BE')
        {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### Newsletter2Go ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&table=tl_module&act=edit&id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Compile the current element
     */
    protected function compile()
    {
        $model = Newsletter2GoModel::getInstance();
        $this->Template->formUniqueCode = $model->getConfigValue('formUniqueCode');
        $this->Template->nl2gStylesConfigObject = $model->getConfigValue('widgetStyleConfig');
        $formTypeAvailable['subscribe'] = $model->getConfigValue('n2go_typeSubscribe');
        $formTypeAvailable['unsubscribe'] = $model->getConfigValue('n2go_typeUnsubscribe');
        $this->Template->nl2gFormTypeAvailable = $formTypeAvailable;
        $result = $model->getFormType($this->arrData['id']);

        if ($result === 'Unsubscribe-Form') {
            $formType = 'unsubscribe';
        } else {
            $formType = 'subscribe';
        }

        $this->Template->nl2gFormType = $formType;
        $this->Template->uniqueId = uniqid();

        // checks if utils.js is loaded
        $this->Template->utilsJs = !isset($GLOBALS['n2go_script_loaded']) ?
            '!function(e,t,n,c,r,a,i){e.Newsletter2GoTrackingObject=r,e[r]=e[r]||
            function(){(e[r].q=e[r].q||[]).push(arguments)},e[r].l=1*new Date,a=t.createElement(n),
            i=t.getElementsByTagName(n)[0],a.async=1,a.src=c,i.parentNode.insertBefore(a,i)}
            (window,document,"script","//static.newsletter2go.com/utils.js","n2g");' : '';

    }

    private function myGenerateAjax()
    {
        $notFound = false;
        $noValidEmail = false;
        $post = array();
        $model = Newsletter2GoModel::getInstance();

        $post['key'] = $model->getConfigValue('apiKey');
        $post['doicode'] = $model->getConfigValue('doiCode');
        $fieldsInfo = (array) $model->getConfigValue('fields', true);
        $texts = $model->getConfigValue('texts', true);


        foreach ($fieldsInfo as $k => $v) {
            $param = \Input::post($k);
            if (!empty($v['required']) && empty($param)) {
                $notFound = true;
                break;
            }

            if ($k == 'email') {
                if (!filter_var($param, FILTER_VALIDATE_EMAIL)) {
                    $noValidEmail = true;
                }
            }

            if ($k != 'email' && !$v['selected']) {
                continue;
            }

            $post[$k] = $_POST[$k];
        }

        if ($notFound) {
            $result = array('success' => 0, 'message' => $texts['failureRequired']);
            echo json_encode($result);
            die;
        }
        if ($noValidEmail) {
            $result = array('success' => 0, 'message' => $texts['failureEmail']);
            echo json_encode($result);
            die;
        }

        $response = $model->executeN2Go('create/recipient', $post);
        $result = array('success' => $response['success']);
        if (!$response) {
            $result['message'] = $texts['failureEmail'];
        } else {
            switch ($response['status']) {
                case 200:
                    $result['message'] = $texts['success'];
                    break;
                case 441:
                    $result['message'] = $texts['failureSubsc'];
                    break;
                case 434:
                case 429:
                    $result['message'] = $texts['failureEmail'];
                    break;
                default:
                    $result['message'] = $texts['failureError'];
                    break;
            }
        }

        echo json_encode($result);
    }

}