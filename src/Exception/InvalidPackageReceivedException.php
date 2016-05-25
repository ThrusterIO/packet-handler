<?php

namespace Thruster\Component\PacketHandler\Exception;

use Exception;

/**
 * Class InvalidPackageReceivedException
 *
 * @package Thruster\Component\PacketHandler\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class InvalidPackageReceivedException extends \Exception
{
    /**
     * @inheritDoc
     */
    public function __construct($package)
    {
        if (is_object($package)) {
            $message = sprintf(
                'Received object of class "%s" instead of Package',
                get_class($package)
            );
        } else {
            $message = sprintf(
                'Received "%s" instead of object of Package',
                gettype($package)
            );
        }

        parent::__construct($message);
    }

}
