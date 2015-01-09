<?php

namespace App\Cloud\Entity;

/**
 * @Entity
 * @Table(name="app_cloud_recipe"  ,
 *  uniqueConstraints={
 *          @UniqueConstraint(name="client_recipe_name", columns={"client_id","recipe_name",  "multiple_name" })
 *      })
 */
class Recipe {

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id ;

    /** @Column(type="string", length=128) */
    private $recipe_name ;

    /** @Column(type="string", length=128, nullable=true) */
    private $multiple_name ;

    /**
     * @var Recipe
     * @ManyToOne(targetEntity="Client", inversedBy="children", cascade={"persist"} )
     * @JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    private $parent ;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="Client", mappedBy="parent")
     */
    private $children ;

    /**
     * @var bool
     * @Column(type="boolean")
     */
    private $private ;

    /**
     * @Column(type="array")
     */
    private $data_bag = array() ;

    /**
     * @var Client
     * @ManyToOne(targetEntity="Client", inversedBy="recipes", cascade={"persist"} )
     * @JoinColumn(name="client_id", referencedColumnName="id", nullable=false)
     */
    private $client ;

    /**
     * @Column(type="datetime", nullable=true)
     */
    private $try_install;

    /**
     * @Column(type="datetime", nullable=true)
     */
    private $installed;

    public function getId() {
        return $this->id ;
    }

    public function getMultipleName() {
        return $this->multiple_name ;
    }

    public function setMultipleName($value) {
        $this->multiple_name = $value ;
    }

    public function getRecipeName() {
        return $this->recipe_name ;
    }

    public function setRecipeName($name){
         $this->recipe_name = $name ;
    }

    /**
     * @return Recipe
     */
    public function getParent(){
        return $this->parent ;
    }

    public function setParent(Recipe $parent){
        $this->parent = $parent ;
    }

    public function isPrivate(){
        return $this->private ;
    }

    public function setPrivate( $value ){
         $this->private = $value ;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildren(){
        return $this->children ;
    }

    public function getDataBag() {
        return $this->data_bag ;
    }

    public function setDataBag( array $value ) {
        $this->data_bag = $value ;
    }

    /**
     * @return \DateTime
     */
    public function getTryInstall(){
        return $this->try_install ;
    }

    public function setTryInstall(\DateTime $value  = null ){
        if ( null === $value ) {
            $value  = new \DateTime('now') ;
        }
        $this->try_install = $value ;
    }

    /**
     * @return \DateTime
     */
    public function getInstalled(){
        return $this->installed ;
    }

    public function setInstalled(\DateTime $value  = null ){
        if ( null === $value ) {
            $value  = new \DateTime('now') ;
        }
        $this->try_install = null ;
        $this->installed = $value ;
    }

    /**
     * @return Client
     */
    public function getClient() {
        return $this->client ;
    }

    /**
     * @param Client $value
     */
    public function setClient(Client $value) {
        $this->client = $value ;
    }

    public function __toString() {
        $env_name    = $this->client->getEnv()->getName() ;
        $client_name    = $this->client->getName() ;
        if( $this->multiple_name  ) {
            return sprintf('Recipe( %s:%s, %s, %s )', $this->recipe_name, $this->multiple_name , $client_name, $env_name ) ;
        }
        return sprintf('Recipe( %s, %s, %s )', $this->recipe_name, $client_name, $env_name ) ;
    }
}