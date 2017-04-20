<?php
/**
 * @author Niels A.D.
 * @package Ganon
 * @link http://code.google.com/p/ganon/
 * @license http://dev.perl.org/licenses/artistic.html Artistic License
 */

class HTML_Parser_Base extends Tokenizer_Base {
	const TOK_TAG_OPEN = 100;
	const TOK_TAG_CLOSE = 101;
	const TOK_SLASH_FORWARD = 103;
	const TOK_SLASH_BACKWARD = 104;
	const TOK_STRING = 104;
	const TOK_EQUALS = 105;
	var $identifiers = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890:-_!?%';
	var $status = array();
	var $custom_char_map = array(
		'<' => self::TOK_TAG_OPEN,
		'>' => self::TOK_TAG_CLOSE,
		"'" => 'parse_string',
		'"' => 'parse_string',
		'/' => self::TOK_SLASH_FORWARD,
		'\\' => self::TOK_SLASH_BACKWARD,
		'=' => self::TOK_EQUALS
	);
	function __construct($doc = '', $pos = 0) {
		parent::__construct($doc, $pos);
		$this->parse_all();
	}
	var $tag_map = array(
		'!doctype' => 'parse_doctype',
		'?' => 'parse_php',
		'?php' => 'parse_php',
		'%' => 'parse_asp',
		'style' => 'parse_style',
		'script' => 'parse_script'
	);
	protected function parse_string() {
		if ($this->next_pos($this->doc[$this->pos], false) !== self::TOK_UNKNOWN) {
			--$this->pos;
		}
		return self::TOK_STRING;
	}
	function parse_text() {
		$len = $this->pos - 1 - $this->status['last_pos'];
		$this->status['text'] = (($len > 0) ? substr($this->doc, $this->status['last_pos'] + 1, $len) : '');
	}
	function parse_comment() {
		$this->pos += 3;
		if ($this->next_pos('-->', false) !== self::TOK_UNKNOWN) {
			$this->status['comment'] = $this->getTokenString(1, -1);
			--$this->pos;
		} else {
			$this->status['comment'] = $this->getTokenString(1, -1);
			$this->pos += 2;
		}
		$this->status['last_pos'] = $this->pos;
		return true;
	}
	function parse_doctype() {
		$start = $this->pos;
		if ($this->next_search('[>', false) === self::TOK_UNKNOWN)  {
			if ($this->doc[$this->pos] === '[') {
				if (($this->next_pos(']', false) !== self::TOK_UNKNOWN) || ($this->next_pos('>', false) !== self::TOK_UNKNOWN)) {
					$this->addError('Invalid doctype');
					return false;
				}
			}
			$this->token_start = $start;
			$this->status['dtd'] = $this->getTokenString(2, -1);
			$this->status['last_pos'] = $this->pos;
			return true;
		} else {
			$this->addError('Invalid doctype');
			return false;
		}
	}
	function parse_cdata() {
		if ($this->next_pos(']]>', false) === self::TOK_UNKNOWN) {
			$this->status['cdata'] = $this->getTokenString(9, -1);
			$this->status['last_pos'] = $this->pos + 2;
			return true;
		} else {
			$this->addError('Invalid cdata tag');
			return false;
		}
	}
	function parse_php() {
		$start = $this->pos;
		if ($this->next_pos('?>', false) !== self::TOK_UNKNOWN) {
			$this->pos -= 2; 
		}
		$len = $this->pos - 1 - $start;
		$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
		$this->status['last_pos'] = ++$this->pos;
		return true;
	}
	function parse_asp() {
		$start = $this->pos;
		if ($this->next_pos('%>', false) !== self::TOK_UNKNOWN) {
			$this->pos -= 2; 
		}
		$len = $this->pos - 1 - $start;
		$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
		$this->status['last_pos'] = ++$this->pos;
		return true;
	}
	function parse_style() {
		if ($this->parse_attributes() && ($this->token === self::TOK_TAG_CLOSE) && ($start = $this->pos) && ($this->next_pos('</style>', false) === self::TOK_UNKNOWN)) {
			$len = $this->pos - 1 - $start;
			$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
			$this->pos += 7;
			$this->status['last_pos'] = $this->pos;
			return true;
		} else {
			$this->addError('No end for style tag found');
			return false;
		}
	}
	function parse_script() {
		if ($this->parse_attributes() && ($this->token === self::TOK_TAG_CLOSE) && ($start = $this->pos) && ($this->next_pos('</script>', false) === self::TOK_UNKNOWN)) {
			$len = $this->pos - 1 - $start;
			$this->status['text'] = (($len > 0) ? substr($this->doc, $start + 1, $len) : '');
			$this->pos += 8;
			$this->status['last_pos'] = $this->pos;
			return true;
		} else {
			$this->addError('No end for script tag found');
			return false;
		}
	}
	function parse_conditional() {
		if ($this->status['closing_tag']) {
			$this->pos += 8;
		} else {
			$this->pos += (($this->status['comment']) ? 5 : 3);
			if ($this->next_pos(']', false) !== self::TOK_UNKNOWN) {
				$this->addError('"]" not found in conditional tag');
				return false;
			}
			$this->status['tag_condition'] = $this->getTokenString(0, -1);
		}
		if ($this->next_no_whitespace() !== self::TOK_TAG_CLOSE) {
			$this->addError('No ">" tag found 2 for conditional tag');
			return false;
		}
		if ($this->status['comment']) {
			$this->status['last_pos'] = $this->pos;
			if ($this->next_pos('-->', false) !== self::TOK_UNKNOWN) {
				$this->addError('No ending tag found for conditional tag');
				$this->pos = $this->size - 1;
				$len = $this->pos - 1 - $this->status['last_pos'];
				$this->status['text'] = (($len > 0) ? substr($this->doc, $this->status['last_pos'] + 1, $len) : '');
			} else {
				$len = $this->pos - 10 - $this->status['last_pos'];
				$this->status['text'] = (($len > 0) ? substr($this->doc, $this->status['last_pos'] + 1, $len) : '');
				$this->pos += 2;
			}
		}
		$this->status['last_pos'] = $this->pos;
		return true;
	}
	function parse_attributes() {
		$this->status['attributes'] = array();
		while ($this->next_no_whitespace() === self::TOK_IDENTIFIER) {
			$attr = $this->getTokenString();
			if (($attr === '?') || ($attr === '%')) {
				break;
			}
			if ($this->next_no_whitespace() === self::TOK_EQUALS) {
				if ($this->next_no_whitespace() === self::TOK_STRING) {
					$val = $this->getTokenString(1, -1);
				} else {
					if (!isset($stop)) {
						$stop = $this->whitespace;
						$stop['<'] = true;
						$stop['>'] = true;
					}
					while ((++$this->pos < $this->size) && (!isset($stop[$this->doc[$this->pos]]))) {}
					--$this->pos;
					$val = $this->getTokenString();
					if (trim($val) === '') {
						$this->addError('Invalid attribute value');
						return false;
					}
				}
			} else {
				$val = $attr;
				$this->pos = (($this->token_start) ? $this->token_start : $this->pos) - 1;
			}
			$this->status['attributes'][$attr] = $val;
		}
		return true;
	}
	function parse_tag_default() {
		if ($this->status['closing_tag']) {
			$this->status['attributes'] = array();
			$this->next_no_whitespace();
		} else {
			if (!$this->parse_attributes()) {
				return false;
			}
		}
		if ($this->token !== self::TOK_TAG_CLOSE) {
			if ($this->token === self::TOK_SLASH_FORWARD) {
				$this->status['self_close'] = true;
				$this->next();
			} elseif ((($this->status['tag_name'][0] === '?') && ($this->doc[$this->pos] === '?')) || (($this->status['tag_name'][0] === '%') && ($this->doc[$this->pos] === '%'))) {
				$this->status['self_close'] = true;
				$this->pos++;
				if (isset($this->char_map[$this->doc[$this->pos]]) && (!is_string($this->char_map[$this->doc[$this->pos]]))) {
					$this->token = $this->char_map[$this->doc[$this->pos]];
				} else {
					$this->token = self::TOK_UNKNOWN;
				}
			}
		}
		if ($this->token !== self::TOK_TAG_CLOSE) {
			$this->addError('Expected ">", but found "'.$this->getTokenString().'"');
			if ($this->next_pos('>', false) !== self::TOK_UNKNOWN) {
				$this->addError('No ">" tag found for "'.$this->status['tag_name'].'" tag');
				return false;
			}
		}
		return true;
	}
	function parse_tag() {
		$start = $this->pos;
		$this->status['self_close'] = false;
		$this->parse_text();
		$next = (($this->pos + 1) < $this->size) ? $this->doc[$this->pos + 1] : '';
		if ($next === '!') {
			$this->status['closing_tag'] = false;
			if (substr($this->doc, $this->pos + 2, 2) === '--') {
				$this->status['comment'] = true;
				if (($this->doc[$this->pos + 4] === '[') && (strcasecmp(substr($this->doc, $this->pos + 5, 2), 'if') === 0)) {
					return $this->parse_conditional();
				} else {
					return $this->parse_comment();
				}
			} else {
				$this->status['comment'] = false;
				if ($this->doc[$this->pos + 2] === '[') {
					if (strcasecmp(substr($this->doc, $this->pos + 3, 2), 'if') === 0) {
						return $this->parse_conditional();
					} elseif (strcasecmp(substr($this->doc, $this->pos + 3, 5), 'endif') === 0) {
						$this->status['closing_tag'] = true;
						return $this->parse_conditional();
					} elseif (strcasecmp(substr($this->doc, $this->pos + 3, 5), 'cdata') === 0) {
						return $this->parse_cdata();
					}
				}
			}
		} elseif ($next === '/') {
			$this->status['closing_tag'] = true;
			++$this->pos;
		} else {
			$this->status['closing_tag'] = false;
		}
		if ($this->next() !== self::TOK_IDENTIFIER) {
			$this->addError('Tagname expected');
				$this->status['last_pos'] = $start - 1;
				return true;
		}
		$tag = $this->getTokenString();
		$this->status['tag_name'] = $tag;
		$tag = strtolower($tag);
		if (isset($this->tag_map[$tag])) {
			$res = $this->{$this->tag_map[$tag]}();
		} else {
			$res = $this->parse_tag_default();
		}
		$this->status['last_pos'] = $this->pos;
		return $res;
	}
	function parse_all() {
		$this->errors = array();
		$this->status['last_pos'] = -1;
		if (($this->token === self::TOK_TAG_OPEN) || ($this->next_pos('<', false) === self::TOK_UNKNOWN)) {
			do {
				if (!$this->parse_tag()) {
					return false;
				}
			} while ($this->next_pos('<') !== self::TOK_NULL);
		}
		$this->pos = $this->size;
		$this->parse_text();
		return true;
	}
}
class HTML_Parser extends HTML_Parser_Base {
	var $root = 'HTML_Node';
	var $hierarchy = array();
	var	$tags_selfclose = array(
		'area'		=> true,
		'base'		=> true,
		'basefont'	=> true,
		'br'		=> true,
		'col'		=> true,
		'command'	=> true,
		'embed'		=> true,
		'frame'		=> true,
		'hr'		=> true,
		'img'		=> true,
		'input'		=> true,
		'ins'		=> true,
		'keygen'	=> true,
		'link'		=> true,
		'meta'		=> true,
		'param'		=> true,
		'source'	=> true,
		'track'		=> true,
		'wbr'		=> true
	);
	function __construct($doc = '', $pos = 0, $root = null) {
		if ($root === null) {
			$root = new $this->root('~root~', null);
		}
		$this->root =& $root;
		parent::__construct($doc, $pos);
	}
	function __invoke($query = '*') {
		return $this->select($query);
	}
	function __toString() {
		return $this->root->getInnerText();
	}
	function select($query = '*', $index = false, $recursive = true, $check_self = false) {
		return $this->root->select($query, $index, $recursive, $check_self);
	}
	protected function parse_hierarchy($self_close = null) {
		if ($self_close === null) {
			$this->status['self_close'] = ($self_close = isset($this->tags_selfclose[strtolower($this->status['tag_name'])]));
		}
		if ($self_close) {
			if ($this->status['closing_tag']) {
				$c = $this->hierarchy[count($this->hierarchy) - 1]->children;
				$found = false;
				for ($count = count($c), $i = $count - 1; $i >= 0; $i--) {
					if (strcasecmp($c[$i]->tag, $this->status['tag_name']) === 0) {
						for($ii = $i + 1; $ii < $count; $ii++) {
							$index = null; 
							$c[$i + 1]->changeParent($c[$i], $index);
						}
						$c[$i]->self_close = false;
						$found = true;
						break;
					}
				}
				if (!$found) {
					$this->addError('Closing tag "'.$this->status['tag_name'].'" which is not open');
				}
			} elseif ($this->status['tag_name'][0] === '?') {
				$index = null; 
				$this->hierarchy[count($this->hierarchy) - 1]->addXML($this->status['tag_name'], '', $this->status['attributes'], $index);
			} elseif ($this->status['tag_name'][0] === '%') {
				$index = null; 
				$this->hierarchy[count($this->hierarchy) - 1]->addASP($this->status['tag_name'], '', $this->status['attributes'], $index);
			} else {
				$index = null; 
				$this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);
			}
		} elseif ($this->status['closing_tag']) {
			$found = false;
			for ($count = count($this->hierarchy), $i = $count - 1; $i >= 0; $i--) {
				if (strcasecmp($this->hierarchy[$i]->tag, $this->status['tag_name']) === 0) {
					for($ii = ($count - $i - 1); $ii >= 0; $ii--) {
						$e = array_pop($this->hierarchy);
						if ($ii > 0) {
							$this->addError('Closing tag "'.$this->status['tag_name'].'" while "'.$e->tag.'" is not closed yet');
						}
					}
					$found = true;
					break;
				}
			}
			if (!$found) {
				$this->addError('Closing tag "'.$this->status['tag_name'].'" which is not open');
			}
		} else {
			$index = null; 
			$this->hierarchy[] = $this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);	
		}
	}
	function parse_cdata() {
		if (!parent::parse_cdata()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addCDATA($this->status['cdata'], $index);
		return true;
	}
	function parse_comment() {
		if (!parent::parse_comment()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addComment($this->status['comment'], $index);
		return true;
	}
	function parse_conditional() {
		if (!parent::parse_conditional()) {return false;}
		if ($this->status['comment']) {
			$index = null; 
			$e = $this->hierarchy[count($this->hierarchy) - 1]->addConditional($this->status['tag_condition'], true, $index);
			if ($this->status['text'] !== '') {
				$index = null; 
				$e->addText($this->status['text'], $index);
			}
		} else {
			if ($this->status['closing_tag']) {
				$this->parse_hierarchy(false);
			} else {
				$index = null; 
				$this->hierarchy[] = $this->hierarchy[count($this->hierarchy) - 1]->addConditional($this->status['tag_condition'], false, $index);
			}
		}
		return true;
	}
	function parse_doctype() {
		if (!parent::parse_doctype()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addDoctype($this->status['dtd'], $index);
		return true;
	}
	function parse_php() {
		if (!parent::parse_php()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addXML('php', $this->status['text'], $index);
		return true;
	}
	function parse_asp() {
		if (!parent::parse_asp()) {return false;}
		$index = null; 
		$this->hierarchy[count($this->hierarchy) - 1]->addASP('', $this->status['text'], $index);
		return true;
	}
	function parse_script() {
		if (!parent::parse_script()) {return false;}
		$index = null; 
		$e = $this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);
		if ($this->status['text'] !== '') {
			$index = null; 
			$e->addText($this->status['text'], $index);
		}
		return true;
	}
	function parse_style() {
		if (!parent::parse_style()) {return false;}
		$index = null; 
		$e = $this->hierarchy[count($this->hierarchy) - 1]->addChild($this->status, $index);
		if ($this->status['text'] !== '') {
			$index = null; 
			$e->addText($this->status['text'], $index);
		}
		return true;
	}
	function parse_tag_default() {
		if (!parent::parse_tag_default()) {return false;}
		$this->parse_hierarchy(($this->status['self_close']) ? true : null);
		return true;
	}
	function parse_text() {
		parent::parse_text();
		if ($this->status['text'] !== '') {
			$index = null; 
			$this->hierarchy[count($this->hierarchy) - 1]->addText($this->status['text'], $index);
		}
	}
	function parse_all() {
		$this->hierarchy = array(&$this->root);
		return ((parent::parse_all()) ? $this->root : false);
	}
}
class HTML_Parser_HTML5 extends HTML_Parser {
	var $tags_optional_close = array(
		'li' 			=> array('li' => true),
		'dt' 			=> array('dt' => true, 'dd' => true),
		'dd' 			=> array('dt' => true, 'dd' => true),
		'address' 		=> array('p' => true),
		'article' 		=> array('p' => true),
		'aside' 		=> array('p' => true),
		'blockquote' 	=> array('p' => true),
		'dir' 			=> array('p' => true),
		'div' 			=> array('p' => true),
		'dl' 			=> array('p' => true),
		'fieldset' 		=> array('p' => true),
		'footer' 		=> array('p' => true),
		'form' 			=> array('p' => true),
		'h1' 			=> array('p' => true),
		'h2' 			=> array('p' => true),
		'h3' 			=> array('p' => true),
		'h4' 			=> array('p' => true),
		'h5' 			=> array('p' => true),
		'h6' 			=> array('p' => true),
		'header' 		=> array('p' => true),
		'hgroup' 		=> array('p' => true),
		'hr' 			=> array('p' => true),
		'menu' 			=> array('p' => true),
		'nav' 			=> array('p' => true),
		'ol' 			=> array('p' => true),
		'p' 			=> array('p' => true),
		'pre' 			=> array('p' => true),
		'section' 		=> array('p' => true),
		'table' 		=> array('p' => true),
		'ul' 			=> array('p' => true),
		'rt'			=> array('rt' => true, 'rp' => true),
		'rp'			=> array('rt' => true, 'rp' => true),
		'optgroup'		=> array('optgroup' => true, 'option' => true),
		'option'		=> array('option'),
		'tbody'			=> array('thread' => true, 'tbody' => true, 'tfoot' => true),
		'tfoot'			=> array('thread' => true, 'tbody' => true),
		'tr'			=> array('tr' => true),
		'td'			=> array('td' => true, 'th' => true),
		'th'			=> array('td' => true, 'th' => true),
		'body'			=> array('head' => true)
	);
	protected function parse_hierarchy($self_close = null) {
		$tag_curr = strtolower($this->status['tag_name']);
		if ($self_close === null) {
			$this->status['self_close'] = ($self_close = isset($this->tags_selfclose[$tag_curr]));
		}
		if (! ($self_close || $this->status['closing_tag'])) {
			$tag_prev = strtolower($this->hierarchy[count($this->hierarchy) - 1]->tag);			
			if (isset($this->tags_optional_close[$tag_curr]) && isset($this->tags_optional_close[$tag_curr][$tag_prev])) {
				array_pop($this->hierarchy);
			}
		}
		return parent::parse_hierarchy($self_close);
	}
}

?>