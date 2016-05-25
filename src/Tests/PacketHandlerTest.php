<?php

namespace Thruster\Component\PacketHandler\Tests;

use Thruster\Component\PacketHandler\Package;
use Thruster\Component\PacketHandler\Packet;
use Thruster\Component\PacketHandler\PacketHandler;
use Thruster\Component\PacketHandler\PacketSubscriberInterface;
use Thruster\Component\PacketHandler\StreamHandler;

/**
 * Class PacketHandlerTest
 *
 * @package Thruster\Component\PacketHandler\Tests
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class PacketHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PacketHandler
     */
    protected $packetHandler;

    public function setUp()
    {
        $this->packetHandler = new PacketHandler();
    }



    public function testAddProvider()
    {
        $mock = $this->getMockBuilder('\Thruster\Component\PacketHandler\StreamHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->packetHandler->addProvider($mock);
        $this->assertEquals($mock, $this->packetHandler->getProvider(0));

        $this->assertCount(1, $this->packetHandler->getProviders());
    }

    public function testAddProviderIdentifier()
    {
        $mock = $this->getMockBuilder('\Thruster\Component\PacketHandler\StreamHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->packetHandler->addProvider($mock, 'a');
        $this->assertEquals($mock, $this->packetHandler->getProvider('a'));

        $this->assertCount(1, $this->packetHandler->getProviders());
        $this->assertArrayHasKey('a', $this->packetHandler->getProviders());
    }

    public function testRemoveProvider()
    {
        $mock = $this->getMockBuilder('\Thruster\Component\PacketHandler\StreamHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->packetHandler->addProvider($mock);

        $this->assertCount(1, $this->packetHandler->getProviders());

        $this->packetHandler->removeProvider($mock);

        $this->assertCount(0, $this->packetHandler->getProviders());
    }

    public function testRemoveProviderWithoutArgs()
    {
        $mock = $this->getMockBuilder('\Thruster\Component\PacketHandler\StreamHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->packetHandler->addProvider($mock);

        $this->assertCount(1, $this->packetHandler->getProviders());

        $this->packetHandler->removeProvider();

        $this->assertCount(1, $this->packetHandler->getProviders());
    }

    /**
     * @expectedException \Thruster\Component\PacketHandler\Exception\ProviderNotFoundException
     * @expectedExceptionMessage Provider "foobar" not found
     */
    public function testGetProviderNotFound()
    {
        $this->packetHandler->getProvider('foobar');
    }

    public function testRemoveProviderIdentifier()
    {
        $mock = $this->getMockBuilder('\Thruster\Component\PacketHandler\StreamHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->packetHandler->addProvider($mock, 'a');
        $this->assertEquals($mock, $this->packetHandler->getProvider('a'));

        $this->assertCount(1, $this->packetHandler->getProviders());
        $this->assertArrayHasKey('a', $this->packetHandler->getProviders());


        $this->packetHandler->removeProvider(null, 'a');

        $this->assertCount(0, $this->packetHandler->getProviders());
        $this->assertArrayNotHasKey('a', $this->packetHandler->getProviders());
    }

    public function testReceivedPackageNotForPackageHandler()
    {
        $packet = new Package([]);

        $this->packetHandler->onPackage(
            $packet,
            new StreamHandler(
                $this->getMockBuilder('\Thruster\Component\Stream\Stream')
                    ->disableOriginalConstructor()
                    ->getMock()
            )
        );
    }

    public function testReceivedPackageWithoutHandler()
    {
        $streamMock = $this->getMockBuilder('\Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $packet = $this->getMockBuilder('\Thruster\Component\PacketHandler\Packet')
            ->setConstructorArgs(['foo_bar', ['foo' => 'bar']])
            ->setMethods(['setStream'])
            ->getMock();

        $packet->expects($this->once())
            ->method('setStream')
            ->with($streamMock);

        $package = new Package([$packet]);
        $package->setStream($streamMock);

        $this->packetHandler->onPackage($package, new StreamHandler($streamMock));
    }

    public function testReceivedPackage()
    {
        $streamMock = $this->getMockBuilder('\Thruster\Component\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $packet = $this->getMockBuilder('\Thruster\Component\PacketHandler\Packet')
            ->setConstructorArgs(['foo_bar', ['foo' => 'bar']])
            ->setMethods(['setStream'])
            ->getMock();

        $packet->expects($this->once())
            ->method('setStream')
            ->with($streamMock);

        $package = new Package([$packet]);
        $package->setStream($streamMock);

        $this->packetHandler->addHandler($packet->getName(), $this->expectCallableExactly(1));

        $this->packetHandler->onPackage($package, new StreamHandler($streamMock));
    }

    public function testAddHasRemoveHandler()
    {
        $mock = $this->getMockBuilder(__CLASS__)
            ->getMock();

        $callback = $this->getCallableMock();

        $this->assertFalse($this->packetHandler->hasHandlers());
        $this->packetHandler->addHandler('foo', $callback);
        $this->assertTrue($this->packetHandler->hasHandlers());

        $this->assertEquals(
            [
                'foo' => [
                    0 => [
                        $callback,
                    ],
                ],
            ],
            $this->packetHandler->getHandlers(null, true)
        );

        $this->packetHandler->removeHandler('foo', $callback);
        $this->packetHandler->removeHandler('foo', $callback);
        $this->assertFalse($this->packetHandler->hasHandlers());
    }

    public function testAddAndRemoveSubscribers()
    {
        $subscriber = $this->getSubscriber();
        $this->packetHandler->addSubscriber($subscriber);

        $this->assertEquals(
            [
                'foo' => [0 => [[$subscriber, 'foo']]],
                'rab' => [0 => [[$subscriber, 'rab']]],
                'bar' => [
                    0 => [[$subscriber, 'bar']],
                    10 => [[$subscriber, 'rab']]
                ]
            ],
            $this->packetHandler->getHandlers(null, true)
        );

        $this->assertTrue($this->packetHandler->hasHandlers());
        $this->packetHandler->removeSubscriber($subscriber);
        $this->assertFalse($this->packetHandler->hasHandlers());
    }

    public function testDispatch()
    {
        $streamHandler = $this->getMockBuilder('\Thruster\Component\PacketHandler\StreamHandler')
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();

        $packet = new Packet('foo_bar', ['foo' => 'bar']);

        $streamHandler->expects($this->once())
            ->method('send')
            ->with($packet);

        $this->packetHandler->addProvider($streamHandler);

        $this->packetHandler->dispatch($packet);
    }

    public function getSubscriber()
    {
        return new class implements PacketSubscriberInterface {
            /**
             * @inheritDoc
             */
            public static function getSubscribedPackets() : array
            {
                return [
                    'foo' => 'foo',
                    'rab' => ['rab'],
                    'bar' => [
                        ['bar'],
                        ['rab', 10]
                    ]
                ];
            }

            public function foo()
            {
            }

            public function bar()
            {
            }

            public function rab()
            {
            }
        };
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

    private function getCallableMock()
    {
        $mock = $this->createCallableMock();

        return [$mock, 'someMethod'];
    }

    public function someMethod()
    {
    }
}
