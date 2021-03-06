<?php

declare(strict_types=1);

namespace Mathematicator\Engine\MathFunction;


use RuntimeException;

final class FunctionManager
{
	/** @var MathFunction[] (functionName => MathFunction) */
	private static $functions = [];


	/**
	 * @return string[]
	 */
	public static function getFunctionNames(): array
	{
		self::mountBasicFunctions();

		return array_keys(self::$functions);
	}


	/**
	 * @return MathFunction[]
	 */
	public static function getFunctions(): array
	{
		self::mountBasicFunctions();

		return self::$functions;
	}


	/**
	 * @param string $name
	 * @return MathFunction
	 */
	public static function getFunction(string $name): MathFunction
	{
		self::mountBasicFunctions();

		if (isset(self::$functions[$name]) === false) {
			throw new RuntimeException('Function "' . $name . '" does not exist.');
		}

		return self::$functions[$name];
	}


	public static function addFunction(string $name, MathFunction $function): void
	{
		if (isset(self::$functions[$name]) === true && self::$functions[$name] !== $function) {
			throw new RuntimeException('Function "' . $name . '" already exist.');
		}

		self::$functions[$name] = $function;
	}


	/**
	 * @param string $name
	 * @param mixed $haystack
	 * @param array<int, mixed> $params
	 * @return mixed
	 */
	public static function invoke(string $name, $haystack, ...$params)
	{
		return self::getFunction($name)->invoke($haystack, $params);
	}


	private static function mountBasicFunctions(): void
	{
		if (self::$functions !== []) {
			return;
		}

		self::addFunction('sin', new Sin);
		self::addFunction('cos', new Cos);
		self::addFunction('tan', new Tan);
		self::addFunction('cotan', new Tan);
		self::addFunction('tg', new Tan);
		self::addFunction('log', new Logarithm);
		self::addFunction('ln', new Logarithm);
		self::addFunction('sqrt', new Sqrt);
	}
}
