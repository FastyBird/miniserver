(window.webpackJsonp=window.webpackJsonp||[]).push([[19],{678:function(e,t,n){var content=n(683);content.__esModule&&(content=content.default),"string"==typeof content&&(content=[[e.i,content,""]]),content.locals&&(e.exports=content.locals);(0,n(94).default)("1e2901a1",content,!0,{sourceMap:!1})},682:function(e,t,n){"use strict";n(678)},683:function(e,t,n){var r=n(93)(!1);r.push([e.i,".fb-account-sign-header__heading{font-weight:300;margin:0;text-align:center}",""]),e.exports=r},690:function(e,t,n){"use strict";var r=n(20),o=Object(r.b)({name:"AccountSignHeader",props:{heading:{type:String,required:!0}}}),d=(n(682),n(58)),component=Object(d.a)(o,(function(){var e=this,t=e.$createElement;return(e._self._c||t)("h3",{staticClass:"fb-account-sign-header__heading"},[e._v("\n  "+e._s(e.heading)+"\n")])}),[],!1,null,null,null);t.a=component.exports},914:function(e,t,n){"use strict";n.r(t);n(32),n(64),n(49);var r=n(20),o=n(16),d=n(10),l=n.n(d),c=n(677),f=n(690),v=Object(r.b)({name:"RequestPasswordPage",components:{ValidationProvider:c.b,ValidationObserver:c.a,AccountSignHeader:f.a},layout:"sign",middleware:"anonymous",transition:"fade",setup:function(){var e=Object(r.l)(),t=e.app.i18n,n=e.$flashMessage,d=e.$backendApi,f=e.$bus,v=Object(r.j)(),form=Object(r.i)({model:{uid:""}}),_=Object(r.j)(!1),m=Object(r.j)(!1);return Object(c.d)({en:{fields:{uid:{required:t.t("account.fields.identity.uid.validation.required").toString()},password:{required:t.t("account.fields.identity.password.validation.required").toString()}}}}),Object(c.c)("required",{validate:function(e){return{required:!0,valid:!["",null,void 0].includes(e)}},computesRequired:!0}),{validator:v,form:form,makingRequest:_,isSubmitted:m,handleSubmit:function(){f.$emit("wait-page_reloading",10);var e=t.t("account.messages.passwordRequestFail").toString();_.value=!0,d.requestPassword({uid:form.model.uid}).then((function(){_.value=!1,m.value=!0})).catch((function(t){_.value=!1,null!==l()(t,"exception",null)?n.exception(t.exception,e):null!==l()(t,"response",null)?n.requestError(t.response,e):n.error(e),m.value=!1}))},sizeTypes:o.J,buttonVariantTypes:o.N}}}),_=n(58),component=Object(_.a)(v,(function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("div",{staticClass:"fb-password-request-page__container"},[e.isSubmitted?n("account-sign-header",{attrs:{heading:e.$t("account.headings.instructionEmailed")}}):n("account-sign-header",{attrs:{heading:e.$t("account.headings.passwordReset")}}),e._v(" "),e.makingRequest?n("div",{staticClass:"fb-password-request-page__processing"},[n("fb-ui-spinner",{attrs:{size:e.sizeTypes.LARGE}}),e._v(" "),n("strong",[e._v(e._s(e.$t("account.texts.processing")))])],1):e._e(),e._v(" "),e.makingRequest?e._e():n("div",[e.isSubmitted?n("p",{staticClass:"fb-password-request-page__info"},[n("small",[e._v(e._s(e.$t("account.texts.resetPasswordInstructionsEmailed")))])]):e._e(),e._v(" "),e.isSubmitted?e._e():n("validation-observer",{ref:"validator",scopedSlots:e._u([{key:"default",fn:function(t){var r=t.handleSubmit;return[n("form",{on:{submit:function(e){return e.preventDefault(),r(r)}}},[n("validation-provider",{attrs:{name:"uid",rules:"required"},scopedSlots:e._u([{key:"default",fn:function(t){var r=t.errors;return[n("fb-form-input",{attrs:{error:r[0],"has-error":r.length>0,label:e.$t("account.fields.identity.uid.title"),required:!0,name:"uid"},model:{value:e.form.model.uid,callback:function(t){e.$set(e.form.model,"uid",t)},expression:"form.model.uid"}})]}}],null,!0)}),e._v(" "),n("fb-ui-button",{attrs:{variant:e.buttonVariantTypes.PRIMARY,block:"",uppercase:"",type:"submit"}},[e._v("\n          "+e._s(e.$t("account.buttons.resetPassword.title"))+"\n        ")]),e._v(" "),n("p",{staticClass:"fb-password-request-page__info"},[n("small",[e._v(e._s(e.$t("account.texts.resetPasswordInfo")))])])],1)]}}],null,!1,1732630145)})],1)],1)}),[],!1,null,null,null);t.default=component.exports}}]);