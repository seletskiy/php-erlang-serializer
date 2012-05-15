<?php

/** Test helper. */
if (file_exists(dirname(__FILE__) . '/../TestHelper.php')) {
	require_once dirname(__FILE__) . '/../TestHelper.php';
}

/** @see Erlang_Serializer_ */
require_once "Erlang/Serializer.php";

/**
 * Для запуска отдельного тесткейса.
 * @ignore
 */
// @codingStandardsIgnoreStart
if (!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'Erlang_SerializerTest::main');
}
// @codingStandardsIgnoreEnd

/**
 * Testcase for Erlang Serializer.
 *
 * @category    Erlang
 * @package     Erlang_Serializer
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012 NGS
 */
class Erlang_SerializerTest extends PHPUnit_Framework_TestCase
{
	public static function main()
	{
		$suite = new PHPUnit_Framework_TestSuite(__CLASS__);
		$result = PHPUnit_TextUI_TestRunner::run($suite);
	}


	public function setUp()
	{
		$this->_serializer = new Erlang_Serializer;
	}


	public function tearDown()
	{
		unset($this->_serializer);
	}


	public function testCanSerializeNull()
	{
		$this->assertSerialized('nil', null);
	}


	public function testcanSerializeString()
	{
		$this->assertSerialized('"string"', "string");
	}


	public function testCanSerializeEmptyList()
	{
		$this->assertSerialized('[]', array());
	}


	public function testCanSerializeNumericList()
	{
		$this->assertSerialized('[1, 2, 3]', array(1, 2, 3));
	}


	public function testCanSerializeNumericListWithStrings()
	{
		$this->assertSerialized('["string", 1, 2]', array("string", 1, 2));
	}


	public function testCanSerializeAssocAsPropList()
	{
		$this->assertSerialized('[{key, "value"}]', array("key" => "value"));
	}


	public function testCanSerializeNotCommonAtom()
	{
		$this->assertSerialized("[{'Key', \"value\"}]", array("Key" => "value"));
	}


	public function testCanSerializeAtomWithQuote()
	{
		$this->assertSerialized("[{'\'', \"value\"}]", array("'" => "value"));
	}


	public function testCanSerializeStringWithQuote()
	{
		$this->assertSerialized('"\""', "\"");
	}


	public function testCanSerializeStringWithSlash()
	{
		$this->assertSerialized('"\\\\"', "\\");
	}


	public function testCannotSerializeUnsupportedType()
	{
		$this->assertSerialized(null, new stdClass);
	}


	/**
	 * Asserts $in value correctly serialized in erlang format.
	 *
	 * @param mixed $in Input data to serialize.
	 * @param string $expected Expected result.
	 */
	protected function assertSerialized($expected, $in)
	{
		$this->assertEquals($expected, $this->_serializer->serialize($in));
	}
}

/**
 * Для запуска отдельного тесткейса.
 * @ignore
 */
// @codingStandardsIgnoreStart
if (PHPUnit_MAIN_METHOD == 'Erlang_SerializerTest::main') {
	Erlang_SerializerTest::main();
}
// @codingStandardsIgnoreEnd
