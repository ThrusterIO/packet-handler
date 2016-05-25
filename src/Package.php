<?php

namespace Thruster\Component\PacketHandler;

use Thruster\Component\Stream\Stream;

/**
 * Class Package
 *
 * @package Thruster\Component\PacketHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Package
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var Stream
     */
    private $stream;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @return Stream
     */
    public function getStream() : Stream
    {
        return $this->stream;
    }

    /**
     * @param Stream $stream
     *
     * @return $this
     */
    public function setStream(Stream $stream)
    {
        $this->stream = $stream;

        return $this;
    }
}
