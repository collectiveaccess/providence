<?php

# Data classes and a parser for MARC data.

define("MARC_DELIMITER", "\x1f");
define("MARC_FT", "\x1e");	# Field terminator
define("MARC_RT", "\x1d");	# Record terminator

# FIXME - These conversions only support those characters that are
# absolutey necessary.  The mnemonics for other characters should
# be added at some point.
class MarcHelpers {
	function toMnem($s) {
		$map = array(
			'{' => '{lcub}',
			'}' => '{rcub}',
			'$' => '{dollar}',
			'\\' => '{bsol}',
		);
		$t = '';
		while (strlen($s)) {
			$did_subst = False;
			foreach($map as $from => $to) {
				if (substr($s, 0, strlen($from)) == $from) {
					$t .= $to;
					$s = substr($s, strlen($from));
					$did_subst = True;
					break;
				}
			}
			if (!$did_subst) {
				if (ereg('^  +', $s, $m)) {
					$t .= str_repeat('\\', strlen($m[0]));
					$s = substr($s, strlen($m[0]));
				} else {
					$t .= $s{0};
					$s = substr($s, 1);
				}
			}
		}
		return $t;
	}
	function fromMnem($s) {
		$map = array(
			'{lcub}' => '{',
			'{rcub}' => '}',
			'{dollar}' => '$',
			'{bsol}' => '\\',
			'\\' => ' ',
		);
		$t = '';
		while (strlen($s)) {
			$did_subst = False;
			foreach($map as $from => $to) {
				if (substr($s, 0, strlen($from)) == $from) {
					$t .= $to;
					$s = substr($s, strlen($from));
					$did_subst = True;
					break;
				}
			}
			if (!$did_subst) {
				$t .= $s{0};
				$s = substr($s, 1);
			}
		}
		return $t;
	}
}

/* Base class for Control and Data fields */
class MarcField {
	var $tag;
	function MarcField($tag='') {
		$this->tag=strtoupper($tag);
	}
	function getValue($identifier=NULL) {
		$l = $this->getValues($identifier);
		if (count($l) > 0) {
			return $l[0];
		} else {
			return NULL;
		}
	}
	/* Methods below should be overridden */
	function getMnem() {
		return '='.$this->tag.'  ';
	}
	function get() {
	}
	function getValues($identifier=NULL) {
		return array();
	}
}

class MarcControlField extends MarcField {
	var $data;
	function MarcControlField($tag='', $data='') {
		$this->MarcField($tag);
		$this->data=$data;
	}
	function get() {
		return $this->data . MARC_FT;
	}
	function getMnem() {
		return parent::getMnem() . MarcHelpers::toMnem($this->data) . "\n";
	}
	function getValues($identifier=NULL) {
		if ($identifier !== NULL) {
			return array();
		} else {
			array($this->data);
		}
	}
}

class MarcSubfield {
	var $identifier;
	var $data;
	function MarcSubfield($i, $d) {
		$this->identifier=strtolower($i);
		$this->data=$d;
	}
	function get() {
		return MARC_DELIMITER . $this->identifier . $this->data;
	}
	function getMnem() {
		return '$' . MarcHelpers::toMnem($this->identifier) . MarcHelpers::toMnem($this->data);
	}
}

class MarcDataField extends MarcField {
	var $indicators;
	var $subfields;
	function MarcDataField($tag='', $indicators='  ') {
		$this->MarcField($tag);
		$this->indicators=$indicators;
		$this->subfields=array();	# list of Subfield
	}
	function get() {
		$s = $this->indicators;
		foreach ($this->subfields as $sf) {
			$s .= $sf->get();
		}
		return $s . MARC_FT;
	}
	function getMnem() {
		$s = parent::getMnem() . str_replace(' ', '\\', $this->indicators);
		foreach ($this->subfields as $sf) {
			$s .= $sf->getMnem();
		}
		return $s . "\n";
	}
	function getSubfields($identifier=NULL) {
		if ($identifier === NULL) {
			return $this->subfields;
		} else {
			$ret = array();
			foreach ($this->subfields as $sf) {
				if ($sf->identifier == $identifier) {
					array_push($ret, $sf);
				}
			}
			return $ret;
		}
	}
	function getSubfield($identifier=NULL) {
		$l = $this->getSubfields($identifier);
		if (count($l) > 0) {
			return $l[0];
		} else {
			return NULL;
		}
	}
	function getValues($identifier=NULL) {
		return array_map(create_function('$sf', 'return $sf->data;'),
			$this->getSubfields($identifier));
	}
}

