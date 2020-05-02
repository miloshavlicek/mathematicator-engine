<?php

declare(strict_types=1);

namespace Mathematicator\Engine;


use Mathematicator\Engine\Controller\IController;
use Mathematicator\Router\Router;
use Nette\DI\Container;
use Nette\DI\Extensions\InjectExtension;

final class Engine
{

	/** @var string[] */
	private static $allowedFunctions = ['sin', 'cos', 'tan', 'cotan', 'tg', 'log\d*', 'ln', 'sqrt'];

	/** @var Router */
	private $router;

	/** @var QueryNormalizer */
	private $queryNormalizer;

	/** @var Container */
	private $serviceFactory;

	/** @var ExtraModule[] */
	private $extraModules = [];


	/**
	 * @param Router $router
	 * @param QueryNormalizer $queryNormalizer
	 * @param Container $container
	 */
	public function __construct(Router $router, QueryNormalizer $queryNormalizer, Container $container)
	{
		$this->router = $router;
		$this->queryNormalizer = $queryNormalizer;
		$this->serviceFactory = $container;
	}


	/**
	 * @return string[]
	 */
	public static function getAllowedFunctions(): array
	{
		return self::$allowedFunctions;
	}


	/**
	 * @param string $function
	 */
	public static function addAllowedFunction(string $function): void
	{
		self::$allowedFunctions[] = $function;
	}


	/**
	 * @param string $query
	 * @return EngineResult|EngineMultiResult
	 * @throws InvalidDataException
	 */
	public function compute(string $query): EngineResult
	{
		$queryEntity = $this->buildQuery($query);

		if (preg_match('/^(?<left>.+?)\s+vs\.?\s+(?<right>.+?)$/', $queryEntity->getQuery(), $versus)) {
			return (new EngineMultiResult($queryEntity->getQuery()))
				->addResult($this->compute($versus['left']), 'left')
				->addResult($this->compute($versus['right']), 'right');
		}

		$controller = $this->router->routeQuery($queryEntity->getQuery());
		$matchedRoute = (string) preg_replace('/^.+\\\\([^\\\\]+)$/', '$1', $controller);

		if ($result = $this->processCallback($queryEntity, $controller)) {
			$return = new EngineSingleResult(
				$queryEntity->getQuery(),
				$matchedRoute,
				$result->getContext()->getInterpret(),
				$result->getContext()->getBoxes(),
				$result->getContext()->getSources(),
				$queryEntity->getFilteredTags()
			);
		} else {
			$return = new EngineSingleResult($queryEntity->getQuery(), $matchedRoute);
		}

		foreach ($this->extraModules as $extraModule) {
			$extraModule->setEngineSingleResult($return);
			if ($extraModule->match($queryEntity->getQuery()) === true) {
				foreach (InjectExtension::getInjectProperties(\get_class($extraModule)) as $property => $service) {
					$extraModule->{$property} = $this->serviceFactory->getByType($service);
				}
				if (method_exists($extraModule, 'setQuery')) {
					$extraModule->setQuery($queryEntity->getQuery());
				}
				$extraModule->actionDefault();
			}
		}

		return $return;
	}


	/**
	 * @param ExtraModule $extraModule
	 */
	public function addExtraModule(ExtraModule $extraModule): void
	{
		$this->extraModules[] = $extraModule;
	}


	/**
	 * @param Query $query
	 * @param string $serviceName
	 * @return IController|null
	 * @throws InvalidDataException
	 */
	private function processCallback(Query $query, string $serviceName): ?IController
	{
		/** @var IController|null $controller */
		$controller = $this->serviceFactory->getByType($serviceName);

		if ($controller !== null) {
			// 1. Process magic services
			foreach (InjectExtension::getInjectProperties(\get_class($controller)) as $property => $service) {
				$controller->{$property} = $this->serviceFactory->getByType($service);
			}

			// 2. Create context
			$controller->createContext($query);

			try {
				$controller->actionDefault();
			} catch (TerminateException $e) {
			}
		}

		return $controller ?? null;
	}


	/**
	 * @param string $query
	 * @return Query
	 */
	private function buildQuery(string $query): Query
	{
		return new Query(
			$query,
			$this->queryNormalizer->normalize($query)
		);
	}
}
