<?php
/**
 * Widgets List page for admin dashboard.
 *
 * @package WooFlutter
 */
namespace WOOFLUTTER\inc;
use WOOFLUTTER\inc\Traits\Singleton;
class Widgets {
	use Singleton;
	private $translations = [];
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		// add_shortcode('widgets_lists', [$this, 'widgets_lists']);
		add_action('admin_menu', [$this, 'populate_menu']);
		add_action('wp_ajax_wooflutter/ajax/update/widget', [$this, 'update_widget']);
	}
	public function populate_menu() {
		add_submenu_page('tools.php', __('Widgets list grid', 'wooflutter'), __('Flutterwave', 'wooflutter'), 'install_plugins', 'wooflutterwave', [$this, 'widgets_lists_output']);
	}
	public function get_widgets_lists() {
		$widgets = apply_filters('wooflutter/widgets/list', []);
		foreach ($widgets as $widgetID => $widget) {
			$widget['ID'] = $widgetID;
			$widgets[$widgetID] = $widget;
		}
		usort($widgets, function ($a, $b) {
			return $a['priority'] <=> $b['priority'];
		});
		return $widgets;
	}
	public function widgets_lists_output() {
		$widgets = $this->get_widgets_lists();
		// 
		?>
		<div class="wooflutter">
			<div class="wooflutter__widgets">
				<div class="wooflutter__container">
					<div class="wooflutter__wrap">
						<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wooflutter__form" method="post" name="theform">
							<div class="wooflutter__content">
								<h2><?php echo esc_html(__('Widgets List', 'wooflutter')); ?></h2>
								<p><?php echo esc_html(__('Select modules for Flutterwave Playments. You can always enable & disable any widgets form here. Disabling an widget will pause all functionalities for that widget and previous data won\'t be remove from database. It is recommend that you always remain enable widgets functional on your site so that it won\'t skip any payments.', 'wooflutter')); ?></p>
							</div>
							<div class="wooflutter__row">
								<?php foreach ($widgets as $index => $widget): ?>
								<?php $widget = (object) wp_parse_args($widget, ['title' => '', 'description' => '', 'image' => '', 'active' => false]); ?>
								<div class="wooflutter__single">
									<div class="wooflutter__card">
										<div class="wooflutter__head">
											<div class="wooflutter__left"></div>
											<div class="wooflutter__right">
												<div class="wooflutter__swatch">
													<label>
														<input type="checkbox" role="switch" class="wooflutter__checkbox" name="wooflutter_widgets[]" value="<?php echo esc_attr($widget->ID); ?>" id="switcher-<?php echo esc_attr($widget->ID); ?>" <?php echo esc_attr(($widget->active)?'checked':''); ?>>
														<span class="state">
															<span class="toggle-container">
																<span class="position"></span>
															</span>
															<span class="on" aria-hidden="true">On</span>
															<span class="off" aria-hidden="true">Off</span>
														</span>
													</label>
												</div>
											</div>
										</div>
										<div class="wooflutter__body">
											<div class="wooflutter__image">
												<img src="<?php echo esc_attr($widget->image); ?>" alt="<?php echo esc_attr($widget->ID); ?>" class="wooflutter__img">
											</div>
											<div class="wooflutter__title">
												<span><?php echo esc_html($widget->title); ?></span>
											</div>
											<div class="wooflutter__details">
												<span><?php echo esc_html($widget->description); ?></span>
											</div>
										</div>
										<!-- <div class="wooflutter__foot"></div> -->
									</div>
								</div>
								<?php endforeach; ?>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php 
	}
	public function update_widget() {
        $widget = isset($_POST['widget'])?$_POST['widget']:false;
		if ($widget) {
			$widgets = (array) get_option('wooflutter-widgets', []);
			switch ($_POST['state']) {
				case 'true':
					if (!in_array($widget, $widgets)) {
						$widgets[] = $widget;
					}
					break;
				default:
					if (in_array($widget, $widgets)) {
						$index = array_search($widget, $widgets);
						unset($widgets[$index]);
					}
					break;
			}
			$updated = update_option('wooflutter-widgets', $widgets);
			if ($updated) {
				wp_send_json_success($widgets);
			}
		}
		wp_send_json_error();
    }
}
