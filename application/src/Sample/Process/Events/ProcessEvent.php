<?php
namespace Sample\Process\Events;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\Event;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="process_event")
 * @\Doctrine\ORM\Mapping\InheritanceType("JOINED")
 * @\Doctrine\ORM\Mapping\DiscriminatorColumn(name="discr", type="string")
 * @\Doctrine\ORM\Mapping\DiscriminatorMap({
 *   "process" = "ProcessEvent", 
 *   "check" = "CheckEvent",
 *   "collect" = "CollectEvent",
 *   "start" = "StartEvent",
 *   "error" = "CollectEvent",
 *   "input" = "InputEvent"
 * })
 */
class ProcessEvent extends Event
{
    /**
     * @\Doctrine\ORM\Mapping\Id
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     * @\Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @\Doctrine\ORM\Mapping\Column(type="datetime")
     */
    protected $accessed;

    /**
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     */
    protected $pid;

    /**
     * Some UUID for secure access without knowledge of resource or $pid
     *
     * @\Doctrine\ORM\Mapping\Column(type="string", length=50)
     *
     * @var string $securePointer
     */
    protected $securePointer;

    /**
     * Set securePointer
     *
     * @param string $securePointer
     *
     * @return ProcessEvent
     */
    public function setSecurePointer($securePointer)
    {
        $this->securePointer = $securePointer;
    
        return $this;
    }

    /**
     * Get securePointer
     *
     * @return string
     */
    public function getSecurePointer()
    {
        return $this->securePointer;
    }

    /**
     * Set pid
     *
     * @param int $pid
     *
     * @return ProcessEvent
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }
    
    /**
     * Get pid
     *
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set accessed
     *
     * @param datetime $accessed
     *
     * @return ProcessEvent
     */
    public function setAccessed($accessed)
    {
        $this->accessed = $accessed;
    
        return $this;
    }

    /**
     * Get accessed
     *
     * @return date
     */
    public function getAccessed()
    {
        return $this->accessed;
    }
}
