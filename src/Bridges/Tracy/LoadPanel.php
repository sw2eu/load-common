<?php

namespace Sw2\Load\Bridges\Tracy;

use Sw2\Load\Compiler\ICompiler;
use Tracy\IBarPanel;

/**
 * Class LoadPanel
 *
 * @package Sw2\Load
 */
class LoadPanel implements IBarPanel
{
	/** @var ICompiler[] */
	private $compilers = [];

	/** @var int */
	private $elapsedTime = 0;

	/** @var int */
	private $filesCount	= 0;

	/** @var bool */
	private $computed = FALSE;

	/**
	 * @param string $category
	 * @param ICompiler $compiler
	 */
	public function addCompiler($category, ICompiler $compiler)
	{
		$this->compilers[$category][] = $compiler;
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$this->compute();

		ob_start();
		require __DIR__ . '/LoadPanel.panel.phtml';

		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		$this->compute();

		ob_start();
		require __DIR__ . '/LoadPanel.tab.phtml';

		return ob_get_clean();
	}

	private function compute()
	{
		if ($this->computed) return;

		foreach ($this->compilers as $category => $compilers) {
			/** @var ICompiler $compiler */
			foreach ($compilers as $compiler) {
				foreach ($compiler->getStatistics() as $name => $statistics) {
					if (!empty($statistics['time'])) {
						$this->elapsedTime += $statistics['time'];
					}
					$this->filesCount++;
				}
			}
		}
		$this->computed = TRUE;
	}

}
