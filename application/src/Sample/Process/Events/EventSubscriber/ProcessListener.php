<?php
namespace Sample\Process\Events\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Eole\Sandstone\Websocket\Event\ConnectionEvent;
use Sample\Process\Events;
use Sample\Process\Exception;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Tools\ProcessInterface;
use Tools\ProcOpenProcess;

class ProcessListener extends AbstractListener implements ProcessInterface
{
    private $serializer;
    private $entityManager;
    private $logger;

    public function log($str) {
        $this->logger->debug($str);
    }

    public function __construct($topic, EntityManager $entityManager, Serializer $serializer, LoggerInterface $logger) {
        parent::__construct($topic);
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        $events = parent::getSubscribedEvents();
        return array_merge($events, [
            'check' => 'onCheck',
            'collect' => 'onCollect',
            'input' => 'onInput',
            'errors' => 'onCollectErrors',
            'process' => 'onStartProcess',
        ]);
    }

    /**
     * Default constructor takes a topic name
     */
    public function onCheck(CheckEvent $event)
    {
        $this->process = $this->findProcess($event);
        $message = $this->serializer->serialize($this->check());
        $this->broadcast([
            'type' => 'check',
            'message' => $message
        ]);
        $this->log($event);
    }

    public function onCollect(CollectEvent $event)
    {
        $this->process = $this->findProcess($event);
        $message = $this->serializer->serialize($this->collect());
        $this->broadcast([
            'type' => 'collect',
            'message' => $message
        ]);
        $this->log($event);
    }

    public function onInput(InputEvent $event)
    {
        $this->process = $this->findProcess($event);
        $input = $event->getInput();
        $message = $this->serializer->serialize($this->input($input));
        $this->broadcast([
            'type' => 'input',
            'message' => $message
        ]);
    }

    public function onCollectErrors(CollectEvent $event)
    {
        $this->process = $this->findProcess($event);
        $message = $this->serializer->serialize($this->printErr());
        $this->broadcast([
            'type' => 'error',
            'message' => $message
        ]);
    }

    public function onStart(StartEvent $event)
    {
        $this->process = $this->findProcess($event);
        $this->start();
        $status = $this->process->wait();
        if (is_bool($status)) {
            $addendum = ' started successfully';
        } else {
            $addendum = ' failed to start';
        }
        $this->broadcast([
            'type' => 'start',
            'message' => 'Process ' . $process->getPid() . $addendum
        ]);
    }

    public function check()
    {
        return ['success' => $this->process->check()];
    }

    public function collect($timeout=null)
    {
        // @todo maybe more?
        return $this->process->collect($timeout);
    }

    public function input($input)
    {
        $stream = fopen('php://memory','r+');
        fwrite($stream, $input);
        rewind($stream);
        return ['success' => $this->process->input($stream)];
    }

    public function printErr()
    {
        $stream = fopen('php://memory', 'r+');
        $this->process->printErr($stream);
        rewind($stream);
        return ['errors' => stream_get_contents($stream)];
    }

    public function start()
    {
        $bt = debug_backtrace();
        if ($bt[0]['function'] !== 'onStart') {
            // @todo check its from this object too
            // just because interface has them public
            throw new BadMethodCallException('should only be called from onStart');
        }
        $this->process->start();
    }

    public function wait()
    {
        // just here to fulfill the interface
        throw new BadMethodCallException('method unnecessary');
    }

    public function findProcess(ProcessEvent $event)
    {
        // @todo use a service to contain all processes
        if ($pid = $event->getPid()) {
            foreach (ProcOpenProcess::CheckProcessesHaveExited() as $key => $status) {
                if ($pid == explode(ProcOpenProcess::KEYSPLIT, $key)) {
                    $exited = true;
                }
            }
        }
        $ptr = $event->getSecurePointer();
        throw new BadMethodCallException('Not implemented yet.');
    }

    protected function _processPublishEvent($topic, Event $event)
    {
        
    }

    public function onOpen(ConnectionEvent $event)
    {
        
    }

    public function onClose(ConnectionEvent $event)
    {
        
    }

    public function onAuthentication(ConnectionEvent $event)
    {

    }

    public function onError(ConnectionEvent $event)
    {

    }

    public function onSub(ConnectionEvent $event)
    {

    }

    public function onUnsub(ConnectionEvent $event)
    {

    }

    public function onRpc(ConnectionEvent $event)
    {

    }
}