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
 * @internal
 */
class Erlang_Serializer_Number
{
	/**
	 * Serializes number value;
	 *
	 * @param mixed $data Data to serialize.
	 * @return string Number as string.
	 */
	public function serialize($data)
	{
		if (!is_numeric($data)) {
			return null;
		}

		return strval($data);
	}
}
