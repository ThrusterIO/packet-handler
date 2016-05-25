<?php

namespace Thruster\Component\PacketHandler;

/**
 * Interface PacketSubscriberInterface
 *
 * @package Thruster\Component\PacketHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface PacketSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedPackets() : array;
}
