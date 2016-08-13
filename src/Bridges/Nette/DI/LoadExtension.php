<?php

namespace Sw2\Load\Bridges\Nette\DI;

use Nette;
use Nette\Caching\Cache;
use Nette\Utils\Validators;
use Sw2\Load\Bridges\Tracy\LoadPanel;

/**
 * Class AbstractLoadExtension
 *
 * @package Sw2\Load
 */
abstract class LoadExtension extends Nette\DI\CompilerExtension
{
	const TRACY_PANEL = 'sw2load.tracyPanel';

	/** @var array */
	public $defaults = [
		'debugger' => FALSE,
		'genDir' => 'webtemp',
		'files' => [],
	];

	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);
		Validators::assertField($config, 'debugger', 'boolean');
		Validators::assertField($config, 'genDir', 'string');
		Validators::assertField($config, 'files', 'array');

		$builder = $this->getContainerBuilder();
		$wwwDir = $builder->parameters['wwwDir'];
		$genDir = $config['genDir'];

		if (!is_writable("$wwwDir/$genDir")) {
			throw new Nette\IOException("Directory '$wwwDir/$genDir' is not writable.");
		}
	}

	/**
	 * @param string $namespace
	 * @param string $class
	 */
	protected function addCompilerDefinition($namespace, $class)
	{
		$builder = $this->getContainerBuilder();

		$cacheDefName = "sw2load.cache.{$this->name}";
		$builder->addDefinition($cacheDefName)
			->setClass(Cache::class, ['@cache.storage', 'Sw2.Load' . ucfirst($namespace)])
			->setAutowired(FALSE);

		$builder->addDefinition("sw2load.compiler.{$this->name}")
			->setClass($class, [
				'cache' => "@$cacheDefName",
				'debugMode' => $builder->parameters['debugMode'],
				'wwwDir' => $builder->parameters['wwwDir'],
				'genDir' => $this->config['genDir'],
				'files' => $this->config['files'],
			])
			->setAutowired(FALSE);
	}

	/**
	 * @param string $class
	 */
	protected function registerMacros($class)
	{
		$builder = $this->getContainerBuilder();
		if ($builder->hasDefinition('nette.latte')) {
			$builder->getDefinition('nette.latte')
				->addSetup('?->onCompile[] = function ($engine) { ' . $class . '::install($engine->getCompiler()); }', ['@self']);
		}
		if ($builder->hasDefinition('latte.latteFactory')) {
			$builder->getDefinition('latte.latteFactory')
				->addSetup('?->onCompile[] = function ($engine) { ' . $class . '::install($engine->getCompiler()); }', ['@self']);
		}
	}

	/**
	 * Add debug panel, if needed.
	 */
	protected function registerDebugger()
	{
		if ($this->config['debugger']) {
			$builder = $this->getContainerBuilder();
			if (!$builder->hasDefinition(self::TRACY_PANEL)) {
				$builder->addDefinition(self::TRACY_PANEL)->setClass(LoadPanel::class);
			}
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

}
