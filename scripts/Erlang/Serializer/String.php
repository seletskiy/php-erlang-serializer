<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/** @see Erlang_Serializer_Abstract */
require_once "Erlang/Serializer/Abstract.php";


/**
 * Serializer for string.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @internal
 */
class Erlang_Serializer_String extends Erlang_Serializer_Abstract
{
	/**
	 * Constructs serializer.
	 *
	 * Some dirty hack to prevent recursion, because of Erlang_Serializer_Abstract
	 * creates this class in constructor.
	 */
	public function __construct()
	{
		$this->_matcher = new Erlang_Serializer_SchemeMatcher;
	}


	/**
	 * Serializes string.
	 *
	 * @param mixed $data Data to serialize.
	 * @param array $scheme Serialization scheme.
	 * @param array $path Path to item.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $path = array())
	{
		parent::serialize($data, $scheme + array('string' => 'string'), $path);

		if (!is_string($data)) {
			return null;
		}

		return $this->_serializeData($data, array('/string', '/'));
	}


	/**
	 * Serializes input string as atom.
	 *
	 * This method used because of PHP have not atom type.
	 *
	 * @param string $string String to be represented as atom.
	 * @return string Erlang atom.
	 */
	protected function _serializeAsAtom($string)
	{
		if (preg_match('/^[[:lower:]][_[:alnum:]]*$/', $string)) {
			return $string;
		}

		return "'" . addcslashes($string, "'\\") . "'";
	}


	/**
	 * Serializes input string as erlang string.
	 *
	 * @param string $string Incoming string.
	 * @return string Erlang string.
	 */
	protected function _serializeAsString($string)
	{
		return '"' . addcslashes($string, '"\\') . '"';
	}
}
