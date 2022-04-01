<?php
use \Codeception\Util\Locator;

class BasicsCest
{
	private $faker;
	protected $create_user_data;

	public function __construct(){
		$this->faker = Faker\Factory::create('pt_BR');
		$this->create_user_data = [ 
			'first_name' => $this->faker->firstName(),
			'last_name' => $this->faker->lastName,
			'company' => $this->faker->word,
			'address_1' => $this->faker->word,
			'city' => $this->faker->word,
			'postcode' => '99999999',
			'phone' => '999999',
			'email' => $this->faker->email,
			'password' => $this->faker->word,
		];
	}
	// tests
	public function Login(AcceptanceTester $I)
	{
		$I->seeInDatabase('wp_users', ['user_login' => 'admin']);

		$I->loginAsAdmin();
		$I->amOnAdminPage('/');
		$I->see('Painel');
	}

	public function CheckoutCreateAccount(AcceptanceTester $I)
	{
		$I->amOnPage('/product/product-name/');
		$I->click('add-to-cart');
		$I->seeCurrentURLEquals('/product/product-name/');
		$I->click('View cart');
		$I->amOnPage('/cart/');
		$I->click('Proceed to checkout');
		$I->amOnPage('/checkout/');
		$I->fillField('#billing_first_name', $this->create_user_data[ 'first_name' ]);
		$I->fillField('#billing_last_name', $this->create_user_data[ 'last_name' ]);
		$I->fillField('#billing_company', $this->create_user_data[ 'company' ]);
		$I->fillField('#billing_address_1', $this->create_user_data[ 'address_1' ]);
		$I->fillField('#billing_city', $this->create_user_data[ 'city' ]);
		$I->fillField('#billing_phone', $this->create_user_data[ 'phone' ]);
		$I->fillField('#billing_email', $this->create_user_data[ 'email' ] );
		$I->checkOption('createaccount');
		//$I->waitForElement('#account_password', 10); //TO DO REFATORAR USANDO WAITFOR

		$I->wait(2);

		$I->fillField('#account_password', $this->create_user_data[ 'password' ] );
		$I->fillField('#billing_postcode', $this->create_user_data[ 'postcode' ]);


		$I->click([ 'id' => 'place_order']);
		$I->wait(2);

		$I->see('Order received');

		//$password = $I->grabTextFrom('#password');
		$order_id = $I->grabTextFrom( Locator::find( 'li', ['class' => 'woocommerce-order-overview__order order'] ) );
		$order_id = str_replace( 'ORDER NUMBER:', '', $order_id );

		$I->wait(5);

		$I->logOut();

		$I->amOnPage('/');
		$I->click('My account');
		$I->amOnPage('/my-account/');

		$I->loginAs( $this->create_user_data[ 'email' ], $this->create_user_data[ 'password' ] );

		$I->amOnPage('/my-account/');
		$I->amOnPage('/my-account/orders/');

		$I->see($order_id); 

		//$I->seeInDatabase('wp_users', ['user_email' => $this->create_user_email]); //TO DO VERIFICAR CRIAÇÂO USUÁRIO
	}

	public function EditAccount( AcceptanceTester $I )
	{



		$new_last_name = $this->faker->lastName;

		//Edição do usuário
		$I->click('Account details');
		$I->amOnPage('/my-account/edit-account/');
		$I->fillField( '#account_last_name', $new_last_name );
		$I->click('save_account_details');
		$I->amOnPage('/my-account/');
		//$I->see('Account details changed successfully.');
		$I->click('Account details');
		$I->amOnPage('/my-account/edit-account/');
		$I->see($new_last_name);
		$I->click('Shop');
		$I->amOnPage('/shop/');
		$I->click('Add to cart');
		$I->amOnPage('/shop/');
		$I->click('View cart');
		$I->amOnPage('/cart/');
		$I->click('Proceed to checkout');
		$I->amOnPage('/checkout/');
		$I->wait(2);
		$I->see('Order received');

	}

