<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/** @see Erlang_Serializer_Abstract */
require_once "Erlang/Serializer/Abstract.php";


/**
 * Serializer for null value.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @internal
 */
class Erlang_Serializer_Null extends Erlang_Serializer_Abstract
{
	/**
	 * Serializes null value.
	 *
	 * @param mixed $data Data to serialize.
	 * @param array $scheme Serialization scheme.
	 * @param array $path Path to item.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $path = array())
	{
		parent::serialize($data, $scheme, $path);

		if (!is_null($data)) {
			return null;
		}

		return $this->_serializers->string->serialize(
			"nil", array('string' => 'atom'), $path);
	}
}
