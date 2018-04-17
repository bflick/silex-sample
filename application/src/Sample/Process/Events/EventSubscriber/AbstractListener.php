<?php

namespace Sample\Process\Events\EventSubscriber;

use Eole\Sandstone\Websocket\Topic;
use Eole\Sandstone\Websocket\Event\ConnectionEvent;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\Serializer;
use Ratchet\Wamp\WampConnection;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractListener extends Topic implements EventSubscriberInterface
{
    /**
     * Default constructor takes a topic name
     */

    protected abstract function _processPublishEvent($topic, Event $event);

    /**
     * Broadcast message to each subscribing client.
     *
     * {@InheritDoc}
     */
    public function onPublish(WampConnection $conn, $topic, $event)
    {
        //        $message = $this->_processPublishEvent($topic, $event);
        $this->broadcast([
            'type' => 'message',
            'message' => $event,
        ]);
    }

    /**
     * Subscribe to update event.
     *
     * {@InheritDoc}
     */
    public static function getSubscribedEvents()
    {
        $events = [
            ConnectionEvent::ON_OPEN => 'onOpen',
            ConnectionEvent::ON_CLOSE => 'onClose',
            ConnectionEvent::ON_AUTHENTICATION => 'onAuth',
            ConnectionEvent::ON_ERROR => 'onError',
            ConnectionEvent::ON_SUBSCRIBE => 'onSub',
            ConnectionEvent::ON_OPEN => 'onUnsub',
            ConnectionEvent::ON_RPC => 'onRpc'
       ];
       return $events;
    }

    // do something when someone connects
    // retrieve some connection data with $event->getConn()->Websocket;
    // which returns a ConnectionInterface from RatchetPHP
    public abstract function onOpen(ConnectionEvent $event);
    // Retrieve authenticated user with $event->getUser() (returns Symfony UserInterface)
    public abstract function onClose(ConnectionEvent $event);
    // Retrieve authenticated user with $event->getUser() (returns Symfony UserInterface)
    public abstract function onAuthentication(ConnectionEvent $event);
    // 	Retrieve the raised exception with $event->getError() (Returns an \Exception)    
    public abstract function onError(ConnectionEvent $event);
    // Know which topic has been subscribed with $event->getTopic()
    public abstract function onSub(ConnectionEvent $event);
    // Know which topic has been unsubscribed with $event->getTopic()
    public abstract function onUnsub(ConnectionEvent $event);
        // 	Retrieve remote procedure call ID and parameters with $event->getId() and $event->getParams()
    public abstract function onRpc(ConnectionEvent $event);
}