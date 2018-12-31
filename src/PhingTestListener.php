<?php

declare(strict_types = 1);

namespace VasekPurchart\Phing\PhingTester;

use BuildEvent;
use Project;

class PhingTestListener implements \BuildListener
{

	private const MESSAGE_BUILD_FAILED = 'BUILD FAILED';
	private const MESSAGE_BUILD_SUCCESSFUL = 'BUILD FINISHED';

	/** @var \BuildEvent[] */
	private $logs;

	public function buildStarted(BuildEvent $event): void
	{
		// empty
	}

	public function buildFinished(BuildEvent $event): void
	{
		$buildSuccessful = $event->getException() === null;
		$message = $buildSuccessful
			? self::MESSAGE_BUILD_SUCCESSFUL
			: sprintf('%s: %s', self::MESSAGE_BUILD_FAILED, $event->getException()->getMessage());
		$verbosity = $buildSuccessful ? Project::MSG_VERBOSE : Project::MSG_ERR;

		$message = PHP_EOL . $message . PHP_EOL;

		$event->setMessage($message, $verbosity);

		$this->messageLogged($event);
	}

	public function targetStarted(BuildEvent $event): void
	{
		// empty
	}

	public function targetFinished(BuildEvent $event): void
	{
		// empty
	}

	public function taskStarted(BuildEvent $event): void
	{
		// empty
	}

	public function taskFinished(BuildEvent $event): void
	{
		// empty
	}

	public function messageLogged(BuildEvent $event): void
	{
		$this->logs[] = $event;
	}

	/**
	 * @return \BuildEvent[]
	 */
	public function getLogs(): array
	{
		return $this->logs;
	}

}
