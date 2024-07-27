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
			this.init_settings_screen();
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
		init_settings_screen() {
			const thisClass = this;
			document.querySelectorAll('.wooflutter__tabarea__wrap .wooflutter__tab[data-tab]').forEach(tabBtn => {
				tabBtn.addEventListener('click', (event) => {
					event.preventDefault();
					var tabs = Array.from(tabBtn.parentElement.children).filter(button => button.nodeName === 'DIV');
					var tabContents = Array.from(document.querySelectorAll('.wooflutter__tab_single[data-tab]'));
					tabs.filter(button => button.classList.contains('active')).map(btn => {
						btn.classList.remove('active');
						tabContents.filter(tab => tab.dataset.tab == btn.dataset.tab).map(tab => tab.classList.remove('active'));
					});
					tabBtn.classList.add('active');
					tabContents.filter(tab => tab.dataset.tab == tabBtn.dataset.tab).map(tab => tab.classList.add('active'));
					const data = new FormData();
					var fdt = {_nonce: thisClass.ajaxNonce, action: 'wooflutter/ajax/update/active/tab', active: tabBtn.dataset.tab};
					Object.keys(fdt).forEach(key => data.append(key, fdt[key]));
					thisClass.post.sendToServer(data, thisClass)
					.then(response => {
						// thisClass.toast.fire({icon: 'success', title: 'Active tab updated to server.'});
					})
					.catch(err => {console.error("Error:", err);});
				});
			});
			if (window?.thesettings) {
				window.thesettings.addEventListener('submit', (event) => {
					event.preventDefault();
					const data = new FormData(window.thesettings);
					var fdt = {_nonce: thisClass.ajaxNonce, action: 'wooflutter/ajax/update/settings'};
					Object.keys(fdt).forEach(key => data.append(key, fdt[key]));
					thisClass.post.sendToServer(data, thisClass)
					.then(response => {
						thisClass.toast.fire({icon:'success', title: 'Settings saved successfully'});
					})
					.catch(err => {console.error("Error:", err);});
				});
			}
		}
	}
	new WooFlutterwave();
})(jQuery);