class MarcRecord {
	var $default_leader = '00000nam a2200000uu 4500';
	var $_leader_fields = array(
		# array(name, type, length, title, required value)
		array('length', 'num', 5, 'length', NULL),
		array('status', 'str', 1, 'record status', NULL),
		array('type', 'str', 1, 'record type', NULL),
		array('impl_0708', 'str', 2, 'impl_0708', NULL),
		array('encoding', 'str', 1, 'character encoding', NULL),
		array('nindicators', 'num', 1, 'indicator count', 2),
		array('identlen', 'num', 1, 'subfield code length', 2),
		array('baseAddr', 'num', 5, 'base address of data', NULL),
		array('impl1719', 'str', 3, 'impl_1719', NULL),
		array('entryMapLength', 'num', 1, 'length-of-field length', 4),
		array('entryMapStart', 'num', 1, 'starting-character-position length', 5),
		array('entryMapImpl', 'num', 1, 'implementation-defined length', 0),
		array('entryMapUndef', 'num', 1, 'undefined entry-map field', 0),
	);

	var $fields;
	function MarcRecord() {
		# Provide a default leader
		$this->setLeader($this->default_leader);
		$this->fields = array();
	}
	function setLeader($ldr, $lenient=False) {
		if ($lenient) {
			$ldr = rtrim($ldr);
		}
		if (strlen($ldr) != strlen($this->default_leader)) {
			if ($lenient) {
				$ldr .= substr($this->default_leader, strlen($ldr));
				$ldr = substr($ldr, 0, strlen($this->default_leader));
			} else {
				return 'wrong leader length';
			}
		}
		foreach ($this->_leader_fields as $f) {
			$v = substr($ldr, 0, $f[2]);
			$ldr = substr($ldr, $f[2]);
			if ($f[1] == 'num') {
				if (!$lenient && !ctype_digit($v)) {
					return 'MARC21 requires ' . $f[3] . ' to be numeric';
				}
				$v += 0;
			}
			if (!$lenient and $f[4] !== NULL and $v != $f[4]) {
				return 'MARC21 requires ' . $f[3] . ' of ' . $f[4];
			}
			$this->$f[0] = $v;
		}
		return NULL;
	}

	function getLeader() {
		$ldr = '';
		foreach ($this->_leader_fields as $f) {
			$s = '';
			if ($f[1] == 'str') {
				$s = $this->$f[0];
			} else if ($f[1] == 'num') {
				$s = sprintf('%0'.$f[2].'u', $this->$f[0]);
			}
			if (strlen($s) != $f[2]) {
				$s = sprintf('%-'.$f[2].'s', $s);
				$s = substr($s, 0, $f[2]);
			}
			$ldr .= $s;
		}
		assert('strlen($ldr) == 24');
		return $ldr;
	}

	// Returns array(record_string, error)
	// where record_string is only valid if error is NULL
	function get() {
		$directory = '';
		$data = '';
		foreach ($this->fields as $f) {
			$d = $f->get();
			$l = array(
				array($f->tag, 3, 'tag has wrong length: '.$f->tag),
				array(strlen($d), 4, $f->tag.' field too long'),
				array(strlen($data), 5, 'record too long'),
			);
			foreach ($l as $t) {
				$s = sprintf('%0'.$t[1].'u', $t[0]);
				if (strlen($s) != $t[1]) {
					return array(NULL, $t[2]);
				}
				$directory .= $s;
			}
			$data .= $d;
		}
		# 24 is the leader length, 1 for the field terminator
		$this->baseAddr = 24 + strlen($directory) + 1;
		# 1 for the record terminator
		$this->length = $this->baseAddr + strlen($data) + 1;
		return array($this->getLeader() . $directory . MARC_FT . $data . MARC_RT, NULL);
	}

	function getMnem() {
		$s = '=LDR  ' . MarcHelpers::toMnem($this->getLeader()) . "\n";
		foreach ($this->fields as $f) {
			$s .= $f->getMnem();
		}
		return $s . "\n";
	}

	function getFields($tag=NULL) {
		if ($tag === NULL) {
			return $this->fields;
		}
		$a = array();
		foreach ($this->fields as $f) {
			if ($f->tag == $tag) {
				array_push($a, $f);
			}
		}
		return $a;
	}

	function getField($tag=NULL) {
		$l = $this->getFields($tag);
		if (count($l) > 0) {
			return $l[0];
		} else {
			return NULL;
		}
	}

