<?php

namespace App\Cloud\Entity;

/**
 * @Entity
 * @Table(name="app_cloud_env" ,
 *  indexes={
 *          @Index(name="name",columns={"name"})
 *      })
 */
class Env {

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @Column(type="string", length=128, unique=true) */
    private $name ;

    /** @Column(type="string", length=128, nullable=true) */
    private $domain ;

    /** @Column(type="string", length=32, nullable=true) */
    private $subnet = '192.168.1.0/24' ;

    /** @Column(type="string", length=32, nullable=true) */
    private $dns1 = '8.8.8.8' ;

    /** @Column(type="string", length=32, nullable=true) */
    private $dns2 = '8.8.4.4' ;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="Client", mappedBy="env")
     */
    private $clients ;

    public function __construct() {
        $this->clients = new \Doctrine\Common\Collections\ArrayCollection() ;
    }

    public function getId(){
        return $this->id ;
    }

    public function getName() {
        return $this->name ;
    }

    public function setName($value) {
        $this->name = $value ;
    }

    public function getSubnet() {
        return $this->subnet ;
    }

    public function setSubnet($value) {
        $this->subnet = $value ;
    }

    public function getDns1() {
        return $this->dns1 ;
    }

    public function setDns1($value) {
        $this->dns1 = $value ;
    }

    public function getDns2() {
        return $this->dns2 ;
    }

    public function setDns2($value) {
        $this->dns2 = $value ;
    }

    public function getDomain() {
        return $this->domain ;
    }

    public function setDomain( $value ){
        $this->domain = $value ;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getClients(){
        return $this->clients ;
    }

    public function getDefaultIp(){
        return preg_replace('/\d+\/\d+$/', '', $this->subnet);
    }

    public function __toString() {
        return sprintf('Env( %s )', $this->getName() ) ;
    }

} 