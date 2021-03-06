<?php

declare(strict_types=1);

namespace Mathematicator\Engine\Tests;


use Mathematicator\Engine\Source;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class SourceTest extends TestCase
{

	public function testComplete(): void
	{
		$source = new Source;
		$source->setTitle('Baraja.cz');
		$source->setUrl('https://baraja.cz');
		$source->setDescription('Official author site.');
		$source->setAuthors(['Jan Barášek']);
		$source->addAuthor('Baraja');

		$rendered = '<a href="https://baraja.cz" target="_blank">Baraja.cz</a><br>'
			. 'Official author site.<br>'
			. 'Authors:<br><span class="text-secondary">- Jan Barášek<br>- Baraja</span>';

		Assert::same($rendered, (string) $source);
	}


	public function testSimple(): void
	{
		$source = new Source;
		$source->setTitle('Baraja.cz');

		Assert::same('<b>Baraja.cz</b>', (string) $source);
	}
}

(new SourceTest)->run();