	public function Checkout(AcceptanceTester $I)
	{
		$I->amOnPage('/product/product-name');
		$I->click([ 'name' => 'add-to-cart']);
		$I->see('Product Name” has been added to your cart.');
		$I->click(['link' => 'View cart']);
		$I->see('Cart');
		$I->click(['link' => 'Proceed to checkout']);

		$I->amOnPage('/checkout/');
		$I->see('Checkout');

		$I->fillField('#billing_first_name', $this->faker->firstName());
		$I->fillField('#billing_last_name', $this->faker->lastName);
		$I->fillField('#billing_company', $this->faker->company);
		$I->selectOption('Country / Region','Brazil');
		$I->fillField('#billing_address_1', $this->faker->streetAddress);
		$I->fillField('#billing_city', $this->faker->city);
		$I->selectOption('State / County',$this->faker->state);
		$I->fillField('#billing_postcode', $this->faker->postcode);
		$I->fillField('#billing_phone', $this->faker->phone);
		$I->fillField('#billing_email', $this->faker->email);

		$I->wait(2);
		$I->click([ 'id' => 'place_order']);

		$I->wait(2);
		$I->see('Order received');

		$order = $I->getOrder();
		$I->wait(2);
		$I->seeInDatabase( 'wp_woocommerce_order_items', [ 'order_id' => $order ] );
	}

	public function RegisterProduct(AcceptanceTester $I)
	{
		$I->loginAsAdmin();
		$I->amOnAdminPage('/');
		$I->see('Painel');

		$I->amOnPage( '/wp-admin/post-new.php?post_type=product' );
		$I->see( 'Add new product' );

		$title = 'Produto '. $this->faker->words(3, true);
		$I->click( '#excerpt-html' );
		$I->fillField( '#excerpt', $this->faker->paragraph(5, true) );
		$I->fillField( '#_regular_price', '20' );
		$I->fillField( '#title', $title );
		$I->click( '#content-html');
		$I->fillField( '#content', $this->faker->paragraph(5, true) );

		$I->wait(2);

		$I->click([ 'id' => 'publish']);
		$I->see( 'Product published' );

		$I->click( [ 'link' => 'View Product' ] );
		$I->see( $title );
	}

	public function RegisterPost(AcceptanceTester $I)
	{
		$I->loginAsAdmin();
		$I->amOnAdminPage('/');
		$I->see('Painel');

		//Criar Post
		$I->amOnPage( '/wp-admin/post-new.php' );
		$I->wait(2);

		$I->tryToClick( Locator::find( 'button', ['aria-label' => 'Close dialog']) );

		$title = $this->faker->words(3, true);
		$I->click( '.rich-text' );
		$I->fillField( '.rich-text', $title );

		$I->click( '.block-editor-block-list__layout' );
		$I->fillField( '.block-editor-rich-text__editable', $this->faker->paragraph(3, true) );

		$I->click([ 'class' => 'editor-post-publish-button__button']);
		$I->wait(1);

		$I->click([ 'class' => 'editor-post-publish-panel__header-publish-button']);

		$I->wait(2);
		$I->see( 'is now live.');

		//Edição Post

		$I->amOnPage( '/wp-admin/edit.php' );
		$I->click(['link' => $title]);

		$I->click( '.rich-text' );
		$I->fillField( '.rich-text', $this->faker->words(5, true) );

		$I->click( '.block-editor-block-list__layout' );
		$I->fillField( '.block-editor-rich-text__editable', $this->faker->paragraph(5, true) );

		$I->click([ 'class' => 'editor-post-publish-button__button']);

		$I->wait(2);

		$I->see( 'Post updated.' );

	}

	public function RegisterPage(AcceptanceTester $I)
	{
		$I->loginAsAdmin();
		$I->amOnAdminPage('/');
		$I->see('Painel');

		//Criar Página
		$I->amOnPage( '/wp-admin/post-new.php?post_type=page' );
		$I->wait(2);

		$I->tryToClick( Locator::find( 'button', ['aria-label' => 'Close dialog']) );

		$title = $this->faker->words(3, true);
		$I->click( '.rich-text' );
		$I->fillField( '.rich-text', $title );

		$I->click( '.block-editor-block-list__layout' );
		$I->fillField( '.block-editor-rich-text__editable', $this->faker->paragraph(5, true) );

		$I->click([ 'class' => 'editor-post-publish-button__button']);
		$I->wait(1);

		$I->click([ 'class' => 'editor-post-publish-panel__header-publish-button']);

		$I->wait(2);
		$I->see( 'is now live.');

		//Edição Página

		$I->amOnPage( 'wp-admin/edit.php?post_type=page' );
		$I->click(['link' => $title]);

		$I->click( '.rich-text' );
		$I->fillField( '.rich-text', $this->faker->words(5, true) );

		$I->click( '.block-editor-block-list__layout' );
		$I->fillField( '.block-editor-rich-text__editable', $this->faker->paragraph(5, true) );

		$I->click([ 'class' => 'editor-post-publish-button__button']);

		$I->wait(2);

		$I->see( 'Page updated.' );
	}
}
