/**
 * Server Communication & Requets Protocol.
 */

class Post {
    constructor(thisClass) {
      this.setup_hooks(thisClass);
    }
    setup_hooks(thisClass) {
      this.eventElements = [];
    }
    /**
     * @param {FormData} data arguments that should be transsferred to server.
     * @param {MasterClass} thisClass to handle root scripts
     * @returns json || Error
     */
    sendToServer(data, thisClass, args = {}) {
      const PostClass = this;const $ = jQuery;
      args = Object.assign({}, {
        data: data,
        cache: false,
        type: "POST",
        dataType: 'json',
        contentType: false,
        processData: false,
        url: thisClass.ajaxUrl,
      }, {...args});
      // PostClass.eventElements = [];
      return new Promise((resolve, reject) => {
        // console.log('Before');
        if (args.eventStream && !!window.EventSource) {
          var source = new EventSource(args.url);
  
          source.addEventListener('message', (event) => {
            // console.log(event, event.data);
            PostClass.do('event-message', event);
            if (event.data) {
              try {
                event.json = JSON.parse(event.data.trim());
                if (event.json?.hook && event.json.hook.includes('close_event')) {
                  PostClass.do('event-finish', event);
                  source.close();resolve(event.json);
                }
                if (event.json?.progress) {
                  event.percentComplete = event.json.progress;
                  PostClass.do('event-progress', event);
                }
              } catch (error) {
                console.error(error);
              }
            }
          }, false);
            
          source.addEventListener('open', event => PostClass.do('event-open', event), false);
          source.addEventListener('join', event => PostClass.do('event-join', event));
          source.addEventListener('leave', event => PostClass.do('event-leave', event));
          source.addEventListener("notice", event => PostClass.do('event-notice', event));
          source.addEventListener("update", event => PostClass.do('event-update', event));
  
          source.addEventListener('error', (event) => {
            PostClass.do('event-error', event);
            if (event.readyState == EventSource.CLOSED) {
              // console.log('Connection was closed.', event);
              source.close();reject(event);
            } else if (event.readyState == EventSource.CONNECTING) {
              // console.log('Reconnecting...', event);
            } else {
            }
          }, false);
        } else {
          // Result to xhr polling :(
            // https://api.jquery.com/jQuery.ajax/
          $.ajax({
            xhr: function() {
              // console.log('xhr');
              var xhr = $.ajaxSettings.xhr();
              // Upload progress
              xhr.upload.onprogress = (event) => {
                // console.log('upload.onprogress', event);
                if (event.lengthComputable) {event.percentComplete = (event.loaded / event.total) * 100;}
                PostClass.do('upload-progress', event);
              };
              // Download progress
              xhr.onprogress = (event) => {
                // console.log('onprogress', event);
                if (event.lengthComputable) {event.percentComplete = (event.loaded / event.total) * 100;}
                PostClass.do('progress', event);
              };
              return xhr;
            },
            success: (json, status, jqXHR) => {
              // console.log('Success', json);
              thisClass.lastJson = json?.data??json;
              // Handle success logic (toasts, hooks, etc.)
              const message = PostClass.extractMessage(json, thisClass);
              if (message) {
                if (thisClass?.toastify) {
                  thisClass.toastify({
                    text: message,
                    duration: 3000,
                    className: "info",
                    stopOnFocus: true,
                    style: {
                      background: json.success
                        ? 'linear-gradient(to right, rgb(255 197 47), rgb(251 229 174))'
                        : 'linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))',
                    },
                  }).showToast();
                }
              }
              if (json.data?.hooks) {
                json.data.hooks.forEach((hook) => {
                  if (hook == 'event_registered') {
                    PostClass.sendToServer(data, thisClass, {
                      eventStream: true,
                      url: `${thisClass.config?.site_url??location.origin}wp-json/sospopsproject/event/stream/run`
                    });
                  } else {
                    document.body.dispatchEvent(new Event(hook));
                  }
                  
                });
              }
              PostClass.do('success', {json: json, status: status, jqXHR: jqXHR});
              resolve(json.data); // Resolve with the parsed data
            },
            error: (jqXHR, status, err) => {
              console.error('Error', err);
              const errorText = PostClass.formatErrorText(err, thisClass); // Refactored for clarity
              PostClass.do('error', err);
              if (thisClass?.toastify) {
                thisClass.toastify({
                  text: errorText, className: "info",
                  style: {background: "linear-gradient(to right, rgb(222 66 75), rgb(249 144 150))"},
                }).showToast();
              }
              reject(err); // Reject with the error
            },
            beforeSend: (xhr) => {
              // Effective for event-stream.
              // xhr.setRequestHeader("Range", "bytes=" + lastPosition + "-");
              // console.log('before send');
              // Show the progress bar.
              PostClass.do('beforesend', {});
            },
            complete: () => {
              // console.log('complete');
              // Hide the progress bar.
              PostClass.do('complete', {});
            },
            xhrFields: {
              // Getting on progress streaming response
              onprogress: (event) => {
                // console.log('onprogress', event);
                if (event.lengthComputable) {event.percentComplete = (event.loaded / event.total) * 100;}
                PostClass.do('progress', event);
              }
            },
            ...args
          }).done(data => {
            PostClass.do('done', data);
            PostClass.do('success', data);
            resolve(data);
          }).fail(error => {
            PostClass.do('fail', error);
            PostClass.do('error', error);
            reject(error);
          }).always((data = {}) => {
            PostClass.do('done', data);
            PostClass.do('success', data);
          });
        }
        
      });
    }
    extractMessage(json, thisClass) {
      return ((json?.data??false)&&typeof json.data==='string')?json.data:(
        (typeof json.data?.message==='string')?json.data.message:false
      );
    }
    formatErrorText(err, thisClass) {
      if (err.responseText && err.responseText !== '') {
        return err.responseText; // Prioritize response text if available
      }
      return thisClass.i18n?.somethingwentwrong??'Something went wrong!'; // Default fallback message
    }
    on(hooks, element, callback) {
      if (element === false) {
        element = document.createElement('div');
      }
      if (typeof element === "string") {
        element = document.querySelector(element);
      }
      if (!element) {
        console.warn(`Element not found for hook "${hook}"`);
        return;
      }
      if (typeof hooks !== 'object') {
        hooks = [hooks];
      }
      hooks.forEach(hook => {
        this.eventElements.push({hook: hook, element: element, callback: callback});
        element.addEventListener(hook, callback);
      });
      
    }
    off(hook, element, callback) {
      const index = this.eventElements.findIndex(
        (row) => row.hook === hook && row.element === element
      );
      if (index !== -1) {
        this.eventElements.splice(index, 1); // Remove the matching row
      } else {
        console.warn('No hook found with hook "' + hook + '" and element', element);
      }
    }
    do(hook, event = {}) {
      // if (!(event instanceof Object)) {event = {...event};}
      this.eventElements.filter(row => row.hook == hook).forEach(row => row.element.dispatchEvent(new CustomEvent(hook, {
        detail: {
          event: event,
        },
      })));
    }
    event(event) {
      return (event?.detail && event.detail?.event)?event.detail.event:event;
    }
  
    example() {
      // PostClass.sendToServer(formdata, thisClass)
      // .then((response) => {console.log("Success:", response);})
      // .catch((err) => {console.error("Error:", err);});

        // var formdata = new FormData();
        // formdata.append('action', 'sospopsproject/ajax/import/clean');
        // formdata.append('taxonomy', button.dataset.taxonomy);
        // formdata.append('clean', button.dataset.target);
        // formdata.append('_nonce', thisClass.ajaxNonce);
        // thisClass.Post.sendToServer(formdata, thisClass, {
        //     // eventStream: true, url: thisClass.ajaxUrl+`?action=sospopsproject/ajax/import/clean&clean=${button.dataset.target}&taxonomy=${button.dataset.taxonomy}`
        // });
    }
    
  }
  export default Post;