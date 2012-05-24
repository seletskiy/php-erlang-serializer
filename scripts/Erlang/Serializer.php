<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

/** @see Erlang_Serializer_Null */
require_once "Erlang/Serializer/Null.php";

/** @see Erlang_Serializer_Number */
require_once "Erlang/Serializer/Number.php";

/** @see Erlang_Serializer_String */
require_once "Erlang/Serializer/String.php";

/** @see Erlang_Serializer_Array */
require_once "Erlang/Serializer/Array.php";


/** @see Erlang_Serializer_Bool */
require_once "Erlang/Serializer/Bool.php";


/**
 * Serializer to erlang term format.
 *
 * Of course, you can set custom serialization rules, {@see serialize}.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 */
class Erlang_Serializer
{
	/** @var array Array of serializers. */
	protected $_serializers = array();

	/** @var array Default serialization scheme. */
	protected $_scheme = array(
		'::numeric' => 'number',
		'::number' => 'number',
		'::string' => 'string',
		'::array' => 'list',
		'::array#::number@keyvalue' => 'is',
		'::array#::string@keyvalue' => 'keytuple',
		'::array#::number@key' => 'is',
		'::array#::string@key' => 'atom'
	);


	/**
	 * Constructs serializer.
	 *
	 * Register base element serializers.
	 *
	 * @param array $scheme Serialization scheme ({@see serialize()}).
	 * @param array $serializer	Custom serializers ({@see Erlang_Serialize_Null}).
	 */
	public function __construct($scheme = array(), $serializers = array())
	{
		$this->_serializers = array(
			new Erlang_Serializer_Null,
			new Erlang_Serializer_Bool,
			new Erlang_Serializer_Number,
			new Erlang_Serializer_String,
			new Erlang_Serializer_Array
		);

		$this->_serializers = array_merge($this->_serializers, $serializers);
		$this->_scheme = $scheme + $this->_scheme;
	}


	/**
	 * Serializes $data to erlang format using iterative algorithm.
	 *
	 * All PHP strings serializes into Erlang strings except
	 * keys of assoc array, they are serialized into atoms.
	 *
	 * Default serialization scheme:
	 * * `null` serializes as `nil` atom.
	 * * all `php strings` serialized as `erlang strings`;
	 * * all `numbers` serialized as `numbers`;
	 * * all `string numbers` serialized as `numbers`;
	 * * all `arrays` serialized as `lists`;
	 * * all `numeric items in arrays` serialized as `lists`;
	 * * all `assoc items in arrays` serialized as `tuple` `{$key, $value}`;
	 * * all `numeric array keys` serialized as `number`;
	 * * all `string array keys` serialized as `atom`;
	 *
	 * You can specify serialization scheme using simple rules:
	 * `array($selector => $type, ...)`
	 *
	 * Where `$selector` is a pattern for `path` to currently
	 * serialized item.
	 *
	 * Path constructed this way:
	 * * always begins from `/`, that represent root element;
	 * * `::array` added for nested array element;
	 * * `::string` added for nested string element;
	 * * `::number` added for nested number element;
	 * * `#$num` for numeric keys of array;
	 * * `#"$key"` for string keys of array;
	 * * additional `/` added for every nest level;
	 * * `@key` to specify key of currently serialized item of array;
	 * * `@keyvalue` to specify pair `$key => $value` of currenly serialized element.
	 *
	 * Note: if you want to specify way of serialization pair `$key => $value` in array,
	 * e.g. if you want serialize `array(1 => "bla")` into `[{1, "bla"}]`, you need
	 * to specify scheme for `@keyvalue` selector, e.g. `/::array@keyvalue => keytuple`.
	 *
	 * In a pattern you can use wildcard `*` to skip anything on current
	 * nest level (e.g. between / and next /).
	 *
	 * Examples:
	 * * `array("bla" => array(3 => "test"))`, the `test` element
	 *   can be identified by:
	 * ** `/::array#bla/::array#3/` - exact match;
	 * ** `/::array#::string/::array#::number` - fuzzy match;
	 * ** `/::array/::array/` - matches for any key names;
	 * ** `/<star>/<star>/` - same as above (<star> stands for * symbol)
	 * ** `::array/` - matches any nested array;
	 * ** `#3/` - any `3` key in any array;
	 * * `array("bla" => 1, "lala" => 2)`, the `lala` key can be
	 *   identified by:
	 * ** `/::array#lala@key` - exact match;
	 * ** `#lala@key` - `lala` key in any array;
	 *
	 * You can use this target erlang types for selectors:
	 * * `::number`:
	 * ** `number` (default);
	 * ** `string`;
	 * * `::numeric` (number strings, such as "1234"):
	 * ** `number` (default);
	 * ** `string`;
	 * * `::array`:
	 * ** `list` (default);
	 * ** `tuple`;
	 * * `::string`:
	 * ** `atom` (default for array keys);
	 * ** `string` (default for other strings);
	 * * `::array#::number@keyvalue` (numeric array item):
	 * ** `is` (default, key will be ommited);
	 * ** `keytuple` (serializes into `tuple` `{key, value}`);
	 * * `::array#::string@keyvalue` (assoc array item):
	 * ** `keytuple` (default, serializes into `tuple` `{key, value}`);
	 * ** `is` (key will be ommited);
	 * * `::array#::number@key` (numeric keys for arrays):
	 * ** `number` (default);
	 * ** `atom`;
	 * ** `string`;
	 * * `::array#::string@key` (string keys for arrays):
	 * ** `atom` (default);
	 * ** `string`;
	 *
	 * @param mixed $data Data for serialization.
	 * @param array $scheme Serialization scheme (override default).
	 * @return string Erlang term.
	 */
	public function serialize($data, $scheme = array())
	{
		$result = array(array('data' => $data, 'path' => array()));

		do {
			// Do a loop while all data will be serialized.
			$result = $this->_serializePartial($result, $scheme + $this->_scheme);
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
	 * * `array('data' => $data, 'path' => $path)` for not serialized yet item,
	 *   where `$data` is data to serialize and `$path` is part of path,
	 *   that describe path to `$data` in full data array (from `serialize()` function).
	 *
	 * `$partial` data is returned by many serializer functions to prevent deep nested
	 * recursion.
	 *
	 * @param array $partial Contains string for serialized item and array for not.
	 * @return array Array contains serialized components.
	 */
	protected function _serializePartial($partial, $scheme)
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
				$serialized = $this->_serializeDataWithStack($part['data'], $scheme, $part['path']);
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
	 * @param array $path Path stack for scheme matching.
	 * @return array List of serialized components.
	 */
	protected function _serializeDataWithStack($data, $scheme, $path)
	{
		// Chain of serializators.
		// Every invokation trying to serialize data if `$complete` is false.
		foreach ($this->_serializers as $serializer) {
			$result = $serializer->serialize($data, $scheme, $path);
			if (!is_null($result)) {
				return $result;
			}
		}

		return null;
	}
}
