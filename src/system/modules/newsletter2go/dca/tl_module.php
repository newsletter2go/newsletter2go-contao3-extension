<?php

/**
 * Load tl_newsletter2go language file
 */
System::loadLanguageFile('tl_newsletter2go');

/**
 * add palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'newsletter2go_static';
$GLOBALS['TL_DCA']['tl_module']['palettes']['Newsletter2Go'] = '{title_legend},name,type;{config_legend},n2go_form_type';


/**
 * add subpalettes
 */
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['newsletter2go_static'] = 'n2go_form_type';


/**
 * add fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['n2go_form_type'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_newsletter2go']['n2go_form_type'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_newsletter2go', 'getOptions'),
	'sql'                     => "text NULL"
);


/**
 * Class tl_module_newssletter2go
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_module_newsletter2go extends Backend
{

	/**
	 * Return all options as array
	 * @return array
	 */
	public function getOptions()
	{
		$model = Newsletter2GoModel::getInstance();
		$authKey = $model->getConfigValue('auth_key');
		$formUniqueCode = $model->getConfigValue('formUniqueCode');
		$forms = $model->getForms($authKey);

		if ($forms) {
			foreach ($forms as $f) {
				if ($formUniqueCode == $f['hash']) {
					$subscribe = $f['type_subscribe'];
					$unsubscribe = $f['type_unsubscribe'];
				}
			}
		}
		$options = array();

		$subscribe === true ? $options[] = 'Subscribe-Form' : '';
		$unsubscribe === true ? $options[] = 'Unsubscribe-Form' : '';

		return $options;
	}

}