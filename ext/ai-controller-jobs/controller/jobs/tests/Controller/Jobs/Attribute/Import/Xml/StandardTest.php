<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2018
 */


namespace Aimeos\Controller\Jobs\Attribute\Import\Xml;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $aimeos;


	protected function setUp()
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperJobs::getContext();
		$this->aimeos = \TestHelperJobs::getAimeos();

		$config = $this->context->getConfig();
		$config->set( 'controller/jobs/attribute/import/xml/location', __DIR__ . '/_testfiles' );

		$this->object = new \Aimeos\Controller\Jobs\Attribute\Import\Xml\Standard( $this->context, $this->aimeos );
	}


	protected function tearDown()
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Attribute import XML', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$text = 'Imports new and updates existing attributes from XML files';
		$this->assertEquals( $text, $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->object->run();

		$manager = \Aimeos\MShop::create( $this->context, 'attribute' );
		$item = $manager->findItem( 'unittest-xml', ['attribute/property', 'media', 'price', 'text'], 'product', 'color' );
		$manager->deleteItem( $item->getId() );

		$this->assertEquals( 'Test attribute 2', $item->getLabel() );
		$this->assertEquals( 1, count( $item->getRefItems( 'media' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'price' ) ) );
		$this->assertEquals( 1, count( $item->getRefItems( 'text' ) ) );
		$this->assertEquals( 1, count( $item->getPropertyItems() ) );
	}
}
