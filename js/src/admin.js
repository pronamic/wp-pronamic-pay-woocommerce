function test() {
	const displayElement = document.getElementById( 'woocommerce_pronamic_pay_ideal_icon_display' );
	const iconElement    = document.getElementById( 'woocommerce_pronamic_pay_ideal_icon' );

	if ( ! displayElement ) {
		return;
	}

	if ( ! iconElement ) {
		return;
	}

	const iconElementRow = iconElement.closest( 'tr' );

	if ( ! iconElementRow ) {
		return;
	}

	function update() {
		iconElementRow.style.display = ( 'custom' === displayElement.value ) ? '' : 'none';

		console.log( iconElementRow.display );
	}

	displayElement.addEventListener( 'change', function ( event ) {
		update();
	} );

	update();
}

test();
