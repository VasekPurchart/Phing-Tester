<?php

declare(strict_types = 1);

namespace VasekPurchart\Phing\PhingTester;

use BuildEvent;
use Closure;
use PhingFile;

use PHPUnit\Framework\Assert;

use Project;
use ProjectConfigurator;

class PhingTester
{

	/** @var \Project */
	private $project;

	/** @var \VasekPurchart\Phing\PhingTester\PhingTestListener */
	private $phingTestListener;

	public function __construct(string $buildFilePath, string $baseDirectoryPath = null)
	{
		$this->project = new Project();
		$this->project->init();

		$buildFile = new PhingFile($buildFilePath);
		$this->project->setUserProperty('phing.file', $buildFile->getAbsolutePath());
		$this->project->setUserProperty('phing.dir', dirname($buildFile->getAbsolutePath()));

		if ($baseDirectoryPath !== null) {
			$this->project->setBasedir($baseDirectoryPath);
		}

		$this->phingTestListener = new PhingTestListener($this);
		$this->project->addBuildListener($this->phingTestListener);

		ProjectConfigurator::configureProject($this->project, new PhingFile($buildFilePath));
	}

	public function executeTarget(string $targetName)
	{
		try {
			$this->project->fireBuildStarted();
			$this->project->executeTarget($targetName);
			$this->project->fireBuildFinished(null);
		} catch (\Exception $e) {
			$this->project->fireBuildFinished($e);
			throw $e;
		}
	}

	public function assertLogMessage(
		string $message,
		string $targetName = null,
		int $priority = Project::MSG_INFO
	)
	{
		$this->assertInLogs(function (BuildEvent $logBuildEvent) use ($message) {
			return strpos($logBuildEvent->getMessage(), $message) !== false;
		}, $message, $targetName, $priority);
	}

	public function assertLogMessageRegExp(
		string $messagePattern,
		string $targetName = null,
		int $priority = Project::MSG_INFO
	)
	{
		$this->assertInLogs(function (BuildEvent $logBuildEvent) use ($messagePattern) {
			return preg_match($messagePattern, $logBuildEvent->getMessage());
		}, $messagePattern, $targetName, $priority);
	}

	/**
	 * @param \Closure $messageAssert callback(\BuildEvent)
	 * @param string $messageErrorSnippet
	 * @param string|null $targetName
	 * @param integer $priority
	 */
	private function assertInLogs(
		Closure $messageAssert,
		string $messageErrorSnippet,
		$targetName,
		int $priority
	)
	{
		$this->findInLogs(
			$messageAssert,
			$targetName,
			$priority,
			function () {
				Assert::assertTrue(true); // increase number of positive assertions
			},
			function () use ($messageErrorSnippet) {
				Assert::fail(
					sprintf('Message >>> %s <<< not found in logs:', $messageErrorSnippet)
					. PHP_EOL
					. PHP_EOL
					. $this->formatLogsForOutput($this->phingTestListener->getLogs())
				);
			}
		);
	}

	public function assertNotLogMessage(
		string $message,
		string $targetName = null,
		int $priority = Project::MSG_DEBUG
	)
	{
		$this->assertNotInLogs(function (BuildEvent $logBuildEvent) use ($message) {
			return strpos($logBuildEvent->getMessage(), $message) !== false;
		}, $message, $targetName, $priority);
	}

	public function assertNotLogMessageRegExp(
		string $messagePattern,
		string $targetName = null,
		int $priority = Project::MSG_DEBUG
	)
	{
		$this->assertNotInLogs(function (BuildEvent $logBuildEvent) use ($messagePattern) {
			return preg_match($messagePattern, $logBuildEvent->getMessage());
		}, $messagePattern, $targetName, $priority);
	}

	/**
	 * @param \Closure $messageAssert callback(\BuildEvent)
	 * @param string $messageErrorSnippet
	 * @param string|null $targetName
	 * @param integer $priority
	 */
	private function assertNotInLogs(
		Closure $messageAssert,
		string $messageErrorSnippet,
		$targetName,
		int $priority
	)
	{
		$this->findInLogs(
			$messageAssert,
			$targetName,
			$priority,
			function (BuildEvent $logBuildEvent) use ($messageErrorSnippet) {
				Assert::fail(
					sprintf(
						'Message >>> %s <<< found in logs, but >>> %s <<< was not expected to be found. Complete logs:',
						$logBuildEvent->getMessage(),
						$messageErrorSnippet
					)
					. PHP_EOL
					. PHP_EOL
					. $this->formatLogsForOutput($this->phingTestListener->getLogs())
				);
			},
			function () {
				Assert::assertTrue(true); // increase number of positive assertions
			}
		);
	}

	/**
	 * @param \Closure $messageAssert callback(\BuildEvent)
	 * @param string|null $targetName
	 * @param integer $priority
	 * @param \Closure $foundCallback callback(\BuildEvent)
	 * @param \Closure $notFoundCallback
	 */
	private function findInLogs(
		Closure $messageAssert,
		$targetName,
		int $priority,
		Closure $foundCallback,
		Closure $notFoundCallback
	)
	{
		foreach ($this->phingTestListener->getLogs() as $logBuildEvent) {
			if (
				(
					$targetName === null
					|| ($logBuildEvent->getTarget() !== null && $logBuildEvent->getTarget()->getName() === $targetName)
				)
				&& ($priority === null || $logBuildEvent->getPriority() <= $priority)
				&& $messageAssert($logBuildEvent)
			) {
				$foundCallback($logBuildEvent);
				return;
			}
		}

		$notFoundCallback();
	}

	/**
	 * @codeCoverageIgnore only used during error output in tests, which can't be tested
	 *
	 * @param \BuildEvent[] $logBuildEvents
	 * @return string
	 */
	private function formatLogsForOutput(array $logBuildEvents): string
	{
		return implode(PHP_EOL, array_map(function (BuildEvent $logBuildEvent) {
			return ($logBuildEvent->getTask() !== null
				? sprintf('[%s] ', $logBuildEvent->getTask()->getTaskName())
				: '')
				. $logBuildEvent->getMessage();
		}, $logBuildEvents));
	}

	/**
	 * @param string $targetName
	 * @param \Closure|null $checksCallback callback(\BuildException)
	 */
	public function expectFailedBuild(string $targetName, Closure $checksCallback = null)
	{
		try {
			$this->executeTarget($targetName);
			// @codeCoverageIgnoreStart
			// affects PHPUnit global state
			Assert::fail(sprintf('Expected build of target "%s" to fail', $targetName));
			// @codeCoverageIgnoreEnd
		} catch (\BuildException $e) {
			if ($checksCallback !== null) {
				$checksCallback($e);
			} else {
				Assert::assertTrue(true); // increase number of positive assertions
			}
		}
	}

	public function getProject(): Project
	{
		return $this->project;
	}

}
