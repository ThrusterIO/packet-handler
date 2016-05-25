<?php

namespace Thruster\Component\PacketHandler;

use Thruster\Component\Stream\Stream;

/**
 * Class Packet
 *
 * @package Thruster\Component\PacketHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Packet implements PacketInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var StreamHandler
     */
    protected $streamHandler;

    /**
     * @var bool
     */
    protected $propagationStopped;

    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
        $this->propagationStopped = false;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
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

    /**
     * @return StreamHandler
     */
    public function getStreamHandler() : StreamHandler
    {
        return $this->streamHandler;
    }

    /**
     * @param StreamHandler $streamHandler
     *
     * @return $this
     */
    public function setStreamHandler(StreamHandler $streamHandler)
    {
        $this->streamHandler = $streamHandler;

        return $this;
    }

    public function isPropagationStopped() : bool
    {
        return $this->propagationStopped;
    }

    /**
     * @return $this
     */
    public function stopPropagation() : self
    {
        $this->propagationStopped = true;

        return $this;
    }
}
