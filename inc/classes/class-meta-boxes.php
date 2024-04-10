<?php
/**
 * Init Dashboard Meta boxes
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;

class Meta_Boxes {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		/**
		 * Actions.
		 */
		add_action('add_meta_boxes', [$this, 'add_custom_meta_box']);
		// add_action('save_post', [$this, 'save_post_meta_data']);
	}
	/**
	 * Add custom meta box.
	 *
	 * @return void
	 */
	public function add_custom_meta_box() {
		$screens = ['shop_order'];
		foreach ($screens as $screen) {
			add_meta_box(
				'payment_details_metabox',           				// Unique ID
				__('Payment Details', 'esignbinding'),  					// Box title
				[$this, 'custom_meta_box_html'],  					// Content callback, must be of type callable
				$screen,                   								// Post type
				'advanced',                   							// context
				'high'													// priority
			);
		}
	}
	/**
	 * Custom meta box HTML(for form)
	 *
	 * @param object $post Post.
	 *
	 * @return void
	 */
	public function custom_meta_box_html($post) {
		$order = wc_get_order($post->ID);
		// 
		?>
		<table class="payment_table">
			<thead>
				<tr>
					<td class="th">Payment URI</td>
					<td class="th"><?php echo esc_html($order->get_checkout_payment_url()); ?></td>
				</tr>
				<tr>
					<td class="th">Trx ID#</td>
					<td class="th"><?php echo esc_html($order->get_transaction_id()); ?></td>
				</tr>
				<tr>
					<td class="th">Data</td>
					<td class="th"><?php // print_r($order->get_meta_data('_flutterwave_trx_info')); ?></td>
				</tr>
			</thead>
		</table>
		<?php
		// 
		// print_r($order);
	}
	/**
	 * Save post meta into database
	 * when the post is saved.
	 *
	 * @param integer $post_id Post id.
	 *
	 * @return void
	 */
	public function save_post_meta_data($post_id) {
		/**
		 * When the post is saved or updated we get $_POST available
		 * Check if the current user is authorized
		 */
		if (! current_user_can('edit_post', $post_id)) {
			return;
		}
		/**
		 * Check if the nonce value we received is the same we created.
		 */
		if (! isset($_POST['hide_title_meta_box_nonce_name']) ||
		     ! wp_verify_nonce($_POST['hide_title_meta_box_nonce_name'], plugin_basename(__FILE__))
		) {
			return;
		}
		if (array_key_exists('aquila_hide_title_field', $_POST)) {
			update_post_meta(
				$post_id,
				'_hide_page_title',
				$_POST['aquila_hide_title_field']
			);
		}
	}
}