	function getValues($spec=NULL) {
		$l = array();
		if ($spec === NULL) {
			array_push($l, NULL);
		} else {
			$l = explode('$', $spec, 2);
		}
		if (count($l) == 1) {
			array_push($l, NULL);
		}
		$a = array();
		foreach ($this->getFields($l[0]) as $f) {
			foreach ($f->getValues($l[1]) as $v) {
				array_push($a, $v);
			}
		}
		return $a;
	}

	function getValue($spec=NULL) {
		$l = $this->getValues($spec);
		if (count($l) > 0) {
			return $l[0];
		} else {
			return NULL;
		}
	}
}

class MarcParseError {
	var $msg;
	var $record;
	var $line;
	function MarcParseError($msg, $record=NULL, $line=NULL) {
		$this->msg = $msg;
		$this->record = $record;
		$this->line = $line;
	}
	function toStr() {
		$s = '';
		if ($this->line !== NULL) {
			$s .= 'Line '.$this->line.': ';
		}
		if ($this->record !== NULL) {
			$s .= 'Record '.$this->record.': ';
		}
		return $s . $this->msg;
	}
}

class MarcBaseParser {
	var $lenient;
	var $records;
	var $_recnum;
	var $_unparsed;
	function MarcBaseParser($lenient=true) {
		$this->lenient = $lenient;
		$this->records = array();
		$this->_recnum = 0;
		$this->_unparsed = '';
	}
	function parse($unparsed) {
		$this->_unparsed .= $unparsed;
		return $this->_parse();
	}

	# Must be overridden by derived classes
	function eof() {
		$this->_recnum = 0;
		return 0;
	}
	function _error($s) {
		return new MarcParseError($s);
	}
	function _parse() {
		return 0;
	}
}

class MarcParser extends MarcBaseParser {
	function eof() {
		if (!$this->lenient and strlen($this->_unparsed) > 0) {
			return new MarcParseError('trailing junk or incomplete record at end of file');
		}
		$this->_recnum = 0;
		return 0;
	}
	function _error($s) {
		return new MarcParseError($s, $this->_recnum);
	}
	function _parse() {
		$old_len = count($this->records);
		while (strlen($this->_unparsed) >= 5) {
			$rec_len = substr($this->_unparsed, 0, 5);
			if (!ctype_digit($rec_len)) {
				return $this->_error("garbled length field");
			}
			if ($rec_len < 24) {
				return $this->_error("impossibly small length field");
			}
			if (strlen($this->_unparsed) < $rec_len) {
				break;
			}
			$r = $this->_parseRecord(substr($this->_unparsed, 0, $rec_len));
			if (is_a($r, 'MarcParseError')) {
				return $r;
			}
			array_push($this->records, $r);
			$this->_unparsed = substr($this->_unparsed, $rec_len);
		}
		return count($this->records)-$old_len;
	}

	function _parseRecord($rec) {
		$r = new MarcRecord();
		$this->_recnum += 1;
		$err = $r->setLeader(substr($rec, 0, 24), $this->lenient);
		if ($err) {
			return $this->_error("Invalid Leader: ".$err);
		}

		$base=$r->baseAddr;
		$entries = $this->_parseDirectory(substr($rec, 24, $base-24));
		if (is_a($entries, 'MarcParseError')) {
			return $entries;
		}
		foreach ($entries as $e) {
			$f = substr($rec, $base+$e['start'], $e['length']);
			$field = $this->_parseField($e['tag'], $f);
			if (is_a($field, 'MarcParseError')) {
				return $field;
			}
			array_push($r->fields, $field);
		}
		return $r;
	}

	function _parseDirectory($directory) {
		if (!$this->lenient and $directory{strlen($directory)-1} != MARC_FT) {
			return $this->_error('directory unterminated');
		}
		$directory = substr($directory, 0, -1);
		$emap = array(
			'tag' => 3,
			'length' => 4,
			'start' => 5,
		);
		$entry_len = $emap['tag'] + $emap['length'] + $emap['start'];
		if (strlen($directory) % $entry_len != 0) {
			return $this->_error('directory is the wrong length');
		}
		$entries=array();
		while (strlen($directory)) {
			$e = array();
			$e['tag'] = substr($directory, 0, $emap['tag']);
			$p = $emap['tag'];
			foreach (array('length', 'start') as $f) {
				$s = substr($directory, $p, $emap[$f]);
				if (!ctype_digit($s)) {
					return self._error('non-numeric '.$f.' field in directory entry '.count(entries));
				}
				$e[$f] = $s;
				$p += $emap[$f];
			}
			array_push($entries, $e);
			$directory = substr($directory, $p);
		}
		return $entries;
	}

