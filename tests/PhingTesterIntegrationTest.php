<?php

declare(strict_types = 1);

namespace VasekPurchart\Phing\PhingTester;

use Generator;
use PHPUnit\Framework\Assert;
use Project;

class PhingTesterIntegrationTest extends \PHPUnit\Framework\TestCase
{

	public function testExecuteTarget(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;

		$this->expectNotToPerformAssertions();
		$tester->executeTarget($target);
	}

	public function testMessageInLogs(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertLogMessage('Lorem ipsum');
	}

	public function testMessageInLogsWithRegExp(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertLogMessageRegExp('~lorem.+amet~i');
	}

	public function testMessageNotInLogs(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertNotLogMessage('Lorem XXX');
	}

	public function testMessageRegExpNotInLogs(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertNotLogMessageRegExp('~lorem.+XXX.+amet~i');
	}

	public function testMessageInLogsFromCustomTarget(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;

		$fooTarget = $target . '-foo';
		$tester->executeTarget($fooTarget);

		$barTarget = $target . '-bar';
		$tester->executeTarget($barTarget);

		$tester->assertLogMessage('FOO');
		$tester->assertLogMessage('FOO', $fooTarget);
		$tester->assertNotLogMessage('FOO', $barTarget);

		$tester->assertLogMessage('BAR');
		$tester->assertLogMessage('BAR', $barTarget);
		$tester->assertNotLogMessage('BAR', $fooTarget);
	}

	public function testMessageInLogsWithCustomPriority(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$tester->assertLogMessage('Lorem ipsum', null, Project::MSG_DEBUG);
		$tester->assertNotLogMessage('Lorem ipsum', null, Project::MSG_VERBOSE);
	}

	public function testFailBuild(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->expectFailedBuild($target);
	}

	public function testFailBuildWithMessage(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->expectFailedBuild($target);

		$tester->assertLogMessage('Fail message', $target, Project::MSG_DEBUG);
	}

	public function testFailBuildCheckBuildException(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->expectFailedBuild($target, function (\BuildException $e) use ($target): void {
			Assert::assertRegExp(sprintf('~%s.+not.+exist~', $target), $e->getMessage());
		});
	}

	/**
	 * @return mixed[][]|\Generator
	 */
	public function baseDirectoryDataProvider(): Generator
	{
		yield 'default base directory matches buildfile' => [
			'buildFilePath' => __DIR__ . '/phing-tester-integration-test.xml',
			'baseDirectoryPath' => null,
			'expectedLogMessageRegExp' => sprintf('~^basedir: %s$~', __DIR__),
		];

		yield 'custom base directory' => (static function (): array {
			$customBaseDir = realpath(__DIR__ . '/..');

			return [
				'buildFilePath' => __DIR__ . '/phing-tester-integration-test.xml',
				'baseDirectoryPath' => $customBaseDir,
				'expectedLogMessageRegExp' => sprintf('~^basedir: %s$~', $customBaseDir),
			];
		})();
	}

	/**
	 * @dataProvider baseDirectoryDataProvider
	 *
	 * @param string $buildFilePath
	 * @param string|null $baseDirectoryPath
	 * @param string $expectedLogMessageRegExp
	 */
	public function testBaseDirectory(
		string $buildFilePath,
		?string $baseDirectoryPath,
		string $expectedLogMessageRegExp
	): void
	{
		$tester = new PhingTester($buildFilePath, $baseDirectoryPath);
		$tester->executeTarget('base-directory');
		$tester->assertLogMessageRegExp($expectedLogMessageRegExp);
	}

	public function testGetCustomProjectProperties(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		Assert::assertSame('bar', $tester->getProject()->getProperty('foo'));
	}

}
