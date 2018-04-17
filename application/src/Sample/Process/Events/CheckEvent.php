<?php
namespace Sample\Process\Events;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="check_event")
 */
class CheckEvent extends ProcessEvent
{
    /**
     * Whether the process is finished
     *
     * @\Doctrine\ORM\Mapping\Column(type="boolean")
     */
    protected $exited;

    /**
     * Whether the process is finished
     *
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     */
    protected $exitCode;

        
    /**
     * Set exited
     *
     * @param datetime $exited
     *
     * @return CheckEvent
     */
    public function setExited($exited)
    {
        $this->exited = $exited;
        return $this;
    }
    
    /**
     * Get exited
     *
     * @return date
     */
    public function getExited()
    {
        return $this->exited;
    }
    
    /**
     * Set exitCode
     *
     * @param datetime $exitCode
     *
     * @return CheckEvent
     */
    public function setExitCode($exitCode)
    {
        $this->exitCode = $exitCode;
        return $this;
    }
    
    /**
     * Get exitCode
     *
     * @return date
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }
}