	function _parseField($tag, $field) {
		if (!$this->lenient and $field{strlen($field)-1} != MARC_FT) {
			return $this->_error('variable field unterminated: '+$field);
		}
		$field = substr($field, 0, -1);

		if (substr($tag, 0, 2) == '00') {
			return new MarcControlField($tag, $field);
		}

		# 2 is the number of indicators
		$f = new MarcDataField($tag, substr($field, 0, 2));
		$field = substr($field, 2);

		if ($field{0} != MARC_DELIMITER) {
			return $this->_error("missing delimiter in ".$f->tag." field, got '".$field."' instead");
		}
		$elems = explode(MARC_DELIMITER, $field);
		# Elements begin with a delimiter, but we treat it as
		# a separator, so the first one will always be empty and
		# is discarded.
		array_shift($elems);
		$f->subfields = array();
		foreach ($elems as $e) {
			# $e{0} is the subfield code
			array_push($f->subfields, new MarcSubfield($e{0}, substr($e, 1)));
		}
		return $f;
	}
}

class MarcMnemParser extends MarcBaseParser {
	function MarcMnemParser($lenient=True) {
		$this->MarcBaseParser($lenient);
		$this->_line = 0;
		$this->_rec = NULL;
		$this->_field = NULL;
		$this->_recnum = 1;
	}
	function _error($s) {
		return new MarcParseError($s, $this->_recnum, $this->_line);
	}
	function eof() {
		$this->_unparsed .= "\n\n";
		$n = $this->_parse();
		if (is_a($n, 'MarcParseError')) {
			return $n;
		}
		$this->_recnum = 1;
		if ($this->_rec != NULL) {
			array_push($this->records, $this->_rec);
			$this->_rec = NULL;
			return $n+1;
		}
		return $n;
	}

	function _parse() {
		$old_len = count($this->records);
		$data = str_replace("\r", "", $this->_unparsed);
		$lines = explode("\n", $data);
		$this->_unparsed = '';
		if (count($lines)) {
			# The last element is a partial line or an empty string.
			$this->_unparsed = array_pop($lines);
		}
		foreach ($lines as $l) {
			// Correct for explode() removing the newlines.
			$l .= "\n";
			if ($l{0} == '#') {
				// Comment
			} else if ($l{0} == '=') {
				$err = $this->_addField($this->_field);
				if (is_a($err, 'MarcParseError')) {
					return $err;
				}
				$this->_field = $l;
			} else if (trim($l) == '') {
				if ($this->_field) {
					$err = $this->_addField($this->_field);
					if (is_a($err, 'MarcParseError')) {
						return $err;
					}
					$this->_field = NULL;
				}
				if ($this->_rec) {
					array_push($this->records, $this->_rec);
					$this->_recnum += 1;
					$this->_rec = NULL;
				}
			} else if (!$this->_field) {
				return $this->_error("extra garbage outside of fields");
			} else {
				$this->_field .= $l;
			}
			$this->_line += 1;
		}
		return count($this->records)-$old_len;
	}

	function _addField($field) {
		if (!$field) {
			return;
		}
		if ($field{0} != '=') {
			return $this->_error("can't happen: non-field data in _field");
		}
		$field = rtrim($field, "\r\n");		# lose final newline
		if (strlen($field) < 4) {
			return $this->_error("field too short");
		}
		$tag = substr($field, 1, 3);
		if (substr($field, 4, 2) != '  ') {
			return $this->_error("two spaces must separate the tag from field data");
		}
		if (!$this->_rec) {
			$this->_rec = new MarcRecord();
		}

		# Set leader
		if (eregi('^(000|LDR)$', $tag)) {
			$ldr = MarcHelpers::fromMnem(substr($field, 6));
			$err = $this->_rec->setLeader($ldr, $this->lenient);
			if ($err) {
				return $this->_error("Invalid Leader: ".$err);
			}
			return;
		}

		if (substr($tag, 0, 2) == '00') {
			$data = MarcHelpers::fromMnem(substr($field, 6));
			$f = new MarcControlField($tag, $data);
		} else {
			$ind = MarcHelpers::fromMnem(substr($field, 6, 2));
			$f = new MarcDataField($tag, $ind);
			$data = substr($field, 8);
			$subs = explode('$', $data);
			# Subfields begin with a delimiter, but we treat it as
			# a separator, so the first one will always be empty (or
			# junk) and is discarded.
			array_shift($subs);
			$f->subfields = array();
			foreach ($subs as $s) {
				$d = MarcHelpers::fromMnem(substr($s, 1));
				# $s{0} is the subfield code
				array_push($f->subfields, new MarcSubfield($s{0}, $d));
			}
		}
		array_push($this->_rec->fields, $f);
		return;
	}
}
