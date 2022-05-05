<?php
/**
 * @var array $data
 */

?>
<input id="endpoint-url" type="hidden" name="endpoint-url" value="<?php
echo $data['url']; ?>"/>
<tr valign="top">
    <th scope="row" class=""><?php
		esc_html_e( 'Notifications', 'biller' ); ?></th>
    <td class="forminp" id="">
        <div class="wc_input_table_wrapper">
            <table class="widefat wc_input_table" style="min-width: 1000px;">
                <thead>
                <tr>
                    <th class="notification-cell" style="width: 54px"><?php
						esc_html_e( 'ID', 'biller' ); ?></th>
                    <th class="notification-cell" style="width: 180px"><?php
						esc_html_e( 'Date', 'biller' ); ?></th>
                    <th class="notification-cell" style="width: 110px"><?php
						esc_html_e( 'Type', 'biller' ); ?></th>
                    <th class="notification-cell" style="width: 115px"><?php
						esc_html_e( 'Order number', 'biller' ); ?></th>
                    <th class="notification-cell"><?php
						esc_html_e( 'Message', 'biller' ); ?></th>
                    <th class="notification-cell"><?php
						esc_html_e( 'Details', 'biller' ); ?></th>
                </tr>
                </thead>

                <tbody id="table">

                </tbody>
                <tfoot>
                <tr>
                    <th colspan="7">
                        <a id="nextPage" style="float:right;"
                           class="add button"><?php
							esc_html_e( 'Next', 'biller' ); ?></a>
                        <a id="previousPage" style="float:right;"
                           class="remove_rows button"><?php
							esc_html_e( 'Previous', 'biller' ); ?></a>
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>
    </td>
</tr>
