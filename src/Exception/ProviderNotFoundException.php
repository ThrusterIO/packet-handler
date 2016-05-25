<?php

namespace Thruster\Component\PacketHandler\Exception;

use Exception;

/**
 * Class ProviderNotFoundException
 *
 * @package Thruster\Component\PacketHandler\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ProviderNotFoundException extends Exception
{
    /**
     * @inheritDoc
     */
    public function __construct($name)
    {
        $message = sprintf(
            'Provider "%s" not found',
            $name
        );

        parent::__construct($message);
    }

}
