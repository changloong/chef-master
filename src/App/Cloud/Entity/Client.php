<?php

namespace App\Cloud\Entity;

/**
 * @Entity
 * @Table(name="app_cloud_client" ,
 *  uniqueConstraints={
 *          @UniqueConstraint(name="env_client_name", columns={"env_id", "name"})
 *      })
 */
class Client {

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", length=128)
     */
    private $name ;

    /**
     * @Column(type="string", length=128, nullable=true)
     */
    private $hostname ;

    /**
     * @Column(type="string", length=32)
     */
    private $ip ;

    /**
     * @Column(type="integer")
     */
    private $port ;

    /**
     * @Column(type="string", length=128)
     */
    private $user ;

    /**
     * @Column(type="string", length=128, nullable=true)
     */
    private $__password ;


    /**
     * @Column(type="string", length=128, nullable=true)
     */
    private $home ;

    /**
     * @Column(type="datetime", nullable=true)
     */
    private $bootstrap ;

    /**
     * @Column(type="string", length=128, nullable=true)
     */
    private $bash_path ;

    /**
     * @var Env
     * @ManyToOne(targetEntity="Env", inversedBy="clients", cascade={"persist"} )
     * @JoinColumn(name="env_id", referencedColumnName="id", nullable=false)
     */
    private $env ;

    /**
     * @Column(type="array")
     */
    private $roles ;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="Recipe", mappedBy="client")
     */
    private $recipes ;


    public function __construct() {
        $this->recipes = new \Doctrine\Common\Collections\ArrayCollection() ;
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

    public function getHostname() {
        return $this->hostname ;
    }
    public function setHostname( $value ) {
        $this->hostname = $value ;
    }

    public function getIp(){
        return $this->ip ;
    }
    public function setIp($value){
        $this->ip = $value ;
    }

    public function getPort(){
        return $this->port  ;
    }

    public function setPort($value){
        $this->port = $value ;
    }

    public function getUser(){
        return $this->user ;
    }

    public function setUser($value){
        $this->user = $value ;
    }

    public function getPassword() {
        return $this->__password ;
    }

    public function setPassword($value) {
        $this->__password     = $value ;
    }

    public function getHome() {
        return $this->home ;
    }

    public function setHome( $value ) {
        $this->home = $value ;
    }

    /**
     * @return Env
     */
    public function getEnv() {
        return $this->env ;
    }

    public function setEnv(Env $env) {
        $this->env = $env ;
    }

    public function getRoles() {
        return $this->roles ;
    }

    public function setRoles(array $roles) {
        $this->roles    = $roles ;
    }

    public function getBashPath(){
        return $this->bash_path ;
    }
    public function setBashPath($value){
        $this->bash_path = $value ;
    }

    /**
     * @return \DateTime
     */
    public function getBootstrap(){
        return $this->bootstrap ;
    }

    public function setBootstrap(\DateTime $value  = null ){
        $this->bootstrap = $value ;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getRecipes (){
        return $this->recipes ;
    }

    public function __toString(){
        $name = $this->name ;
        $env = $this->getEnv()->getName() ;
        return sprintf('Client( %s, %s )', $name, $env);
    }
}
