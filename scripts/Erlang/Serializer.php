<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/**
 * Serializer to erlang term format.
 *
 * Of course, you can set custom serialization rules, {@see serialize}.
 *
 * @todo Refactoring: exctract `_serialize(Null|Array|String|...)` and so on methods to different classes.
 *
 * PHPMD warnings:
 * * The class Erlang_Serializer has 27 methods.
 *   Consider refactoring Erlang_Serializer to keep number of methods under 10.
 * * The class Erlang_Serializer has an overall complexity of 55
 *   which is very high. The configured complexity threshold is 50.
 */
class Erlang_Serializer
{
	/**
	 * Serializes $data to erlang format using iterative algorithm.
	 *
	 * All PHP strings serializes into Erlang strings except
	 * keys of assoc array, they are serialized into atoms.
	 *
	 * Default serialization scheme:
	 * * `null` serializes as `nil` atom.
	 * * all `php-strings` serialized as `erlang-strings`;
	 * * all `numeric keys in arrays` serialized as `lists`;
	 * * all `assoc keys in arrays` serialized as `tuple` `{key, value}`;
	 * * all `array keys` serialized as `atom`;
	 *
	 * You can specify serialization scheme using simple rules:
	 * `array($selector => $type, ...)`
	 *
	 * Where `$selector` is a regexp (with anchor `$`) for `path` to currently
	 * serialized item.
	 *
	 * Path constructed this way:
	 * * always begins from `/`, that represent root element;
	 * * `::array` added for nested array element;
	 * * `::string` added for nested string element;
	 * * `::number` added for nested number element;
	 * * `#key` added for every `key` in array;
	 * * additional `/` added for every nest level;
	 * * you may use `@key` to specify target type for key of array,
	 *   not for matching item.
	 *
	 * Examples:
	 * * `array("bla" => array(3 => "test"))`, the `test` element
	 *   can be identified by:
	 * ** `^/::array#bla/::array#3/` - exact match;
	 * ** `^/::array#::string/::array#::number` - fuzzy match;
	 * ** `^/::array/::array/` - matches for any key names;
	 * ** `::array/` - matches any nested array;
	 * ** `#3/` - any `3` key in any array;
	 * * `array("bla" => 1, "lala" => 2)`, the `lala` key can be
	 *   identified by:
	 * ** `^/::array#lala/@key` - exact match;
	 * ** `#lala/@key` - `lala` key in any array;
	 *
	 * You can use scheme for types:
	 * * `::array`:
	 * ** `list` (default);
	 * ** `tuple`;
	 * * `::string`:
	 * ** `atom` (default for array keys);
	 * ** `string` (default for other strings);
	 * * `::array#::number` (numeric array item):
	 * ** `is` (default, key will be ommited);
	 * ** `keytuple` (serializes into `tuple` `{key, value}`);
	 * * `::array#::string` (assoc array item):
	 * ** `keytuple` (default, serializes into `tuple` `{key, value}`);
	 * ** `is` (key will be ommited);
	 * * `/@key` (keys for arrays):
	 * ** `atom` (default);
	 * ** `string`;
	 *
	 * @param mixed $data Data for serialization.
	 * @param array $scheme Serialization scheme.
	 * @return string Erlang term.
	 */
	public function serialize($data, $scheme = array())
	{
		$this->_stack = array();
		$this->_scheme = $scheme;

		$result = array(array('data' => $data, 'stack' => array()));

		do {
			$result = $this->_serializePartial($result);
		} while (count($result) != 1 && is_string($result[0]));

		return $result[0];
	}


