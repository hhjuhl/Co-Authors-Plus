!function(){"use strict";var e,t={363:function(){var e=window.wp.blocks,t=window.wp.element,n=window.wp.i18n,r=window.wp.blockEditor,o=window.wp.components,a=JSON.parse('{"u2":"cap/coauthor-avatar"}');(0,e.registerBlockType)(a.u2,{edit:function(e){let{context:a,attributes:i,setAttributes:l}=e;const{avatar_urls:c,link:u}=a,{isLink:s,size:f}=i;if(!c)return null;const p=Object.keys(c).map((e=>({value:e,label:`${e} x ${e}`}))),v=(0,t.createElement)("img",{width:f,height:f,src:`${c[f]}`});return(0,t.createElement)(t.Fragment,null,(0,t.createElement)("div",(0,r.useBlockProps)(),s?(0,t.createElement)("a",{href:u,onClick:e=>e.preventDefault()},v):v),(0,t.createElement)(r.InspectorControls,null,(0,t.createElement)(o.PanelBody,{title:(0,n.__)("Avatar Settings")},(0,t.createElement)(o.ToggleControl,{label:(0,n.__)("Make avatar a link to author archive."),onChange:()=>l({isLink:!s}),checked:s}),(0,t.createElement)(o.SelectControl,{label:(0,n.__)("Avatar size"),value:f,options:p,onChange:e=>{l({size:Number(e)})}}))))}})}},n={};function r(e){var o=n[e];if(void 0!==o)return o.exports;var a=n[e]={exports:{}};return t[e](a,a.exports,r),a.exports}r.m=t,e=[],r.O=function(t,n,o,a){if(!n){var i=1/0;for(s=0;s<e.length;s++){n=e[s][0],o=e[s][1],a=e[s][2];for(var l=!0,c=0;c<n.length;c++)(!1&a||i>=a)&&Object.keys(r.O).every((function(e){return r.O[e](n[c])}))?n.splice(c--,1):(l=!1,a<i&&(i=a));if(l){e.splice(s--,1);var u=o();void 0!==u&&(t=u)}}return t}a=a||0;for(var s=e.length;s>0&&e[s-1][2]>a;s--)e[s]=e[s-1];e[s]=[n,o,a]},r.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){var e={826:0,431:0};r.O.j=function(t){return 0===e[t]};var t=function(t,n){var o,a,i=n[0],l=n[1],c=n[2],u=0;if(i.some((function(t){return 0!==e[t]}))){for(o in l)r.o(l,o)&&(r.m[o]=l[o]);if(c)var s=c(r)}for(t&&t(n);u<i.length;u++)a=i[u],r.o(e,a)&&e[a]&&e[a][0](),e[a]=0;return r.O(s)},n=self.webpackChunk=self.webpackChunk||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))}();var o=r.O(void 0,[431],(function(){return r(363)}));o=r.O(o)}();