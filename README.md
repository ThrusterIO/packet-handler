# PacketHandler Component

[![Latest Version](https://img.shields.io/github/release/ThrusterIO/packet-handler.svg?style=flat-square)]
(https://github.com/ThrusterIO/packet-handler/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)]
(LICENSE)
[![Build Status](https://img.shields.io/travis/ThrusterIO/packet-handler.svg?style=flat-square)]
(https://travis-ci.org/ThrusterIO/packet-handler)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ThrusterIO/packet-handler.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/packet-handler)
[![Quality Score](https://img.shields.io/scrutinizer/g/ThrusterIO/packet-handler.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/packet-handler)
[![Total Downloads](https://img.shields.io/packagist/dt/thruster/packet-handler.svg?style=flat-square)]
(https://packagist.org/packets/thruster/packet-handler)

[![Email](https://img.shields.io/badge/email-team@thruster.io-blue.svg?style=flat-square)]
(mailto:team@thruster.io)

The Thruster PacketHandler Component.


## Install

Via Composer

```bash
$ composer require thruster/packet-handler
```

## Usage

```php
use Thruster\Component\EventLoop\EventLoop;
use Thruster\Component\Socket\SocketPair;
use Thruster\Component\PacketHandler\Packet;
use Thruster\Component\PacketHandler\PacketHandler;
use Thruster\Component\PacketHandler\StreamHandler;

class PingPacket extends Packet
{
    const NAME = 'ping';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }
}

class PongPacket extends Packet
{
    const NAME = 'pong';

    private $pid;

    public function __construct()
    {
        $this->pid = posix_getpid();

        parent::__construct(self::NAME);
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }
}

$loop = new EventLoop();

$socketPair = new SocketPair($loop);
$socketPair->create();

$packetHandler = new PacketHandler();
$packetHandler->addHandler(PingPacket::NAME, function (PingPacket $packet) {
    $packet->getStreamHandler()->send(new PongPacket());
});

$packetHandler->addHandler(PongPacket::NAME, function (PongPacket $packet) {
    echo posix_getpid() . ': Received PONG from ' . $packet->getPid() . PHP_EOL;
});

if (pcntl_fork() > 0) {
    $connection = $socketPair->useLeft();

    $packetHandler->addProvider(new StreamHandler($connection));

    $loop->addPeriodicTimer(2, function () use ($packetHandler) {
        $packetHandler->dispatch(new PingPacket());
    });

    $loop->run();

} else {
    $loop->afterFork();

    $connection = $socketPair->useRight();

    $packetHandler->addProvider(new StreamHandler($connection));

    $loop->run();
}
```

## Testing

```bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## License

Please see [License File](LICENSE) for more information.
