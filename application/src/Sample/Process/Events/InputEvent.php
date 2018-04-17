<?php
namespace Sample\Process\Events;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="input_event")
 */
class InputEvent extends ProcessEvent
{
    /**
     * Default length 255 char should be fine here
     *
     * @\Doctrine\ORM\Mapping\Column(type="string")
     */
    protected $input;

    /**
     * Set input
     *
     * @param string $input
     *
     * @return Audit
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }
    
    /**
     * Get input
     *
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }
}
