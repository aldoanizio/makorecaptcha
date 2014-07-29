<?php

/**
 * @copyright  Aldo Anizio Lugão Camacho
 * @license    http://www.makoframework.com/license
 */

namespace makorecaptcha\service;

use \makorecaptcha\ReCaptcha;

/**
 * ReCaptcha service.
 *
 * @author  Aldo Anizio Lugão Camacho
 */

class ReCaptchaService extends \mako\application\services\Service
{
	/**
	 * Registers the service.
	 *
	 * @access  public
	 */

	public function register()
	{
		$this->container->registerSingleton(['makorecaptcha\ReCaptcha', 'makoReCaptcha'], function($container)
		{
			return new ReCaptcha($container->get('config'), $container->get('logger'));
		});
	}
}