	/**
	 * Main function for iteration style serializing.
	 *
	 * Runs as many times, while all items in `$partial` are serialized.
	 *
	 * `$partial` is a simple `array`, that contains:
	 * * `string` for fully serialized item;
	 * * `array('data' => $data, 'stack' => $stack)` for not serialized yet item,
	 *   where `$data` is data to serialize and `$stack` is part of path,
	 *   that describe path to `$data` in full data array (from `serialize()` function).
	 *
	 * `$partial` data is returned by many serializer functions to prevent deep nested
	 * recursion.
	 *
	 * @param array $partial Contains string for serialized item and array for not.
	 * @return array Array contains serialized components.
	 */
	protected function _serializePartial($partial)
	{
		$result = array();
		$buffer = array();

		foreach ($partial as $part) {
			// If we have a string, then it is a serialized
			// part and we try to join in to next serialized parts.
			if (is_string($part)) {
				$buffer[] = $part;
			}

			// If we have an array, then it is unserialized data, so
			// we trying to serialize it.
			if (is_array($part)) {
				$serialized = $this->_serializePhpValue($part['data'], $part['stack']);
				// `$result[] = join($buffer)` is a wrong solution here,
				// because of `join(array()) == ""`, and we don't want
				// empty element in result array.
				$result = array_merge($result, (array)join($buffer));
				$result = array_merge($result, (array)$serialized);
				$buffer = array();
			}
		}

		return array_merge($result, (array)join($buffer));
	}


	/**
	 * Main serialization function.
	 *
	 * Serializes specified php value (using path stack).
	 *
	 * @param mixed $data Input data.
	 * @param array $stack Path stack for scheme matching.
	 * @return array List of serialized components.
	 */
	protected function _serializePhpValue($data, $stack)
	{
		$this->_stack = $stack;

		$result = $data;
		$complete = false;

		// Chain of serializators.
		// Every invokation trying to serialize data if `$complete` is false.
		list($complete, $result) = $this->_serializeNull($result, $complete);
		list($complete, $result) = $this->_serializeArray($result, $complete);
		list($complete, $result) = $this->_serializeNumber($result, $complete);
		list($complete, $result) = $this->_serializeString($result, $complete);

		if (!$complete) {
			return null;
		}

		return $result;
	}


	//////////////////////////////////////////////////////////////////////////
	// Serializer Wrappers
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Try to serialize incoming data as null.
	 *
	 * @param mixed $data Null value.
	 * @param bool $complete Pass true to skip this method.
	 * @return array `array($complete, $result)`
	 */
	protected function _serializeNull($data, $complete)
	{
		if ($complete || !is_null($data)) {
			return array($complete, $data);
		}

		return array(true, $this->_serializeStringAsAtom("nil"));
	}


	/**
	 * Try to serialize incoming data as array.
	 *
	 * @param mixed $data Array value.
	 * @param bool $complete Pass true to skip this method.
	 * @return array `array($complete, $result)`
	 */
	protected function _serializeArray($data, $complete)
	{
		if ($complete || !is_array($data)) {
			return array($complete, $data);
		}

		return array(true,
			$this->_serializeData(__FUNCTION__, $data, '::array', 'list'));
	}


	/**
	 * Try to serialize incoming data as number.
	 *
	 * @param mixed $data Number value.
	 * @param bool $complete Pass true to skip this method.
	 * @return array `array($complete, $result)`
	 */
	protected function _serializeNumber($data, $complete)
	{
		if ($complete || !is_numeric($data)) {
			return array($complete, $data);
		}

		return array(true, strval($data));
	}


	/**
	 * Try to serialize incoming data as string.
	 *
	 * @param mixed $data String value.
	 * @param bool $complete Pass true to skip this method.
	 * @return array `array($complete, $result)`
	 */
	protected function _serializeString($data, $complete)
	{
		if ($complete || !is_string($data)) {
			return array($complete, $data);
		}

		return array(true,
			$this->_serializeData(__FUNCTION__, $data, '::string', 'string'));
	}


	//////////////////////////////////////////////////////////////////////////
	// Array Serializers
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Serializes array as simple list.
	 *
	 * @param array $array Array to serialize.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayAsList($array)
	{
		return $this->_serializeArrayWithDelims($array, '[', ']');
	}


