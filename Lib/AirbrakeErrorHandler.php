<?php

use Airbrake\Notifier as AirbrakeNotifier;
use Airbrake\Errors;

App::uses('Router', 'Routing');
App::uses('Debugger', 'Utility');

class AirbrakeErrorHandler extends ErrorHandler
{

	/**
	 * Creates a new Airbrake Notifier instance, or returns an instance created earlier.
	 *
	 * You should set credentials to Airbrake\Notifier by setting the CakeBreak.credentials
	 * configuration property.
	 *
	 * To set the project id and project key:
	 *
	 * ```
	 * Configure::write('CakeBreak.credentials', [
	 *  'projectId' => 12345,
	 *  'projectKey' => 'abcdefg'
	 * ]);
	 * ```
	 *
	 * @return Airbrake\Notifier
	 */
	private static function getAirbrake() {
		static $notifier = null;

		if ($notifier === null) {
			$credentials = Configure::read('CakeBreak.credentials');

			$notifier = new AirbrakeNotifier($credentials);
		}

		return $notifier;
	}


	/**
	 * Return configuration options setted
	 *
	 * Example:
	 *
	 * ```
	 * Configure::write('CakeBreak.options', [
	 *  'environment' => 'production'
	 * ]);
	 * ```
	 *
	 * @return array with the options
	 *
	 */
	private static function getOptions() {
		$options = Configure::read('CakeBreak.options');

		if ($options === null)
			return [];

		return $options;
	}


	/**
	 * Get request parameters.
	 *
	 * @return array with request parameters
	 */
	private static function getParams() {

		if (php_sapi_name() !== 'cli') {
			$request = Router::getRequest();
			return $request->params;
		}

		return [];
	}


	/**
	 * Transform a error code in a exception.
	 *
	 * @return Exception
	 */
	private static function createException($code, $message, $backtrace) {
		switch ($code) {
            case E_NOTICE:
            case E_USER_NOTICE:
                return new Errors\Notice($message, $backtrace);
            case E_WARNING:
            case E_USER_WARNING:
                return new Errors\Warning($message, $backtrace);
            case E_ERROR:
            case E_CORE_ERROR:
            case E_RECOVERABLE_ERROR:
                return new Errors\Fatal($message, $backtrace);
        }

        return new Errors\Error($message, $backtrace);
	}


	/**
	 * {@inheritDoc}
	 */
	public static function handleError($code, $description, $file = null, $line = null, $context = null) {
		list($error, $log) = self::mapErrorCode($code);

		$backtrace = debug_backtrace();
		$exception = self::createException($code, $description, $backtrace);

		$requestParams = self::getParams();
		$options       = self::getOptions();

		$notifier = static::getAirbrake();

		$notifier->addFilter(function ($notice) {
			$trace = Debugger::trace(array('format' => 'points'));

			$notice['context']['errorClass'] = $error;
			$notice['context']['backtrace'] = $backtrace;
			$notice['context']['errorMessage'] = $description;
			$notice['context']['extraParameters'] = array('CakeTrace' => $trace);

			$notices['context'] = array_merge($notices['context'], $requestParams);
			$notices['content'] = array_merge($notices['context'], $options);
		});

		$notifier->notify($exception);

		return parent::handleError($code, $description, $file, $line, $context);
	}


	/**
	 * {@inheritDoc}
	 */
	public static function handleException($exception) {
		$notifier = static::getAirbrake();
		$notifier->notify($exception);

		return parent::handleException($exception);
	}
}
