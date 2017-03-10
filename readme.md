Phing Tester
============

[Phing](https://github.com/phingofficial/phing/) is a build system which you can extend by writing PHP code. This is really useful, especially for PHP projects so that you do not need any additional technologies and can potentially reuse existing code. But when you are writing such extensions, you should also test them. And because the nature of the build system is printing output, manipulating files and other "system" stuff, then writing isolated tests becomes an issue. Phing Tester should help with this task by providing a way to run Phing targets from PHPUnit tests as if they were run from the command line itself.

Usage
-----

With Phing Tester you can write a XML buildfile as usual with Phing and then run its targets from PHP code:

```xml
<?xml version="1.0" encoding="utf-8"?>
<!-- phing-tester-example.xml -->
<project name="PhingTesterExample" default="lorem-ipsum">

	<target name="lorem-ipsum">
		<echo>Lorem ipsum dolor sit amet</echo>
	</target>

</project>
```

```php
<?php

use VasekPurchart\Phing\PhingTester\PhingTester;

$tester = new PhingTester(__DIR__ . '/phing-tester-example.xml');
$tester->executeTarget('lorem-ipsum');
```

### Testing output

In the example above the target `lorem-ipsum` is executed in the context of the buildfile and then you can check whatever the target was supposed to achieve, in this case it should print out a message. For testing purposes it would be problematic if the actual output would be printed out, so there are Phing Tester method for output checking such as:

```php
<?php

$tester->assertLogMessage('Lorem ipsum');
```

`assertLogMessage` searches all log messages if any contains the given string. You can also search for log messages using regexp. It is handy to refine the results by specifying target which should produce the message and/or its priority.

### Testing errors

If the build fails a `\BuildException` is thrown as usual in Phing. If you only want to test the exception and its message you can use standard PHPUnits tools:

```php
<?php

$this->expectException(\BuildException::class);
$this->expectExceptionMessage('My error');
```

But if you want to also check the state after the target execution, this is not enough since the code in the test is interrupted by the exception. If you want to do that, use `expectFailedBuild` instead of `executeTarget` and you can continue with asserts:

```php
<?php

$tester->expectFailedBuild($target);

$tester->assertLogMessage('Fail message', $target, Project::MSG_ERR);
```

If you need to check the thrown exception thoroughly, you can pass a callback to `expectFailedBuild` and you will get the exception as parameter:

```php
<?php

$tester->expectFailedBuild($target, function (\BuildException $e) use ($target) {
	$this->assertRegExp(sprintf('~%s.+not.+exist~', $target), $e->getMessage());
});
```

### Testing properties

When writing Phing tests you might want to check the state of some properties or use them for writing other assertions:

```php
<?php

$this->assertSame('bar', $tester->getProject()->getProperty('foo'));
```

### Convention

There is no enforced convention for using this tool, you can use it in tests however you feel right, but this approach works for me in terms of readability the most:

1) Match one TestCase with one buildfile.
2) Targets in the buildfile represent individual tests and their name matches (if there is no need to reuse the targets or use more targets for one test).
3) When asserting log output, try to be more specific - use target name and output priority to minimalize false positives/negatives.

The pair can then look something like:

```xml
<?xml version="1.0" encoding="utf-8"?>
<!-- phing-tester-convention-example.xml -->
<project name="PhingTesterConventionExample" default="test">

	<target name="testFoo">
		<echo>Foo</echo>
	</target>

	<target name="testBar">
		<echo>Bar</echo>
	</target>

</project>
```

```php
<?php

use VasekPurchart\Phing\PhingTester\PhingTester;

class PhingTesterConventionExampleTest extends \PHPUnit\Framework\TestCase
{

	public function testFoo()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-convention-example.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$tester->assertLogMessage('Foo', $target, Project::MSG_INFO);
	}

	public function testBar()
	{
		$tester = new PhingTester(__DIR__ . '/phing-tester-convention-example.xml');
		$target = __FUNCTION__;
		$tester->executeTarget($target);

		$tester->assertLogMessage('Bar', $target, Project::MSG_INFO);
	}

}
```

Installation
------------

1) Install package [`vasek-purchart/phing-tester`](https://packagist.org/packages/vasek-purchart/phing-tester) with [Composer](https://getcomposer.org/):

```bash
composer require --dev vasek-purchart/phing-tester
```

2) Add Phing initialization to your tests bootstrap file ([example in this repo](tests/bootstrap.php)):

```php
Phing::startup();
```
