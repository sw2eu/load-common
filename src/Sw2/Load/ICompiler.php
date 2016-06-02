<?php

namespace Sw2\Load;

/**
 * Interface ICompiler
 *
 * @package Sw2\Load
 */
interface ICompiler
{

	/**
	 * @return array
	 */
	public function getStatistics();

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function link($name);

}
