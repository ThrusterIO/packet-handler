<?php

namespace Thruster\Component\PacketHandler;

use Thruster\Component\Stream\Stream;

/**
 * Interface PacketInterface
 *
 * @package Thruster\Component\PacketHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface PacketInterface
{
    public function getName() : string;

    public function setStream(Stream $stream);

    public function getStream() : Stream;

    public function setStreamHandler(StreamHandler $stream);

    public function getStreamHandler() : StreamHandler;

    public function isPropagationStopped() : bool;
}
