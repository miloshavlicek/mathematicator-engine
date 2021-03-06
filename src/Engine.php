<?php

declare(strict_types=1);

namespace Mathematicator\Engine;


use function get_class;
use Mathematicator\Engine\Controller\Controller;
use Mathematicator\Router\Router;
use Nette\DI\Extensions\InjectExtension;
use Psr\Container\ContainerInterface;

final class Engine
{

	/** @var Router */
	private $router;

	/** @var QueryNormalizer */
	private $queryNormalizer;

	/** @var ContainerInterface */
	private $container;

	/** @var ExtraModule[] */
	private $extraModules = [];


	/**
	 * @param Router $router
	 * @param QueryNormalizer $queryNormalizer
	 * @param ContainerInterface $container
	 */
	public function __construct(Router $router, QueryNormalizer $queryNormalizer, ContainerInterface $container)
	{
		$this->router = $router;
		$this->queryNormalizer = $queryNormalizer;
		$this->container = $container;
	}


	/**
	 * @param string $query
	 * @return EngineResult
	 * @throws InvalidDataException
	 */
	public function compute(string $query): EngineResult
	{
		$queryEntity = new Query($query, $this->queryNormalizer->normalize($query));

		if (preg_match('/^(?<left>.+?)\s+(?:vs\.?|versus)\s+(?<right>.+?)$/', $queryEntity->getQuery(), $versus)) {
			return (new EngineMultiResult($queryEntity->getQuery()))
				->addResult($this->compute($versus['left']), 'left')
				->addResult($this->compute($versus['right']), 'right');
		}

		$controllerClass = $this->router->routeQuery($queryEntity->getQuery());
		$matchedRoute = (string) preg_replace('/^.+\\\\([^\\\\]+)$/', '$1', $controllerClass);
		$context = $this->invokeController($queryEntity, $controllerClass)->getContext();
		$result = new EngineSingleResult($queryEntity->getQuery(), $matchedRoute, $context->getInterpret(), $context->getBoxes(), $context->getSources(), $queryEntity->getFilteredTags());

		foreach ($this->extraModules as $extraModule) {
			if ($extraModule->setEngineSingleResult($result)->match($queryEntity->getQuery()) === true) {
				foreach (InjectExtension::getInjectProperties(get_class($extraModule)) as $property => $service) {
					$extraModule->{$property} = $this->container->get($service);
				}
				if ($extraModule instanceof ExtraModuleWithQuery) {
					$extraModule->setQuery($queryEntity->getQuery());
				}
				$extraModule->actionDefault();
			}
		}

		return $result;
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
	 * @return Controller
	 * @throws InvalidDataException
	 */
	private function invokeController(Query $query, string $serviceName): Controller
	{
		/** @var Controller $controller */
		$controller = $this->container->get($serviceName);

		// 1. Inject services to public properties
		foreach (InjectExtension::getInjectProperties($serviceName) as $property => $service) {
			$controller->{$property} = $this->container->get($service);
		}

		// 2. Create context
		$controller->createContext($query);

		try {
			$controller->actionDefault();
		} catch (TerminateException $e) {
		}

		return $controller;
	}
}
