<?php
// ADD FIND IN SET TO META COMPARE
// https://gist.github.com/mikeschinkel/6402058
class WOPC_Find_In_Set_Compare_To_Meta_Query {
	function __construct() {
		add_action( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
	}

	function posts_where( $where, $query ) {
		global $wpdb;
		foreach ( $query->meta_query->queries as $meta_query ) {
			if ( isset( $meta_query['compare'] ) && 'find_in_set' == strtolower( $meta_query['compare'] ) ) {
				$search  = "( {$wpdb->postmeta}.meta_key = '" . preg_quote( $meta_query['key'] ) . "' AND {$wpdb->postmeta}.meta_value = '" . preg_quote( $meta_query['value'] ) . "' )";
				$replace = "( {$wpdb->postmeta}.meta_key = '" . preg_quote( $meta_query['key'] ) . "' AND FIND_IN_SET( '" . preg_quote( $meta_query['value'] ) . "', {$wpdb->postmeta}.meta_value ))";
				$where   = str_replace( $search, $replace, $where );
			}
		}

		return $where;
	}
}

new WOPC_Find_In_Set_Compare_To_Meta_Query();

// OTHER FUNCTIONS
function wopc_sanitize_array( $input ) {
	$sanitary_values = implode( ', ', $input );

	return $sanitary_values;
}


/*
	$data = array( 'order_id' => 50 );
	echo wopc_replace_tags('{{order_billing_fullname}}', $data );
*/
function wopc_replace_tags( $string, $args = null ) {
	if ( preg_match_all( '/{{(.*?)}}/', $string, $m ) ) {
		if ( isset( $args['order_id'] ) ) {
			$args['order'] = new WC_Order( $args['order_id'] );
		}
		if ( isset( $args['campaign_id'] ) ) {
			$args['campaign'] = get_post( $args['campaign_id'] );
		}
		foreach ( $m[1] as $i => $varname ) {
			$data = '';
			switch ( $m[1][ $i ] ) {
				case 'order_id':
					$data = $args['order']->get_id();
					break;
				case 'order_date':
					$order_data = $args['order']->get_data();
					$data       = $order_data['date_created']->date( 'Y-m-d H:i:s' );
					unset( $order_data );
					break;
				case 'order_billing_firstname':
					$data = $args['order']->get_billing_first_name();
					break;
				case 'order_billing_lastname':
					$data = $args['order']->get_billing_last_name();
					break;
				case 'order_billing_fullname':
					$data = $args['order']->get_formatted_billing_full_name();
					break;
				case 'order_billing_country':
					$data = $args['order']->get_billing_country();
					break;
				case 'codes':
					$glue = apply_filters( 'wopc_codes_posttype_campaing_args_fields', '<br />' );
					$data = implode( $glue, $args['codes'] );
					break;
				case 'site_email':
					$data = get_bloginfo( 'admin_email' );
					break;
				case 'site_name':
					$data = get_bloginfo( 'name' );
					break;
				case 'campaign_name':
					$data = $args['campaign']->post_title;
					break;
				default:
					$data = apply_filters( 'wopc_replace_tags_new_value', $m[1][ $i ], $args );
			}
			$string = str_replace( $m[0][ $i ], sprintf( '%s', $data ), $string );
		}
	}

	return $string;
}

function wopc_products_tags( $items, $explode = ',' ) {
	global $wopc;
	$html = '';
	if ( is_string( $items ) ) {
		$items = explode( $explode, $items );
	}

	if ( 0 < count( $items ) ) {
		$html .= '<ul id="products_list" class="tagchecklist">';
		foreach ( $items as $item ) {
			$product = wc_get_product( $item );
			if ( false != $product ) {
				$html .= '<li>
					<button type="button" id="product_li-' . $item . '" data-attr="' . $item . '" class="ntdelbutton" title="' . sprintf( __( 'Remove product: %s', $wopc->domain ), $product->get_title() ) . '">
						<span class="remove-tag-icon" aria-hidden="true"></span>
					</button>&nbsp;' . $product->get_title() . '
				</li>';
			}
		}
		$html .= '</ul>';
	}

	return $html;
}

function wopc_setting_html( $args, $usage = 'settings' ) {
	$html         = '';
	$args['name'] = esc_attr( $args['name'] );
	$args['id']   = esc_attr( $args['id'] );
	$args         = apply_filters( 'wopc_field_args-' . $args['id'], $args );

	$html = apply_filters( 'wopc_campaign_setting_html_before', $html );
	ob_start();
	$required = '';
	if ( isset( $args['required'] ) ) {
		$required = 'required';
	}

	// LABEL CLASS
	$class_add_label = array();
	if ( isset( $args['class_label'] ) ) {
		$class_add_label = explode( ' ', $args['class_label'] );
	}
	$class_add_label = apply_filters( 'wopc_field_html_class_label', $class_add_label );
	$class_add_label = apply_filters( 'wopc_field_html_class_label-' . $args['id'], $class_add_label );

	// INPUT CLASS
	$class_add_input = array();
	if ( isset( $args['class_input'] ) ) {
		$class_add_input = explode( ' ', $args['class_input'] );
	}
	$class_add_input = apply_filters( 'wopc_field_html_class_input', $class_add_input );
	$class_add_input = apply_filters( 'wopc_field_html_class_input-' . $args['id'], $class_add_input );

	// INPUT ATTR
	$attr_input = array();
	if ( isset( $args['attr_input'] ) ) {
		foreach ( $args['attr_input'] as $k => $attr_val ) {
			$attr_input[] = $k . '="' . $attr_val . '"';
		}
	}
	$attr_input                = apply_filters( 'wopc_field_html_attr_input', $attr_input );
	$attr_input                = apply_filters( 'wopc_field_html_attr_input-' . $args['id'], $attr_input );

	switch ( $args['type'] ) {
		case 'checkbox':
			$args = apply_filters( 'wopc_field_checkbox_args', $args );
			break;
		case 'select':
			$args = apply_filters( 'wopc_field_select_args', $args );
		case 'select_multiple':
			$args = apply_filters( 'wopc_field_select_multiple_args', $args );
			$value_select      = $args['value'];

			$multiple = '';
			if ( 'select_multiple' == $args['type'] ) {
				$value_select = str_replace( ', ', ',', $value_select );
				$value_select = explode( ',', $value_select );
				$multiple     = ' multiple';
			}

			if ( 'settings' == $usage ) {
				echo '<label class="' . implode( ' ', $class_add_label ) . '" for="' . $args['id'] . '">';
			}
			?>
            <select id="<?php echo $args['id']; ?>"
                    class="<?php echo implode( ' ', $class_add_input ); ?>"
                    value="<?php echo $args['value']; ?>"
                    name="<?php echo $args['name'] . ( 'select_multiple' == $args['type'] ? '[]' : '' ); ?>"
				<?php echo $required . $multiple; ?>>
				<?php
				foreach ( $args['values'] as $k => $value ) {
					if ( 'select' == $args['type'] ) {
						$selected = selected( $value_select, $k, false );
					} elseif ( 'select_multiple' == $args['type'] ) {
						if ( in_array( $k, $value_select ) ) {
							$selected = ' selected="selected"';
						} else {
							$selected = '';
						}
					}
					echo '<option value="' . $k . '" ' . $selected . '>' . $value . '</option>';
				}
				?>
            </select>
			<?php
			if ( 'settings' == $usage ) {
				echo '</label>';
			}
			break;
		case 'checkbox_slider':
			$args = apply_filters( 'wopc_field_checkbox_slider_args', $args );
			$class_add_label[] = 'switch';

			if ( 'settings' == $usage ) {
				echo '<label class="' . implode( ' ', $class_add_label ) . '" for="' . $args['id'] . '">';
			}
			?>
            <input id="<?php echo $args['id']; ?>"
                   class="<?php echo implode( ' ', $class_add_input ); ?>"
                   value="<?php echo $args['checked_value']; ?>"
                   name="<?php echo $args['name']; ?>"
                   type="checkbox"
				<?php echo $required . ' ' . checked( $args['value'], $args['checked_value'], false ); ?>
            />
			<?php echo '<span class="slider round"></span>'; ?>
			<?php
			if ( 'settings' == $usage ) {
				echo '</label>';
			}
			break;
		case 'textarea':
			$args = apply_filters( 'wopc_field_textarea_args', $args );
			if ( 'settings' == $usage ) {
				echo '<label class="' . implode( ' ', $class_add_label ) . '" for="' . $args['id'] . '">';
			}
			?>
            <textarea id="<?php echo $args['id']; ?>"
                      class="<?php echo implode( ' ', $class_add_input ); ?>"
                      name="<?php echo $args['name']; ?>"
                      type="<?php echo $args['type']; ?>"
				<?php echo $required . ' ' . implode( ' ', $attr_input ); ?>><?php echo $args['value']; ?></textarea>
			<?php
			if ( 'settings' == $usage ) {
				echo '</label>';
			}
			break;
		case 'select_autocomplete':
			$args         = apply_filters( 'wopc_field_select_autocomplete_args', $args );
			$hidden_class = '';
			if ( isset( $args['hidden_class'] ) && is_array( $args['hidden_class'] ) ) {
				$hidden_class = array();
				foreach ( $args['hidden_class'] as $key => $val ) {
					$hidden_class[] = $key . ' = "' . $val . '"';
				}
				$hidden_class = implode( ' ', $hidden_class );
			}

			$attr_hidden = '';
			if ( isset( $args['attr_hidden'] ) ) {
				$attr_hidden = array();
				foreach ( $args['attr_hidden'] as $key => $val ) {
					$attr_hidden[] = $key . ' = "' . $val . '"';
				}
				$attr_hidden = implode( ' ', $attr_hidden );
			}
			echo '<input name="' . $args['name'] . '" type="hidden" id="' . $args['name'] . '" class="' . $hidden_class . '" value="' . $args['value'] . '" ' . $attr_hidden . '/>';
			echo '<input type="text" autocomplete="off" id="autocomplete_' . $args['name'] . '" class="wopc_sutocomplete ' . implode( ' ', $class_add_input ) . '" ' . $attr_input . '/>';
			echo wp_nonce_field( 'wopc_nonce_' . $args['name'], 'wopc_nonce_' . $args['name'], '', false );
			break;
		default:
			$args = apply_filters( 'wopc_field_' . $args['type'] . '_args', $args );
			if ( 'settings' == $usage ) {
				echo '<label class="' . implode( ' ', $class_add_label ) . '" for="' . $args['id'] . '">';
			}
			?>
            <input id="<?php echo $args['id']; ?>"
                   class="<?php echo implode( ' ', $class_add_input ); ?>"
                   name="<?php echo $args['name']; ?>"
                   type="<?php echo $args['type']; ?>"
                   value="<?php echo $args['value']; ?>"
				<?php echo $required . ' ' . implode( ' ', $attr_input ); ?>
            />
			<?php
			if ( 'settings' == $usage ) {
				echo '</label>';
			}
	}

	if ( 'settings' == $usage && isset( $args['description'] ) && $args['description'] != "" ) {
		echo '<p class="description">' . $args['description'] . '</p>';
	}

	$html = ob_get_contents();
	ob_end_clean();
	$html = apply_filters( 'wopc_campaign_setting_html_after', $html );

	return $html;
}

function wopc_email_headers( $from_email, $from_name ) {
	return array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . $from_name . ' <' . $from_email . '>',
	);
}