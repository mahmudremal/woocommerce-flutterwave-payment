/**
 * Frontend Script.
 * 
 * @package WooFlutter
 */
// import * as React from "react";
// import { render } from "react-dom";
import Post from "../modules/post";
import Form from "./split";
import Utilities from "../modules/utilities";

(function ($) {
	class PaymentManageFlutterwave extends Utilities {
		constructor() {
			super(this);
			this.setup_hooks();
		}
		setup_hooks() {
			window.DokanFlutter = this;
			this.load_split_blocks();
			this.fetch_bank_branches();
			this.fix_payment_edit_links();
		}
		fix_payment_edit_links() {
			document.querySelectorAll('.general-details .dokan-w5 a.dokan-btn[data-method]').forEach(anchor => {
				anchor.href = `${anchor.href.slice(0, -1)}-manage-${anchor.dataset.method}`;
			});
		}
		fetch_bank_branches() {
			const thisClass = this;
			document.querySelectorAll('select#account_bank').forEach(select => {
				select.addEventListener('change', (event) => {
					if (event.target.value && event.target.options[event.target.selectedIndex] && event.target.options[event.target.selectedIndex].dataset?.bank_id) {
						var formdata = new FormData();
						formdata.append('action', 'wooflutter/ajax/bank/branches');
						formdata.append('bank_id', event.target.options[event.target.selectedIndex].dataset.bank_id);
						formdata.append('_nonce', thisClass.ajaxNonce);
						thisClass.Post.sendToServer(formdata, thisClass).then(data => {
							console.log('Branches', data)
						});
					}
				});
			});
		}
		load_split_blocks() {
			document.querySelectorAll('#split_root').forEach(rootElement => {
				const stored = Object.values(JSON.parse(rootElement.dataset.splits));
				wp.element.render(<Form stored={stored}/>, rootElement);
			});
		}

	}
	new PaymentManageFlutterwave();
})(jQuery);
