<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2018
 */


namespace Aimeos\Admin\JQAdm\Customer\Address;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp()
	{
		$this->view = \TestHelperJqadm::getView();
		$this->context = \TestHelperJqadm::getContext();

		$this->object = new \Aimeos\Admin\JQAdm\Customer\Address\Standard( $this->context );
		$this->object = new \Aimeos\Admin\JQAdm\Common\Decorator\Page( $this->object, $this->context );
		$this->object->setAimeos( \TestHelperJqadm::getAimeos() );
		$this->object->setView( $this->view );
	}


	protected function tearDown()
	{
		unset( $this->object, $this->view, $this->context );
	}


	public function testCreate()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );

		$this->view->item = $manager->createItem();
		$result = $this->object->create();

		$this->assertContains( 'item-address', $result );
		$this->assertNull( $this->view->get( 'errors' ) );
	}


	public function testCopy()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );

		$this->view->item = $manager->findItem( 'UTC001', array( 'customer/address' ) );
		$result = $this->object->copy();

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertContains( 'Example company', $result );
	}


	public function testDelete()
	{
		$result = $this->object->delete();

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertNull( $result );
	}


	public function testGet()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );

		$this->view->item = $manager->findItem( 'UTC001', array( 'customer/address' ) );
		$result = $this->object->get();

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertContains( 'Example company', $result );
	}


	public function testSave()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->view->item = $manager->createItem();

		$param = array(
			'site' => 'unittest',
			'address' => array(
				array(
					'customer.address.id' => '',
					'customer.address.email' => 'test@example.com',
					'customer.address.firstname' => 'test',
					'customer.address.lastname' => 'user',
				),
			),
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $param );
		$this->view->addHelper( 'param', $helper );

		$result = $this->object->save();

		$this->assertNull( $this->view->get( 'errors' ) );
		$this->assertNull( $result );
		$this->assertEquals( 1, count( $this->view->item->getAddressItems() ) );

		foreach( $this->view->item->getAddressItems() as $addrItem )
		{
			$this->assertEquals( 'test@example.com', $addrItem->getEmail() );
			$this->assertEquals( 'test', $addrItem->getFirstname() );
			$this->assertEquals( 'user', $addrItem->getLastname() );
		}
	}


	public function testSaveException()
	{
		$object = $this->getMockBuilder( \Aimeos\Admin\JQAdm\Customer\Address\Standard::class )
			->setConstructorArgs( array( $this->context, \TestHelperJqadm::getTemplatePaths() ) )
			->setMethods( array( 'fromArray' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'fromArray' )
			->will( $this->throwException( new \RuntimeException() ) );

		$view = \TestHelperJqadm::getView();
		$view->item = \Aimeos\MShop::create( $this->context, 'customer' )->createItem();

		$object->setView( $view );

		$this->setExpectedException( \Aimeos\Admin\JQAdm\Exception::class );
		$object->save();
	}


	public function testSaveMShopException()
	{
		$object = $this->getMockBuilder( \Aimeos\Admin\JQAdm\Customer\Address\Standard::class )
			->setConstructorArgs( array( $this->context, \TestHelperJqadm::getTemplatePaths() ) )
			->setMethods( array( 'fromArray' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'fromArray' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$this->view = \TestHelperJqadm::getView();
		$this->view->item = \Aimeos\MShop::create( $this->context, 'customer' )->createItem();

		$object->setView( $this->view );

		$this->setExpectedException( \Aimeos\Admin\JQAdm\Exception::class );
		$object->save();
	}


	public function testSearch()
	{
		$this->assertNull( $this->object->search() );
	}


	public function testGetSubClient()
	{
		$this->setExpectedException( \Aimeos\Admin\JQAdm\Exception::class );
		$this->object->getSubClient( 'unknown' );
	}
}
