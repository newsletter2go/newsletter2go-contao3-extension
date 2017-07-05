<?php

namespace Contao;

class Newsletter2GoCallback extends \Controller
{

    /** @var  Newsletter2GoCallBack */
    private static $instance;

    /** @var Newsletter2GoModel  */
    private $n2goModel;

    /**
     * __construct function.
     */
    protected function __construct()
    {
        parent::__construct();
        $this->n2goModel = Newsletter2GoModel::getInstance();
    }

    /**
     * Gets an instance from itself.
     *
     * @return Newsletter2GoCallBack
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Newsletter2GoCallBack();
        }

        return self::$instance;
    }

    public function run()
    {

        $authKey = \Input::post('auth_key');
        $accessToken = \Input::post('access_token');
        $refreshToken = \Input::post('refresh_token');
        $model = Newsletter2GoModel::getInstance();

        if (!empty($authKey)) {
            $model->saveConfigValue('auth_key', $authKey.':foo');
        }
        if (!empty($accessToken)) {
            $model->saveConfigValue('access_token', $accessToken);
        }
        if (!empty($refreshToken)) {
            $model->saveConfigValue('refresh_token', $refreshToken);
        }

        $result = array('success' => true);
        echo json_encode($result);
        exit;

    }
}
