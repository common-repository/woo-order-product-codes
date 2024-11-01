<?php

class WOPC_Code_Generator extends WOPC {
	private $pattern = '';
	private $campaign = 0;
	public $valid_pattern = true;

	private $valid_characters = array(
		'l' => 'abcdefghijklmnopqrstuvwxyz',
		'L' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'c' => '!@#$%^&*()_+=-[];,.?',
		'n' => '0123456789',
		'a' => '',
		'C' => '',
	);

	function __construct( $pattern, $campaign = 0 ) {
		$this->valid_characters = apply_filters( 'wopc_code_genrator_valid_characters', $this->valid_characters );
		$this->pattern          = $pattern;
		$this->campaign         = $campaign;
		$valid_pattern          = $this->wopc_is_pattern_valid();
		if ( true !== $valid_pattern ) {
			$this->valid_pattern = $valid_pattern;
		}
		$this->valid_pattern = apply_filters( 'wopc_code_genrator_valid_pattern', $this->valid_pattern );
	}

	// VALIDATE PATTERN
	public function wopc_is_pattern_valid() {
		$return_val = true;
		if ( is_string( $this->pattern ) ) {
			if ( '' !== $this->pattern ) {
				$nr_open   = substr_count( $this->pattern, '[' );
				$nr_closed = substr_count( $this->pattern, ']' );

				if ( $nr_open === $nr_closed ) {
					$pattern_array = explode( '[', $this->pattern );

					reset( $pattern_array );
					$key = key( $pattern_array );
					unset( $pattern_array[ $key ] );

					foreach ( $pattern_array as $pattern_string ) {
						if ( substr( $pattern_string, - 1 ) === ']' ) {
							$pattern_string = trim( $pattern_string, ']' );
							$pattern        = explode( ':', $pattern_string, 2 );
							$contains_fixed = 0;

							$types = str_split( $pattern[0] );
							foreach ( $types as $type ) {
								if ( 'C' == $type ) {
									$contains_fixed = 1;
								}
								if ( ! array_key_exists( $type, $this->valid_characters ) ) {
									if ( ! is_array( $return_val ) ) {
										$return_val = array();
									}
									$return_val[] = sprintf( __( 'Pattern [%s] has incorrect type.', $this->domain ), $pattern_string );
									break;
								}
							}

							if ( 1 != $contains_fixed ) {
								if ( strpos( $pattern[1], '-' ) ) {
									$pattern[1] = explode( '-', $pattern[1] );
									if ( ! ctype_digit( $pattern[1][0] ) || ! ctype_digit( $pattern[1][1] ) ) {
										if ( ! is_array( $return_val ) ) {
											$return_val = array();
										}
										$return_val[] = sprintf( __( 'Pattern [%s] has incorrect length value.', $this->domain ), $pattern_string );
									}
								} else {
									if ( ! ctype_digit( $pattern[1] ) ) {
										if ( ! is_array( $return_val ) ) {
											$return_val = array();
										}
										$return_val[] = sprintf( __( 'Pattern [%s] has incorrect length value.', $this->domain ), $pattern_string );
									}
								}
							} else {
								if ( strpos( $pattern[1], ':' ) ) {
									if ( ! is_array( $return_val ) ) {
										$return_val = array();
									}
									$return_val[] = sprintf( __( 'Pattern [%s] has invalid characters in declaration.', $this->domain ), $pattern_string );
								}
							}
						} else {
							$return_val = sprintf( __( 'Pattern %s has incorrect structure. Pattern must end with ].', $this->domain ), $this->pattern );
						}
					}
				} else {
					$return_val = sprintf( __( 'Incorrect number of [] charcaters in pattern %s.', $this->domain ), $this->pattern );
				}
			} else {
				$return_val = __( 'Pattern is empty.', $this->domain );
			}
		} else {
			$return_val = __( 'Pattern is invalid type.', $this->domain );
		}

		return $return_val;
	}

	// GENERATE CODE
	private function wopc_generate_random_string( $length = 10, $type = 'a', $characters = '' ) {
		if ( 'C' == $type && '' != $characters ) {
			return $characters;
		} else {
			if ( 'a' == $type ) {
				$characters .= implode( '', $this->valid_characters );
			} else {
				$type = str_split( $type );
				foreach ( $type as $type_single ) {
					if ( isset( $this->valid_characters[ $type_single ] ) ) {
						$characters .= $this->valid_characters[ $type_single ];
					}
				}
			}
			$characters_length = strlen( $characters );

			$return_string = '';
			for ( $i = 0; $i < $length; $i ++ ) {
				$return_string .= $characters[ rand( 0, $characters_length - 1 ) ];
			}

			return $return_string;
		}
	}

	public function wopc_create_code() {
		$code          = '';
		$pattern_array = explode( '[', $this->pattern );

		reset( $pattern_array );
		$key = key( $pattern_array );
		unset( $pattern_array[ $key ] );

		foreach ( $pattern_array as $key => $pattern ) {
			$pattern = apply_filters( 'wopc_code_generator_pattern', $pattern );
			$pattern = apply_filters( 'wopc_code_generator_pattern_campaign_' . $this->campaign, $pattern );
			$pattern = apply_filters( 'wopc_code_generator_pattern_campaign_single_' . $this->campaign . '_' . $key, $pattern );

			if ( substr( $pattern, - 1 ) === ']' ) {
				$pattern = trim( $pattern, ']' );
				$pattern = explode( ':', $pattern, 2 );
				if ( '' != $pattern[1] ) {
					$length     = 0;
					$characters = '';
					if ( 'C' == $pattern[0] ) {
						$characters = $pattern[1];
					} else {
						if ( strpos( $pattern[1], '-' ) ) {
							$pattern[1]    = explode( '-', $pattern[1] );
							$pattern[1][0] = intval( $pattern[1][0] );
							$pattern[1][1] = intval( $pattern[1][1] );
							if ( $pattern[1][0] > $pattern[1][1] ) {
								$pattern[1] = array_reverse( $pattern[1] );
							}

							$length = rand( $pattern[1][0], $pattern[1][1] );
						} else {
							$length = intval( $pattern[1] );
						}
					}
					$code .= $this->wopc_generate_random_string( $length, $pattern[0], $characters );
				}
			}
		}
		$code = apply_filters( 'wopc_code_generator_code', $code );
		$code = apply_filters( 'wopc_code_generator_code_campaign_' . $this->campaign, $code );

		return $code;
	}
}
