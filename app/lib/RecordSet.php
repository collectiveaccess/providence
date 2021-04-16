<?php
class RecordSet {
	/**
	 *
	 */
	private $source; 
	
	/**
	 *
	 */
	private $type; 
	
	/**
	 * @param ca_set|ResultContext $source Underlying set of records. Can be either a ca_sets instance or ResultContext
	 */
	public function __construct($source) {
		$this->setSource($source);
	}
	
	/**
	 *
	 */
	public function setSource($source) {
		$this->source = $source;
		$this->type = get_class($source);
	}
	
	/**
	 *
	 */
	public function getSource() {
		return $this->source;
	}
	
	/**
	 *
	 */
	public function ID() {
		switch($this->type) {
			case 'ca_sets':
				return $this->source->getPrimaryKey();
				break;
			case 'ResultContext':
				return 'ResultContext';
				break;		
		}
		return null;
	}
	
	/**
	 *
	 */
	public function name() {
		switch($this->type) {
			case 'ca_sets':
				return $this->source->get('ca_sets.preferred_labels.name');
				break;
			case 'ResultContext':
				return 'ResultContext Name here';
				break;		
		}
		return null;
	}
	
	/**
	 *
	 */
	public function tableName() {
		switch($this->type) {
			case 'ca_sets':
				return Datamodel::tableName($this->source->get('table_num'));
				break;
			case 'ResultContext':
				return $this->source->tableName();
				break;		
		}
		return null;
	}
	
	/**
	 *
	 */
	public function tableNum() {
		switch($this->type) {
			case 'ca_sets':
				return (int)$this->source->get('table_num');
				break;
			case 'ResultContext':
				return Datamodel::tableNum($this->source->tableName());
				break;		
		}
		return null;
	}
	
	/**
	 * 
	 */
	public function getTypesForItems(array $options=null) {
		switch($this->type) {
			case 'ca_sets':
				return $this->source->getTypesForItems($options);
				break;
			case 'ResultContext':
				return [];
				break;		
		}
		return null;
	}
	
	/**
	 * 
	 */
	public function getItemCount(array $options=null) {
		switch($this->type) {
			case 'ca_sets':
				return $this->source->getItemCount($options);
				break;
			case 'ResultContext':
				return 0;
				break;		
		}
		return null;
	}
	
	/**
	 * 
	 */
	public function getItemRowIDs(array $options=null) {
		switch($this->type) {
			case 'ca_sets':
				return $this->source->getItemRowIDs($options);
				break;
			case 'ResultContext':
				return [];
				break;		
		}
		return null;
	}

}
