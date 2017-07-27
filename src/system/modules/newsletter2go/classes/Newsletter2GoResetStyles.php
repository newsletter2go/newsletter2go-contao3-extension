<?php

namespace Contao;

class Newsletter2GoResetStyles extends \Controller
{

    /** @var  Newsletter2GoResetStyles */
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
     * @return Newsletter2GoResetStyles
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Newsletter2GoResetStyles();
        }

        return self::$instance;
    }

    public function run()
    {
        $style = \Input::post('style', true);

        $model = Newsletter2GoModel::getInstance();

        if (!empty($style)) {
            $model->saveConfigValue('widgetStyleConfig', $style);
        }

        $result = array('success' => true);
        echo json_encode($result);

    }
}
