<?php
/**
 * @var array $data
 */
?>
<div class="biller-overview-box">
    <input type="hidden" id="biller-order-capture-url" value="<?php echo $data['capture_link']; ?>">
    <input type="hidden" id="biller-order-cancel-url" value="<?php echo $data['cancel_link']; ?>">
    <div>
        <label class="biller-property-name">
			<?php echo __( 'Payment status:', 'biller-business-invoice' ); ?>
        </label>
        <label>
			<?php echo $data['payment_status']; ?>
        </label>
    </div>
    <div id="biller-payment-link-container" class="biller-buttons-container <?php echo $data['display_link'] ? '' : 'biller-hide'; ?>">
        <label class="biller-property-name" for="biller-payment-link-input">
			<?php echo __( 'Payment link', 'biller-business-invoice' ); ?>
			<?php echo wc_help_tip( __( "The payment link will redirect the customer to Biller for payment.",
				'biller-business-invoice' ) ); ?>
        </label>
        <div>
            <input id="biller-payment-link-input" class="biller-link-input" type="text"
                   value="<?php echo $data['payment_link']; ?>" readonly>
            <button id="biller-copy-btn" class="button" type="button"><?php echo __( 'Copy', 'biller-business-invoice' ); ?></button>
        </div>
    </div>
    <div id="biller-payment-link-container" class="biller-buttons-container <?php echo $data['display_company_info_message'] ? '' : 'biller-hide'; ?>">
        <label class="biller-property-name">
			<?php echo __( 'Payment link', 'biller-business-invoice' ); ?>
			<?php echo wc_help_tip( __( "For Biller a company name is required for a valid payment link, please enter the custom field value of the order.",
				'biller-business-invoice' ) ); ?>
        </label>
    </div>
    <div class="biller-buttons-container">
        <button id="biller-cancel-button"
                class="<?php echo $data['display_cancel_button'] ? 'button' : 'biller-hide'; ?>" type="button">
			<?php echo __( 'Cancel', 'biller-business-invoice' ); ?>
        </button>
        <button id="biller-capture-button"
                class="<?php echo $data['display_capture_button'] ? 'button' : 'biller-hide'; ?>" type="button">
			<?php echo __( 'Capture', 'biller-business-invoice' ); ?>
        </button>
    </div>
</div>
