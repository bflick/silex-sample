<?php

namespace Sample\Housing\Entities;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="audits")
 */
class Dormatory
{

    public function __construct()
    {
        $this->audits = new ArrayCollection();
    }
    
    /**
     * @\Doctrine\ORM\Mapping\Id
     * @\Doctrine\ORM\Mapping\Column(type="integer")
     * @\Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    
    /**
     * @\Doctrine\ORM\Mapping\ManyToMany(targetEntity="Dormatory", mappedBy="dormatories")
     */
    protected $audits;
    
    
    /**
     * Add audits
     *
     * @param Audit $audit
     */
    public function addAudit(Audit $audit)
    {
        $this->audits->add($audit);
        $audit->addDormatory($this);
    }

    /**
     * Remove audits
     *
     * @param Audit $audit
     */
    public function removeAudit(Audit $audit) {
        $this->audits->remove($audit);
        $audit->remomveDormatory();
    }

    /**
     * Get audits
     *
     * @return ArrayCollection<Audit>
     */
    public function getAudits()
    {
        return $this->audits;
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
     * @return Dormatory
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

    
    /**
     * @\Doctrine\ORM\Mapping\OneToMany(targetEntity="Bedroom", mappedBy="dormatory_id", cascade={"persist", "remove"})
     */
    protected $bedrooms;

    /**
     * add Bedroom
     */
    public function addBedroom(Bedroom $bedroom)
    {
        $this->bedrooms->add($bedroom);
        $bedroom->setDormatoryId($this->getId());
    }

    /**
     *
     * Remove bedroom
     */
    public function removeBedroom(Bedroom $bedroom) {
        $this->bedrooms->remove($bedroom);
        $bedroom->setDormatoryId(0);
    }

    /**
     * Get bedrooms
     *
     * @return ArrayCollection<Bedroom>
     */
    public function getBedrooms()
    {
        return $this->bedroom;
    }
}
