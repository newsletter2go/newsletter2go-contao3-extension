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
     * In order to use multiple identical tags on single page tag will need to have flag |refresh (example: {{n2g::subscribe::plugin|refresh}}).
     * This is required in order to prevent caching and to have unique id for all forms.
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
        $n2gTag = explode('::', $strTag);

        if ($n2gTag[0] === 'n2g') {

            $model = Newsletter2GoModel::getInstance();
            $formUniqueCode = $model->getConfigValue('formUniqueCode');
            $widgetStyleConfig = $model->getConfigValue('widgetStyleConfig');
            $formTypeAvailable['subscribe'] = $model->getConfigValue('n2go_typeSubscribe');
            $formTypeAvailable['unsubscribe'] = $model->getConfigValue('n2go_typeUnsubscribe');
            $params = '';
            $popup = false;

            // checks if utils.js is loaded
            $utilsJs = !isset($GLOBALS['n2go_script_loaded']) ?
                '!function(e,t,n,c,r,a,i){e.Newsletter2GoTrackingObject=r,e[r]=e[r]||
                function(){(e[r].q=e[r].q||[]).push(arguments)},e[r].l=1*new Date,a=t.createElement(n),
                i=t.getElementsByTagName(n)[0],a.async=1,a.src=c,i.parentNode.insertBefore(a,i)}
                (window,document,"script","//static.newsletter2go.com/utils.js","n2g");' : '';

            switch ($n2gTag[1]) {
                case '':
                case 'plugin':
                    $func = 'subscribe:createForm';
                    $n2gTag[1] = 'subscribe';
                    break;
                case 'popup':
                    $popup = true;
                    $func = 'subscribe:createPopup';
                    $n2gTag[2] ?: $n2gTag[2] = 10;
                    $params = ", $n2gTag[2]";
                    break;
                case 'subscribe':
                    if ($n2gTag[2] == 'popup') {
                        $popup = true;
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

            if (!empty($func) && !empty($formUniqueCode) && !empty($formTypeAvailable[$n2gTag[1]])) {
                $GLOBALS['n2go_script_loaded'] = true;
                $uniqueId = uniqid();
                $params = "'$func', $widgetStyleConfig" . $params . ($uniqueId && !$popup ? ",'" . $uniqueId . "'" : '');

                $widget = "<script id='" . ($uniqueId && !$popup ? $uniqueId : 'n2g_script') . "'>
                            $utilsJs
                            document.addEventListener('DOMContentLoaded', function() {
                                  n2g('create','$formUniqueCode');
                                  n2g($params);
                            }, false);
                            </script>";
            } else {
                if (empty($formUniqueCode)) {
                    $widget = '<div class="widget nl2go-widget">Please select form in Newsletter2Go component settings.</div>';
                } else if (empty($formTypeAvailable[$n2gTag[1]])) {
                    $widget = '<div class="widget nl2go-widget">This form type is not available for selected form</div>';
                } else {
                    $widget = "<p style='color: red'>wrong Newsletter2Go tag <b>$strTag</b>: it should be '{{n2g::plugin}}' or '{{n2g::popup}}</p>'";
                }
            }
        }

        return $widget;
    }
}