<?php

/**
 * Serializer to erlang term format.
 *
 * Rules:
 * <ul>
 * <li><pre>NULL          -> nil;</pre>
 * <li><pre>"string"      -> "string";</pre>
 * <li><pre>numeric array -> list [...];</pre>
 * <li><pre>assoc array   -> proplist [{$key, $value}, ...];</pre>
 * </ul>
 */
class Erlang_Serializer
{
	/**
	 * Serializes $data to erlang format.
	 *
	 * All PHP strings serializes into Erlang strings except
	 * keys of assoc array, they are serialized into atoms.
	 *
	 * @param mixed $data Data for serialization.
	 * @return string Erlang term.
	 */
	public function serialize($data)
	{
		$result = $data;
		$serialized = false;

		list($serialized, $result) = $this->_serializeNull($result, $serialized);
		list($serialized, $result) = $this->_serializeArray($result, $serialized);
		list($serialized, $result) = $this->_serializeString($result, $serialized);
		list($serialized, $result) = $this->_serializeNumber($result, $serialized);

		if (!$serialized) {
			return null;
		}

		return $result;
	}


	/**
	 * Serializes input string as atom.
	 *
	 * This method used because of PHP have not atom type.
	 *
	 * @param string $data String to be represented as atom.
	 * @return string Erlang atom.
	 */
	public function serializeAtom($data)
	{
		if (preg_match('/^[[:lower:]][_[:alnum:]]*$/', $data)) {
			return $data;
		}

		return "'" . addcslashes($data, "'\\") . "'";
	}


	/**
	 * Serializes null value.
	 *
	 * @param mixed $data Null value.
	 * @param bool $serialized Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeNull($data, $serialized)
	{
		if ($serialized || !is_null($data)) {
			return array($serialized, $data);
		}

		return array(true, $this->serializeAtom("nil"));
	}


	/**
	 * Serializes array value.
	 *
	 * @param mixed $data Array value.
	 * @param bool $serialized Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeArray($data, $serialized)
	{
		if ($serialized || !is_array($data)) {
			return array($serialized, $data);
		}

		$result = array();
		foreach ($data as $key => $value) {
			$result[] = $this->_serializeArrayItem($key, $value);
		}

		return array(true, '[' . join(', ', $result) . ']');
	}


	/**
	 * Serializes array item.
	 *
	 * @param mixed $key Key of item.
	 * @param mixed $value item.
	 * @return string Serialized value or array item.
	 */
	protected function _serializeArrayItem($key, $value)
	{
		$result = $value;
		$serialized = false;

		list($serialized, $result) = $this->_serializeArrayItemNumeric(
			$key, $result, $serialized);
		list($serialized, $result) = $this->_serializeArrayItemAssoc(
			$key, $result, $serialized);

		return $result;
	}


	/**
	 * Serializes numeric array item (with numeric key).
	 *
	 * @param mixed $key Key of item.
	 * @param mixed $value item.
	 * @param bool $serialized Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeArrayItemNumeric($key, $value, $serialized)
	{
		if ($serialized || !is_numeric($key)) {
			return array($serialized, $value);
		}

		return array(true, $this->serialize($value));
	}


	/**
	 * Serializes assoc array item.
	 *
	 * @param mixed $key Key of item.
	 * @param mixed $value item.
	 * @param bool $serialized Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeArrayItemAssoc($key, $value, $serialized)
	{
		if ($serialized) {
			return array($serialized, $value);
		}

		return array(true,
			'{' . $this->serializeAtom($key) . ', ' . $this->serialize($value) . '}');
	}


	/**
	 * Serializes string.
	 *
	 * @param mixed $data String value.
	 * @param bool $serialized Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeString($data, $serialized)
	{
		if ($serialized || !is_string($data)) {
			return array($serialized, $data);
		}

		return array(true, '"' . addcslashes($data, '"\\') . '"');
	}


	/**
	 * Serializes number.
	 *
	 * @param mixed $data Number value.
	 * @param bool $serialized Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeNumber($data, $serialized)
	{
		if ($serialized || !is_numeric($data)) {
			return array($serialized, $data);
		}

		return array(true, $data);
	}
}
