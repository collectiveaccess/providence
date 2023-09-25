<?php
 
require_once(__CA_LIB_DIR__."/IDNumbering/MultipartIDNumber.php");

class ProvMultipartIDNumber extends MultipartIDNumber {
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

	/**
	 * Duplicate all functionality from MultipartIDNumber->genNextValue() but not exclude deleted records.
	 *
	 * @param string $element_name
	 * @param mixed $value [Default is null]
	 *
	 * @return int Next value for SERIAL element or the string "ERR" on error
	 */
	public function getNextValue($element_name, $value=null) {
		if (!$value) { $value = $this->getValue(); }
		$element_info = $this->getElementInfo($element_name);

		$table = $this->getFormat();
		if(!Datamodel::tableExists($table)) { return 'ERR'; }
		$field = Datamodel::getTableProperty($table, 'ID_NUMBERING_ID_FIELD');
		if(!$field) { return 'ERR'; }
		$sort_field = Datamodel::getTableProperty($table, 'ID_NUMBERING_SORT_FIELD');
		if (!$sort_field) { $sort_field = $field; }

		$separator = $this->getSeparator();
		$elements = $this->getElements();

		$is_parent = null;

		if ($value == null) {
			$element_vals = [];
			$i = 0;
			foreach($elements as $ename => $element_info) {
				if ($ename == $element_name) { break; }
				switch($element_info['type']) {
					case 'CONSTANT':
						$element_vals[] = $element_info['value'];
						break;
					case 'YEAR':
					case 'MONTH':
					case 'DAY':
						$date = getDate();
						if ($element_info['type'] == 'YEAR') {
							if ($element_info['width'] == 2) {
								$date['year'] = substr($date['year'], 2, 2);
							}
							$element_vals[] = $date['year'];
						}
						if ($element_info['type'] == 'MONTH') { $element_vals[]  = $date['mon']; }
						if ($element_info['type'] == 'DAY') { $element_vals[]  = $date['mday']; }
						break;
					case 'LIST':
						if ($element_info['default']) {
							$element_vals[] = $element_info['default'];
						} else {
							if (is_array($element_info['values'])) {
								$element_vals[] = array_shift($element_info['values']);
							}
						}
						break;
					case 'PARENT':
						$is_parent = $i;
						$element_vals[] = $this->getParentValue();
						break;
					case 'SERIAL':
						$element_vals[] = '';
						break;
					default:
						$element_vals[] = '';
						break;
				}
				$i++;
			}
		} elseif(is_array($value)) {
			$element_vals = [];
			$i = 0;
			foreach($elements as $ename => $element_info) {
				switch($element_info['type']) {
					case 'PARENT':
						$is_parent = $i;
						$element_vals[$i] = $value[$ename] ?? null;
						break;
					case 'CONSTANT':
						$element_vals[$i] = $element_info['value'];
						break;
					case 'SERIAL':
						$element_vals[$i] = $value[$ename] ?? '';
						break;
					default:
						$element_vals[$i] = $value[$ename] ?? null;
						break;
				}
				$i++;
			}
		} else {
			$element_vals = $this->explodeValue($value);

			$i = 0;
			foreach($elements as $ename => $element_info) {
				switch($element_info['type']) {
					case 'PARENT':
						$is_parent = $i;
						break;
					case 'CONSTANT':
						$element_vals[$i] = $element_info['value'];
						break;
					case 'SERIAL':
						if(!isset($element_vals[$i])) { $element_vals[$i] = ''; }
						break;
				}
				$i++;
			}
		}

		if(!is_null($is_parent)) {
			$this->isChild(true, $element_vals[$is_parent]);
		}

		$tmp = [];
		$i = 0;
		$blank_count = 0;
		foreach($elements as $ename => $element_info) {
			if ($ename == $element_name) { break; }
			$v = array_shift($element_vals);
			if(is_array($v)) { $v = join($separator, $v); }
			if (!strlen($v)) { $blank_count++; }
			$tmp[] = $v;
			$i++;
		}
		if ($blank_count > 0) {
			return (($zeropad_to_length = (int)$element_info['zeropad_to_length']) > 0) ? sprintf("%0{$zeropad_to_length}d", 1) : 1;
		}

		$stub = trim(join($separator, $tmp));

		$this->db->dieOnError(false);

		// Get the next number based upon field data
		$type_id = null;
		$type_limit_sql = '';

		$params = [];

		if($stub === '') {
			$field_limit_sql = "{$field} <> ''";
		} else {
			$field_limit_sql = "{$field} LIKE ?";
			$params = [$stub.$separator.'%'];
			if ($separator) {
				$field_limit_sql .= " AND {$field} NOT LIKE ?";
				$params[] = $stub.$separator.'%'.$separator.'%';
			}
		}

		if (!($t_instance = Datamodel::getInstanceByTableName($table, true))) { return 'ERR'; }
		if ((bool)$element_info['sequence_by_type']) {
			$stypes = is_array($element_info['sequence_by_type']) ? $element_info['sequence_by_type'] : [$element_info['sequence_by_type']];
			$sequence_by_types = caMakeTypeIDList($table, $stypes, ['dontIncludeSubtypesInTypeRestriction' => (bool)$element_info['dont_include_subtypes']]);
			$type = $this->getType();
			if ($type == '__default__') {
				$types = $this->getTypes();

				$exclude_type_ids = [];
				foreach($types as $type) {
					if ($type == '__default__') { continue; }
					if ($type_id = (int)$t_instance->getTypeIDForCode($type)) {
						$exclude_type_ids[] = $type_id;
					}
				}
				if (sizeof($exclude_type_ids) > 0) {
					$type_limit_sql = " AND type_id NOT IN (?)";
					$params[] = $exclude_type_ids;
				}
			} elseif(is_array($sequence_by_types) && sizeof($sequence_by_types)) {
				$type_limit_sql = " AND type_id IN (?)";
				$params[] = $sequence_by_types;
			} elseif($type_id = (int)$t_instance->getTypeIDForCode($type)) {
				$type_limit_sql = " AND type_id = ?";
				$params[] = $type_id;
			}
		}

		if ($qr_res = $this->db->query("
			SELECT {$field} FROM {$table}
			WHERE
				{$field_limit_sql}
				{$type_limit_sql}
			ORDER BY
				{$sort_field} DESC
			LIMIT 1
		", $params)) {
			if ($this->db->numErrors()) {
				return "ERR";
			}

			// Figure out what the sequence (last) number in the multipart number taken from the field is...
			if ($qr_res->numRows()) {
				while($qr_res->nextRow()) {
					$tmp = $this->explodeValue($qr_res->get($field));
					if(is_numeric($tmp[$i]) && (intval($tmp[$i]) < pow(2,64))) {
						$num = intval($tmp[$i]) + 1;
						break;
					}
				}
				if ($num == '') { $num = 1; }
				if (is_array($tmp) && (sizeof($tmp) > 1)) {
					array_pop($tmp);
					$stub = join($separator, $tmp);
				} else {
					$stub = '';
				}
			} else {
				$num = 1;
			}

			// Now get the last used sequence number for this "stub"
			$max_num = 0;

			// Make the new number one more than the last used number if it is less than the last
			// (this prevents numbers from being reused when records are deleted or renumbered)
			if ($num <= $max_num) {
				$num = $max_num + 1;
			}

			if(isset($element_info['minimum']) && (($min = (int)$element_info['minimum']) > 0) && ($num < $min)) {
				$num = $min;
			}

			if (($zeropad_to_length = (int)$element_info['zeropad_to_length']) > 0) {
				return sprintf("%0{$zeropad_to_length}d", $num);
			} else {
				return $num;
			}
		} else {
			return 'ERR';
		}
	}

}
