<?php

declare(strict_types = 1);

namespace VasekPurchart\Phing\PhingTester;

use Project;

class PhingTesterIntegrationTest extends \PHPUnit\Framework\TestCase
{

	public function testExecuteTarget(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$this->assertTrue(true); // build should not fail and reach this
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
			$this->assertRegExp(sprintf('~%s.+not.+exist~', $target), $e->getMessage());
		});
	}

	public function testDefaultBaseDirectoryMatchesBuildfile(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('base-directory');
		$tester->assertLogMessageRegExp(sprintf('~^basedir: %s$~', __DIR__));
	}

	public function testSetCustomBaseDirectory(): void
	{
		$customBaseDir = realpath(__DIR__ . '/..');

		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml', $customBaseDir);
		$tester->executeTarget('base-directory');
		$tester->assertLogMessageRegExp(sprintf('~^basedir: %s$~', $customBaseDir));
	}

	public function testGetCustomProjectProperties(): void
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$this->assertSame('bar', $tester->getProject()->getProperty('foo'));
	}

}
