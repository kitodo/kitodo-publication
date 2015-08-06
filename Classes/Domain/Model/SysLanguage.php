<?php
namespace Eww\Dpf\Domain\Model;

class SysLanguage extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {
    

        /**
	 * uid
	 *
	 * @var integer
	 */
	protected $uid;

        /**
	 * pid
	 *
	 * @var integer
	 */
	protected $pid;

    
        /**
	 * title
	 *
	 * @var string
	 */
	protected $title = '';


        /**
	 * flag
	 *
	 * @var string
	 */
	protected $flag = '';

        
        /**
	 * staticLangIsocode
	 *
	 * @var string
	 */
	protected $staticLangIsocode = '';

        
        /**
	 * Returns the uid
	 *
	 * @return string $uid
	 */
	public function getUid() {
		return $this->uid;
	}

        /**
	 * Sets the uid
	 *
	 * @param string $uid
	 */
	public function setUid($uid) {
		$this->uid = $uid;
	}
        
	/**
	 * Returns the pid
	 *
	 * @return string $pid
	 */
	public function getPid() {
		return $this->pid;
	}
        
        /**
	 * Sets the pid
	 *
	 * @param string $pid
	 */
	public function setPid($pid) {
		$this->pid = $pid;
	}
        
        /**
	 * Returns the title
	 *
	 * @return string $title
	 */
	public function getTitle() {
		return $this->title;
	}
        
        /**
	 * Sets the title
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
        
        /**
	 * Returns the flag
	 *
	 * @return string $flag
	 */
	public function getFlag() {
		return $this->flag;
	}
       
        /**
	 * Sets the flag
	 *
	 * @param string $flag
	 */
	public function setFlag($flag) {
		$this->flag = $flag;
	}
        
        /**
	 * Returns the staticLangIsocode
	 *
	 * @return string $staticLangIsocode
	 */
	public function getStaticLangIsocode() {
		return $this->staticLangIsocode;
	}
        
        /**
	 * Sets the staticLangIsocode
	 *
	 * @param string $staticLangIsocode
	 */
	public function setStaticLangIsocode($staticLangIsocode) {
		$this->staticLangIsocode = $staticLangIsocode;
	}
}

?>