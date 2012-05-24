<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/** @see Erlang_Serializer_Array_Item_Assoc */
require_once "Erlang/Serializer/Array/Item/Assoc.php";

/** @see Erlang_Serializer_Abstract */
require_once "Erlang/Serializer/Abstract.php";


/**
 * Serializes numeric item from array (item with numeric key).
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @internal
 */
class Erlang_Serializer_Array_Item_Numeric extends Erlang_Serializer_Array_Item_Assoc
{
	/**
	 * Serializes numeric array item.
	 *
	 * @param mixed $data Data to serialize `array($key => $value)`.
	 * @param array $scheme Serialization scheme.
	 * @param array $path Path to item.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $path = array())
	{
		Erlang_Serializer_Abstract::serialize($data, $scheme, $path);

		if (!is_array($data)) {
			return null;
		}

		$key = key($data);
		if (!is_numeric($key)) {
			return null;
		}

		return $this->_serializeData($data, array("#$key", '#number', ''), '@keyvalue');
	}
}
