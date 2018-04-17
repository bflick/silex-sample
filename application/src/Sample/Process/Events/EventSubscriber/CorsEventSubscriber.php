<?php
namespace Sample\Process\Events\EventSubscriber;

use CorsHelper\CorsListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsEventSubscriber extends CorsListener implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 10000),
        );
    }
}
