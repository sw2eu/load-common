<?php

namespace Sw2\Load\Compiler;

/**
 * Interface ICompiler
 * @package Sw2\Load\Compiler
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
