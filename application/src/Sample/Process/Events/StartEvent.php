<?php
namespace Sample\Process\Events;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="start_event")
 */
class StartEvent extends ProcessEvent
{
    /**
     * @\Doctrine\ORM\Mapping\Column(type="datetime")
     */
    protected $started;

    /**
     * Whether the process started successfully
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean")
     */
    protected $success;

    /**
     * Set started
     *
     * @param datetime $started
     *
     * @return StartEvent
     */
    public function setStarted($started)
    {
        $this->started = $started;
        return $this;
    }
    
    /**
     * Get started
     *
     * @return date
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Set success
     *
     * @param datetime $success
     *
     * @return StartEvent
     */
    public function setSuccess($success)
    {
        $this->success = $success;
        return $this;
    }
    
    /**
     * Get success
     *
     * @return date
     */
    public function getSuccess()
    {
        return $this->success;
    }

}
