<?php
/**
 * Bootstraps the CakeBrake plugin.
 * Before loading the plugin, please set the required credentials:
 *
 * Configure::write('CakeBrake.credentials', [
 *  'projectId' => <PROJECT ID>,
 *  'projectKey' => <PROJECT KEY>
 * ]);
 */

App::uses('AirbrakeErrorHandler', 'CakeBrake.Lib');

/**
 * Sets the ErrorHandler and ExceptionHandler to
 * AirbrakeErrorHandler.
 */
Configure::write('Error', array(
	'handler' => 'AirbrakeErrorHandler::handleError',
	'level' => E_ALL & ~E_DEPRECATED,
	'trace' => true
));

Configure::write('Exception', array(
	'handler' => 'AirbrakeErrorHandler::handleException',
	'renderer' => 'ExceptionRenderer',
	'log' => true
));