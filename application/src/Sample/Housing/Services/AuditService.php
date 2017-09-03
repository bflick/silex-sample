<?php
namespace Sample\Housing\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\Serializer;
use Eole\Sandstone\Websocket\Topic;
use Ratchet\Wamp\WampConnection;
use Sample\Housing\Entities\Dormatory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditService extends Topic implements EventSubscriberInterface
{
    private $serializer;
    private $entityManager;

    public function __construct($topic, EntityManager $entityManager, Serializer $serializer) {
        parent::__construct($topic);
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }
    /**
     * Broadcast message to each subscribing client.
     *
     * {@InheritDoc}
     */
    public function onPublish(WampConnection $conn, $topic, $event)
    {
        $dormRepo = $this->entityManager->getRepository(Dormatory::class);
        $message = $this->serializer->serialize($dormRepo->findAll(), 'json');
        $this->broadcast([
            'type' => 'message',
            'message' => $message,
        ]);
    }

    /**
     * Subscribe to update event.
     *
     * {@InheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'update' => 'onAuditMade',
        ];
    }

    /**
     * Audit listener.
     *
     * @param Audit $event
     */
    public function onAuditMade(Audit $event)
    {
        $this->broadcast([
            'type' => 'audit',
            'message' => 'An audit has been made to rooms: '.$event->getTitle(),
        ]);
        $titles = explode('|', $event->getTitle());
        foreach ($titles as $title) {
            $idArr = explode(',', $title);
            $update = new Update($idArr, $event->getContent());
        }
    }
}