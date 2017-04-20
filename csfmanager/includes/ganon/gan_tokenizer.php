<?php
/**
 * @author Niels A.D.
 * @package Ganon
 * @link http://code.google.com/p/ganon/
 * @license http://dev.perl.org/licenses/artistic.html Artistic License
 */

class Tokenizer_Base {
	const TOK_NULL = 0;
	const TOK_UNKNOWN = 1;
	const TOK_WHITESPACE = 2;
	const TOK_IDENTIFIER = 3;
	var $doc = '';
	var $size = 0;
	var $pos = 0;
	var $line_pos = array(0, 0);
	var $token = self::TOK_NULL;
	var $token_start = null;
	var $whitespace = " \t\n\r\0\x0B";
	var $identifiers = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_';
	var $custom_char_map = array();
	var $char_map = array();
	var $errors = array();
	function __construct($doc = '', $pos = 0) {
		$this->setWhitespace($this->whitespace);
		$this->setIdentifiers($this->identifiers);
		$this->setDoc($doc, $pos);
	}
	function setDoc($doc, $pos = 0) {
		$this->doc = $doc;
		$this->size = strlen($doc);
		$this->setPos($pos);
	}
	function getDoc() {
		return $this->doc;
	}
	function setPos($pos = 0) {
		$this->pos = $pos - 1;
		$this->line_pos = array(0, 0);
		$this->next();
	}
	function getPos() {
		return $this->pos;
	}
	function getLinePos() {
		return array($this->line_pos[0], $this->pos - $this->line_pos[1]);
	}
	function getToken() {
		return $this->token;
	}
	function getTokenString($start_offset = 0, $end_offset = 0) {
		$token_start = ((is_int($this->token_start)) ? $this->token_start : $this->pos) + $start_offset;
		$len = $this->pos - $token_start + 1 + $end_offset;
		return (($len > 0) ? substr($this->doc, $token_start, $len) : '');
	}
	function setWhitespace($ws) {
		if (is_array($ws)) {
			$this->whitespace = array_fill_keys(array_values($ws), true);
			$this->buildCharMap();
		} else {
			$this->setWhiteSpace(str_split($ws));
		}
	}
	function getWhitespace($as_string = true) {
		$ws = array_keys($this->whitespace);
		return (($as_string) ? implode('', $ws) : $ws);
	}
	function setIdentifiers($ident) {
		if (is_array($ident)) {
			$this->identifiers = array_fill_keys(array_values($ident), true);
			$this->buildCharMap();
		} else {
			$this->setIdentifiers(str_split($ident));
		}
	}
	function getIdentifiers($as_string = true) {
		$ident = array_keys($this->identifiers);
		return (($as_string) ? implode('', $ident) : $ident);
	}
	function mapChar($char, $map) {
		$this->custom_char_map[$char] = $map;
		$this->buildCharMap();
	}
	function unmapChar($char) {
		unset($this->custom_char_map[$char]);
		$this->buildCharMap();
	}
	protected function buildCharMap() {
		$this->char_map = $this->custom_char_map;
		if (is_array($this->whitespace)) {
			foreach($this->whitespace as $w => $v) {
				$this->char_map[$w] = 'parse_whitespace';
			}
		}
		if (is_array($this->identifiers)) {
			foreach($this->identifiers as $i => $v) {
				$this->char_map[$i] = 'parse_identifier';
			}
		}
	}
	function addError($error) {
		$this->errors[] = htmlentities($error.' at '.($this->line_pos[0] + 1).', '.($this->pos - $this->line_pos[1] + 1).'!');
	}
	protected function parse_linebreak() {
		if($this->doc[$this->pos] === "\r") {
			++$this->line_pos[0];
			if ((($this->pos + 1) < $this->size) && ($this->doc[$this->pos + 1] === "\n")) {
				++$this->pos;
			}
			$this->line_pos[1] = $this->pos;
		} elseif($this->doc[$this->pos] === "\n") {
			++$this->line_pos[0];
			$this->line_pos[1] = $this->pos;
		}
	}
	protected function parse_whitespace() {
		$this->token_start = $this->pos;
		while(++$this->pos < $this->size) {
			if (!isset($this->whitespace[$this->doc[$this->pos]])) {
				break;
			} else {
				$this->parse_linebreak();
			}
		}
		--$this->pos;
		return self::TOK_WHITESPACE;
	}
	protected function parse_identifier() {
		$this->token_start = $this->pos;
		while((++$this->pos < $this->size) && isset($this->identifiers[$this->doc[$this->pos]])) {}
		--$this->pos;
		return self::TOK_IDENTIFIER;
	}
	function next() {
		$this->token_start = null;
		if (++$this->pos < $this->size) {
			if (isset($this->char_map[$this->doc[$this->pos]])) {
				if (is_string($this->char_map[$this->doc[$this->pos]])) {
					return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
				} else {
					return ($this->token = $this->char_map[$this->doc[$this->pos]]);
				}
			} else {
				return ($this->token = self::TOK_UNKNOWN);
			}
		} else {
			return ($this->token = self::TOK_NULL);
		}
	}
	function next_no_whitespace() {
		$this->token_start = null;
		while (++$this->pos < $this->size) {
			if (!isset($this->whitespace[$this->doc[$this->pos]])) {
				if (isset($this->char_map[$this->doc[$this->pos]])) {
					if (is_string($this->char_map[$this->doc[$this->pos]])) {
						return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
					} else {
						return ($this->token = $this->char_map[$this->doc[$this->pos]]);
					}
				} else {
					return ($this->token = self::TOK_UNKNOWN);
				}
			} else {
				$this->parse_linebreak();
			}
		}
		return ($this->token = self::TOK_NULL);
	}
	function next_search($characters, $callback = true) {
		$this->token_start = $this->pos;
		if (!is_array($characters)) {
			$characters = array_fill_keys(str_split($characters), true);
		}
		while(++$this->pos < $this->size) {
			if (isset($characters[$this->doc[$this->pos]])) {
				if ($callback && isset($this->char_map[$this->doc[$this->pos]])) {
					if (is_string($this->char_map[$this->doc[$this->pos]])) {
						return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
					} else {
						return ($this->token = $this->char_map[$this->doc[$this->pos]]);
					}
				} else {
					return ($this->token = self::TOK_UNKNOWN);
				}
			} else {
				$this->parse_linebreak();
			}
		}
		return ($this->token = self::TOK_NULL);
	}
	function next_pos($needle, $callback = true) {
		$this->token_start = $this->pos;
		if (($this->pos < $this->size) && (($p = stripos($this->doc, $needle, $this->pos + 1)) !== false)) {
			$len = $p - $this->pos - 1;
			if ($len > 0) {
				$str = substr($this->doc, $this->pos + 1, $len);
				if (($l = strrpos($str, "\n")) !== false) {
					++$this->line_pos[0];
					$this->line_pos[1] = $l + $this->pos + 1;
					$len -= $l;
					if ($len > 0) {
						$str = substr($str, 0, -$len);
						$this->line_pos[0] += substr_count($str, "\n");
					}
				}
			}
			$this->pos = $p;
			if ($callback && isset($this->char_map[$this->doc[$this->pos]])) {
				if (is_string($this->char_map[$this->doc[$this->pos]])) {
					return ($this->token = $this->{$this->char_map[$this->doc[$this->pos]]}());
				} else {
					return ($this->token = $this->char_map[$this->doc[$this->pos]]);
				}
			} else {
				return ($this->token = self::TOK_UNKNOWN);
			}
		} else {
			$this->pos = $this->size;
			return ($this->token = self::TOK_NULL);
		}
	}
	protected function expect($token, $do_next = true, $try_next = false, $next_on_match = 1) {
		if ($do_next) {
			if ($do_next === 1) {
				$this->next();
			} else {
				$this->next_no_whitespace();
			}
		}
		if (is_int($token)) {
			if (($this->token !== $token) && ((!$try_next) || ((($try_next === 1) && ($this->next() !== $token)) || (($try_next === true) && ($this->next_no_whitespace() !== $token))))) {
				$this->addError('Unexpected "'.$this->getTokenString().'"');
				return false;
			}
		} else {
			if (($this->doc[$this->pos] !== $token) && ((!$try_next) || (((($try_next === 1) && ($this->next() !== self::TOK_NULL)) || (($try_next === true) && ($this->next_no_whitespace() !== self::TOK_NULL))) && ($this->doc[$this->pos] !== $token)))) {
				$this->addError('Expected "'.$token.'", but found "'.$this->getTokenString().'"');
				return false;
			}
		}
		if ($next_on_match) {
			if ($next_on_match === 1) {
				$this->next();
			} else {
				$this->next_no_whitespace();
			}
		}
		return true;
	}
}

?>