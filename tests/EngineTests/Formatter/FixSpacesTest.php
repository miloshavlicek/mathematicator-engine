<?php

declare(strict_types=1);

namespace Mathematicator\Engine\Tests\Helper;


use Mathematicator\FixSpaces;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class FixSpacesTest extends TestCase
{

	/**
	 * @dataprovider getFixTestCases
	 * @param string $expected
	 * @param string $input
	 */
	public function testFix(string $expected, string $input): void
	{
		Assert::same($expected, FixSpaces::fix($input));
	}


	/**
	 * @return string[]
	 */
	public function getFixTestCases(): array
	{
		return [
			['some text', 'some       text'],
			[' 158&nbsp;examples ', '    158   examples '],
			['This sentence contains 10&nbsp;words.', 'This sentence contains 10 words.'],
			// ['5*8', ' 5*  8'] // TODO: current result: 5*&nbsp;8
		];
	}
}

(new FixSpacesTest())->run();
