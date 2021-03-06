<?php

declare(strict_types=1);

namespace Mathematicator\Engine\Tests\Helper;


use Mathematicator\Engine\Helper\DateTime;
use Mathematicator\Engine\MathematicatorException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

class DateTimeTest extends TestCase
{

	/**
	 * @dataprovider getDateTimeIsoTestCases
	 * @param string $expected
	 * @param int $input
	 */
	public function testGetDateTimeIso(string $expected, int $input): void
	{
		Assert::same($expected, DateTime::getDateTimeIso($input));
	}


	/**
	 * @dataprovider getFormatTimeAgoTestCases
	 * @param string $expected
	 * @param array $input
	 */
	public function testFormatTimeAgo(string $expected, array $input): void
	{
		Assert::same($expected, DateTime::formatTimeAgo($input[0], $input[1], $input[2], $input[3]), $expected);
	}


	public function testCreateInstance(): void
	{
		Assert::exception(function () {
			new DateTime;
		}, \Error::class);
	}


	public function testUnknownLanguage(): void
	{
		Assert::exception(function () {
			DateTime::formatTimeAgo(256, true, 'de');
		}, MathematicatorException::class);
	}


	/**
	 * @return string[]
	 */
	public function getDateTimeIsoTestCases(): array
	{
		return [
			['2020-12-25 20:12:03', (int) (new \DateTime('2020-12-25 20:12:03'))->format('U')],
		];
	}


	/**
	 * @return string[]
	 */
	public function getFormatTimeAgoTestCases(): array
	{
		return [
			['1 sekunda', [strtotime('2020-01-25 20:12:03'), false, 'cz', strtotime('2020-01-25 20:12:04')]],
			['1 minuta', [strtotime('2020-01-25 20:12:03'), false, 'cz', strtotime('2020-01-25 20:13:04')]],
			['1 minuta 3 měsíce', [strtotime('2020-01-25 20:12:03'), true, 'cz', strtotime('2020-01-25 20:13:10')]], // Possible bug
			['5 minut', [strtotime('2020-01-25 20:12:03'), false, 'cz', strtotime('2020-01-25 20:17:03')]],
			['1 měsíc', [strtotime('2019-12-25 20:12:03'), false, 'cz', strtotime('2020-01-25 20:12:03')]],
			['9 let', [strtotime('2010-01-25 20:12:03'), false, 'cz', strtotime('2020-01-25 20:12:05')]], // Possible bug
			['1 mesiac', [strtotime('2019-12-25 20:12:03'), false, 'sk', strtotime('2020-01-25 20:12:03')]],
			['2 months', [strtotime('2019-12-25 20:12:03'), false, 'en', strtotime('2020-02-25 20:12:03')]],
		];
	}
}

(new DateTimeTest())->run();
