<?php

namespace Sw2\Load\Compiler;

use Nette\Caching\Cache;
use Tracy\Debugger;

/**
 * Class BaseCompiler
 * @package Sw2\Load\Compiler
 */
abstract class BaseCompiler implements ICompiler
{
	/** @var Cache */
	private $cache;

	/** @var bool */
	private $debugMode;

	/** @var string */
	private $wwwDir;

	/** @var string */
	private $genDir;

	/** @var array */
	private $files;

	/** @var array */
	private $statistics = [];

	/**
	 * @param Cache $cache
	 * @param bool $debugMode
	 * @param string $wwwDir
	 * @param string $genDir
	 * @param array $files
	 */
	public function __construct(Cache $cache, $debugMode, $wwwDir, $genDir, array $files)
	{
		$this->cache = $cache;
		$this->debugMode = $debugMode;
		$this->wwwDir = $wwwDir;
		$this->genDir = $genDir;
		$this->files = $files;
	}


	/**
	 * @return array
	 */
	public function getStatistics()
	{
		return $this->statistics;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function link($name)
	{
		$path = $this->cache->load([$name, $this->debugMode]);
		$files = $this->files[$name];
		$files = is_string($files) ? [$files] : $files;
		if ($path === NULL) {
			$time = $this->getModifyTime($files);
			$path = $this->genDir . '/' . $this->getOutputFilename($name, $files, $time, $this->debugMode);
			$this->cache->save([$name, $this->debugMode], $path);
		}

		$genFile = "{$this->wwwDir}/$path";
		if (!file_exists($genFile) || ($this->debugMode && filemtime($genFile) < (isset($time) ? $time : ($time = $this->getModifyTime($files))))) {
			$start = microtime(TRUE);
			$parsedFiles = $this->compile($files, $genFile);
			if ($this->debugMode) {
				$this->statistics[$name]['time'] = microtime(TRUE) - $start;
				$this->statistics[$name]['parsedFiles'] = $parsedFiles;
			}
		}
		if ($this->debugMode) {
			$this->statistics[$name]['size'] = filesize($genFile);
			$this->statistics[$name]['file'] = count($files) > 1 ? $files : reset($files);
			$this->statistics[$name]['date'] = isset($time) ? $time : ($time = $this->getModifyTime($files));
			$this->statistics[$name]['path'] = $path;
		}
		Debugger::barDump($this->statistics);

		return $path;
	}

	/**
	 * @param array $sourceFiles
	 * @return int
	 */
	abstract protected function getModifyTime($sourceFiles);

	/**
	 * @param string $name
	 * @param array $files
	 * @param int $time
	 * @param bool $debugMode
	 * @return string
	 */
	abstract protected function getOutputFilename($name, $files, $time, $debugMode);

	/**
	 * @param array $sourceFiles
	 * @param string $outputFile
	 * @return array
	 */
	abstract protected function compile($sourceFiles, $outputFile);

}
