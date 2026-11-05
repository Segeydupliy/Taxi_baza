(function(){
  'use strict';
  var $=function(s,r){return (r||document).querySelector(s)}, $$=function(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))};
  var form=$('#tbf-reg'); if(!form) return;
  var views={register:$('#v-register'),confirm:$('#v-confirm'),done:$('#v-done')};
  function show(n){Object.keys(views).forEach(function(k){views[k].hidden=(k!==n)});window.scrollTo(0,0)}

  /* custom select */
  var btn=$('#tbf-type-btn'), list=$('#tbf-type-list'), hid=$('#tbf-type'), label=$('#tbf-type-label');
  function open(o){btn.setAttribute('aria-expanded',o);list.hidden=!o}
  btn.addEventListener('click',function(){open(btn.getAttribute('aria-expanded')!=='true')});
  $$('.tbf-sel__opt',list).forEach(function(li){
    li.addEventListener('click',function(){
      $$('.tbf-sel__opt',list).forEach(function(x){x.setAttribute('aria-selected',x===li)});
      hid.value=li.dataset.value; label.textContent=li.textContent; label.classList.add('tbf-sel__hint'); open(false); validateLive();
    });
  });
  document.addEventListener('click',function(e){if(!e.target.closest('.tbf-sel'))open(false)});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')open(false)});

  /* show password */
  $('#tbf-eye').addEventListener('click',function(){var i=$('#r-pass');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Показати':'Сховати'});

  function err(el,msg){var w=el.closest('.tbf-f');w.classList.toggle('tbf-f--invalid',!!msg);var p=$('.tbf-f__err',w);p.textContent=msg||'';p.hidden=!msg}
  var emailRe=/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/, phoneRe=/^\+?[\d\s]{9,16}$/;
  function check(showErrors){
    var ok=true, name=$('#r-name').value.trim(), id=$('#r-id').value.trim(), pw=$('#r-pass').value;
    var e=[];
    if(name.length<2) e.push([$('#r-name'),'Вкажіть ім’я або назву флотилії.']);
    if(!(emailRe.test(id)||phoneRe.test(id))) e.push([$('#r-id'),'Вкажіть коректний email або телефон.']);
    if(pw.length<8) e.push([$('#r-pass'),'Мінімум 8 символів.']);
    if(!hid.value) e.push([btn,'Оберіть тип облікового запису.']);
    if(!$('#r-agree').checked) e.push([$('#r-agree'),'']);
    ok=!e.length;
    if(showErrors){
      ['#r-name','#r-id','#r-pass'].forEach(function(s){err($(s),'')}); err(btn,''); $('.tbf-agree').classList.remove('tbf-agree--invalid');
      e.forEach(function(x){ if(x[0].id==='r-agree') $('.tbf-agree').classList.add('tbf-agree--invalid'); else err(x[0],x[1]) });
    }
    return ok;
  }
  var submit=$('#tbf-submit');
  function validateLive(){submit.disabled=!check(false)}
  form.addEventListener('input',validateLive); form.addEventListener('change',validateLive); validateLive();

  form.addEventListener('submit',function(e){
    e.preventDefault(); if(!check(true)) return;
    var id=$('#r-id').value.trim();
    $('#tbf-sent-to').textContent=id;
    // prototype: theme replaces with fetch(admin-ajax.php?action=tbf_register) -> sends confirmation email, creates user with role "flotila", status pending
    show('confirm'); startTimer();
  });

  /* resend with cooldown */
  var resend=$('#tbf-resend'), timerEl=$('#tbf-timer'), t=null;
  function startTimer(){var s=60;resend.disabled=true;clearInterval(t);t=setInterval(function(){s--;timerEl.textContent=s>0?'Надіслати повторно можна через '+s+' с':'';if(s<=0){clearInterval(t);resend.disabled=false}},1000);timerEl.textContent='Надіслати повторно можна через 60 с'}
  resend.addEventListener('click',function(){timerEl.textContent='Лист надіслано повторно.';resend.disabled=true;setTimeout(startTimer,1500)});
  $('#tbf-change-email').addEventListener('click',function(){clearInterval(t);show('register');$('#r-id').focus()});
  /* prototype helper: ?step=confirm|done to preview states */
  var step=(location.search.match(/step=(\w+)/)||[])[1]; if(step&&views[step]) show(step);
})();
