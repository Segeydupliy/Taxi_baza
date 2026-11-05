(function(){
  'use strict';
  var form=document.getElementById('tbf-profile'); if(!form) return;
  var q=function(s,r){return (r||document).querySelector(s)}, qa=function(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))};

  /* show/hide blocks that depend on a toggle: [data-show-if="<input id>"] */
  function syncDeps(){
    qa('[data-show-if]').forEach(function(el){
      var src=document.getElementById(el.getAttribute('data-show-if'));
      el.hidden=!(src&&src.checked);
      qa('input,select,textarea',el).forEach(function(i){i.disabled=el.hidden}); // hidden values are not submitted
    });
    // payout day only for weekly / biweekly
    var f=q('#p-freq').value, day=q('#p-day-f'); var needDay=(f==='weekly'||f==='biweekly');
    day.hidden=!needDay; q('#p-day').disabled=!needDay;
    // own-car: commission label/base depend on payment type
    var t=q('#o-pay').value, lab=q('#o-fee-l'), base=q('#o-base-f');
    lab.firstChild.nodeValue = t==='fixed' ? 'Сума за тиждень (€)' : 'Комісія флотилії (%)';
    base.hidden = (t!=='percent'); q('#o-base').disabled=base.hidden;
  }
  form.addEventListener('change',syncDeps); syncDeps();

  /* validation */
  function setErr(field,msg){
    field.classList.toggle('tbf-field--invalid',!!msg);
    var p=q('.tbf-error',field);
    if(!p&&msg){p=document.createElement('p');p.className='tbf-error';field.appendChild(p)}
    if(p){p.textContent=msg||p.textContent;p.hidden=!msg}
  }
  function num(v){return parseFloat(String(v).replace(',','.'))}
  function validate(){
    var ok=true, first=null;
    function bad(f,m){setErr(f,m);ok=false;if(!first)first=f}
    qa('.tbf-field').forEach(function(f){setErr(f,'')});
    qa('input[required]',form).forEach(function(i){ if(!i.disabled&&!i.value.trim()) bad(i.closest('.tbf-field'),'Заповніть це поле.') });
    function group(name,msg){var g=q('[data-group="'+name+'"]',form); if(g&&!qa('input:checked',g).length) bad(g,msg)}
    group('platforms','Оберіть хоча б одну платформу.');
    group('langs','Оберіть хоча б одну мову.');
    group('paymethod','Оберіть хоча б один спосіб виплати.');
    group('docs','Оберіть хоча б один документ.');
    var mg=q('[data-group="models"]',form); if(!qa('input:checked',mg).length) bad(mg,'Оберіть хоча б одну модель співпраці.');
    function numField(id,min,max,msg){var i=q('#'+id); if(!i||i.disabled)return; var n=num(i.value); if(isNaN(n)||n<min||n>max) bad(i.closest('.tbf-field'),msg)}
    numField('o-fee',0,100,'Вкажіть число від 0 до 100.');
    numField('r-price',1,100000,'Вкажіть ціну в євро.');
    numField('r-comm',0,100,'Вкажіть число від 0 до 100.');
    numField('r-dep',0,100000,'Вкажіть суму депозиту.');
    numField('r-min',1,1000,'Вкажіть термін.');
    numField('k-fuel-v',0,100,'Вкажіть знижку.');
    numField('k-svc-v',0,100,'Вкажіть число від 0 до 100.');
    var sw=q('#k-svc'); if(sw&&sw.checked&&!qa('input[name="svc_what[]"]:checked',form).length) bad(q('input[name="svc_what[]"]',form).closest('.tbf-field'),'Оберіть, на що діє знижка.');
    var ph=q('#f-phone'); if(ph.value.trim()&&!/^\+421[\d\s]{9,12}$/.test(ph.value.trim())) bad(ph.closest('.tbf-field'),'Формат: +421 XXX XXX XXX');
    if(first) first.scrollIntoView({behavior:'smooth',block:'center'});
    return ok;
  }

  var modal=q('#tbf-modal'), errToast=q('#tbf-toast-err'), saving=false;
  function openModal(){modal.hidden=false;q('#tbf-modal-close').focus()}
  function closeModal(){modal.hidden=true}
  q('#tbf-modal-close').addEventListener('click',closeModal);
  modal.addEventListener('click',function(e){if(e.target===modal)closeModal()});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.hidden)closeModal()});

  form.addEventListener('submit',function(e){
    e.preventDefault(); if(saving||!validate()) return;
    saving=true; var b=q('#tbf-save'); b.disabled=true;
    // prototype: the theme replaces this with fetch(admin-ajax.php?action=tbf_profile_save) and handles ok/error
    var data=new FormData(form);
    (window.tbfSaveProfile?window.tbfSaveProfile(data):Promise.resolve({ok:true})).then(function(r){
      if(r&&r.ok){openModal()} else {throw 0}
    }).catch(function(){
      errToast.hidden=false; setTimeout(function(){errToast.hidden=true},5000);
    }).then(function(){saving=false;b.disabled=false});
  });
  q('#tbf-cancel').addEventListener('click',function(){form.reset();syncDeps();qa('.tbf-field').forEach(function(f){setErr(f,'')})});

  /* logo preview (client side only) */
  var file=q('#tbf-logo-file'), pic=q('#tbf-logo-pic'), orig=pic.innerHTML;
  file.addEventListener('change',function(){
    var f=file.files[0]; if(!f) return;
    if(!/^image\/(jpeg|png|webp)$/.test(f.type)||f.size>5*1024*1024){file.value='';alert('Оберіть JPG, PNG або WebP до 5 МБ.');return}
    var img=new Image(); img.alt=''; img.src=URL.createObjectURL(f); pic.innerHTML=''; pic.appendChild(img);
  });
  q('#tbf-logo-del').addEventListener('click',function(){file.value='';pic.innerHTML=orig});
})();
