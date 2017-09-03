<?php

namespace Sample\Housing\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\Event;

/**
 * @\Doctrine\ORM\Mapping\Entity
 * @\Doctrine\ORM\Mapping\Table(name="dormatories")
 */
class Audit extends Event
{

    public function __construct()
    {
        $this->dormatories = new ArrayCollection();
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
     * @\Doctrine\ORM\Mapping\Column(type="string", length=35)
     */
    protected $title;
    
    /**
     * Set title
     *
     * @param string $title
     *
     * @return Audit
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }
    
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    
    /**
     * @\Doctrine\ORM\Mapping\Column(type="datetime")
     */
    protected $updated;

    /**
     * Set updated
     *
     * @param datetime $updated
     *
     * @return Audit
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    
        return $this;
    }
    
    /**
     * Get updated
     *
     * @return date
     */
    public function getUpdated()
    {
        return $this->updated;
    }

 
    /**
     * I Plan on making this a text audit trail of db updates.
     * @\Doctrine\ORM\Mapping\Column(type="text")
     */
    protected $content;    
    
    /**
     * Set content
     *
     * @param string $content
     *
     * @return Audit
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }
    
    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * @\Doctrine\ORM\Mapping\ManyToMany(targetEntity="Dormatory", inversedBy="audits")
     * @\Doctrine\ORM\Mapping\JoinTable(name="dormatory_audits", joinColumns={
     *   @\Doctrine\ORM\Mapping\JoinColumn(name="audit_id", referencedColumnName="id"),
     * }, inverseJoinColumns={
     *   @\Doctrine\ORM\Mapping\JoinColumn(name="dormatory_id", referencedColumnName="id"),
     * })
     */
    protected $dormatories;
    
    /**
     * Add dormatories
     *
     * @param Dormatory $dorm
     */
    public function addDormatory(Dormatory $dorm)
    {
        $this->dormatories->add($dorm);
        $dorm->addAudit($this);
    }

    /**
     * Remove dormatories
     *
     * @param Dormatory $dorm
     */
    public function removeDormatory(Dormatory $dorm)
    {
        $this->dormatories->remove($dorm);
        $dorm->removeAudit($this);
    }

    /**
     * Get dormatories
     *
     * @return ArrayCollection<Dormatory>
     */
    public function getDormatories()
    {
        return $this->dormatories;
    }
}