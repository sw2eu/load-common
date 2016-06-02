<?php

namespace Sw2\Load;

use Nette;
use Nette\Caching\Cache;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

/**
 * Class AbstractLoadExtension
 *
 * @package Sw2\Load
 */
abstract class LoadExtension extends Nette\DI\CompilerExtension
{
	const TRACY_PANEL = 'sw2load.tracyPanel';

	/**
	 * @param string $namespace
	 * @param string $class
	 * @param array $args
	 */
	protected function addCompilerDefinition($namespace, $class, array $args)
	{
		$builder = $this->getContainerBuilder();

		$cacheDefName = "sw2load.cache.{$this->name}";
		$builder->addDefinition($cacheDefName)
			->setClass(Cache::class, ['@cache.storage', 'Sw2.' . ucfirst($namespace) . 'Load'])
			->setAutowired(FALSE);

		$builder->addDefinition("sw2load.compiler.{$this->name}")
			->setClass($class, array_merge(["@$cacheDefName"], $args))
			->setAutowired(FALSE);
	}

	/**
	 * @param string $class
	 */
	protected function registerMacros($class)
	{
		$this->getContainerBuilder()->getDefinition('nette.latte')
			->addSetup('?->onCompile[] = function ($engine) { ' . $class . '::install($engine->getCompiler()); }', ['@self']);

		$this->getContainerBuilder()->getDefinition('latte.latteFactory')
			->addSetup('?->onCompile[] = function ($engine) { ' . $class . '::install($engine->getCompiler()); }', ['@self']);
	}

	/**
	 * Add debug panel, if needed.
	 *
	 * @param bool $useDebugger
	 */
	protected function registerDebugger($useDebugger)
	{
		$builder = $this->getContainerBuilder();
		if ($useDebugger && !$builder->hasDefinition(self::TRACY_PANEL)) {
			$builder->addDefinition(self::TRACY_PANEL)
				->setClass(LoadPanel::class);
		}
		if ($useDebugger) {
			$builder->getDefinition(self::TRACY_PANEL)
				->addSetup('addCompiler', [$this->name, "@sw2load.compiler.{$this->name}"]);
		}
	}

	/**
	 * Adjusts DI container compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @param Nette\PhpGenerator\ClassType $class
	 */
	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->getMethod('initialize');
		$builder = $this->getContainerBuilder();

		if ($builder->parameters['debugMode'] && $builder->hasDefinition(self::TRACY_PANEL)) {
			$initialize->addBody($builder->formatPhp('?;', [
				new Nette\DI\Statement('@Tracy\Bar::addPanel', ['@' . self::TRACY_PANEL, self::TRACY_PANEL]),
			]));
		}
	}

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

	/**
	 * @param string $ext
	 * @param string $mainFile
	 *
	 * @return int
	 */
	public static function computeMaxTime($ext, $mainFile)
	{
		$time = 0;
		/** @var \SplFileInfo $file */
		foreach (Finder::find("*.$ext")->from(dirname($mainFile)) as $file) {
			$time = max($time, $file->getMTime());
		}

		return $time;
	}

}
