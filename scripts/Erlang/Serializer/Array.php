<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/** @see Erlang_Serializer_Abstract */
require_once "Erlang/Serializer/Abstract.php";

/** @see Erlang_Serializer_Array_Item_Numeric.php */
require_once "Erlang/Serializer/Array/Item/Numeric.php";

/** @see Erlang_Serializer_Array_Item_Numeric.php */
require_once "Erlang/Serializer/Array/Item/Numeric.php";


/**
 * Serializer for arrays.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @internal
 */
class Erlang_Serializer_Array extends Erlang_Serializer_Abstract
{
	/**
	 * Constructs serialier.
	 *
	 * Registers two serializers: assoc and numeric items serializers.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_serializers->item = array(
			new Erlang_Serializer_Array_Item_Numeric,
			new Erlang_Serializer_Array_Item_Assoc
		);
	}


	/**
	 * Serializes array.
	 *
	 * @param mixed $data Data to serialize.
	 * @param array $scheme Serialization scheme.
	 * @param array $path Path to item.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $path = array())
	{
		parent::serialize($data, $scheme, $path);

		if (!is_array($data)) {
			return null;
		}

		return $this->_serializeData($data, array('/array', '/'));
	}

	/**
	 * Serializes array as simple list.
	 *
	 * @param array $array Array to serialize.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeAsList($array)
	{
		return $this->_serializeWithDelims($array, '[', ']');
	}


	/**
	 * Serializes array as tuple.
	 *
	 * @param array $array Array to serialize.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeAsTuple($array)
	{
		return $this->_serializeWithDelims($array, '{', '}');
	}


	/**
	 * Abstract method to serialize array with custom delims.
	 *
	 * @param array $array Array to serialize.
	 * @param string $ldelim Left delimiter.
	 * @param string $rdelim Right delimiter.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeWithDelims($array, $ldelim, $rdelim)
	{
		$result = array();
		// Poor man's join function.
		foreach ($array as $key => $value) {
			$result = array_merge($result, $this->_serializeItem($key, $value));
			$result[] = ', ';
		}

		return array_merge(
			array($ldelim),
			array_slice($result, 0, -1),
			array($rdelim));
	}


	/**
	 * Serializes array item.
	 *
	 * @param mixed $key Key of item.
	 * @param mixed $value item.
	 * @return string Serialized value or array item.
	 */
	protected function _serializeItem($key, $value)
	{

		foreach ($this->_serializers->item as $serializer) {
			$result = $serializer->serialize(array($key => $value),
				$this->_scheme, $this->_path);
			if (!is_null($result)) {
				return $result;
			}
		}

		return null;
	}
}
