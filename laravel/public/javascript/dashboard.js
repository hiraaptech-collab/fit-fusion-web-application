// Small shared interactions: mobile navigation, confirmations and database charts.
(() => {
 const sidebar=document.querySelector('#dashSidebar'),toggle=document.querySelector('#dashToggle'),overlay=document.querySelector('#dashOverlay');
 function menu(open){sidebar?.classList.toggle('open',open);if(overlay)overlay.hidden=!open;toggle?.setAttribute('aria-expanded',String(open));document.body.style.overflow=open?'hidden':'';if(open)sidebar.querySelector('button,a')?.focus();else toggle?.focus();}
 toggle?.addEventListener('click',()=>menu(!sidebar.classList.contains('open')));
 document.querySelector('#dashClose')?.addEventListener('click',()=>menu(false));overlay?.addEventListener('click',()=>menu(false));
 document.addEventListener('keydown',event=>{if(!sidebar?.classList.contains('open'))return;if(event.key==='Escape')menu(false);if(event.key==='Tab'){const items=[...sidebar.querySelectorAll('button,a[href]')].filter(el=>getComputedStyle(el).display!=='none');const first=items[0],last=items.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}});
 window.matchMedia('(min-width:992px)').addEventListener('change',event=>{if(event.matches)menu(false);});
 const dialog=document.querySelector('#dashConfirm');let pendingForm;
 document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(form.dataset.confirmed==='yes')return;event.preventDefault();pendingForm=form;document.querySelector('#dashConfirmText').textContent=form.dataset.confirm;dialog.showModal();}));
 document.querySelector('#dashCancel')?.addEventListener('click',()=>dialog.close());
 document.querySelector('#dashProceed')?.addEventListener('click',()=>{dialog.close();pendingForm.dataset.confirmed='yes';pendingForm.requestSubmit();});
 const canvas=document.querySelector('[data-chart]');if(!canvas)return;
 let rows=JSON.parse(canvas.dataset.chart), metric=document.querySelector('#chartMetric')?.value||canvas.dataset.metric||'clients';
 function paint(){
  const ratio=window.devicePixelRatio||1,rect=canvas.getBoundingClientRect();canvas.width=rect.width*ratio;canvas.height=rect.height*ratio;
  const ctx=canvas.getContext('2d');ctx.scale(ratio,ratio);const width=rect.width,height=rect.height,pad=38;
  const values=rows.map(row=>Number(row[metric]||0));
  const minimum=Math.min(0,...values), maximum=Math.max(1,...values), span=maximum-minimum;
  const yFor=value=>pad+(maximum-value)/span*(height-2*pad), baseline=yFor(0);
  ctx.clearRect(0,0,width,height);ctx.font='11px Inter, sans-serif';ctx.fillStyle='#786A5D';
  for(let i=0;i<=4;i++){const value=maximum-span*i/4,y=yFor(value);ctx.strokeStyle='#E7DECF';ctx.beginPath();ctx.moveTo(pad,y);ctx.lineTo(width-12,y);ctx.stroke();ctx.fillText(value.toFixed(span>100?0:1),1,y+4);}
  ctx.strokeStyle='#5C4632';ctx.beginPath();ctx.moveTo(pad,baseline);ctx.lineTo(width-12,baseline);ctx.stroke();
  const slot=(width-pad-14)/Math.max(rows.length,1);
  rows.forEach((row,index)=>{const value=values[index],x=pad+slot*index+slot*.15,y=Math.min(baseline,yFor(value)),barHeight=Math.abs(yFor(value)-baseline);
   ctx.fillStyle=value<0?'#B89B72':'#C6A15B';ctx.fillRect(x,y,Math.max(3,slot*.7),barHeight);ctx.fillStyle='#5C4632';ctx.save();ctx.translate(x,height-pad+17);ctx.rotate(-.3);ctx.fillText(slot<65?row.month.slice(0,3):row.month,0,0);ctx.restore();});  canvas.setAttribute('aria-label',metric+' chart: '+rows.map((row,index)=>row.month+' '+values[index]).join(', '));
 }
 canvas.addEventListener('mousemove',event=>{const rect=canvas.getBoundingClientRect(),index=Math.floor((event.clientX-rect.left-38)/((rect.width-52)/Math.max(rows.length,1)));canvas.title=rows[index]?rows[index].month+': '+rows[index][metric]:'';});
 document.querySelector('#chartMetric')?.addEventListener('change',event=>{metric=event.target.value;paint();});
 document.querySelector('#reportControls')?.addEventListener('change',async event=>{
  if(event.target.id==='chartMetric')return;const form=event.currentTarget,url=new URL(form.action);url.search=new URLSearchParams(new FormData(form)).toString();
  try{const response=await fetch(url,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});if(!response.ok)throw new Error();const data=await response.json();rows=data.rows;paint();
   const table=document.querySelector('#reportTable');
   if(table){const body=table.querySelector('tbody');body.replaceChildren();rows.forEach(row=>{const tr=document.createElement('tr');const keys=['month','clients','memberships',...(data.financial?['revenue','expenses','net']:[])];keys.forEach(key=>{const td=document.createElement('td');td.textContent=['revenue','expenses','net'].includes(key)?Number(row[key]).toFixed(2):String(row[key]);tr.append(td);});body.append(tr);});table.querySelectorAll('[data-currency]').forEach(th=>th.textContent=th.dataset.currency+' '+data.currency);}
   document.querySelector('#chartStatus').textContent='Report updated.';}
  catch{document.querySelector('#chartStatus').textContent='Unable to refresh this report. Reload and try again.';}
 });window.addEventListener('resize',paint);paint();
})();



