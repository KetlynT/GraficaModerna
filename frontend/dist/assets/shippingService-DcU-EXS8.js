import{c as o,h as s}from"./index-rlvyAQjv.js";/**
 * @license lucide-react v0.555.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const p=[["path",{d:"M21 12a9 9 0 1 1-6.219-8.56",key:"13zald"}]],d=o("loader-circle",p);/**
 * @license lucide-react v0.555.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const r=[["path",{d:"M5 12h14",key:"1ays0h"}],["path",{d:"M12 5v14",key:"s699le"}]],l=o("plus",r),u={calculate:async(a,e)=>{const t={destinationCep:a,items:e.map(c=>({productId:c.productId,quantity:c.quantity}))};return(await s.post("/shipping/calculate",t)).data},calculateForProduct:async(a,e)=>{const t=e.replace(/\D/g,"");return(await s.get(`/shipping/product/${a}/${t}`)).data}};export{d as L,l as P,u as S};
