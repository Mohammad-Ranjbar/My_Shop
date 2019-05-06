<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Metaways Infosystems GmbH, 2012
 * @copyright Aimeos (aimeos.org), 2015-2018
 */

namespace Aimeos\Controller\Frontend\Basket;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;
	private $testItem;


	protected function setUp()
	{
		\Aimeos\MShop::cache( true );

		$this->context = \TestHelperFrontend::getContext();

		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$this->testItem = $manager->findItem( 'U:TESTP', ['attribute', 'media', 'price', 'product', 'text'] );

		$this->object = new \Aimeos\Controller\Frontend\Basket\Standard( $this->context );
	}


	protected function tearDown()
	{
		\Aimeos\MShop::cache( false );

		$this->object->clear();
		$this->context->getSession()->set( 'aimeos', [] );

		unset( $this->context, $this->object );
	}


	public function testAdd()
	{
		$result = $this->object->add( ['order.base.comment' => 'test'] );

		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
		$this->assertEquals( 'test', $this->object->get()->getComment() );
	}


	public function testClear()
	{
		$this->object->addProduct( $this->testItem );

		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $this->object->clear() );
		$this->assertEquals( 0, count( $this->object->get()->getProducts() ) );
	}


	public function testGet()
	{
		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Base\Iface::class, $this->object->get() );
	}


	public function testSave()
	{
		$stub = $this->getMockBuilder( \Aimeos\MShop\Order\Manager\Base\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['setSession'] )
			->getMock();

		\Aimeos\MShop::inject( 'order/base', $stub );

		$stub->expects( $this->exactly( 2 ) )->method( 'setSession' );

		$object = new \Aimeos\Controller\Frontend\Basket\Standard( $this->context );
		$object->addProduct( $this->testItem );

		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $object->save() );
	}


	public function testSetType()
	{
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $this->object->setType( 'test' ) );
	}


	public function testStore()
	{
		$stub = $this->getMockBuilder( \Aimeos\MShop\Order\Manager\Base\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['store'] )
			->getMock();

		\Aimeos\MShop::inject( 'order/base', $stub );

		$stub->expects( $this->once() )->method( 'store' )->will( $this->returnValue( $stub->createItem() ) );

		$object = new \Aimeos\Controller\Frontend\Basket\Standard( $this->context );
		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Base\Iface::class, $object->store() );
	}


	public function testStoreLimit()
	{
		$this->context->setEditor( 'core:lib/mshoplib' );
		$config = $this->context->getConfig();
		$config->set( 'controller/frontend/basket/limit-count', 0 );
		$config->set( 'controller/frontend/basket/limit-seconds', 86400 * 365 );

		$object = new \Aimeos\Controller\Frontend\Basket\Standard( $this->context );

		$this->setExpectedException( \Aimeos\Controller\Frontend\Basket\Exception::class );
		$object->store();
	}


	public function testLoad()
	{
		$stub = $this->getMockBuilder( \Aimeos\MShop\Order\Manager\Base\Standard::class )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['load'] )
			->getMock();

		\Aimeos\MShop::inject( 'order/base', $stub );

		$stub->expects( $this->once() )->method( 'load' )
			->will( $this->returnValue( $stub->createItem() ) );

		$object = new \Aimeos\Controller\Frontend\Basket\Standard( $this->context );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Base\Iface::class, $object->load( -1 ) );
	}


	public function testAddDeleteProduct()
	{
		$basket = $this->object->get();
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$item = $manager->findItem( 'CNC', ['attribute', 'media', 'price', 'product', 'text'] );

		$result1 = $this->object->addProduct( $item, 2, [], [], [], 'default', 'unitsupplier' );
		$item2 = $this->object->get()->getProduct( 0 );
		$result2 = $this->object->deleteProduct( 0 );

		$this->assertEquals( 0, count( $basket->getProducts() ) );
		$this->assertEquals( 'CNC', $item2->getProductCode() );
		$this->assertEquals( 'default', $item2->getStockType() );
		$this->assertEquals( 'unitsupplier', $item2->getSupplierCode() );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result1 );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result2 );
	}


	public function testAddProductCustomAttribute()
	{
		$attrItem = \Aimeos\MShop::create( $this->context, 'attribute' )->findItem( 'custom', [], 'product', 'date' );
		$attrValues = [$attrItem->getId() => '2000-01-01'];

		$result = $this->object->addProduct( $this->testItem, 1, [], [], $attrValues );
		$basket = $this->object->get();

		$this->assertEquals( 1, count( $basket->getProducts() ) );
		$this->assertEquals( '2000-01-01', $basket->getProduct( 0 )->getAttribute( 'date', 'custom' ) );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testAddProductCustomPrice()
	{
		$attrItem = \Aimeos\MShop::create( $this->context, 'attribute' )->findItem( 'custom', [], 'product', 'price' );
		$attrValues = [$attrItem->getId() => '0.01'];

		$result = $this->object->addProduct( $this->testItem, 1, [], [], $attrValues );
		$basket = $this->object->get();

		$this->assertEquals( 1, count( $basket->getProducts() ) );
		$this->assertEquals( '0.01', $basket->getProduct( 0 )->getPrice()->getValue() );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testAddProductCustomPriceException()
	{
		$attrItem = \Aimeos\MShop::create( $this->context, 'attribute' )->findItem( 'custom', [], 'product', 'price' );
		$attrValues = [$attrItem->getId() => ','];

		$this->setExpectedException( \Aimeos\Controller\Frontend\Basket\Exception::class );
		$this->object->addProduct( $this->testItem, 1, [], [], $attrValues );
	}


	public function testAddProductAttributePrice()
	{
		$attrItem = \Aimeos\MShop::create( $this->context, 'attribute' )->findItem( 'xs', [], 'product', 'size' );

		$result = $this->object->addProduct( $this->testItem, 1, [], [$attrItem->getId() => 2] );

		$this->assertEquals( '43.90', $this->object->get()->getPrice()->getValue() );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testAddProductAttributeNotAssigned()
	{
		$attrItem = \Aimeos\MShop::create( $this->context, 'attribute' )->findItem( '30', [], 'product', 'width' );
		$ids = [$attrItem->getId()];

		$this->setExpectedException( '\\Aimeos\\Controller\\Frontend\\Basket\\Exception' );
		$this->object->addProduct( $this->testItem, 1, [], $ids, $ids );
	}


	public function testAddProductNegativeQuantityException()
	{
		$this->setExpectedException( '\\Aimeos\\MShop\\Order\\Exception' );
		$this->object->addProduct( $this->testItem, -1 );
	}


	public function testAddProductNoPriceException()
	{
		$item = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'MNOP' );

		$this->setExpectedException( '\\Aimeos\\MShop\\Price\\Exception' );
		$this->object->addProduct( $item );
	}


	public function testAddProductConfigAttributeException()
	{
		$this->setExpectedException( '\\Aimeos\\Controller\\Frontend\\Basket\\Exception' );
		$this->object->addProduct( $this->testItem, 1, [], [-1] );
	}


	public function testAddProductLowQuantityPriceException()
	{
		$item = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'IJKL', ['attribute', 'price'] );

		$this->setExpectedException( '\\Aimeos\\MShop\\Price\\Exception' );
		$this->object->addProduct( $item );
	}


	public function testAddProductHigherQuantities()
	{
		$item = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'IJKL' );

		$result = $this->object->addProduct( $item, 2 );

		$this->assertEquals( 2, $this->object->get()->getProduct( 0 )->getQuantity() );
		$this->assertEquals( 'IJKL', $this->object->get()->getProduct( 0 )->getProductCode() );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testDeleteProductFlagError()
	{
		$this->object->addProduct( $this->testItem, 2 );

		$item = $this->object->get()->getProduct( 0 );
		$item->setFlags( \Aimeos\MShop\Order\Item\Base\Product\Base::FLAG_IMMUTABLE );

		$this->setExpectedException( '\\Aimeos\\Controller\\Frontend\\Basket\\Exception' );
		$this->object->deleteProduct( 0 );
	}


	public function testUpdateProduct()
	{
		$this->object->addProduct( $this->testItem );

		$item = $this->object->get()->getProduct( 0 );
		$this->assertEquals( 1, $item->getQuantity() );

		$result = $this->object->updateProduct( 0, 4 );
		$item = $this->object->get()->getProduct( 0 );

		$this->assertEquals( 4, $item->getQuantity() );
		$this->assertEquals( 'U:TESTP', $item->getProductCode() );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testUpdateProductFlagError()
	{
		$this->object->addProduct( $this->testItem, 2 );

		$item = $this->object->get()->getProduct( 0 );
		$item->setFlags( \Aimeos\MShop\Order\Item\Base\Product\Base::FLAG_IMMUTABLE );

		$this->setExpectedException( '\\Aimeos\\Controller\\Frontend\\Basket\\Exception' );
		$this->object->updateProduct( 0, 4 );
	}


	public function testAddCoupon()
	{
		$this->object->addProduct( $this->testItem, 2 );

		$result = $this->object->addCoupon( 'GHIJ' );
		$basket = $this->object->get();

		$this->assertEquals( 1, count( $basket->getCoupons() ) );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testAddCouponExceedCount()
	{
		$this->object->addProduct( $this->testItem, 2 );
		$this->object->addCoupon( 'GHIJ' );

		$this->setExpectedException( '\\Aimeos\\Controller\\Frontend\\Basket\\Exception' );
		$this->object->addCoupon( 'GHIJ' );
	}


	public function testAddCouponInvalidCode()
	{
		$this->setExpectedException( \Aimeos\MShop\Plugin\Provider\Exception::class );
		$this->object->addCoupon( 'invalid' );
	}


	public function testDeleteCoupon()
	{
		$this->object->addProduct( $this->testItem, 2 );
		$this->object->addCoupon( '90AB' );

		$result = $this->object->deleteCoupon( '90AB' );
		$basket = $this->object->get();

		$this->assertEquals( 0, count( $basket->getCoupons() ) );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testAddAddress()
	{
		$values = array(
			'order.base.address.company' => '<p onclick="javascript: alert(\'gotcha\');">Example company</p>',
			'order.base.address.vatid' => 'DE999999999',
			'order.base.address.title' => '<br/>Dr.',
			'order.base.address.salutation' => \Aimeos\MShop\Common\Item\Address\Base::SALUTATION_MR,
			'order.base.address.firstname' => 'firstunit',
			'order.base.address.lastname' => 'lastunit',
			'order.base.address.address1' => 'unit str.',
			'order.base.address.address2' => ' 166',
			'order.base.address.address3' => '4.OG',
			'order.base.address.postal' => '22769',
			'order.base.address.city' => 'Hamburg',
			'order.base.address.state' => 'Hamburg',
			'order.base.address.countryid' => 'de',
			'order.base.address.languageid' => 'de',
			'order.base.address.telephone' => '05554433221',
			'order.base.address.email' => 'test@example.com',
			'order.base.address.telefax' => '05554433222',
			'order.base.address.website' => 'www.example.com',
		);

		$result = $this->object->addAddress( 'payment', $values );
		$address = $this->object->get()->getAddress( 'payment', 0 );

		$this->assertEquals( 'Example company', $address->getCompany() );
		$this->assertEquals( 'Dr.', $address->getTitle() );
		$this->assertEquals( 'firstunit', $address->getFirstname() );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}


	public function testDeleteAddress()
	{
		$this->object->addAddress( 'payment', [] );
		$this->assertEquals( 1, count( $this->object->get()->getAddress( 'payment') ) );

		$result = $this->object->deleteAddress( 'payment' );

		$this->assertEquals( 0, count( $this->object->get()->getAddress( 'payment') ) );
		$this->assertInstanceOf( \Aimeos\Controller\Frontend\Basket\Iface::class, $result );
	}



	public function testAddServicePayment()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$service = $manager->findItem( 'unitpaymentcode', [], 'service', 'payment' );

		$this->object->addService( $service );
		$item = $this->object->get()->getService( 'payment', 'unitpaymentcode' )->getCode();
		$this->assertEquals( 'unitpaymentcode', $item );

		$this->setExpectedException( '\\Aimeos\\Controller\\Frontend\\Basket\\Exception' );
		$this->object->addService( $service, ['prepay' => true] );
	}


	public function testAddServiceDelivery()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$service = $manager->findItem( 'unitcode', [], 'service', 'delivery' );

		$this->object->addService( $service );
		$item = $this->object->get()->getService( 'delivery', 'unitcode' );
		$this->assertEquals( 'unitcode', $item->getCode() );

		$this->setExpectedException( '\\Aimeos\\Controller\\Frontend\\Basket\\Exception' );
		$this->object->addService( $service, ['fast shipping' => true, 'air shipping' => false] );
	}


	public function testDeleteServices()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$service = $manager->findItem( 'unitcode', [], 'service', 'delivery' );

		$this->assertSame( $this->object, $this->object->addService( $service ) );
		$this->assertEquals( 'unitcode', $this->object->get()->getService( 'delivery', 'unitcode' )->getCode() );

		$this->assertSame( $this->object, $this->object->deleteService( 'delivery' ) );
		$this->assertEquals( 0, count( $this->object->get()->getService( 'delivery' ) ) );
	}


	public function testDeleteServicePosition()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$service = $manager->findItem( 'unitcode', [], 'service', 'delivery' );

		$this->assertSame( $this->object, $this->object->addService( $service ) );
		$this->assertEquals( 'unitcode', $this->object->get()->getService( 'delivery', 'unitcode' )->getCode() );

		$this->assertSame( $this->object, $this->object->deleteService( 'delivery', 0 ) );
		$this->assertEquals( 0, count( $this->object->get()->getService( 'delivery' ) ) );
	}


	public function testCheckLocale()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'service' );
		$payment = $manager->findItem( 'unitpaymentcode', [], 'service', 'payment' );
		$delivery = $manager->findItem( 'unitcode', [], 'service', 'delivery' );

		$this->object->addProduct( $this->testItem, 2 );
		$this->object->addCoupon( 'OPQR' );

		$this->object->addService( $payment );
		$this->object->addService( $delivery );

		$basket = $this->object->get();
		$price = $basket->getPrice();

		foreach( $basket->getProducts() as $product )
		{
			$this->assertEquals( 2, $product->getQuantity() );
			$product->getPrice()->setCurrencyId( 'CHF' );
		}

		$basket->getService( 'delivery', 'unitcode' )->getPrice()->setCurrencyId( 'CHF' );
		$basket->getService( 'payment', 'unitpaymentcode' )->getPrice()->setCurrencyId( 'CHF' );
		$basket->getLocale()->setCurrencyId( 'CHF' );
		$price->setCurrencyId( 'CHF' );

		$this->context->getLocale()->setCurrencyId( 'CHF' );
		$this->object->addAddress( 'payment', $this->getAddress( 'Example company' )->toArray() );

		$this->context->getSession()->set( 'aimeos/basket/currency', 'CHF' );
		$this->context->getLocale()->setCurrencyId( 'EUR' );

		$this->context->getSession()->set( 'aimeos/basket/content-unittest-en-EUR-', null );

		$object = new \Aimeos\Controller\Frontend\Basket\Standard( $this->context );
		$basket = $object->get();

		foreach( $basket->getProducts() as $product )
		{
			$this->assertEquals( 'EUR', $product->getPrice()->getCurrencyId() );
			$this->assertEquals( 2, $product->getQuantity() );
		}

		$this->assertEquals( 'EUR', $basket->getService( 'payment', 'unitpaymentcode' )->getPrice()->getCurrencyId() );
		$this->assertEquals( 'EUR', $basket->getService( 'delivery', 'unitcode' )->getPrice()->getCurrencyId() );
		$this->assertEquals( 'EUR', $basket->getLocale()->getCurrencyId() );
		$this->assertEquals( 'EUR', $basket->getPrice()->getCurrencyId() );
	}


	/**
	 * @param string $company
	 */
	protected function getAddress( $company )
	{
		$customer = \Aimeos\MShop\Customer\Manager\Factory::create( \TestHelperFrontend::getContext(), 'Standard' );
		$addressManager = $customer->getSubManager( 'address', 'Standard' );

		$search = $addressManager->createSearch();
		$search->setConditions( $search->compare( '==', 'customer.address.company', $company ) );
		$items = $addressManager->searchItems( $search );

		if( ( $item = reset( $items ) ) === false ) {
			throw new \RuntimeException( sprintf( 'No address item with company "%1$s" found', $company ) );
		}

		return $item;
	}
}