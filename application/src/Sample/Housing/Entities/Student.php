<?php

namespace Sample\Housing\Entities;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="students")
 */
class Student
{
    /**
     * @\Doctrine\ORM\Mapping\Id
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     * @\Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="Dormatory")
     */
    protected $dormatory;

    public function setDormatory(Dormatory $dorm)
    {
        $this->dormatory = $dorm;
        return $this;
    }

    public function getDormatory()
    {
        return $this->dormatory;
    }

    /**
     * @\Doctrine\ORM\Mapping\ManyToOne(targetEntity="Bedroom")
     */
    protected $bedroom;

    public function setBedroom(Bedroom $br)
    {
        $this->bedroom = $br;
        return $this;
    }

    public function getBedroom()
    {
        return $this->bedroom;
    }

    /**
     * Student's name
     * @\Doctrine\ORM\Mapping\Column(type="string")
     */
    protected $name;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Student
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }
    
    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    
    /**
     * gender
     * @\Doctrine\ORM\Mapping\Column(type="string")
     */
    protected $gender;

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return Student
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    
        return $this;
    }
    
    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }


    /**
     * Student id#
     * @\Doctrine\ORM\Mapping\Column(type="string")
     */
    protected $student_id;

    /**
     * Set student_id
     *
     * @param string $student_id
     *
     * @return Student
     */
    public function setStudentId($student_id)
    {
        $this->student_id = $student_id;
    
        return $this;
    }    

    /**
     * Get student_id
     *
     * @return string
     */
    public function getStudentId()
    {
        return $this->student_id;
    }

    
    /**
     * date of birth
     * @\Doctrine\ORM\Mapping\Column(type="date")
     */
    protected $date_of_birth;
    
    /**
     * Set date_of_birth
     *
     * @param date $date_of_birth
     *
     * @return Student
     */
    public function setDateOfBirth($date_of_birth)
    {
        $this->date_of_birth = $date_of_birth;
    
        return $this;
    }
    
    /**
     * Get date_of_birth
     *
     * @return date
     */
    public function getDateOfBirth()
    {
        return $this->date_of_birth;
    }


    /**
     * Students phone number
     * @\Doctrine\ORM\Mapping\Column(type="string")
     */
    protected $phone;
    
    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Student
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }
    
    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
