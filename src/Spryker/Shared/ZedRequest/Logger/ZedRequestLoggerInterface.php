<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ZedRequest\Logger;

interface ZedRequestLoggerInterface
{
    public function log(string $url, array $payload, array $result, array $debug = []): void;

    public function getLogs(): array;
}
