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
		add_action('wp_ajax_wooflutter/ajax/update/active/tab', [$this, 'update_active_tab']);
		add_action('wp_ajax_wooflutter/ajax/update/settings', [$this, 'update_settings']);
	}
	public function populate_menu() {
		add_submenu_page('tools.php', __('Flutterwave Settings', 'wooflutter'), __('Flutterwave Payment', 'wooflutter'), 'install_plugins', 'wooflutterwave', [$this, 'widgets_lists_output']);
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
		$tabs = [
			'widgets'	=> __('Widgets', 'wooflutter'),
			'settings'	=> __('Settings', 'wooflutter'),
		];
		$activeTab = get_option('wooflutter_tab-active', 'widgets');
		$activeTab = isset($tabs[$activeTab])?$activeTab:array_keys($tabs)[0];
		$widgets = $this->get_widgets_lists();
		// 
		?>
		<div class="wooflutter">
			<div class="wooflutter__widgets">
				<div class="wooflutter__container">
					<div class="wooflutter__wrap">
						<div class="wooflutter__tabarea">
							<div class="wooflutter__tabarea__wrap">
								<input type="hidden" name="wooflutter_tab-active" value="<?php echo esc_attr($activeTab); ?>">
								<?php foreach ($tabs as $tabKey => $tabText) : ?>
								<div class="wooflutter__tab<?php echo esc_attr(($tabKey == $activeTab)?' active':''); ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
                                    <a href="#" class="wooflutter__tab__link"><?php echo esc_html($tabText); ?></a>
                                </div>
								<?php endforeach; ?>
                            </div>
						</div>
						<div class="wooflutter__tabarea__contents">
							<?php foreach ($tabs as $tabKey => $tabText) : ?>
								<div class="wooflutter__tab_single<?php echo esc_attr(($tabKey == $activeTab)?' active':''); ?>" data-tab="<?php echo esc_attr($tabKey); ?>">
									<?php
									switch ($tabKey) {
										case 'widgets':
											?>
											<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wooflutter__form" method="post" name="the<?php echo esc_attr($tabKey); ?>">
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
											<?php
											break;
										case 'settings':
											$settings = (object) wp_parse_args((array) get_option('wooflutter_settings', []), [
												'testmode'				=> false,
												'test_public_key'		=> '',
												'test_secret_key'		=> '',
												'live_public_key'		=> '',
												'live_secret_key'		=> '',
												'live_encript_key'		=> '',
												'custom_logo'			=> ''
											]);
											?>
											<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wooflutter__form" method="post" name="the<?php echo esc_attr($tabKey); ?>">
												<div class="wooflutter__content">
													<h2><?php echo esc_html(__('Flutterwave Settings', 'wooflutter')); ?></h2>
													<p><?php echo esc_html(__('Store your nessery information here including api keys and additional optional informations.', 'wooflutter')); ?></p>
													<div class="wooflutter__formgroup">
														<table class="form-table" role="presentation">
															<tbody>
																<tr>
																	<th>
																		<label for="wooflutter-testmode">Test mode</label>
																	</th>
																	<td>
																		<label for="wooflutter-testmode">
																			<input class="" type="checkbox" name="settings[testmode]" id="wooflutter-testmode" style="" value="1" <?php echo esc_attr($settings->testmode?'checked':''); ?>> Switch on this field to enable Flutterwave payment on woocommerce.
																		</label>
																	</td>
																</tr>
																<tr>
																	<th>
																		<label for="test_public_key">Test Public Key</label>
																	</th>
																	<td>
																		<input name="settings[test_public_key]" id="test_public_key" type="text" value="<?php echo esc_attr($settings->test_public_key); ?>" class="regular-text code">
																	</td>
																</tr>
																<tr>
																	<th>
																		<label for="test_secret_key">Test Secret Key</label>
																	</th>
																	<td>
																		<input name="settings[test_secret_key]" id="test_secret_key" type="text" value="<?php echo esc_attr($settings->test_secret_key); ?>" class="regular-text code">
																	</td>
																</tr>
																<tr>
																	<th>
																		<label for="live_public_key">Live Public Key</label>
																	</th>
																	<td>
																		<input name="settings[live_public_key]" id="live_public_key" type="text" value="<?php echo esc_attr($settings->live_public_key); ?>" class="regular-text code">
																	</td>
																</tr>
																<tr>
																	<th>
																		<label for="live_secret_key">Live Secret Key</label>
																	</th>
																	<td>
																		<input name="settings[live_secret_key]" id="live_secret_key" type="text" value="<?php echo esc_attr($settings->live_secret_key); ?>" class="regular-text code">
																	</td>
																</tr>
																<tr>
																	<th>
																		<label for="live_encript_key">Live Encryption Key</label>
																	</th>
																	<td>
																		<input name="settings[live_encript_key]" id="live_encript_key" type="text" value="<?php echo esc_attr($settings->live_encript_key); ?>" class="regular-text code">
																	</td>
																</tr>
																<tr>
																	<th>
																		<label for="custom_logo">Custom Logo</label>
																	</th>
																	<td>
																		<input name="settings[custom_logo]" id="custom_logo" type="text" value="<?php echo esc_attr($settings->custom_logo); ?>" class="regular-text code">
																	</td>
																</tr>
															</tbody>
														</table>
														<p class="submit">
															<button name="save" class="button-primary" type="submit" value="Save changes">Save changes</button>
														</p>
													</div>
												</div>
												<!--  -->
											</form>
											<?php
											break;
										default:
											# code...
											break;
									}
									?>
								</div>
								<?php endforeach; ?>
						</div>
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
	public function update_active_tab() {
        $activeTab = isset($_POST['active'])?$_POST['active']:false;
		if ($activeTab) {
			$updated = update_option('wooflutter_tab-active', $activeTab);
			if ($updated) {
				wp_send_json_success($activeTab);
			}
		}
		wp_send_json_error();
    }
	public function update_settings() {
        $settings = isset($_POST['settings'])?$_POST['settings']:[];
		if ($settings) {
			$updated = update_option('wooflutter_settings', $settings);
			if ($updated) {
				wp_send_json_success($activeTab);
			}
		}
		wp_send_json_error();
    }
}
