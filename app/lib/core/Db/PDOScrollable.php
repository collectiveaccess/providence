<?php
class PDOScrollable{
	var $opa_res;
	var $opn_cursor;
	public __construct($po_stmt) {
		if(is_object($po_stmt)){
			$this->opa_res = $po_stmt->fetchAll();
		}
	}
	public function getCursor(){
		return $this->opn_cursor;
	}
	public function seek($pn_newval){
		if( ($pn_newval < count($this->opa_res)) ||
			($pn_newval >= 0)){
			$this->opn_cursor = $pn_newval;
		}	
	}
	public function getRow(){
		if(is_array($this->opa_res){
			return $this->opa_res[$this->opn_cursor];
		}
		else{
			return false;
		}
			
	}
	public function getAllRows(){
		if(is_array($this->opa_res)){
			return $this->opa_res
		}
		else{
			return false;
		}
	}
}
?>
