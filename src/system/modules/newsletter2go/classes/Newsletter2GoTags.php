<?php
namespace Contao;

/**
 * Class Newsletter2GoTags
 * @package Contao
 */
class Newsletter2GoTags
{
    /**
     * @param $strTag
     * @return string
     *
     * Tag syntax:  {{n2g}}, {{n2g::}}, {{n2g::plugin}}, {{n2g::subscribe::plugin}} displays subscription form embedded in content
     *              {{n2g::popup}}, {{n2g::popup::}}, {{n2g::subscribe::popup}}, {{n2g::subscribe::popup::}}  displays subscription form as modal window, delay 10 seconds default
     *              {{n2g::popup::n}}, {{n2g::subscribe::popup::n}} modal window with n seconds delay
     *              {{n2g::unsubscribe}} displays unsubscription form embedded in content
     *
     */
    public function n2gReplaceTags($strTag)
    {
        $widget = '';
        $n2gTag = explode('::',$strTag);

        if ($n2gTag[0] === 'n2g') {

            $model = Newsletter2GoModel::getInstance();
            $formUniqueCode = $model->getConfigValue('formUniqueCode');
            $widgetStyleConfig = $model->getConfigValue('widgetStyleConfig');
            $params = '';

            switch ($n2gTag[1]) {
                case '':
                case 'plugin':
                    $func = 'subscribe:createForm';
                    break;
                case 'popup':
                    $func = 'subscribe:createPopup';
                    $n2gTag[2] ? : $n2gTag[2] = 10;
                    $params = ", $n2gTag[2]";
                    break;
                case 'subscribe':
                    if($n2gTag[2] == 'popup') {
                        $func = 'subscribe:createPopup';
                        $n2gTag[3] ?: $n2gTag[3] = 10;
                        $params = ", $n2gTag[3]";
                    } else {
                        $func = 'subscribe:createForm';
                    }
                    break;
                case 'unsubscribe':
                    $func = 'unsubscribe:createForm';
                    break;
                default:
                    $func = '';
                    break;
            }

            if ($func) {
                $params = "'$func', $widgetStyleConfig" . $params;
                $widget = "<script id='n2g_script'>
                    !function(e,t,n,c,r,a,i){e.Newsletter2GoTrackingObject=r,e[r]=e[r]||function(){(e[r].q=e[r].q||[]).push(arguments)},e[r].l=1*new Date,a=t.createElement(n),i=t.getElementsByTagName(n)[0],a.async=1,a.src=c,i.parentNode.insertBefore(a,i)}(window,document,\"script\",\"//static.newsletter2go.com/utils.js\",\"n2g\");
                    n2g('create','$formUniqueCode');
                    n2g($params);
                    </script>";
            } else {
                $widget = "<p style='color: red'>wrong Newsletter2Go tag <b>$strTag</b>: it should be '{{n2g::plugin}}' or '{{n2g::popup}}</p>'";
            }
        }

        return $widget;
    }
}