/**
 * Frontend Script.
 * 
 * @package WooFlutter
 */

(function ($) {
	class WooFlutter_Frontend {
		constructor() {
			this.config = fwpSiteConfig;
			var i18n = fwpSiteConfig?.i18n??{};
			this.ajaxUrl = fwpSiteConfig?.ajaxUrl??'';
			this.ajaxNonce = fwpSiteConfig?.ajax_nonce??'';
			this.i18n = {confirming: 'Confirming', ...i18n};
			this.setup_hooks();
		}
		setup_hooks() {
			window.WooFlutter = this;
			this.setup_events();
		}
		setup_events() {
			const thisClass = this;
			// document.body.addEventListener('load-overalies', (event) => {});
		}

	}
	new WooFlutter_Frontend();
})(jQuery);
