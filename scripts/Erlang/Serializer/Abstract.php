<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */


/**
 * Abstract serializer.
 *
 * @category    Erlang
 * @package     Erlang\Serializer
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
	 * @param array $stack Path to item.
	 * @return array Partial result of serialization.
	 */
	public function serialize($data, $scheme = array(), $stack = array())
	{
		$this->_stack = $stack;
		$this->_scheme = $scheme;

		// avoiding PHPMD warning
		$data = $data;
	}


	/**
	 * Trying to find user defined type for current path.
	 *
	 * @todo 21/05/2012: Slow as hell on deep nested arrays. Refactoring required.
	 * @return string|function|null User defined type or serializer.
	 */
	protected function _matchScheme()
	{
		foreach ($this->_scheme as $match => $type) {
			if ($match[0] == '/') {
				$match = '^' . $match;
			}

			$regexp = str_replace(array('#', '*'), array('\\#', '.*'), $match);
			foreach ($this->_getPathVariants() as $path) {
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
	protected function _serializeData($data, $path)
	{
		array_push($this->_stack, (array)$path);

		$serializer = '_serializeAs' . $this->_matchScheme();
		if (!is_callable(array($this, $serializer))) {
			return null;
		}

		$result = $this->$serializer($data);

		array_pop($this->_stack);

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
		return array('stack' => $this->_stack, 'data' => $data);
	}
}
