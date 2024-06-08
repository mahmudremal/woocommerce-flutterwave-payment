/*
 *   This content is licensed according to the W3C Software License at
 *   https://www.w3.org/Consortium/Legal/2015/copyright-software-and-document
 *
 *   File:  switch-checkbox.js
 *
 *   Desc:  Switch widget using input[type=checkbox] that implements ARIA Authoring Practices
 */

'use strict';

class CheckboxSwitch {
  constructor(domNode) {
    this.switchNode = domNode;
    this.switchNode.addEventListener('focus', () => this.onFocus(event));
    this.switchNode.addEventListener('blur', () => this.onBlur(event));
  }

  onFocus(event) {
    event.currentTarget.parentNode.classList.add('focus');
  }

  onBlur(event) {
    event.currentTarget.parentNode.classList.remove('focus');
  }
}
export default CheckboxSwitch;

