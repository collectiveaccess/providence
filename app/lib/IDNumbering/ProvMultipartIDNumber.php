<?php
 
require_once(__CA_LIB_DIR__."/IDNumbering/MultipartIDNumber.php");

class ProvMultipartIDNumber extends MultipartIDNumber {
	# -------------------------------------------------------
	/**
	 * Initialize the plugin
	 *
	 * @param string $format A format to set as current [Default is null]
	 * @param mixed $type A type to set a current [Default is __default__]
	 * @param string $value A value to set as current [Default is null]
	 * @param Db $db A database connection to use for all queries. If omitted a new connection (may be pooled) is allocated. [Default is null]
	 */
	public function __construct($format=null, $type=null, $value=null, $db=null) {
		parent::__construct($format, $type, $value, $db);
	}
	/**
	 * Custom functionality to split apart PROV IDNOs.
	 *
	 * @param string $value
	 * @return array List of values
	 */
	public function explodeValue($value, $parentPlaceholder=false) {
		$separator = $this->getSeparator();

		if ($value && !$parentPlaceholder && !$separator && $this->formatHas('PARENT', 0)) {
			// starts with PARENT element so replace with placeholder as parent value may include separators
			$parent_value = $this->getParentValue();
			if (!empty($parent_value)) {
				$v_proc = preg_replace( "!^" . preg_quote( $parent_value, '!' ) . "!", "_PARENT_", $value );

				if (str_contains( $v_proc, '_PARENT_' )) {
					$element_vals = $this->explodeValue( $v_proc, true );
					$element_vals = array_map( function ( $v ) use ($parent_value) {
						return preg_replace( "!^_PARENT_!", $parent_value, $v );
					}, $element_vals );
					return $element_vals;
				}
			}
		}
		// Fallback to parent handling
		return parent::explodeValue($value);
	}
	# -------------------------------------------------------
	/**
	 * Returns sortable value padding according to the custom PROV rules
	 *
	 * @param string $value Value from which to derive the sortable value. If omitted the current value is used. [Default is null]
	 * @return string The sortable value
	 */
	public function getSortableValue($value=null) {
		preg_match_all('/([a-zA-Z]+|\d+)/', $value, $matches);
		$output = array_map(function($v) {
			$padding = 20;
			$n = $padding - mb_strlen($v);
			return ( ($n >= 0) ? str_repeat(' ', $n) : '') . $v;
		},  $matches[1]);

		return join(null, $output);
	}
}
