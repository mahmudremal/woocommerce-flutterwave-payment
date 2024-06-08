/**
 * Utilities Scripts.
 */
import Swal from "sweetalert2";
import tippy from 'tippy.js';
import Post from "./post";


class Utilities {
    constructor(config) {
        this.config = config;
        this.setup_necessery_utilities();
    }
    setup_necessery_utilities() {
        this.ajaxUrl = this.config?.ajaxUrl??'';
        this.ajaxNonce = this.config?.ajax_nonce??'';
        this.lastAjax = false;this.tippy = tippy;
        var i18n = this.config?.i18n??{};this.noToast = true;
        this.i18n = {confirming: 'Confirming', ...i18n};
        this.post = new Post(this);
        this.init_toast_n_notifications();
        this.init_i18n_language_pack();
    }
    init_toast_n_notifications() {
        const thisClass = this;
        this.toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer )
                toast.addEventListener('mouseleave', Swal.resumeTimer )
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
    }
    init_i18n_language_pack() {
        var formdata = new FormData();
        formdata.append('action', 'futurewordpress/project/ajax/i18n/js');
        formdata.append('_nonce', this.ajaxNonce);
        this.post.sendToServer(formdata, this)
        .then(response => {
            this.i18n = {...this.i18n, ...response};
        })
        .catch(err => {console.error("Error:", err);});
    }
}
export default Utilities;