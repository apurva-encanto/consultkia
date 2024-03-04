"use strict";(self.webpackChunkscintello_super_admin=self.webpackChunkscintello_super_admin||[]).push([[327],{41646:function(e,t,n){var r,i,s=n(30168),a=(n(72791),n(89690)),o=n(80184);t.Z=function(){var e=(0,a.keyframes)(r||(r=(0,s.Z)(["\n    from {\n    transform: rotate(0deg);\n    }\n    to {\n    transform: rotate(360deg);\n    }\n    "]))),t=a.default.div(i||(i=(0,s.Z)(["\n    margin: 16px;\n    animation: "," 1s linear infinite;\n    transform: translateZ(0);\n    border-top: 2px solid #1D718B ;\n    border-right: 2px solid #1D718B ;\n    border-bottom: 2px solid #1D718B ;\n    border-left: 4px solid linear-gradient(to right, #8360c3, #2ebf91);\n    background: transparent;\n    width: 100px;\n    height: 100px;\n    border-radius: 50%;\n  "])),e);return(0,o.jsxs)("div",{style:{padding:"24px"},children:[(0,o.jsx)(t,{}),(0,o.jsx)("div",{style:{marginLeft:"20px",color:"#1D718B "},children:(0,o.jsx)("b",{children:"Loading..."})})]})}},43327:function(e,t,n){n.r(t);var r,i=n(30168),s=n(29439),a=n(72791),o=n(78983),d=n(43513),l=n(31243),c=n(11087),u=n(89690),p=n(21830),f=n.n(p),m=n(23853),h=n(41646),x=n(58617),g=n(80184);t.default=function(){var e=(0,a.useState)([]),t=(0,s.Z)(e,2),n=t[0],p=t[1],b=(0,a.useState)([]),j=(0,s.Z)(b,2),v=(j[0],j[1]),w=(0,a.useState)(!0),y=(0,s.Z)(w,2),_=y[0],L=y[1],N="undefined"!==typeof window?localStorage.getItem("status"):null,Z=(0,a.useState)(""),A=(0,s.Z)(Z,2),C=A[0],S=A[1],k=(0,a.useState)(!1),V=(0,s.Z)(k,2),B=V[0],D=V[1];(0,a.useEffect)((function(){l.Z.get("lawyerListforAdmin/1",{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){200==e.data.status?p(e.data.data):p([]),console.log("Lawyer. aPPROVE.",e)}))}),[N]);var z=f().mixin({toast:!0,position:"top-end",showConfirmButton:!1,timer:3e3,timerProgressBar:!0,didOpen:function(e){e.addEventListener("mouseenter",f().stopTimer),e.addEventListener("mouseleave",f().resumeTimer)}}),H=[{name:"#",selector:function(e,t){return t+1},width:"50px"},{name:"Image",selector:function(e){return null==e.profile_img?(0,g.jsx)("img",{width:40,style:{borderRadius:"50%"},className:"m-1",src:"https://cdn.vectorstock.com/i/preview-1x/32/12/default-avatar-profile-icon-vector-39013212.jpg",alt:"MDN logo"}):(0,g.jsxs)(c.rU,{to:e.profile_img,target:"_blank",children:[" ",(0,g.jsx)("img",{width:40,style:{borderRadius:"50%"},className:"m-1",src:e.profile_img,alt:"MDN logo"})]})},width:"80px"},{name:"Name",selector:function(e){return(0,g.jsx)("span",{className:"text-capital fs-15",children:e.first_name+" "+e.last_name})},sortable:!0,width:"180px"},{name:"User Name",selector:function(e){return(0,g.jsx)("span",{className:"text-capital fs-15",children:e.user_name})},sortable:!0,width:"180px"},{name:"Mobile number",selector:function(e){return(0,g.jsx)("span",{children:e.phone})},sortable:!0,width:"140px"},{name:"Kyc Status",selector:function(e){return(0,g.jsx)(g.Fragment,{children:(0,g.jsxs)("select",{style:{border:"none",fontSize:"14px",fontWeight:"600",color:"0"==e.is_adminVerified?"#f5d442":"1"==e.is_adminVerified?"green":(e.is_adminVerified,"red"),background:"0"==e.is_adminVerified||"1"==e.is_adminVerified?"#ffffff ":"#ffffff",borderRadius:"8px",padding:"2px"},onChange:function(t){return function(e,t){e.preventDefault();var n=new FormData;n.append("id",t.id),n.append("status",e.target.value),l.Z.post("UpdateKycStatus",n,{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){z.fire({icon:"success",title:e.data.message}),l.Z.get("lawyerListforAdmin/1",{headers:{Accept:"application/json",Authorization:"Bearer "+N}}).then((function(e){p(e.data.data),console.log("Lawyer",e.data.data)}))})).catch((function(e){z.fire({icon:"error",title:e.response.data.message})}))}(t,e)},children:[(0,g.jsx)("option",{selected:"0"==e.is_adminVerified,style:{color:"#f5d442",fontSize:"15px",fontWeight:"700"},disabled:!0,value:"0",children:"Pending"}),(0,g.jsx)("option",{style:{color:"green",fontSize:"15px",fontWeight:"700"},selected:"1"==e.is_adminVerified,value:"1",children:"Approve"}),(0,g.jsx)("option",{style:{color:"red",fontSize:"15px",fontWeight:"700"},selected:"2"==e.is_adminVerified,value:"2",children:"Reject"})]})})},sortable:!0,width:"120px"},{name:"Action",selector:function(e){return(0,g.jsx)("div",{children:(0,g.jsx)(c.rU,{to:"/lawyer-view/".concat(e.id),children:(0,g.jsx)(o.u5,{className:"border-b bg-black m-1","data-coreui-toggle":"tooltip","data-coreui-placement":"top",title:"View",children:(0,g.jsx)(m.rDJ,{size:20})})})})},width:"140px",sortable:!1}],P=n.filter((function(e){return C.toLowerCase().split(" ").every((function(t){var n=e.user_name.toLowerCase().includes(t)||e.first_name.toLowerCase().includes(t)||e.last_name.toLowerCase().includes(t)||e.phone.toLowerCase().includes(t);return console.log("".concat(t," found in item: ").concat(n)),n}))}));console.log("Lawyer filter",n);var E=u.default.input(r||(r=(0,i.Z)(["\n    height: 32px;\n    width: 180px;\n    border-radius: 3px;\n    border-top-left-radius: 5px;\n    border-bottom-left-radius: 5px;\n    border-top-right-radius: 0;\n    border-bottom-right-radius: 0;\n    border: 1px solid #e5e5e5;\n    padding: 0 32px 0 16px;\n    &:hover {\n      cursor: pointer;\n    }\n  "])));return(0,a.useEffect)((function(){var e=setTimeout((function(){v(n),L(!1)}),500);return function(){return clearTimeout(e)}}),[]),(0,g.jsx)(g.Fragment,{children:(0,g.jsx)("div",{className:"px-2",children:(0,g.jsx)(o.b7,{xs:12,children:(0,g.jsxs)(o.xH,{className:"mb-1",children:[(0,g.jsxs)(o.bn,{children:[C&&(0,g.jsx)(x.C4H,{style:{width:30,height:30},onClick:function(){C&&(D(!B),S(""))}}),(0,g.jsx)("strong",{children:"Approved Lawyers List"})]}),(0,g.jsx)(o.sl,{children:(0,g.jsx)(o.rb,{children:(0,g.jsx)(o.b7,{lg:12,children:(0,g.jsxs)(o.xH,{color:"light",textColor:"black",className:"mb-3",children:[(0,g.jsx)(o.bn,{children:(0,g.jsx)("div",{className:"row ",children:(0,g.jsx)(o.b7,{sm:3,children:(0,g.jsx)("div",{className:"text-center",children:(0,g.jsx)(E,{type:"text",placeholder:"Search....",className:"mt-1 mb-2",value:C,autoFocus:!0,onChange:function(e){return S(e.target.value)}})})})})}),(0,g.jsx)(o.sl,{children:0==P.length?(0,g.jsx)("div",{className:"text-center fw-600 my-5 fs-18 text-red",children:(0,g.jsx)("span",{children:"No Approved Lawyers Available"})}):(0,g.jsx)(d.ZP,{columns:H,data:P,defaultSortFieldId:!0,fixedHeader:!0,responsive:!0,pagination:10,subHeaderAlign:"right",highlightOnHover:!0,progressPending:_,progressComponent:(0,g.jsx)(h.Z,{})})})]})})})})]})})})})}}}]);
//# sourceMappingURL=327.27e4f0fe.chunk.js.map