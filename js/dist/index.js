var PronamicPayWooCommerce;(()=>{"use strict";var e={d:(t,n)=>{for(var o in n)e.o(n,o)&&!e.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:n[o]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};e.r(t),e.d(t,{registerMethod:()=>m});const n=window.React,o=window.wc.wcBlocksRegistry,r=window.wc.wcSettings,i=window.wp.htmlEntities,a=window.wp.element,l=window.wp.components,c=e=>e.text,d=({description:e,fields:t,eventRegistration:o})=>{const{onPaymentProcessing:r}=o,[i,c]=(0,a.useState)();return(0,a.useEffect)((()=>r((function(){return{type:"success",meta:{paymentMethodData:i}}}))),[r,i]),(0,n.createElement)(n.Fragment,null,(""!==e||t.length>0)&&(0,n.createElement)("div",null,""!==e&&(0,n.createElement)("div",{dangerouslySetInnerHTML:{__html:e}}),t.map((e=>(0,n.createElement)("div",{key:e.id},(0,n.createElement)(l.BaseControl,{id:e.id,label:e.label},function(e){if(void 0===i||!i.hasOwnProperty(e.id)){let t;if(e.hasOwnProperty("options")&&e.options.length>0){let[n]=e.options;t=n.value}c((n=>({...n,[e.id]:t})))}switch(e.type){case"select":return(0,n.createElement)(l.SelectControl,{id:e.id,options:e.options,onChange:t=>c((n=>({...n,[e.id]:t})))});case"date":return(0,n.createElement)(l.TextControl,{id:e.id,name:e.id,type:"date",onChange:t=>c((n=>({...n,[e.id]:t})))})}}(e),function(e){if(e.error)return(0,n.createElement)("div",null,e.error)}(e)))))))},s=e=>{const{PaymentMethodLabel:t}=e.components;return(0,n.createElement)(n.Fragment,null,""!==e.icon&&(0,n.createElement)(n.Fragment,null,(0,n.createElement)("img",{src:e.icon})," "),""!==e.title&&(0,n.createElement)(t,{text:(0,i.decodeEntities)(e.title)}))};function m(e){const t=(0,r.getSetting)(e+"_data",!1);if(!1===t)return;const a=t.title||"",l=t.description||"";(0,o.registerPaymentMethod)({name:e,label:(0,n.createElement)(s,{title:a,icon:t.icon}),ariaLabel:(0,i.decodeEntities)(a),content:(0,n.createElement)(d,{description:l,fields:t.fields}),edit:(0,n.createElement)(c,{text:l}),placeOrderButtonLabel:t.orderButtonLabel||"",supports:{features:t?.supports||["products"]},canMakePayment:()=>!0})}PronamicPayWooCommerce=t})();