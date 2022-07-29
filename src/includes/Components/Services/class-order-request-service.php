<?php

namespace Biller\Components\Services;

use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository;
use Biller\BusinessLogic\Order\OrderReference\Repository\OrderReferenceRepository;
use Biller\BusinessLogic\Order\OrderService;
use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Amount\Tax;
use Biller\Domain\Amount\TaxableAmount;
use Biller\Domain\Exceptions\CurrencyMismatchException;
use Biller\Domain\Exceptions\InvalidArgumentException;
use Biller\Domain\Exceptions\InvalidCountryCode;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidLocale;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Exceptions\InvalidTypeException;
use Biller\Domain\Order\OrderRequest;
use Biller\Domain\Order\OrderRequest\Address;
use Biller\Domain\Order\OrderRequest\Buyer;
use Biller\Domain\Order\OrderRequest\Company;
use Biller\Domain\Order\OrderRequest\Country;
use Biller\Domain\Order\OrderRequest\Discount;
use Biller\Domain\Order\OrderRequest\Locale;
use Biller\Domain\Order\OrderRequest\OrderLine;
use Biller\Domain\Order\OrderRequestFactory;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\Http\Exceptions\HttpRequestException;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\Infrastructure\ServiceRegister;
use Biller\Utility\Shop_Helper;
use WC_Customer;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Tax;

class Order_Request_Service {
	const SHIPPING = 'Shipping_cost';
	/**
	 * @var OrderService
	 */
	private $order_service;

	/**
	 * @var UserInfoRepository
	 */
	private $user_info_repository;

	public function __construct() {
		$this->order_service        = new OrderService( new OrderReferenceRepository(),
			new Order_Status_Transition_Service(), new Order_Refund_Service() );
		$this->user_info_repository = ServiceRegister::getService( UserInfoRepository::class );
	}

	/**
	 * Get biller payment link
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 * @throws CurrencyMismatchException
	 * @throws HttpCommunicationException
	 * @throws HttpRequestException
	 * @throws InvalidArgumentException
	 * @throws InvalidCountryCode
	 * @throws InvalidCurrencyCode
	 * @throws InvalidLocale
	 * @throws InvalidTaxPercentage
	 * @throws InvalidTypeException
	 * @throws QueryFilterInvalidParamException
	 * @throws RequestNotSuccessfulException
	 */
	public function get_payment_link( WC_Order $order ) {
		$order_request = $this->create_order_request( $order );

		try {
			return $this->order_service->create( $order_request );
		} catch ( HttpRequestException $e ) {
			if ( (int) $e->getCode() === 500 ) {
				throw new HttpRequestException( __( 'The unexpected error occurred, please select different payment method.', 'biller-business-invoice' ) );
			}

			throw $e;
		}
	}

