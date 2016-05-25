<?php

namespace Thruster\Component\PacketHandler;

use Thruster\Component\PacketHandler\Exception\ProviderNotFoundException;

/**
 * Class PacketHandler
 *
 * @package Thruster\Component\PacketHandler
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class PacketHandler
{
    /**
     * @var StreamHandler[]
     */
    private $providers;

    /**
     * @var array
     */
    private $handlers;

    /**
     * @var array
     */
    private $sortedHandlers;

    public function __construct()
    {
        $this->providers      = [];
        $this->handlers       = [];
        $this->sortedHandlers = [];
    }

    /**
     * @param StreamHandler $streamHandler
     * @param string $identifier
     *
     * @return PacketHandler
     */
    public function addProvider(StreamHandler $streamHandler, $identifier = null) : self
    {
        if (null === $identifier) {
            $this->providers[] = $streamHandler;
        } else {
            $this->providers[$identifier] = $streamHandler;
        }

        $streamHandler->on('package', function ($package) use ($streamHandler) {
            $this->onPackage($package, $streamHandler);
        });

        return $this;
    }

    public function removeProvider(StreamHandler $streamHandler = null, $identifier = null)
    {
        if (null === $streamHandler && null === $identifier) {
            return;
        }

        if (null === $identifier) {
            if (false !== ($key = array_search($streamHandler, $this->providers, true))) {
                unset($this->providers[$key]);
            }
        } else {
            if (false !== isset($this->providers[$identifier])) {
                unset($this->providers[$identifier]);
            }
        }
    }

    public function hasProvider($identifier) : bool
    {
        return isset($this->providers[$identifier]);
    }

    public function getProvider($identifier) : StreamHandler
    {
        if (false === $this->hasProvider($identifier)) {
            throw new ProviderNotFoundException($identifier);
        }

        return $this->providers[$identifier];
    }

    /**
     * @return StreamHandler[]
     */
    public function getProviders() : array
    {
        return $this->providers;
    }

    /**
     * @param PacketInterface $packet
     *
     * @return $this
     */
    public function dispatch(PacketInterface $packet)
    {
        foreach ($this->providers as $provider) {
            $provider->send($packet);
        }

        return $this;
    }

    /**
     * @param Package $package
     */
    public function onPackage(Package $package, StreamHandler $streamHandler)
    {
        /** @var PacketInterface $packet */
        $data = $package->getData();
        $packet = array_shift($data);

        if (false === ($packet instanceof PacketInterface)) {
            return;
        }

        $packet->setStream($package->getStream());
        $packet->setStreamHandler($streamHandler);

        $packetType = $packet->getName();

        if (false === isset($this->handlers[$packetType])) {
            return;
        }

        $this->doDispatch($this->getHandlers($packetType), $packet);
    }

    /**
     * @param string $packetType
     * @param bool   $withPriorities
     *
     * @return array
     */
    public function getHandlers(string $packetType = null, bool $withPriorities = false) : array
    {
        if (true === $withPriorities) {
            return $packetType ? $this->handlers[$packetType] : array_filter($this->handlers);
        }

        if (null !== $packetType) {
            if (!isset($this->sortedHandlers[$packetType])) {
                $this->sortHandlers($packetType);
            }

            return $this->sortedHandlers[$packetType];
        }

        foreach ($this->handlers as $packetType => $packetHandlers) {
            if (false === isset($this->sortedHandlers[$packetType])) {
                $this->sortHandlers($packetType);
            }
        }

        return array_filter($this->sortedHandlers);
    }

    /**
     * @param string $packetType
     *
     * @return bool
     */
    public function hasHandlers(string $packetType = null) : bool
    {
        return (bool) count($this->getHandlers($packetType));
    }

    /**
     * @param string   $packetType
     * @param callable $handler
     * @param int      $priority
     *
     * @return $this
     */
    public function addHandler(string $packetType, callable $handler, int $priority = 0) : self
    {
        $this->handlers[$packetType][$priority][] = $handler;

        unset($this->sortedHandlers[$packetType]);

        return $this;
    }

    /**
     * @param string   $packetType
     * @param callable $handler
     *
     * @return $this
     */
    public function removeHandler(string $packetType, callable $handler) : self
    {
        if (false === isset($this->handlers[$packetType])) {
            return $this;
        }

        foreach ($this->handlers[$packetType] as $priority => $handlers) {
            if (false !== ($key = array_search($handler, $handlers, true))) {
                unset($this->handlers[$packetType][$priority][$key], $this->sortedHandlers[$packetType]);
            }

            if (count($this->handlers[$packetType][$priority]) < 1) {
                unset($this->handlers[$packetType][$priority]);
            }
        }

        if (count($this->handlers[$packetType]) < 1) {
            unset($this->handlers[$packetType]);
        }

        return $this;
    }

    /**
     * @param PacketSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function addSubscriber(PacketSubscriberInterface $subscriber) : self
    {
        foreach ($subscriber->getSubscribedPackets() as $packetType => $params) {
            if (is_string($params)) {
                $this->addHandler($packetType, [$subscriber, $params]);
            } elseif (is_string($params[0])) {
                $this->addHandler($packetType, [$subscriber, $params[0]], $params[1] ?? 0);
            } else {
                foreach ($params as $handler) {
                    $this->addHandler($packetType, [$subscriber, $handler[0]], $handler[1] ?? 0);
                }
            }
        }

        return $this;
    }


    /**
     * @param PacketSubscriberInterface $subscriber
     *
     * @return $this
     */
    public function removeSubscriber(PacketSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedPackets() as $packetType => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $handler) {
                    $this->removeHandler($packetType, [$subscriber, $handler[0]]);
                }
            } else {
                $this->removeHandler($packetType, [$subscriber, is_string($params) ? $params : $params[0]]);
            }
        }
        return $this;
    }

    /**
     * @param array           $handlers
     * @param PacketInterface $packet
     */
    private function doDispatch($handlers, PacketInterface $packet)
    {
        foreach ($handlers as $handler) {
            call_user_func($handler, $packet, $this);

            if ($packet->isPropagationStopped()) {
                break;
            }
        }
    }

    /**
     * @param string $packetType
     */
    private function sortHandlers(string $packetType)
    {
        $this->sortedHandlers[$packetType] = [];

        if (isset($this->handlers[$packetType])) {
            krsort($this->handlers[$packetType]);
            $this->sortedHandlers[$packetType] = call_user_func_array('array_merge', $this->handlers[$packetType]);
        }
    }
}
