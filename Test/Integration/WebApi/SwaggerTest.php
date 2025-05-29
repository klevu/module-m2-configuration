<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Configuration\Test\Integration\WebApi;

use Magento\Webapi\Controller\Rest\SchemaRequestProcessorTest;

/**
 * @runTestsInSeparateProcesses
 */
class SwaggerTest extends SchemaRequestProcessorTest
{
    // pulling swagger tests into our testsuites to guard against regressions
}