	/**
	 * Create biller order request
	 *
	 * @param WC_Order $order
	 *
	 * @return OrderRequest
	 * @throws InvalidArgumentException
	 * @throws InvalidTaxPercentage
	 * @throws InvalidTypeException
	 * @throws InvalidCurrencyCode
	 * @throws InvalidLocale
	 * @throws InvalidCountryCode
	 * @throws CurrencyMismatchException
	 * @throws \Exception
	 */
	public function create_order_request( WC_Order $order ) {
		// When order request initialized via the direct order status update to the "Completed" session is missing.
		WC()->frontend_includes();
		WC()->initialize_session();

		$user_info = $this->user_info_repository->getActiveUserInfo();
		if ( $user_info === null ) {
			throw new InvalidArgumentException( 'Biller user does not exist!' );
		}

		$order_currency                    = Currency::fromIsoCode( $order->get_currency() );
		$order_request_factory             = new OrderRequestFactory();
		$order_line_discount_total_inc_tax = 0;
		$customer                          = $order->get_customer_id() ? new \WC_Customer( $order->get_customer_id() ) : null;

		$order_request_factory->setExternalWebshopUID( $user_info->getWebShopUID() );
		$order_request_factory->setExternalOrderUID( $order->get_id() );
		$order_request_factory->setExternalOrderNumber( $order->get_order_number() );
		$order_request_factory->setAmount( Amount::fromFloat( (float) $order->get_total(), $order_currency ) );

		/** @var WC_Order_Item_Product $item */
		foreach ( $order->get_items() as $item ) {
			$item_subtotal_inc_tax = (float) $item->get_subtotal() + (float) $item->get_subtotal_tax();
			$item_total_inc_tax    = (float) $item->get_total() + (float) $item->get_total_tax();
			$item_total_ex_tax     = (float) $item->get_total();
			if ( $item->get_subtotal() > $item->get_total() ) {
				$order_line_discount_total_inc_tax += $item_subtotal_inc_tax - $item_total_inc_tax;
			}

			$order_request_factory->addOrderLine( new OrderLine(
				$item->get_product_id(),
				$item->get_name(),
				TaxableAmount::fromAmounts(
					Amount::fromFloat( $item_total_ex_tax / (int) $item->get_quantity(), $order_currency ),
					Amount::fromFloat( $item_total_inc_tax / (int) $item->get_quantity(), $order_currency )
				),
				$this->get_item_tax_rate( $item, $customer ),
				$item->get_quantity()
			) );
		}

		foreach ( $order->get_fees() as $fee ) {
			$order_request_factory->addOrderLine( new OrderLine(
				$fee->get_id(),
				$fee->get_name(),
				TaxableAmount::fromAmounts(
					Amount::fromFloat( (float) $fee->get_total(), $order_currency ),
					Amount::fromFloat( (float) $fee->get_total() + (float) $fee->get_total_tax(), $order_currency )
				),
				$this->get_fee_tax_rate( $fee, $customer ),
				1
			) );
		}

		if ( ! empty( $order->get_shipping_total() ) ) {
			$shipping_amount = TaxableAmount::fromAmounts(
				Amount::fromFloat( (float) $order->get_shipping_total(), $order_currency ),
				Amount::fromFloat( (float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), $order_currency )
			);

			$order_request_factory->addOrderLine( new OrderLine(
				self::SHIPPING,
				$order->get_shipping_method(),
				$shipping_amount,
				$shipping_amount->getTax()->getPercentage(),
				1
			) );
		}

		$discount_description = '';
		foreach ( $order->get_items( 'coupon' ) as $coupon ) {
			$discount_description .= "{$coupon->get_code()} ";
		}

		if ( $order->get_total_discount( false ) - $order_line_discount_total_inc_tax > 0 ) {
			$order_request_factory->addDiscount(
				new Discount(
					$discount_description,
					TaxableAmount::fromAmountInclTaxAndTax(
						Amount::fromFloat( $order->get_total_discount( false ) - $order_line_discount_total_inc_tax,
							$order_currency ),
						new Tax( 100 * (float) $order->get_discount_tax() / (float) $order->get_discount_total() )
					)
				)
			);
		}

		$order_request_factory->setBuyerCompany( new Company( $order->get_meta( 'biller_company_name' ),
			$order->get_meta( 'biller_registration_number' ), $order->get_meta( 'biller_vat_number' ) ) );
		$order_request_factory->setBuyerRepresentative( new Buyer( $order->get_billing_first_name(),
			$order->get_billing_last_name(), $order->get_billing_email(), $order->get_billing_phone() ) );
		$billingAddress = new Address( $order->get_billing_city(), $order->get_billing_postcode(),
			Country::fromIsoCode( $order->get_billing_country() ), $order->get_billing_address_1(),
			$order->get_billing_address_2(), $order->get_billing_state() );
		$order_request_factory->setBillingAddress( $billingAddress );
		if ( $this->haveShippingAddress( $order ) ) {
			$order_request_factory->setShippingAddress( new Address( $order->get_shipping_city(),
				$order->get_shipping_postcode(), Country::fromIsoCode( $order->get_shipping_country() ),
				$order->get_shipping_address_1(), $order->get_shipping_address_2(), $order->get_shipping_state() ) );
		} else {
			$order_request_factory->setShippingAddress( $billingAddress );
		}
		$order_request_factory->setLocale( Locale::fromCode( explode( '_', get_locale() )[0] ) );
		$order_request_factory->setSuccessUrl( Shop_Helper::get_controller_url(
			'Payment_Redirection',
			'redirect',
			[ 'order_status' => 'success', 'order_id' => $order->get_id() ]
		) );
		$order_request_factory->setErrorUrl( Shop_Helper::get_controller_url(
			'Payment_Redirection',
			'redirect',
			[ 'order_status' => 'error', 'order_id' => $order->get_id() ]
		) );
		$order_request_factory->setCancelUrl( Shop_Helper::get_controller_url(
			'Payment_Redirection',
			'redirect',
			[ 'order_status' => 'cancel', 'order_id' => $order->get_id() ]
		) );
		$order_request_factory->setWebhookUrl( get_site_url() . '/wc-api/biller_webhook' );

		return $order_request_factory->create();
	}

	/**
	 * Calculate item tax rate.
	 *
	 * @param WC_Order_Item_Product $item
	 * @param WC_Customer $customer
	 *
	 * @return int Item tax rate as value between 0 and 100.
	 */
	private function get_item_tax_rate( $item, $customer ) {
		if ( ! $item->get_product() || ! $item->get_product()->is_taxable() || $item->get_subtotal_tax() <= 0 ) {
			return 0;
		}

		return $this->calculate_tax_rate( $item->get_product()->get_tax_class(), $customer );
	}

	/**
	 * Calculate fee tax rate.
	 *
	 * @param WC_Order_item_Fee $fee
	 * @param WC_Customer $customer
	 *
	 * @return float Fee tax rate as value between 0 and 100.
	 */
	private function get_fee_tax_rate( $fee, $customer ) {
		if ( $fee->get_tax_status() !== 'taxable' || $fee->get_total_tax() <= 0 ) {
			return 0;
		}

		return $this->calculate_tax_rate( $fee->get_tax_class(), $customer );
	}

	/**
	 * Calculates final tax rate based on tax class and customer
	 *
	 * @param string $tax_class
	 * @param WC_Customer $customer
	 *
	 * @return float Tax rate as value between 0 and 100.
	 */
	private function calculate_tax_rate( $tax_class, $customer ) {
		$tax_rates      = WC_Tax::get_rates( $tax_class, $customer );
		$final_tax_rate = 0;

		foreach ( $tax_rates as $rate ) {
			if ( ! isset( $rate['rate'] ) ) {
				continue;
			}

			$compound_rate = $rate['rate'];
			if ( $rate['compound'] === "yes" ) {
				$compound_rate = round( $final_tax_rate * ( $rate['rate'] / 100 ) ) + $rate['rate'];
			}

			$final_tax_rate += $compound_rate;
		}

		return $final_tax_rate;
	}

	private function haveShippingAddress( $order ) {
		foreach ( $order->get_address( 'shipping' ) as $item ) {
			if ( ! empty( $item ) ) {
				return true;
			}
		}

		return false;
	}
}