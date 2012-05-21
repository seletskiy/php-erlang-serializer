<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/** @see Erlang_Serializer_Abstract */
require_once "Erlang/Serializer/Abstract.php";


class Erlang_Serializer_Array_Key extends Erlang_Serializer_String
{
	/**
	 * Serializes array key.
	 *
	 * @param numeric|string $key Incoming key.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $stack = array())
	{
		Erlang_Serializer_Abstract::serialize($data, $scheme, $stack);

		return $this->_serializeData($data, '@key');
	}


	/**
	 * Serializes array key as is (`serialize($key)`).
	 *
	 * @param numeric|string $key Incoming key.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeAsIs($key)
	{
		return $this->_makePartial($key);
	}
}

