<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */


/** @see Erlang_Serializer_Exception */
require_once "Erlang/Serializer/Exception.php";


/** @see Erlang_Serializer_SchemeMatcher */
require_once "Erlang/Serializer/SchemeMatcher.php";

/**
 * Abstract serializer.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @internal
 */
class Erlang_Serializer_Abstract
{
	/**
	 * Constructs serializer.
	 *
	 * Register string serializer.
	 */
	public function __construct()
	{
		$this->_matcher = new Erlang_Serializer_SchemeMatcher;

		$this->_serializers = new stdClass;
		$this->_serializers->string = new Erlang_Serializer_String;
	}


	/**
	 * Serializes arbitary data.
	 *
	 * Base method for all inherited classes.
	 *
	 * @param mixed $data Data to serialize.
	 * @param array $scheme Serialization scheme.
	 * @param array $path Path to item.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $path = array())
	{
		$this->_path = $path;
		$this->_scheme = $scheme;

		$data = $data;
	}




	/**
	 * Abstract serializer method.
	 *
	 * Choose user defined or default serializer for current element.
	 *
	 * @param mixed $data Data to serialize;
	 * @param string $default Name of default target type for current element.
	 * @param array $path Path components to current element (e.g. `#1`, `#::number` and so on)
	 * @return array Partial result of serialization.
	 */
	protected function _serializeData($data, $path/*, $path */)
	{
		$path = array_slice(func_get_args(), 1);
		$path = array_map(function ($v) { return (array)$v; }, $path);

		$this->_path = array_merge($this->_path, $path);

		$type = $this->_matcher->match($this->_path, $this->_scheme);
		if (is_null($type)) {
			throw new Erlang_Serializer_Exception(
				'Can not determine target type for current element, '
				. 'path: ' . join(array_filter(array_map('current', $this->_path)))
			);
		}

		$serializer = '_serializeAs' . $type;
		if (!is_callable(array($this, $serializer))) {
			return null;
		}

		$result = $this->$serializer($data);

		$this->_path = array_slice($this->_path, 0, -count($path));

		return $result;
	}


	/**
	 * Makes partial serialized result.
	 *
	 * @param mixed $array Data to be serialzied.
	 * @return array See method code.
	 */
	protected function _makePartial($data)
	{
		return array('path' => $this->_path, 'data' => $data);
	}
}
