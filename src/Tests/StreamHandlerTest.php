<?php

namespace Thruster\Component\PacketHandler\Tests;

use Thruster\Component\PacketHandler\Package;
use Thruster\Component\PacketHandler\StreamHandler;

/**
 * Class StreamHandlerTest
 *
 * @package Thruster\Component\PacketHandler\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class StreamHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreation()
    {
        $streamMock = $this->getMockBuilder('\Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $streamHandler = new StreamHandler($streamMock);

        $this->assertEquals($streamMock, $streamHandler->getStream());
    }

    public function testSendMethod()
    {
        $streamMock = $this->getMockBuilder('\Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->setMethods(['write'])
            ->getMock();

        $streamHandler = new StreamHandler($streamMock, '$$$$$');

        $data      = new \stdClass();
        $data->foo = 'bar';

        $streamMock->expects($this->once())
            ->method('write')
            ->will(
                $this->returnCallback(
                    function ($input) use ($data) {
                        $expected = base64_encode(serialize(new Package([$data]))) . "$$$$$";

                        $this->assertSame($expected, $input);

                        $input = unserialize(base64_decode($input));

                        $this->assertInstanceOf('\Thruster\Component\PacketHandler\Package', $input);

                        $this->assertEquals([$data], $input->getData());

                        return true;
                    }
                )
            );

        $this->assertTrue($streamHandler->send($data));
    }

    public function testReceive()
    {
        $streamMock = $this->getMockBuilder('\Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $packageHandler = new StreamHandler($streamMock);
        $packageHandler->on('package', $this->expectCallableExactly(3));

        $expectedMessages = ['foo', 'bar', 'kitty'];

        foreach ($expectedMessages as $expectedMessage) {
            $package       = new Package([$expectedMessage]);
            $packageString = base64_encode(serialize($package)) . "\r\n\r\n";

            for ($i = 0; $i < strlen($packageString);) {
                $partSize = mt_rand(1, strlen($packageString) - $i);
                $partPackage = substr($packageString, $i, $partSize);

                $i += $partSize;

                $streamMock->emit('data', [$partPackage]);
            }
        }
    }

    /**
     * @expectedException \Thruster\Component\PacketHandler\Exception\InvalidPackageReceivedException
     * @expectedExceptionMessage Received object of class "stdClass" instead of Package
     */
    public function testReceiveInvalid()
    {
        $streamMock = $this->getMockBuilder('\Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $packageHandler = new StreamHandler($streamMock);

        $package       = new \stdClass();
        $packageString = base64_encode(serialize($package)) . "\r\n\r\n";

        $streamMock->emit('data', [$packageString]);
    }

    /**
     * @expectedException \Thruster\Component\PacketHandler\Exception\InvalidPackageReceivedException
     * @expectedExceptionMessage Received "string" instead of object of Package
     */
    public function testReceiveInvalidType()
    {
        $streamMock = $this->getMockBuilder('\Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $packageHandler = new StreamHandler($streamMock);

        $package       = 'asdasdas';
        $packageString = base64_encode(serialize($package)) . "\r\n\r\n";

        $streamMock->emit('data', [$packageString]);
    }

    private function expectCallableExactly($amount)
    {
        $mock = $this->createCallableMock();

        $mock->expects($this->exactly($amount))
            ->method('someMethod');

        return [$mock, 'someMethod'];
    }

    private function createCallableMock()
    {
        return $this->getMock(__CLASS__);
    }

    public function someMethod()
    {
    }
}
