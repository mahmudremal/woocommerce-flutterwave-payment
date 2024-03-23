/**
 * Frontend Script.
 * 
 * @package WooFlutter
 */
import Swal from "sweetalert2";
import Toastify from 'toastify-js';


(function ($) {
	class FutureWordPress_Frontend {
		constructor() {
			this.config = fwpSiteConfig;
			var i18n = fwpSiteConfig?.i18n??{};
			this.ajaxUrl = fwpSiteConfig?.ajaxUrl??'';
			this.ajaxNonce = fwpSiteConfig?.ajax_nonce??'';
			this.i18n = {confirming: 'Confirming', ...i18n};
			this.setup_hooks();
		}
		setup_hooks() {
			window.thisClass = this;
			this.setup_events();
			this.order_terms();
			this.ovaraly_texts();
		}
		setup_events() {
			const thisClass = this;
			document.body.addEventListener('load-overalies', (event) => {
				thisClass.ovaraly_rows = thisClass.lastJson.overalies;
				thisClass.overalies.forEach(wrapRow => {
					if (wrapRow.type == 'attachment') {
						var resRow = thisClass.ovaraly_rows.find(row => row.post_id == wrapRow.post_id && row.image_id == wrapRow.image_id);
					} else {
						var resRow = thisClass.ovaraly_rows.find(row => row.post_id == wrapRow.post_id);
					}
					
					// console.log(resRow, wrapRow);
					if (resRow && resRow?.text) {
						if (resRow.type == 'attachment') {
							wrapRow.element = wrapRow.element.parentElement.parentElement;
						}
						wrapRow.element.dataset.overlayContent = wrapRow.text = resRow.text;
						if (resRow.text?.length <= 10) {wrapRow.element.dataset.overlayPosition = 'bottom';}
					}
				});
			});
		}
		init_toast() {
			const thisClass = this;
			this.toast = Swal.mixin({
				toast: true,
				position: 'top-end',
				showConfirmButton: false,
				timer: 3500,
				timerProgressBar: true,
				didOpen: (toast) => {
					toast.addEventListener('mouseenter', Swal.stopTimer)
					toast.addEventListener('mouseleave', Swal.resumeTimer)
				}
			});
			this.notify = Swal.mixin({
				toast: true,
				position: 'bottom-start',
				showConfirmButton: false,
				timer: 6000,
				willOpen: (toast) => {
				  // Offset the toast message based on the admin menu size
				  var dir = 'rtl' === document.dir ? 'right' : 'left'
				  toast.parentElement.style[dir] = document.getElementById('adminmenu')?.offsetWidth + 'px'??'30px'
				}
			});
			this.toastify = Toastify;
		}
		sendToServer(data) {
			const thisClass = this;var message;
			$.ajax({
				url: thisClass.ajaxUrl,
				type: "POST",
				data: data,    
				cache: false,
				contentType: false,
				processData: false,
				success: function(json) {
					thisClass.lastJson = json.data;
					if ((json?.data??false)) {
						var message = ((json?.data??false)&&typeof json.data==='string')?json.data:(
							(typeof json.data.message==='string')?json.data.message:false
						);
						if (message) {
							// thisClass.toast.fire({icon: (json.success)?'success':'error', title: message})
							thisClass.toastify({text: message,className: "info", duration: 3000, stopOnFocus: true, style: {background: (json.success)?'linear-gradient(to right, rgb(255 197 47), rgb(251 229 174))':'linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))'}}).showToast();
						}
						if (json.data.hooks) {
							json.data.hooks.forEach((hook) => {
								document.body.dispatchEvent(new Event(hook));
							});
						}
					}
				},
				error: function(err) {
					// thisClass.notify.fire({icon: 'warning',title: err.responseText})
					err.responseText = (err.responseText && err.responseText != '')?err.responseText:thisClass.i18n?.somethingwentwrong??'Something went wrong!';
					thisClass.toastify({text: err.responseText,className: "info",style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"}}).showToast();
					// console.log(err);
				}
			});
		}
		order_terms() {
			if (window?.terms_orders) {
				document.querySelectorAll('.elementor-portfolio__filters').forEach(filters => {
					filters.querySelectorAll('.elementor-portfolio__filter:not([data-filter="__all"])').forEach(item => {
						var term = window.terms_orders.find(row => row.term_id == parseInt(item.dataset.filter));
						if (term) {
							// term.element = item;
							item.style.order = term.order_by
						}
					});
					filters.classList.add('elementor-portfolio__sorted');
				});
			}
		}
		ovaraly_texts() {
			const thisClass = this;this.overalies = [];
			// const ovaraly_texts = Object.values(window?.ovaraly_texts??{});
			const blocks = {
				attachment: 'img.attachment-full.size-full[class*="wp-image-"]',
				portfolio: '.elementor-portfolio-item.elementor-post',
			};
			Object.keys(blocks).forEach(key => {
				document.querySelectorAll(blocks[key]).forEach(article => {
					var post_id = thisClass.getPostId(
						article.className,
						(key == 'portfolio')
					);
					var element = (key == 'attachment')?article:article.querySelector('.elementor-portfolio-item__overlay')
					if (post_id && element) {
						const args = {
							post_id: post_id,
							element: element,
							type: key
						};
						if (key == 'attachment') {
							args.post_id = thisClass.config.post_id;
							args.image_id = post_id;

							if (
								thisClass.overalies.find(row => row.post_id == args.post_id && row.image_id == args.image_id)
							) {return;}
						}
						thisClass.overalies.push(args);
					}
				});
			});
			// 
			if (thisClass.overalies.length >= 1) {
				var formdata = new FormData();
				formdata.append('action', 'ctto/ajax/post/content');
				formdata.append('_posts', JSON.stringify(
					thisClass.overalies.map(row => {
						return {
							...row,
							element: false
						};
					})
				));
				formdata.append('_nonce', thisClass.ajaxNonce);
				thisClass.sendToServer(formdata);
			}
		}
		getPostId(className, isPost = true) {
			const regex = (isPost)?/post-(\d+)/:/wp-image-(\d+)/;
			const match = className.match(regex);
			if (match) {return match[1];}
			return null;
		}

	}
	new FutureWordPress_Frontend();
})(jQuery);
