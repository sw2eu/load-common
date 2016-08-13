<?php

namespace Sw2\Load;

use Nette\Utils\Strings;

/**
 * Class Helpers
 * @package Sw2\Load
 */
class Helpers
{

	/**
	 * @param string|array $file
	 * @param int $time
	 * @param bool $debugMode
	 *
	 * @return string
	 */
	public static function computeHash($file, $time, $debugMode)
	{
		$debug = $debugMode ? 1 : 0;
		$file = is_array($file) ? implode(',', $file) : $file;
		$md5Raw = md5("$debug;$file;$time", TRUE);
		$base64 = Strings::replace(base64_encode($md5Raw), '~\W~');

		return Strings::substring(Strings::webalize($base64), 0, 8);
	}

}
