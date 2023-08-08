<?php 
/**
 * The UI for environment indicator is broken, so we have to do this instead.
 * See more info, https://www.drupal.org/project/environment_indicator/issues/2224983
 */
switch ($_SERVER['HTTP_HOST']) {
	case 'edit.infofinland.fi/':
		$config['environment_indicator.indicator']['bg_color'] = '#ffffff';
		$config['environment_indicator.indicator']['fg_color'] = '#000000';
		$config['environment_indicator.indicator']['name'] = 'Production';
		break;
	case 'infofinland-edit.stage.hel.ninja':
		$config['environment_indicator.indicator']['bg_color'] = '#3584e4';
		$config['environment_indicator.indicator']['fg_color'] = '#000000';
		$config['environment_indicator.indicator']['name'] = 'Stage';
		break;
	case 'edit-infofinland.test.hel.ninja':
		$config['environment_indicator.indicator']['bg_color'] = '#ed333b';
		$config['environment_indicator.indicator']['fg_color'] = '#000000';
		$config['environment_indicator.indicator']['name'] = 'Test';
		break;
	case 'edit-infofinland.dev.hel.ninja':
		$config['environment_indicator.indicator']['bg_color'] = '#33d17a';
		$config['environment_indicator.indicator']['fg_color'] = '#000000';
		$config['environment_indicator.indicator']['name'] = 'Development';
		break;
}
