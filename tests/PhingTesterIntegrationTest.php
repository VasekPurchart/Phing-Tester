<?php

declare(strict_types = 1);

namespace VasekPurchart\Phing\PhingTester;

use Project;

class PhingTesterIntegrationTest extends \PHPUnit\Framework\TestCase
{

	public function testExecuteTarget()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$this->assertTrue(true); // build should not fail and reach this
	}

	public function testMessageInLogs()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertLogMessage('Lorem ipsum');
	}

	public function testMessageInLogsWithRegExp()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertLogMessageRegExp('~lorem.+amet~i');
	}

	public function testMessageNotInLogs()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertNotLogMessage('Lorem XXX');
	}

	public function testMessageRegExpNotInLogs()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('lorem-ipsum');

		$tester->assertNotLogMessageRegExp('~lorem.+XXX.+amet~i');
	}

	public function testMessageInLogsFromCustomTarget()
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

	public function testMessageInLogsWithCustomPriority()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$tester->assertLogMessage('Lorem ipsum', null, Project::MSG_DEBUG);
		$tester->assertNotLogMessage('Lorem ipsum', null, Project::MSG_VERBOSE);
	}

	public function testFailBuild()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->expectFailedBuild($target);
	}

	public function testFailBuildWithMessage()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->expectFailedBuild($target);

		$tester->assertLogMessage('Fail message', $target, Project::MSG_DEBUG);
	}

	public function testFailBuildCheckBuildException()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->expectFailedBuild($target, function (\BuildException $e) use ($target) {
			$this->assertRegExp(sprintf('~%s.+not.+exist~', $target), $e->getMessage());
		});
	}

	public function testDefaultBaseDirectoryMatchesBuildfile()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$tester->executeTarget('base-directory');
		$tester->assertLogMessageRegExp(sprintf('~^basedir: %s$~', __DIR__));
	}

	public function testSetCustomBaseDirectory()
	{
		$customBaseDir = realpath(__DIR__ . '/..');

		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml', $customBaseDir);
		$tester->executeTarget('base-directory');
		$tester->assertLogMessageRegExp(sprintf('~^basedir: %s$~', $customBaseDir));
	}

	public function testGetCustomProjectProperties()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-integration-test.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$this->assertSame('bar', $tester->getProject()->getProperty('foo'));
	}

}
