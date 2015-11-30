/*    Copyright (c) 2014 Zuora, Inc.
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy of 
 *   this software and associated documentation files (the "Software"), to use copy, 
 *   modify, merge, publish the Software and to distribute, and sublicense copies of 
 *   the Software, provided no fee is charged for the Software.  In addition the
 *   rights specified above are conditioned upon the following:
 *
 *   The above copyright notice and this permission notice shall be included in all
 *   copies or substantial portions of the Software.
 *
 *   Zuora, Inc. or any other trademarks of Zuora, Inc.  may not be used to endorse
 *   or promote products derived from this Software without specific prior written
 *   permission from Zuora, Inc.
 *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *   FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
 *   ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
 *   (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *   LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *   ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *   (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *   SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
var ifrmId="z_hppm_iframe";var Z=function(){var h="#z-overlay {opacity:0.5;display:inline-block;position:fixed;top:0;left:0;width:100%;height:100%;background-color: #000;z-index: 1001;}";var e="#z-container {border:1px;float:left; overflow: visible; position: absolute;padding: 0px; display: inline-block; top:5%; left:34%; margin: 0 auto;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius:5px;background-color: #FAFAFA; border:1px solid #FAFAFA;border-top-color:#EDEDED;behavior: url(js/PIE.htc);z-index: 1002;}";var m="#z-data {height: 100%; outline: 0px; width: 100%; overflow: visible;display: inline-block;border:1px; -webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius:5px;}";var j="#reset{*, *:before, *:after {display: inline-block;-webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box;}}";var g="#z_hppm_iframe {background-color: #FAFAFA;vertical-align:bottom;z-index:9999;display:block;padding:0px;margin: 0px; border:0px solid #DDD;}";var a="requestPage";var k;var c;var l,f,d;var i=["tenantId","id","token","signature","key","style","submitEnabled","url"];var b=["creditCardNumber","cardSecurityCode","creditCardExpirationYear","creditCardExpirationMonth"];return{validateRequiredParams:function(q){var n=i.length;for(index=0;index<n;index++){if(!q.hasOwnProperty(i[index])){if(i[index]=="submitEnabled"&&q.style.toLowerCase()=="overlay"){continue}else{var o="Param with key ["+i[index]+"] is required.";alert(o);if(!Z.isIE()){console.log(o)}return false}}}return true},isIE:function(){var q=window.navigator.userAgent;var o=q.indexOf("MSIE ");var n=q.indexOf("Trident/");if(o>0){return true}if(n>0){var r=q.indexOf("rv:");return true}return false},validatePCIParams:function(r){var n=b.length;for(index=0;index<n;index++){var o="field_"+b[index];if(r.hasOwnProperty(o)){if(0<r[o].trim().length&&r[o].trim().length<300){var q="Field ["+o+"] for Credit Card payment method type should be encrypted for pre-population";alert(q);if(!Z.isIE()){console.log(q)}return false}}}return true
},init:function(s,t){l="?method=requestPage&host="+encodeURIComponent(document.location.href)+"&";var r=Z.validateRequiredParams(s);if(!r){return false}r=Z.validatePCIParams(s);if(!r){return false}var q=JSON.stringify(s,function(u,v){if(u!=""){if("key"==u){d=v}else{if("url"==u){k=v}else{l=l+u+"="+encodeURIComponent(v)+"&"}}}return v});p=JSON.parse(q);ZXD.receiveMessage(function(v){var u=v.data;u=JSON.parse(u);if(u.success){if(t){t(u)}else{Z.responseHandler(u)}}else{if(u.success==false){Z.deactivateOverlay("z-overlay");Z.deactivateOverlay("z-container");t(u)}else{if(u.action=="close"){Z.deactivateOverlay("z-overlay");Z.deactivateOverlay("z-container")}else{if(u.action=="resize"){Z.receive(u)}else{Z.receive(u)}}}}});var n=b.length;if(s){for(index=0;index<n;index++){var o="field_"+b[index];if(s.hasOwnProperty(o)){s[o]=""}}}return true},prepopulate:function(q){var r=Z.createIframeURL();if(r==document.getElementById(ifrmId).src||(document.getElementById(ifrmId).src.indexOf(r)>=0&&p.hasOwnProperty("customizeErrorRequired")&&p.customizeErrorRequired=="true")){var n=JSON.stringify(q,function(s,u){if(s!=""){var t="setField("+s+":"+u+")";Z.post(ifrmId,t)}return u});var o="setField(key:"+d+")";Z.post(ifrmId,o);Z.post(ifrmId,"setField(style:"+p.style+")");if(p.hasOwnProperty("customizeErrorRequired")&&p.customizeErrorRequired=="true"){Z.post(ifrmId,"customizeErrorRequired");p.customizeErrorRequired="false"}Z.post(ifrmId,"resize");if(c){c()}}if(c){c=null}},contains:function(n,q){for(var o=0;o<n.length;o++){if(n[o]===q){return true}}return false},renderWithErrorHandler:function(o,r,q,n){o.customizeErrorRequired="true";Z.render(o,r,q);Z.customizeErrorHandler(n)},runAfterRender:function(n){c=n},render:function(s,r,x){var v=b.length;if(r){for(index=0;index<v;index++){var t="field_"+b[index];if(r.hasOwnProperty(b[index])){s[t]=r[b[index]]}}}var y=Z.init(s,x);if(!y){return}if(r){var v=Object.keys(r).length;f=r;for(index=0;index<v;index++){var q=Object.keys(s)[index];if(Z.contains(b,q)){f[q]=undefined}}}else{f=null}var o=document.getElementById("zuora_payment");
if(typeof o=="undefined"||!o){return{error:"invalid_request",error_description:"The container you specified does not exist"}}Z.cleanUp(o,"z-overlay");Z.cleanUp(o,"z-container");if(p.style=="inline"){Z.addInlineStyles();Z.createIframe(o);return}if(p.style=="overlay"){Z.addOverlayStyles();var n=Z.generateDiv("z-overlay","z-overlay");o.appendChild(n);var u=Z.generateDiv("z-container","z-container");o.appendChild(u);var w=Z.generateDiv("z-data","z-data");w.tabindex="-1";u.appendChild(w);Z.createIframe(document.getElementById("z-data"));Z.activateOverlay("z-overlay")}},cleanUp:function(o,n){var q=document.getElementById(n);if(q!=null){o.removeChild(q)}},activateOverlay:function(o){try{document.getElementById(o).style.display="inline"}catch(n){}},deactivateOverlay:function(o){try{document.getElementById(o).style.display="none"}catch(n){}},generateDiv:function(r,o,n){var q=document.createElement("div");q.id=r;q.className=o;q.border="0";if(q.addEventListener){q.addEventListener("click",n,false)}else{q.attachEvent("click",n)}return q},addOverlayStyles:function(){var s=document.createElement("style");s.type="text/css";var o=document.createTextNode(h);var t=document.createTextNode(e);var n=document.createTextNode(m);var r=document.createTextNode(g);var q=document.createTextNode(j);if(s.styleSheet){s.styleSheet.cssText=o.nodeValue+" "+t.nodeValue+" "+n.nodeValue+" "+q.nodeValue+" "+r.nodeValue}else{s.appendChild(o);s.appendChild(t);s.appendChild(n);s.appendChild(r);s.appendChild(q)}document.getElementsByTagName("head")[0].appendChild(s)},addInlineStyles:function(){var o=document.createElement("style");o.type="text/css";var n=document.createTextNode(g);if(o.styleSheet){o.styleSheet.cssText=n.nodeValue}else{o.appendChild(n)}document.getElementsByTagName("head")[0].appendChild(o)},createIframe:function(n){var q=Z.createIframeURL();var o=document.createElement("iframe");o.setAttribute("src",q);o.setAttribute("id",ifrmId);o.setAttribute("overflow","visible");o.setAttribute("scrolling","no");o.setAttribute("frameBorder","0");o.setAttribute("allowtransparency","true");
o.setAttribute("class","z_hppm_iframe");o.setAttribute("width","100%");o.setAttribute("height","100%");if(o.addEventListener){o.addEventListener("load",function(r){Z.prepopulate(f);return false},false)}else{o.attachEvent("onload",function(){Z.prepopulate(f);return false})}if(typeof options!="undefined"){if(typeof options.vertical!="undefined"&&options.vertical){o.style.width="100%";o.style.height="100%"}}n.appendChild(o)},createIframeURL:function(){var n=k;return n.concat(l)},post:function(q,o){var n=document.getElementById(q);var r=n.src+"#"+encodeURIComponent(document.location.href);n.src=r;ZXD.postMessage(o,r,n.contentWindow);return false},receive:function(n){ZFB.resizeCaller(ifrmId,n.action,n.height,n.width)},validate:function(q){if(q==null||q==undefined){Z.closeWindow();var o="Validate function required.";alert(o);if(!Z.isIE()){console.log(o)}return false}ZXD.receiveMessage(function(s){var r=s.data;r=JSON.parse(r);q(r)});var n="validate";Z.post(ifrmId,n)},customizeErrorHandler:function(o){if(o==null||o==undefined){Z.closeWindow();var n="Customized error message function required.";alert(n);if(!Z.isIE()){console.log(n)}return false}ZXD.receiveMessage(function(r){var q=r.data;q=JSON.parse(q);if(q.action=="customizeErrorMessage"){o(q.key,q.code,q.message)}})},sendErrorMessageToHpm:function(r,q){var o={action:"customizeErrorMessage",key:r,message:q};var n=JSON.stringify(o);Z.post(ifrmId,n)},closeWindow:function(){Z.deactivateOverlay("z-overlay");Z.deactivateOverlay("z-container")},submit:function(){var n=document.getElementById(ifrmId).src+"#"+encodeURIComponent(document.location.href);document.getElementById(ifrmId).src=n;ZXD.postMessage("postPage",n,document.getElementById(ifrmId).contentWindow);return true},responseHandler:function(n){var o=n.redirectUrl;if(n.success){var q=o+"?refId="+n.refId+"&success="+n.success+"&signature="+n.signature+"&token="+n.token;window.location.replace(q)}else{var q=o+"?errorCode="+n.errorCode+"&errorMessage="+n.errorMessage+"&success="+n.success+"&signature="+n.signature+"&token="+n.token;
window.location.replace(q)}}}}();var ZXD=function(){var e,d,b=1,c,a=this;return{postMessage:function(f,h,g){if(!h){return}g=g||parent;if(a.postMessage){g.postMessage(f,h.replace(/([^:]+:\/\/[^\/]+).*/,"$1"))}else{if(h){g.location=h.replace(/#.*$/,"")+"#"+(+new Date)+(b++)+"&"+f}}},receiveMessage:function(h,g,f){if(a.postMessage){if(h){c=function(l){if(Object.prototype.toString.call(g)==="[object Function]"&&g(l.origin)===!1){return !1}if(typeof g==="string"&&l.origin!==g){if(!f){return !1}else{if(f==="true"){try{if(typeof g==="string"){var i=l.origin.split(".");if(i){var n=i.slice(-2).join(".");var j=g.split(".");var m=j.slice(-2).join(".");if(m.indexOf(n)<=-1){return !1}}}}catch(k){return !1}}else{return !1}}}h(l)}}if(a.addEventListener){a[h?"addEventListener":"removeEventListener"]("message",c,!1)}else{a[h?"attachEvent":"detachEvent"]("onmessage",c)}}else{e&&clearInterval(e);e=null;if(h){e=setInterval(function(){var j=document.location.hash,i=/^#?\d+&/;if(j!==d&&i.test(j)){d=j;h({data:j.replace(i,"")})}},100)}}}}}();var ZFB=function(){var b="yes";var c=navigator.userAgent.substring(navigator.userAgent.indexOf("Firefox")).split("/")[1];var a=parseFloat(c)>=0.1?20:0;return{resizeCaller:function(h,g,d,f){ZFB.resizeIframe(h,g,d,f);if((document.all||document.getElementById)&&b=="no"){var e=document.all?document.all[h]:document.getElementById(h);e.style.display="block"}},resizeIframe:function(f,h,e,g){var d=document.getElementById(f);if(d){d.style.display="block";d.height=Number(e);d.width=Number(g)}}}}();