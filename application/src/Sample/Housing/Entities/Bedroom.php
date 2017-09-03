<?php

namespace Sample\Housing\Entities;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="bedrooms")
 */
class Bedroom
{
    /**
     * @\Doctrine\ORM\Mapping\Id
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     * @\Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    
    /**
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     */
    protected $dormatory_id;

    /**
     * Set dormatory_id
     *
     * @param int $dormatory_id
     *
     * @return Bedroom
     */
    public function setDormatoryId($dormatory_id)
    {
        $this->dormatory_id = $dormatory_id;
    
        return $this;
    }
    
    /**
     * Get dormatory_id
     *
     * @return int
     */
    public function getDormatoryId()
    {
        return $this->dormatory_id;
    }
    
    /**
     * @\Doctrine\ORM\Mapping\OneToOne(targetEntity="Student", cascade={"persist"})
     */
    protected $student;

    /**
     * Set student
     *
     * @param Student $student
     *
     * @return Bedroom
     */
    public function setStudent($student)
    {
        $this->student = $student;
    
        return $this;
    }
    
    /**
     * Get student
     *
     * @return Student
     */
    public function getStudent()
    {
        return $this->student;
    }
    /**
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     */
    protected $floor;
    
    /**
     * Set floor
     *
     * @param int $floor
     *
     * @return Bedroom
     */
    public function setFloor($floor)
    {
        $this->floor = $floor;
    
        return $this;
    }
    
    /**
     * Get floor
     *
     * @return int
     */
    public function getFloor()
    {
        return $this->floor;
    }

    
    /**
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     */
    protected $number;

    /**
     * Set number
     *
     * @param int $number
     *
     * @return Bedroom
     */
    public function setNumber($number)
    {
        $this->number = $number;
    
        return $this;
    }
    
    /**
     * Get number
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

}