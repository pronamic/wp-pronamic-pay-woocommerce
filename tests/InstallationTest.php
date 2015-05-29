<?php

class Pronamic_WP_Pay_Extensions_WooCommerce_InstallationTest extends PHPUnit_Extensions_SeleniumTestCase {
	protected function setUp() {
		$this->setBrowser( '*chrome' );
		$this->setBrowserUrl( 'http://remcotolsma.dev/' );
	}

	public function testMyTestCase() {
		$this->open( '/wp-login.php?redirect_to=http%3A%2F%2Fremcotolsma.dev%2Fwp-admin%2F&reauth=1' );
		$this->type( 'id=user_login', getenv( 'WP_PAY_TEST_USER' ) );
		$this->type( 'id=user_pass', getenv( 'WP_PAY_TEST_PASSWORD' ) );
		$this->click( 'id=wp-submit' );
		$this->waitForPageToLoad( 30000 );

		$this->click( 'link=Configuraties' );
		$this->waitForPageToLoad( 30000 );

		$this->click( 'css=a.add-new-h2' );
		$this->waitForPageToLoad( 30000 );

		$this->click( 'id=title-prompt-text' );
		$this->click( 'id=title' );
		$this->type( 'id=title', 'Test' );
		
		$this->select( 'id=pronamic_gateway_id', 'label=ABN AMRO - iDEAL Easy' );
		$this->click( 'document.post._pronamic_gateway_mode[1]' );
		
		$this->type( 'id=_pronamic_gateway_ogone_psp_id', 'TESTiDEALEASY' );

		$this->click( 'id=publish' );

		$this->waitForPageToLoad( 30000 );
	}
}
