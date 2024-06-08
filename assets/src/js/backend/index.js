import CheckboxSwitch from "../modules/checkbox";
import axios from 'axios';
import Utilities from "../modules/utilities";

(function ($) {
	class WooFlutterwave extends Utilities {
		constructor() {
			super(window?.fwpSiteConfig);
			this.setup_hooks();
			window.thisClass = this;
		}
		setup_hooks() {
			this.init_widgets_grid();
		}
		init_widgets_grid() {
			const thisClass = this;
			Array.from(document.querySelectorAll('input[type=checkbox][role^=switch]')).forEach((element) => new CheckboxSwitch(element));
			document.querySelectorAll('.wooflutter__checkbox').forEach(checkbox => {
				checkbox.addEventListener('change', event => {
					const data = new FormData();
					var fdt = {_nonce: thisClass.ajaxNonce, action: 'wooflutter/ajax/update/widget', widget: event.target.value, state: event.target.checked};
					Object.keys(fdt).forEach(key => {
						data.append(key, fdt[key]);
					});
					thisClass.post.sendToServer(data, thisClass)
					.then(response => {
						thisClass.toast.fire({icon: 'success', title: 'Changes saved successfully'})
					})
					.catch(err => {console.error("Error:", err);});
				});
			});
		}
	}
	new WooFlutterwave();
})(jQuery);
