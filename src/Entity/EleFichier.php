<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EleFichier
 *
 * @ORM\Table(name="ele_fichier", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\EleFichierRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EleFichier {
	
	/**
	 * @var integer
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	
	/**
	 *
	 * @var string 
	 * @ORM\Column(name="url", type="string", length=255)
	 */
	private $url;
	
	/**
	 *
	 * @var \DateTime 
	 * @ORM\Column(name="date", type="datetime")
	 */
	private $date;
		
   /**
    *
	*/
	private $file;
	
	/**
	 * 
	 */
	private $tempFilename;
		
	/**
	 * 
	 * @var unknown
	 */
	private $prefix;
	
	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId(){
		return $this->id;
	}
	
	/**
	 * Set url
	 *
	 * @param string $url        	
	 * @return EleFichier
	 */
	public function setUrl($url){
		$this->url = $url;
		
		return $this;
	}
	
	/**
	 * Get url
	 *
	 * @return string
	 */
	public function getUrl(){
		return $this->url;
	}
	
	/**
	 * Set date
	 *
	 * @param \DateTime $date        	
	 * @return EleFichier
	 */
	public function setDate($date){
		$this->date = $date;
		
		return $this;
	}
	
	/**
	 * Get date
	 *
	 * @return \DateTime
	 */
	public function getDate(){
		return $this->date;
	}
		
	/**
	 * 
	 */
	public function getFile(){
		return $this->file;
	}
	
	/**
	 * 
	 * @param unknown $file
	 * @return \App\Entity\EleFichier
	 */
	public function setFile($file){
		$this->file = $file;
		if (null !== $this->url) {
			$this->tempFilename = $this->url;
			$this->url = null;
		}
		
		return $this;
	}
	
	/**
	 * 
	 */
	public function getPrefix()
	{
	    return $this->prefix;
	}
	
	/**
	 * 
	 * @param unknown $prefix
	 */
	public function setPrefix($prefix)
	{
	    $this->prefix = $prefix;
	    return $this;
	}
		
	/************************************ LOGIQUE METIER ***************************************/
	
	/**
	 * @ORM\PrePersist()
	 * @ORM\PreUpdate()
	 */
	public function preUpload()
	{
		if (null === $this->file) {
			return;
		}
		$this->url = $this->prefix.$this->file->getClientOriginalName();
		$this->date = new \DateTime();
	}
	
	/**
	 * @ORM\PostPersist()
	 * @ORM\PostUpdate()
	 */
	public function upload()
	{
    	if (null === $this->file) {
      		return;
    	}

	    if (null !== $this->tempFilename) {
	      $oldFile = $this->getUploadRootDir().'/'.$this->tempFilename;
	      if (file_exists($oldFile)) {
	        unlink($oldFile);
	      }
	    }
	
	    $this->file->move(
	      $this->getUploadRootDir(),
	      $this->url
	    );
	    
	    
		
	}
	
	/**
	 * @ORM\PreRemove()
	 */
	public function preRemoveUpload()
	{
		$this->tempFilename = $this->getUploadRootDir().'/'.$this->url;
	}
	

	/**
	 * @ORM\PostRemove()
	 */
	public function removeUpload()
	{
		if (file_exists($this->tempFilename)) {
			unlink($this->tempFilename);
		}
	}
	
	
	/**
	 * 
	 * @return string
	 */
	public function getUploadDir()
	{
		return 'uploads/documents';
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getUploadRootDir()
	{
		return __DIR__.'/../../../../web/'.$this->getUploadDir();
	}	
	
	/**
	 * 
	 * @return string
	 */
	public function getWebPath()
	{
		return $this->getUploadDir().'/'.$this->getUrl();
	}	
}