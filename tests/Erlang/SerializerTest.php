<?php

/**
 * @author      Stanislav Seletskiy <s.seletskiy@office.ngs.ru>
 * @copyright   2012
 */

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
 * @package     Erlang\Serializer
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


	public function testCanSerializeStringNumberAsNumber()
	{
		$this->assertSerialized('12345', "12345");
	}


	public function testCanSerializeString()
	{
		$this->assertSerialized('"string"', "string");
	}


	public function testCanSerializeEmptyList()
	{
		$this->assertSerialized('[]', array());
	}


	public function testCanSerializeNumericListAsList()
	{
		$this->assertSerialized('[1, 2, 3]', array(1, 2, 3));
	}


	public function testCanSerializeNumericListAsTuple()
	{
		$this->assertSerialized('{1, 2, 3}', array(1, 2, 3), array('::array' => 'tuple'));
	}


	public function testCanSerializeNumericListAsListOfTuples()
	{
		$this->assertSerialized(
			'[{0, "test"}, {1, "value"}]',
			array(0 => "test", 1 => "value"),
			array('/::array/' => 'keytuple'));
	}


	public function testCanSerializeNestedItemsWithDifferentSetOfRules()
	{
		$this->assertSerialized('[1, 2, {3, 4, 5}]', array(1, 2, array(3, 4, 5)),
			array('/*/::array' => 'tuple'));
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


	public function testCanSetSchemaByType()
	{
		$this->assertSerialized("atom", "atom", array('::string' => 'atom'));
	}


	public function testCanSerializeKeyAsString()
	{
		$this->assertSerialized('[{"key1", 1}, {"key2", 2}]', array('key1' => 1, 'key2' => 2),
			array('::array/@key' => 'string'));
	}


	public function testCanSerializeExactKeyWithSpecificSchema()
	{
		$this->assertSerialized('["test", {1, "bla"}]', array('test', 'bla'),
			array('::array#1/' => 'keytuple'));
	}


	public function testCanSpecifyArrayItemSchemaByType()
	{
		$this->assertSerialized('[{0, "test"}, {1, "bla"}]', array('test', 'bla'),
			array('::array#::number/' => 'keytuple'));
	}


	public function testCanSerializeExactKeyWithSpecificSchema2()
	{
		$this->assertSerialized('["test", {"lala", "bla"}]', array('test', 'lala' => 'bla'),
			array('::array#"lala"/@key' => 'string'));
	}


	public function testCanSerializeAssocItemAsIs()
	{
		$this->assertSerialized('["test"]', array('bla' => "test"),
			array('#::string/' => 'is'));
	}


	public function testCanSerializeDeepNestedArrays()
	{
		$this->assertSerialized('[[[[[[[[1]]]]]]]]', array(array(array(array(array(array(array(array(1)))))))));
	}


	/**
	 * Asserts $in value correctly serialized in erlang format.
	 *
	 * @param mixed $in Input data to serialize.
	 * @param string $expected Expected result.
	 */
	protected function assertSerialized($expected, $in, $schema = array())
	{
		$this->assertEquals($expected,
			$this->_serializer->serialize($in, $schema));
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