	/**
	 * Serializes array as tuple.
	 *
	 * @param array $array Array to serialize.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayAsTuple($array)
	{
		return $this->_serializeArrayWithDelims($array, '{', '}');
	}


	/**
	 * Abstract method to serialize array with custom delims.
	 *
	 * @param array $array Array to serialize.
	 * @param string $ldelim Left delimiter.
	 * @param string $rdelim Right delimiter.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayWithDelims($array, $ldelim, $rdelim)
	{
		$result = array();
		// Poor man's join function.
		foreach ($array as $key => $value) {
			$result = array_merge($result, $this->_serializeArrayItem($key, $value));
			$result[] = ', ';
		}

		return array_merge(
			array($ldelim),
			array_slice($result, 0, -1),
			array($rdelim));
	}


	//////////////////////////////////////////////////////////////////////////
	// Array Item Serializers
	//////////////////////////////////////////////////////////////////////////

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
		$complete = false;

		list($complete, $result) = $this->_serializeArrayItemNumeric(
			$key, $result, $complete);
		list($complete, $result) = $this->_serializeArrayItemAssoc(
			$key, $result, $complete);

		return $result;
	}


	/**
	 * Try to serialize numeric array item (with numeric key).
	 *
	 * @param mixed $key Key of item.
	 * @param mixed $value item.
	 * @param bool $complete Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeArrayItemNumeric($key, $value, $complete)
	{
		if ($complete || !is_numeric($key)) {
			return array($complete, $value);
		}

		return array(true,
			$this->_serializeData(__FUNCTION__,
				array($key => $value),
				array("#$key/", '#::number/', '/'),
				'is'));
	}


	/**
	 * Try to serialize array item with string key (non-numeric);
	 *
	 * @param mixed $key Key of item.
	 * @param mixed $value item.
	 * @param bool $complete Pass true to skip this method.
	 * @return string|mixed Serialized value or $data if method is not applicable.
	 */
	protected function _serializeArrayItemAssoc($key, $value, $complete)
	{
		if ($complete) {
			return array($complete, $value);
		}

		$path = $this->_serializeStringAsString($key);

		return array(true,
			$this->_serializeData(__FUNCTION__,
				array($key => $value),
				array("#$path/", '#::string/', '/'),
				'keytuple'));
	}


	/**
	 * Serializes array item with numeric key as `keytuple` (`{serialize($key), serialize($value)}`).
	 *
	 * @param array $keyvalue `array($key => $value)`.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemNumericAsKeytuple($keyvalue)
	{
		$serializedKey = $this->_serializeArrayItemKey(key($keyvalue));
		$serializedVal = array('stack' => $this->_stack, 'data' => current($keyvalue));

		return array('{', $serializedKey, ', ', $serializedVal, '}');
	}


	/**
	 * Serializes array item as is (`serialize(value)`, key will be ommited).
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemNumericAsIs($keyvalue)
	{
		return array(array('stack' => $this->_stack, 'data' => current($keyvalue)));
	}


	/**
	 * Serializes array item with string key (non-numeric) as is (key will be ommited).
	 *
	 * @param array $keyvalue `array($key => $value)`.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemAssocAsIs($keyvalue)
	{
		return $this->_serializeArrayItemNumericAsIs($keyvalue);
	}


	/**
	 * Serializes array item with string key (non-numeric) as `keytuple` (`{serialize($key), serialize($value)}`).
	 *
	 * @param array $keyvalue `array($key => $value)`.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemAssocAsKeytuple($keyvalue)
	{
		return $this->_serializeArrayItemNumericAsKeytuple($keyvalue);
	}


	//////////////////////////////////////////////////////////////////////////
	// Array Key Serializers
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Serializes array key.
	 *
	 * @param numeric|string $key Incoming key.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemKey($key)
	{
		if (is_numeric($key)) {
			$default = 'is';
		} else {
			$default = 'atom';
		}

		return $this->_serializeData(__FUNCTION__, $key, '@key', $default);
	}


	/**
	 * Serializes array key as is (`serialize($key)`).
	 *
	 * @param numeric|string $key Incoming key.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemKeyAsIs($key)
	{
		return array('stack' => $this->_stack, 'data' => $key);
	}


	/**
	 * Serializes array key as atom.
	 *
	 * @param string $key Incoming key.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemKeyAsAtom($key)
	{
		return $this->_serializeStringAsAtom($key);
	}


	/**
	 * Serializes array key as string.
	 *
	 * @param string $key Incoming key.
	 * @return array Partial result of serialization.
	 */
	protected function _serializeArrayItemKeyAsString($key)
	{
		return $this->_serializeStringAsString($key);
	}


