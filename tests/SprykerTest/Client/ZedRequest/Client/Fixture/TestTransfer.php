<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\ZedRequest\Client\Fixture;

use Spryker\Shared\Kernel\Transfer\AbstractTransfer;

class TestTransfer extends AbstractTransfer
{
    /**
     * @var string
     */
    public const FOO = 'foo';

    /**
     * @var string
     */
    protected $foo;

    /**
     * @var array
     */
    protected $transferPropertyNameMap = [
        'foo' => 'foo',
        'Foo' => 'foo',
    ];

    /**
     * @var array
     */
    protected $transferMetadata = [
        self::FOO => [
            'type' => 'string',
            'name_underscore' => 'foo',
            'is_collection' => false,
            'is_transfer' => false,
        ],
    ];

    /**
     * @param string $foo
     *
     * @return $this
     */
    public function setFoo(string $foo)
    {
        $this->foo = $foo;
        $this->modifiedProperties[static::FOO] = true;

        return $this;
    }

    /**
     * @return string
     */
    public function getFoo(): string
    {
        return $this->foo;
    }

    /**
     * @return $this
     */
    public function requireFoo()
    {
        $this->assertPropertyIsSet(static::FOO);

        return $this;
    }
}
