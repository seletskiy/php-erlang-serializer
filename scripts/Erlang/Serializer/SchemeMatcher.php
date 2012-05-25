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


	/** @var array Pattern cache. */
	protected static $_cache = array();


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
		$pattern = $this->_preparePattern($pattern);

		do {
			// Do a loop while pattern or path is not empty.
			$result = $this->_matchPatternHead($pattern, $path, $absolute);
		} while (!is_bool($result));

		return $result;
	}


	/**
	 * Tries to match first component of pattern.
	 *
	 * This function matches only first element, and if
	 * it matches, shifts input arrays to one element.
	 *
	 * @param array $pattern Prepared pattern, {@see _preparePattern()}
	 * @param array $path Path to current element.
	 * @return bool|null true, if entire pattern matches, false if entire pattern is not matched, null otherwise.
	 */
	protected function _matchPatternHead(&$pattern, &$path, $absolute)
	{
		if (count($pattern) > count($path)) {
			return false;
		}

		if (empty($pattern)) {
			if (empty($path) || !$absolute) {
				return true;
			} else {
				return false;
			}
		}

		$match = $this->_matchPathComponent(end($pattern), end($path));
		switch ($match) {
			case 'nomatch':
				return false;
			case 'match':
				array_pop($pattern);
				array_pop($path);
				return null;
			case 'skip':
				array_pop($path);
				return null;
			default:
				return null;
		}
	}


	/**
	 * Tries to match one part of pattern to one level of path.
	 *
	 * @param string $pattern Part of pattern ({@see _preparePattern()})
	 * @param array $variants Variants of one level of path.
	 * @return string 'match' if matches, 'nomatch' otherwise, 'skip' if current part of path can be skipped.
	 */
	protected function _matchPathComponent($pattern, $variants)
	{
		if (preg_grep($pattern, $variants)) {
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
	 * Parses user specified pattern to internal format.
	 *
	 * @param string $pattern User specified pattern {@see Erlang_Serializer::serialize()}.
	 * @return array Pattern in internal format.
	 */
	protected function _preparePattern($pattern)
	{
		if (isset(self::$_cache[$pattern])) {
			return self::$_cache[$pattern];
		}

		$delims = preg_quote(join($this->_delims), '!');
		$regexp = "!([$delims][^$delims]+)!";
		$flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;

		$prepared = preg_split($regexp, $pattern, null, $flags);

		foreach ($prepared as &$part) {
			if (!in_array($part[0], $this->_delims)) {
				$part = '/' . $part;
			}

			$part = preg_quote($part, '/');
			// Need to replace all \* to [^\/]*
			// Five slashes are:
			//   \* is a simple star in preg_replace
			//   \\ is a simple slash in one quoted string
			//   \\ interpreted as single slash by preg_replace
			// So, preg_replace got '/\\\*/' pattern.
			$part = preg_replace('/\\\\\*/', '[^\/]*', $part);
			$part = "/^$part$/";
		}

		self::$_cache[$pattern] = $prepared;

		return $prepared;
	}
}
