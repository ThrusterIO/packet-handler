<?php

namespace Thruster\Component\PacketHandler;

use Thruster\Component\EventEmitter\EventEmitterInterface;
use Thruster\Component\EventEmitter\EventEmitterTrait;
use Thruster\Component\PacketHandler\Exception\InvalidPackageReceivedException;
use Thruster\Component\Stream\Stream;

/**
 * Class StreamHandler
 *
 * @package Thruster\Component\PacketHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class StreamHandler implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var string
     */
    private $separator;

    /**
     * @var Stream
     */
    private $stream;

    /**
     * @var string
     */
    private $buffer;

    public function __construct(Stream $stream, string $separator = "\r\n\r\n")
    {
        $this->buffer = '';
        $this->stream = $stream;
        $this->separator = $separator;

        $this->stream->on('data', [$this, 'onData']);
    }

    public function send($data)
    {
        $package = new Package(func_get_args());

        $packageString = base64_encode(serialize($package)) . $this->separator;

        return $this->stream->write($packageString);
    }

    public function onData($data)
    {
        $this->buffer .= $data;

        if (preg_match('/' . $this->separator . '/', $this->buffer)) {
            $messages = explode($this->separator, $this->buffer);

            $this->buffer = array_pop($messages);

            foreach ($messages as $message) {
                /** @var Package $package */
                $package = unserialize(base64_decode($message));

                if ($package instanceof Package) {
                    $package->setStream($this->stream);

                    $this->emit('package', [$package]);
                } else {
                    throw new InvalidPackageReceivedException($package);
                }
            }
        }
    }

    /**
     * @return Stream
     */
    public function getStream()
    {
        return $this->stream;
    }
}
