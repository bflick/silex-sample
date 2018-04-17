<?php
namespace Sample\Process\Events;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="collect_event")
 */
class CollectEvent extends ProcessEvent
{
    /**
     * @\Doctrine\ORM\Mapping\Column(type="text")
     */
    protected $output;

    /**
     * Set output
     *
     * @param string $output
     *
     * @return Audit
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }
    
    /**
     * Get output
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

}
   