"use strict";(self.webpackChunkscintello_super_admin=self.webpackChunkscintello_super_admin||[]).push([[50],{41646:function(e,t,n){var r,i,a=n(30168),o=(n(72791),n(89690)),s=n(80184);t.Z=function(){var e=(0,o.keyframes)(r||(r=(0,a.Z)(["\n    from {\n    transform: rotate(0deg);\n    }\n    to {\n    transform: rotate(360deg);\n    }\n    "]))),t=o.default.div(i||(i=(0,a.Z)(["\n    margin: 16px;\n    animation: "," 1s linear infinite;\n    transform: translateZ(0);\n    border-top: 2px solid #1D718B ;\n    border-right: 2px solid #1D718B ;\n    border-bottom: 2px solid #1D718B ;\n    border-left: 4px solid linear-gradient(to right, #8360c3, #2ebf91);\n    background: transparent;\n    width: 100px;\n    height: 100px;\n    border-radius: 50%;\n  "])),e);return(0,s.jsxs)("div",{style:{padding:"24px"},children:[(0,s.jsx)(t,{}),(0,s.jsx)("div",{style:{marginLeft:"20px",color:"#1D718B "},children:(0,s.jsx)("b",{children:"Loading..."})})]})}},86050:function(e,t,n){n.r(t);var r,i=n(30168),a=n(29439),o=n(72791),s=n(78983),d=n(43513),l=n(31243),c=n(11087),u=n(89690),f=n(21830),p=n.n(f),h=n(23853),m=n(41646),x=n(58617),g=n(80184);t.default=function(){var e=(0,o.useState)([]),t=(0,a.Z)(e,2),n=t[0],f=t[1],b=(0,o.useState)([]),j=(0,a.Z)(b,2),w=(j[0],j[1]),v=(0,o.useState)(!0),y=(0,a.Z)(v,2),C=y[0],L=y[1],N="undefined"!==typeof window?localStorage.getItem("status"):null,_=(0,o.useState)(""),k=(0,a.Z)(_,2),A=k[0],B=k[1],D=(0,o.useState)(!1),Z=(0,a.Z)(D,2),S=Z[0],z=Z[1],V=function(e){console.log(e,"dsnfhdsjf"),l.Z.get("addEditPremium/"+e,{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){l.Z.get("lawyerListforAdmin",{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){200==e.data.status?f(e.data.data):f([]),console.log("Lawyer",e.data.data)})),P.fire({icon:"success",title:e.data.message})})).catch((function(e){console.log(e)}))};(0,o.useEffect)((function(){l.Z.get("lawyerListforAdmin",{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){200==e.data.status?f(e.data.data):f([]),console.log("Lawyer",e.data.data)}))}),[N]);var P=p().mixin({toast:!0,position:"top-end",showConfirmButton:!1,timer:3e3,timerProgressBar:!0,didOpen:function(e){e.addEventListener("mouseenter",p().stopTimer),e.addEventListener("mouseleave",p().resumeTimer)}}),F=[{name:"#",selector:function(e,t){return t+1},width:"50px"},{name:"Image",selector:function(e){return null==e.profile_img?(0,g.jsx)("img",{width:40,style:{borderRadius:"50%"},className:"m-1",src:"https://cdn.vectorstock.com/i/preview-1x/32/12/default-avatar-profile-icon-vector-39013212.jpg",alt:"MDN logo"}):(0,g.jsxs)(c.rU,{to:e.profile_img,target:"_blank",children:[" ",(0,g.jsx)("img",{width:40,style:{borderRadius:"50%"},className:"m-1",src:e.profile_img,alt:"MDN logo"})]})},width:"120px"},{name:"Name",selector:function(e){return(0,g.jsx)("span",{className:"text-capital fs-15",children:e.first_name+" "+e.last_name})},sortable:!0,width:"160px"},{name:"User Name",selector:function(e){return(0,g.jsx)("span",{className:"text-capital fs-15",children:e.user_name})},sortable:!0,width:"140px"},{name:"Mobile Number",selector:function(e){return(0,g.jsx)("span",{children:e.phone})},sortable:!0,width:"140px"},{name:"Premium Lawyer",selector:function(e){return(0,g.jsx)(g.Fragment,{children:1==e.is_adminVerified?(0,g.jsx)(s.kV,{defaultChecked:1==e.isPremium,onChange:function(){return V(e.id)},size:"xl",id:"formSwitchCheckDefaultXL",style:{backgroundColor:"#1D718B70",color:"#1D718B70",borderColor:"#1D718B70"}}):(0,g.jsx)(s.kV,{defaultChecked:!1,onChange:function(){return V(e.id)},size:"xl",id:"formSwitchCheckDefaultXL",disabled:!0,style:{backgroundColor:"#1D718B70",color:"#1D718B70",borderColor:"#1D718B70"}})})},width:"140px",sortable:!1},{name:"Kyc Status",selector:function(e){return(0,g.jsx)(g.Fragment,{children:(0,g.jsxs)("select",{style:{border:"none",fontSize:"14px",fontWeight:"600",color:"0"==e.is_adminVerified?"#f5d442":"1"==e.is_adminVerified?"green":"2"==e.is_adminVerified?"red":"",background:"0"==e.is_adminVerified||"1"==e.is_adminVerified?"#ffffff ":"#ffffff",borderRadius:"8px",padding:"2px"},onChange:function(t){return function(e,t){e.preventDefault();var n=new FormData;n.append("id",t.id),n.append("status",e.target.value),l.Z.post("UpdateKycStatus",n,{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){P.fire({icon:"success",title:e.data.message}),l.Z.get("lawyerListforAdmin",{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){f(e.data.data),console.log("Lawyer",e.data.data)}))})).catch((function(e){P.fire({icon:"error",title:e.response.data.message})}))}(t,e)},value:e.is_adminVerified,children:[(0,g.jsx)("option",{style:{color:"#f5d442",fontSize:"15px",fontWeight:"700"},disabled:!0,value:"0",className:"text-center ",children:"Pending"}),(0,g.jsx)("option",{style:{color:"green",fontSize:"15px",fontWeight:"700"},value:"1",className:"text-center ",children:"Approve"}),(0,g.jsx)("option",{style:{color:"red",fontSize:"15px",fontWeight:"700"},value:"2",className:"text-center ",children:"Reject"})]})})},sortable:!0,width:"150px"},{name:"Action",selector:function(e){return(0,g.jsx)("div",{children:(0,g.jsx)(c.rU,{to:"/lawyer-view/".concat(e.id),children:(0,g.jsx)(s.u5,{className:"border-b bg-black m-1","data-coreui-toggle":"tooltip","data-coreui-placement":"top",title:"View",children:(0,g.jsx)(h.rDJ,{size:20})})})})},width:"140px",sortable:!1}],H=n.filter((function(e){return A.toLowerCase().split(" ").every((function(t){return e.user_name.toLowerCase().includes(t)||e.first_name.toLowerCase().includes(t)||e.last_name.toLowerCase().includes(t)||e.phone.toLowerCase().includes(t)}))}));console.log("Lawyer filter",n);var E=u.default.input(r||(r=(0,i.Z)(["\n    height: 32px;\n    width: 180px;\n    border-radius: 3px;\n    border-top-left-radius: 5px;\n    border-bottom-left-radius: 5px;\n    border-top-right-radius: 0;\n    border-bottom-right-radius: 0;\n    border: 1px solid #e5e5e5;\n    padding: 0 32px 0 16px;\n    &:hover {\n      cursor: pointer;\n    }\n  "])));return(0,o.useEffect)((function(){var e=setTimeout((function(){w(n),L(!1)}),500);return function(){return clearTimeout(e)}}),[]),(0,g.jsx)(g.Fragment,{children:(0,g.jsx)("div",{className:"px-2",children:(0,g.jsx)(s.b7,{xs:12,children:(0,g.jsxs)(s.xH,{className:"mb-1",children:[(0,g.jsxs)(s.bn,{children:[A&&(0,g.jsx)(x.C4H,{style:{width:30,height:30},onClick:function(){A&&(z(!S),B(""))}}),(0,g.jsx)("strong",{children:"All Lawyers List"})]}),(0,g.jsx)(s.sl,{children:(0,g.jsx)(s.rb,{children:(0,g.jsx)(s.b7,{lg:12,children:(0,g.jsxs)(s.xH,{color:"light",textColor:"black",className:"mb-3",children:[(0,g.jsx)(s.bn,{children:(0,g.jsx)("div",{className:"row ",children:(0,g.jsx)(s.b7,{sm:3,children:(0,g.jsx)("div",{className:"text-center",children:(0,g.jsx)(E,{type:"text",placeholder:"Search....",className:"mt-1 mb-2",value:A,autoFocus:!0,onChange:function(e){return B(e.target.value)}})})})})}),(0,g.jsx)(s.sl,{children:0==H.length?(0,g.jsx)("div",{className:"text-center fw-600 my-5 fs-18 text-red",children:(0,g.jsx)("span",{children:"No Lawyers Available"})}):(0,g.jsx)(d.ZP,{columns:F,data:H,defaultSortFieldId:!0,fixedHeader:!0,responsive:!0,pagination:10,subHeaderAlign:"right",highlightOnHover:!0,progressPending:C,progressComponent:(0,g.jsx)(m.Z,{})})})]})})})})]})})})})}}}]);
//# sourceMappingURL=50.fef1c75b.chunk.js.map