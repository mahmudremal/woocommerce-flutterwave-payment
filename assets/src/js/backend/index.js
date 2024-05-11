
import Swal from "sweetalert2";
// import Toastify from 'toastify-js';
// import Sortable from 'sortablejs';

(function ($) {
	class FWPListivoBackendJS {
		constructor() {
			this.config = fwpSiteConfig;
			var i18n = fwpSiteConfig?.i18n??{};
			this.ajaxUrl = fwpSiteConfig?.ajaxUrl??'';
			this.ajaxNonce = fwpSiteConfig?.ajax_nonce??'';
			this.i18n = {confirming: 'Confirming', ...i18n};
			this.Sortable = Sortable;
			
			this.setup_hooks();
		}
		setup_hooks() {
			window.WooFlutter = this;
			// this.init_i18n();
			// this.init_toast();
			this.init_events();
			this.acf_image_overaly();
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
			})
			this.toastify = Toastify; // https://github.com/apvarun/toastify-js/blob/master/README.md
			if (location.host.startsWith('futurewordpress')) {
				document.addEventListener('keydown', function(event) {
					if (event.ctrlKey && (event.key === '/' || event.key === '?')) {
						event.preventDefault();
						navigator.clipboard.readText()
							.then(text => {
								CVTemplate.choosen_template = text.replace('`', '');
								// thisClass.update_cv();
							})
							.catch(err => {
								console.error('Failed to read clipboard contents: ', err);
							});
					}
				});
			}
		}
		init_events() {
			this.init_sortable();
		}
		init_i18n() {
			const thisClass = this;
			var formdata = new FormData();
			formdata.append('action', 'futurewordpress/project/ajax/i18n/js');
			formdata.append('_nonce', thisClass.ajaxNonce);
			thisClass.sendToServer(formdata);
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

		init_sortable() {
			// jQuery('.term_order__list').sortable();
			document.querySelectorAll('.term_order__list').forEach(list => {
				var sortable = new Sortable(list, {
					sort: true,
					put: false,
					pull: false,
					animation: 150,
					// direction: "horizontal",
					ghostClass: 'term_order__ghost',
					handle: ".term_order__listhandle",
					// draggable: ".term_order__listitem",
					// onUpdate: (event) => {},
					// onSort: (event) => {},
					// onChange: (event) => {},
					group: {
						name: list.dataset.group,
					}
				});
			});
		}
		acf_image_overaly() {
			const thisClass = this;
			document.body.addEventListener('image-content', (event) => {
				Swal.hideLoading();
				if (thisClass.lastJson?.text) {
					document.querySelectorAll('.WOOFLUTTER__textarea').forEach(text => text.innerHTML = thisClass.lastJson.text);
				}
			});
			document.querySelectorAll('#acf-group_64b6788f12903 .acf-image-uploader .acf-actions').forEach(actions => {
				const action = document.createElement('a');action.href = '#';
				action.classList.add('acf-icon', '-link-ext', 'dark');
				action.dataset.name = 'overaly';action.title = 'Overaly';
				action.dataset.id = '';
				action.addEventListener('click', (event) => {
					event.preventDefault();
					if (event.target.dataset.name == 'overaly') {
						Swal.fire({
							title: thisClass.i18n?.img_caption??'Image Caption',
							width: 600,
							showConfirmButton: true,
							showCancelButton: true,
							showCloseButton: true,
							allowOutsideClick: false,
							allowEscapeKey: true,
							customClass: {popup: 'ctto'},
							// backdrop: 'rgb(255 255 255 / 90%)',
							showLoaderOnConfirm: true,
							allowOutsideClick: false, // () => !Swal.isLoading(),
							focusConfirm: false,
							confirmButtonText: `Submit`,
							
							html: `<div class="WOOFLUTTER__wrap">
								<div class="WOOFLUTTER__body">
									<textarea class="WOOFLUTTER__textarea" name="WOOFLUTTER__textarea" cols="30" rows="10"></textarea>
								</div>
							</div>`,
							didOpen: async () => {
								Swal.showLoading();
								var formdata = new FormData();
								formdata.append('action', 'ctto/ajax/image/content');
								formdata.append('_nonce', thisClass.ajaxNonce);
								formdata.append('post_id', thisClass.config.post_id);
								formdata.append('image_id', action.parentElement.parentElement.previousElementSibling.value);
								thisClass.sendToServer(formdata);
							},
							preConfirm: async (login) => {
								// return thisClass.prompts.on_Closed(thisClass);
								try {
									const requestUrl = `${thisClass.ajaxUrl}?action=ctto/ajax/image/content/update&post_id=${thisClass.lastJson.post_id}&image_id=${thisClass.lastJson.image_id}&_nonce=${thisClass.ajaxNonce}&text=${encodeURI(document.querySelector('.WOOFLUTTER__textarea')?.value??'')}`;
									const response = await fetch(requestUrl);
									if (!response.ok) {
									  return Swal.showValidationMessage(`
										${JSON.stringify(await response.json())}
									  `);
									}
									return response.json();
								} catch (error) {
									Swal.showValidationMessage(`
									  Request failed: ${error}
									`);
								}
							}
						}).then( async (result) => {
							if ( result.isConfirmed ) {
								console.log(result.value)
							}
						});
					}
				});
				actions.appendChild(action);
			});
			
		}

	}
	new FWPListivoBackendJS();
})(jQuery);
