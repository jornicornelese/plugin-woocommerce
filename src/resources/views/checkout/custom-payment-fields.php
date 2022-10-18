<div class="form-row form-row-wide">
	<label> <?php esc_html_e( 'Company name', 'biller-business-invoice' ); ?><span class="required">*</span></label>
	<input id="biller_company_name" class="input-text" name ="biller_company_name" type="text" autocomplete="off">
	<label> <?php esc_html_e( 'Registration number', 'biller-business-invoice' ); ?>
		<span>(<?php esc_html_e( 'recommended', 'biller-business-invoice' ); ?>)</span></label>
	<input id="biller_registration_number" class="input-text" name ="biller_registration_number" type="text" autocomplete="off">
	<label> <?php esc_html_e( 'VAT number', 'biller-business-invoice' ); ?>
		<span>(<?php esc_html_e( 'optional', 'biller-business-invoice' ); ?>)</span></label>
	<input id="biller_vat_number" class="input-text" name ="biller_vat_number" type="text" autocomplete="off">
</div>