	//////////////////////////////////////////////////////////////////////////
	// String Serializers
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Serializes input string as atom.
	 *
	 * This method used because of PHP have not atom type.
	 *
	 * @param string $string String to be represented as atom.
	 * @return string Erlang atom.
	 */
	public function _serializeStringAsAtom($string)
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
	protected function _serializeStringAsString($string)
	{
		return '"' . addcslashes($string, '"\\') . '"';
	}


	//////////////////////////////////////////////////////////////////////////
	// scheme Matching Functions
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Trying to find user defined type for current path.
	 *
	 * @return string|function|null User defined type or serializer.
	 */
	protected function _matchScheme()
	{
		foreach ($this->_scheme as $match => $type) {
			foreach ($this->_getPathVariants() as $path) {
				$regexp = str_replace('#', '\\#', $match);

				if (preg_match("#$regexp$#", $path)) {
					return $type;
				}
			}
		}
	}


	/**
	 * Returns all path variants to current element.
	 *
	 * E.g. item `1` in `array(array(1))` can be accessed as:
	 * * `/::array/::array/`
	 * * `/::array/::array/::number`
	 * * `/::array/::array#0/`
	 * * `/::array/::array#0/::number`
	 * * `/::array/::array#::number/`
	 * * `/::array/::array#::number/::number`
	 *
	 * @return array Path variants.
	 */
	protected function _getPathVariants()
	{
		$tree = array(array('/'));

		foreach ($this->_stack as $variants) {
			$newTree = array();
			foreach ($variants as $variant) {
				foreach ($tree as $leaf) {
					$newTree[] = array_merge($leaf, array($variant));
				}
			}

			$tree = $newTree;
		}

		return array_map('join', $tree);
	}


	//////////////////////////////////////////////////////////////////////////
	// Low Level Serializer Invokers
	//////////////////////////////////////////////////////////////////////////

	/**
	 * Abstract serializer.
	 *
	 * Choose that user defined or default serializer for current element.
	 *
	 * @param string $method Base name of serializer method (e.g. `_serializeArrayItem`).
	 * @param mixed $data Data to serialize;
	 * @param array $descriptions Path components to current element (e.g. `#1`, `#::number` and so on)
	 * @param string $default Name of default target type for current element.
	 */
	protected function _serializeData($method, $data, $descriptions, $default)
	{
		array_push($this->_stack, (array)$descriptions);

		$scheme = $this->_matchScheme();
		$result = $this->_serializeDataAsType($method, $data, $scheme, $default);

		array_pop($this->_stack);

		return $result;
	}


	/**
	 * Serializes specified data as target type.
	 *
	 * @param string $method Base name of serializer method (e.g. `_serializeArrayItem`).
	 * @param mixed $data Data to serialize.
	 * @param mixed User defined schema for incoming element (if applicable).
	 * @param string $default Name of default target type for current element.
	 */
	protected function _serializeDataAsType($method, $data, $scheme, $default)
	{
		$prefix = $method . 'As';
		$serializer = $prefix . $scheme;
		$default = $prefix . $default;
		if (!empty($scheme) && is_callable(array($this, $serializer))) {
			return $this->$serializer($data);
		} else {
			return $this->$default($data);
		}
	}
}
