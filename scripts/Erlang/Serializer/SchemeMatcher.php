<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/**
 * Scheme matcher for serializer.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @internal
 */
class Erlang_Serializer_SchemeMatcher
{
	/** @var array Possible delimiters in pattern. */
	protected $_delims = array('/', '#', '@');


	/**
	 * Trying to find user defined type for current path.
	 *
	 * @return string|function|null User defined type or serializer.
	 */
	public function match($path, $scheme)
	{
		foreach ($scheme as $pattern => $type) {
			if ($this->_matchPattern($path, $pattern)) {
				return $type;
			}
		}

		return null;
	}


	/**
	 * Tries to match path by pattern.
	 *
	 * @param array $path Path to current element.
	 * @param string $pattern User specified pattern.
	 * @return bool true, if matches.
	 */
	protected function _matchPattern($path, $pattern)
	{
		$absolute = $pattern[0] == '/';
		$stack = array(0);
		while (!empty($stack)) {
			$pathIndex = array_pop($stack);

			$pathTail = array_slice($path, $pathIndex);

			if (empty($pathTail)) {
				break;
			}

			if (!$absolute) {
				array_unshift($stack, $pathIndex + 1);
			}

			$patternTail = $this->_parsePattern($pattern);

			do {
				// Do a loop while pattern or path is not empty.
				$result = $this->_matchPatternTail($patternTail, $pathTail);
			} while (!is_bool($result));

			if ($result) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Tries to match first component of pattern.
	 *
	 * This function matches only first element, and if
	 * it matches, shifts input arrays to one element.
	 *
	 * @param array $tail Prepared pattern, {@see _parsePattern()}
	 * @param array $path Path to current element.
	 * @return bool|null true, if entire pattern matches, false if entire pattern is not matched, null otherwise.
	 */
	public function _matchPatternTail(&$tail, &$path)
	{
		if (empty($tail) && empty($path)) {
			return true;
		}

		if (empty($tail) xor empty($path)) {
			return false;
		}

		$match = $this->_matchPathComponent($tail[0], $path[0]);
		switch ($match) {
			case 'nomatch':
				return false;
			case 'match':
				array_shift($tail);
				array_shift($path);
				return null;
			case 'skip':
				array_shift($path);
				return null;
			default:
				return null;
		}
	}


	/**
	 * Tries to match one part of pattern to one level of path.
	 *
	 * @param string $pattern Part of pattern ({@see _parsePattern()})
	 * @param array $variants Variants of one level of path.
	 * @return string 'match' if matches, 'nomatch' otherwise, 'skip' if current part of path can be skipped.
	 */
	public function _matchPathComponent($pattern, $variants)
	{
		if (!in_array($pattern[0], $this->_delims)) {
			$pattern = '/' . $pattern;
		}

		$pattern = preg_quote($pattern, '/');
		// Need to replace all \* to [^\/]*
		// Five slashes are:
		//   \* is a simple star in preg_replace
		//   \\ is a simple slash in one quoted string
		//   \\ interpreted as single slash by preg_replace
		// So, preg_replace got '/\\\*/' pattern.
		$regexp = preg_replace('/\\\\\*/', '[^\/]*', $pattern);
		$regexp = "/^$regexp$/";


		if (preg_grep($regexp, $variants)) {
			return 'match';
		} else {
			if (array_search('', $variants) !== false) {
				return 'skip';
			} else {
				return 'nomatch';
			}
		}
	}


	/**
	 * 
	 */
	public function _parsePattern($pattern)
	{
		$delims = preg_quote(join($this->_delims), '!');
		$regexp = "!([$delims][^$delims]+)!";
		$flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;

		return preg_split($regexp, $pattern, null, $flags);
	}
}
