var PronamicPayWooCommerce;!function(){"use strict";var e={d:function(t,r){for(var n in r)e.o(r,n)&&!e.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:r[n]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r:function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};function r(e){return r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},r(e)}function n(e,t,n){return(t=function(e){var t=function(e,t){if("object"!==r(e)||null===e)return e;var n=e[Symbol.toPrimitive];if(void 0!==n){var o=n.call(e,"string");if("object"!==r(o))return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}(e);return"symbol"===r(t)?t:String(t)}(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function o(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,n=new Array(t);r<t;r++)n[r]=e[r];return n}function i(e,t){return function(e){if(Array.isArray(e))return e}(e)||function(e,t){var r=null==e?null:"undefined"!=typeof Symbol&&e[Symbol.iterator]||e["@@iterator"];if(null!=r){var n,o,_x,i,c=[],_n=!0,a=!1;try{if(_x=(r=r.call(e)).next,0===t){if(Object(r)!==r)return;_n=!1}else for(;!(_n=(n=_x.call(r)).done)&&(c.push(n.value),c.length!==t);_n=!0);}catch(e){a=!0,o=e}finally{try{if(!_n&&null!=r.return&&(i=r.return(),Object(i)!==i))return}finally{if(a)throw o}}return c}}(e,t)||function(e,t){if(e){if("string"==typeof e)return o(e,t);var r=Object.prototype.toString.call(e).slice(8,-1);return"Object"===r&&e.constructor&&(r=e.constructor.name),"Map"===r||"Set"===r?Array.from(e):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?o(e,t):void 0}}(e,t)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}e.r(t),e.d(t,{registerMethod:function(){return b}});var c=window.wp.element,a=window.wc.wcBlocksRegistry,l=window.wc.wcSettings,u=window.wp.htmlEntities,f=window.wp.components;function s(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function m(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?s(Object(r),!0).forEach((function(t){n(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):s(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}var y=function(e){return e.text},d=function(e){var t=e.description,r=e.fields,o=e.eventRegistration.onPaymentProcessing,a=i((0,c.useState)(),2),l=a[0],u=a[1];return(0,c.useEffect)((function(){return o((function(){return{type:"success",meta:{paymentMethodData:l}}}))}),[o,l]),(0,c.createElement)(c.Fragment,null,(""!==t||r.length>0)&&(0,c.createElement)("div",null,""!==t&&(0,c.createElement)("div",{dangerouslySetInnerHTML:{__html:t}}),r.map((function(e){return(0,c.createElement)("div",{key:e.id},(0,c.createElement)(f.BaseControl,{id:e.id,label:e.label},function(e){if(void 0===l||!l.hasOwnProperty(e.id)){var t=void 0;if(e.hasOwnProperty("options")&&e.options.length>0){var r=i(e.options,1)[0];t=r.value}u((function(r){return m(m({},r),{},n({},e.id,t))}))}switch(e.type){case"select":return(0,c.createElement)(f.SelectControl,{id:e.id,options:e.options,onChange:function(t){return u((function(r){return m(m({},r),{},n({},e.id,t))}))}});case"date":return(0,c.createElement)(f.TextControl,{id:e.id,name:e.id,type:"date",onChange:function(t){return u((function(r){return m(m({},r),{},n({},e.id,t))}))}})}}(e),function(e){if(e.error)return(0,c.createElement)("div",null,e.error)}(e)))}))))},p=function(e){var t=e.components.PaymentMethodLabel;return(0,c.createElement)(c.Fragment,null,""!==e.icon&&(0,c.createElement)(c.Fragment,null,(0,c.createElement)("img",{src:e.icon})," "),""!==e.title&&(0,c.createElement)(t,{text:(0,u.decodeEntities)(e.title)}))};function b(e){var t=(0,l.getSetting)(e+"_data",!1);if(!1!==t){var r=t.title||"",n=t.description||"";(0,a.registerPaymentMethod)({name:e,label:(0,c.createElement)(p,{title:r,icon:t.icon}),ariaLabel:(0,u.decodeEntities)(r),content:(0,c.createElement)(d,{description:n,fields:t.fields}),edit:(0,c.createElement)(y,{text:n}),placeOrderButtonLabel:t.orderButtonLabel||"",supports:{features:(null==t?void 0:t.supports)||["products"]},canMakePayment:function(){return!0}})}}PronamicPayWooCommerce=t}();