<?php

namespace Thruster\Component\PacketHandler\Tests;

use Thruster\Component\PacketHandler\Packet;
use Thruster\Component\PacketHandler\StreamHandler;

/**
 * Class PacketTest
 *
 * @package Thruster\Component\PacketHandler\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class PacketTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Packet
     */
    protected $packet;

    public function setUp()
    {
        $this->packet = new Packet('foobar', ['foo' => 'bar']);
    }

    public function testSimple()
    {
        $this->assertSame('foobar', $this->packet->getName());

        $this->assertEquals(['foo' => 'bar'], $this->packet->getData());

        $stream = $this->getMockBuilder('Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $this->packet->setStream($stream);

        $streamHandler = new StreamHandler($stream);

        $this->packet->setStreamHandler($streamHandler);

        $this->assertEquals($streamHandler, $this->packet->getStreamHandler());

        $this->assertEquals($stream, $this->packet->getStream());
        $this->assertFalse($this->packet->isPropagationStopped());
        $this->packet->stopPropagation();
        $this->assertTrue($this->packet->isPropagationStopped());
    }
}
