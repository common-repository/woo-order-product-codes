<?php

class WOPC_Campaign extends WOPC_PostTypes_Campaign {
	private $campaign_data = array();
	private $id = '';

	function __construct( $id_campaign ) {
		if ( isset( $id_campaign ) ) {
			$this->id = $id_campaign;
			$fields   = parent::get_fields();
			$metas    = get_post_custom( $id_campaign );
			foreach ( array_keys($fields) as $key ) {
				$this->__set( $key, $metas[ $key ][0] );
			}
		} else {
			return false;
		}
	}

	public function __get( string $key ) {
		if ( array_key_exists( $key, $this->campaign_data ) ) {
			return $this->campaign_data[ $key ];
		}

		return null;
	}

	public function __set( string $key, $value ) {
		$this->campaign_data[ $key ] = $value;
	}

	public function __isset( string $key ) {
		return array_key_exists( $key, $this->campaign_data );
	}

	public function get_number_code( $total ) {
		$return = 0;
		switch ( $this->__get( 'code_how_many' ) ) {
			case 'variable_codes':
				$return = ceil( $total / $this->__get( 'code_variable' ) );
				break;
			default:
				$return = $this->__get( 'code_fixed' );
		}

		return apply_filters( 'wopc_order_number_codes', $return );
	}

	public function create_code() {
		$code = new WOPC_Code_Generator( $this->__get( 'code_pattern' ), $this->id );
		if ( true === $code->valid_pattern ) {
			return $code->wopc_create_code();
		} else {
			return false;
		}
	}

	public function save_codes( $codes, $order ) {
		if ( is_int( $order ) ) {
			$order = new WC_Order( $order );
		}
		$code_obj = new WOPC_Code();
		$code_obj->create_code_post( $codes, $order );
	}

	public function send_email( $order ) {
		$nr_codes = intval( $this->get_number_code( $order->get_total() ) );
		if ( is_int( $nr_codes ) && $nr_codes > 0 ) {
			// CODES
			$codes = array();
			for ( $i = 0; $i < $nr_codes; $i ++ ) {
				$temp = $this->create_code();
				if ( $temp ) {
					$codes[] = $temp;
				}
			}

			$this->save_codes( $codes, $order );

			//EMAIL
			$convert_data = array(
				'order'       => $order,
				'campaign_id' => $this->id,
				'codes'       => $codes,
			);

			$to         = $order->get_billing_email();
			$subject    = wopc_replace_tags( $this->__get( 'email_subject' ), $convert_data );
			$message    = wopc_replace_tags( $this->__get( 'email_message' ), $convert_data );
			$from_email = wopc_replace_tags( $this->__get( 'email_from' ), $convert_data );
			$from_name  = wopc_replace_tags( $this->__get( 'email_name' ), $convert_data );
			$headers    = wopc_email_headers( $from_email, $from_name );
			wp_mail( $to, $subject, $message, $headers );


			return true;
		}
	}
}
