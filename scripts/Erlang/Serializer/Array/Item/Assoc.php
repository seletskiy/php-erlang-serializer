<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/** @see Erlang_Serializer_Abstract */
require_once "Erlang/Serializer/Abstract.php";

/** @see Erlang_Serializer_Array_Key */
require_once "Erlang/Serializer/Array/Key.php";


/**
 * Serializer for array item with string key (assoc).
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @internal
 */
class Erlang_Serializer_Array_Item_Assoc extends Erlang_Serializer_Abstract
{
	/**
	 * Constructs serializer.
	 *
	 * Register array key serializer.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_serializers->key = new Erlang_Serializer_Array_Key;
	}


	/**
	 * Serializes assoc item.
	 *
	 * @param mixed $data Data to serialize.
	 * @param array $scheme Serialization scheme.
	 * @param array $stack Path to item.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $stack = array())
	{
		parent::serialize($data, $scheme, $stack);

		if (!is_array($data)) {
			return null;
		}

		if (!is_string(key($data))) {
			return null;
		}

		$path = $this->_serializers->string->serialize(key($data));

		return $this->_serializeData($data, array("#$path/", '#::string/', '/'));
	}


	/**
	 * Serializes array item with numeric key as `keytuple` (`{serialize($key), serialize($value)}`).
	 *
	 * @param array $keyvalue `array($key => $value)`.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeAsKeytuple($keyvalue)
	{
		$serializedKey = $this->_serializers->key->serialize(key($keyvalue),
			$this->_scheme, $this->_stack);
		$serializedVal = $this->_makePartial(current($keyvalue));

		return array('{', $serializedKey, ', ', $serializedVal, '}');
	}


	/**
	 * Serializes array item as is (`serialize(value)`, key will be ommited).
	 * @return array Partial result of serialization.
	 */
	protected function _serializeAsIs($keyvalue)
	{
		return array($this->_makePartial(current($keyvalue)));
	}
}
