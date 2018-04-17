<?php

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyWebsocketTopic extends Eole\Sandstone\Websocket\Topic implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'article.created' => 'onArticleCreated',
        ];
    }

    public function onArticleCreated(ArticleEvent $event)
    {
        // Broadcast message on this topic when an article has been created.
        $this->broadcast([
            'message' => 'An article has just been published: '.$event->title,
        ]);
    }
}
