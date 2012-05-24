<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */


/**
 * Serializer for number values.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @internal
 */
class Erlang_Serializer_Number extends Erlang_Serializer_Abstract
{
	/**
	 * Serializes number value;
	 *
	 * @param mixed $data Data to serialize.
	 * @return string Number as string.
	 */
	public function serialize($data, $scheme = array(), $path = array())
	{
		Erlang_Serializer_Abstract::serialize($data, $scheme, $path);

		if (!is_numeric($data)) {
			return null;
		}

		if (is_string($data)) {
			return $this->_serializeData($data, array('/numeric', '/'));
		} else {
			return $this->_serializeData($data, array('/number', '/'));
		}
	}


	/**
	 * Serializes number as number.
	 *
	 * @param numeric $numeric Numeric to serialize.
	 * @return string Result of serialization.
	 */
	protected function _serializeAsNumber($numeric)
	{
		return strval($numeric);
	}


	/**
	 * Serializes number as string.
	 *
	 * @param numeric $numeric Numeric to serialize.
	 * @return string Result of serialization.
	 */
	protected function _serializeAsString($numeric)
	{
		return $this->_serializers->string->serialize(strval($numeric));
	}
}
