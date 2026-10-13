// Run: node tests/verify-navbar-browser.cjs. Requires local PHP, Chrome and MariaDB.
const fs = require('fs'), path = require('path'), http = require('http');
const cp = require('child_process');
const laravel = path.resolve(__dirname, '..'), origin = 'http://127.0.0.1:8002';
const fixtureFile = path.join(__dirname, 'gym-browser-fixture.php');
const output = path.join(laravel, 'storage/app/private/phase4-5-verification');
const php = 'C:\\xampp\\php\\php.exe';
fs.mkdirSync(output, {recursive:true});
const report = {checks:[], layouts:[], exceptions:[], corsErrors:[], failures:[]};
let server, browser, ws, profile, sequence = 0, baseline;
const pending = new Map(), sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const reportFile = process.argv.includes('--interactions-only') ? 'dashboard-interaction-tests.json' : 'dashboard-browser-tests.json';
const save = () => fs.writeFileSync(path.join(output, reportFile), JSON.stringify(report, null, 2));
function check(value, name) {
 if (!value) { report.failures.push(name); throw new Error('Check failed: '+name); }
 report.checks.push(name); if(report.checks.length%10===0)save();
}
function fixture(action) {
 const result = cp.spawnSync(php, [fixtureFile, action], {cwd:laravel,windowsHide:true,encoding:'utf8'});
 if (result.status !== 0) throw new Error('Navbar fixture failed: '+action);
 return JSON.parse(result.stdout);
}
function get(url, cookies='') {
 return new Promise((resolve,reject) => {
  const request = http.get(url, {agent:false,headers:cookies?{Cookie:cookies}:{}}, response => {
   let body=''; response.on('data', chunk => body+=chunk);
   response.on('end', () => resolve({status:response.statusCode, body}));
  });
  request.on('error', reject); request.setTimeout(20000, () => request.destroy(new Error('HTTP timeout')));
 });
}
function call(method, params={}) {
 return new Promise((resolve,reject) => {
  const id=++sequence, timer=setTimeout(() => {pending.delete(id);reject(new Error('Browser command timed out: '+method));},25000);
  pending.set(id,{resolve:value=>{clearTimeout(timer);resolve(value);},reject:error=>{clearTimeout(timer);reject(error);}});
  ws.send(JSON.stringify({id,method,params}));
 });
}
async function evaluate(expression) {
 const result=await call('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});
 if(result.exceptionDetails) throw new Error(result.exceptionDetails.text);
 return result.result.value;
}
const run=(fn,...args) => evaluate('('+fn.toString()+')(...'+JSON.stringify(args)+')');
async function waitFor(expression, timeout=20000) {
 const start=Date.now(); while(Date.now()-start<timeout) {if(await evaluate(expression))return;await sleep(100);}
 report.timeout = await run(() => ({uri:location.pathname,origin:location.origin,ready:document.readyState,menuOpen:document.querySelector('#navbarMobile')?.classList.contains('active'),viewport:{width:innerWidth,height:innerHeight,scale:visualViewport.scale},actions:[...document.querySelectorAll('.navbar-auth-mobile .navbar-auth-link')].map(element=>({label:element.textContent.trim(),href:element.href,rect:element.getBoundingClientRect().toJSON()}))})); await screenshot('failure-navbar',!!report.timeout.menuOpen); throw new Error('Browser condition timed out');
}
async function navigate(uri) {
 const ready='document.readyState!=="loading" && (!!document.querySelector(".dash-page") || (!!document.querySelector("#mainNav") && !!document.querySelector("#footer")))';
 await call('Page.stopLoading');await call('Page.navigate',{url:origin+uri});
 try {await waitFor(ready);} catch(error) {
  // A cold CDN/font request can stall PHP's local test server. Retry the same page.
  await call('Page.stopLoading');await call('Page.navigate',{url:origin+uri});await waitFor(ready,30000);
 }
 await sleep(150);
}async function viewport(width,height=width<768?844:900) {
 await call('Emulation.setDeviceMetricsOverride',{width,height,screenWidth:width,screenHeight:height,deviceScaleFactor:1,mobile:width<768});
 await sleep(600);
}
async function click(selector) {
 for(let attempt=0;attempt<4;attempt++){
  const oldPath=await run(()=>location.pathname);
  await run(selector=>{document.querySelector(selector).scrollIntoView({behavior:'instant',block:'center'});window.qaClickHit=false;document.addEventListener('click',event=>{window.qaClickHit=!!event.target.closest(selector);},{capture:true,once:true});},selector);
  await sleep(selector.startsWith('#bmi')?1400:150);
  const point=await run(selector=>{const r=document.querySelector(selector).getBoundingClientRect();return {x:r.left+r.width/2-visualViewport.offsetLeft,y:r.top+r.height/2-visualViewport.offsetTop};},selector);
  await call('Input.dispatchMouseEvent',{type:'mousePressed',...point,button:'left',clickCount:1});await call('Input.dispatchMouseEvent',{type:'mouseReleased',...point,button:'left',clickCount:1});await sleep(200);
  const after=await run(()=>({hit:window.qaClickHit,path:location.pathname})).catch(()=>({hit:true}));const hit=after.hit||after.path!==oldPath;report.clicks??=[];report.clicks.push({selector,attempt,point,hit});if(hit)return;
 }
 throw new Error('Native click did not reach '+selector);
}async function screenshot(name){const result=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});fs.writeFileSync(path.join(output,name+'.png'),Buffer.from(result.data,'base64'));}
async function signIn(role){await call('Network.clearBrowserCookies');const cookie=fixture('session-'+role);await call('Network.setCookie',{...cookie,url:origin,httpOnly:true,sameSite:'Lax'});}
async function api(uri,payload,method='POST',csrf=true){return run(async(uri,payload,method,csrf)=>{const headers={'Content-Type':'application/json',Accept:'application/json'};if(csrf)headers['X-CSRF-TOKEN']=document.querySelector('meta[name="csrf-token"]').content;const response=await fetch(uri,{method,headers,credentials:'same-origin',body:JSON.stringify(payload)});return {status:response.status,body:await response.json().catch(()=>null)};},uri,payload,method,csrf);}
async function main(){
 fixture('prepare');baseline=fixture('snapshot');
 server=cp.spawn(php,['-S','127.0.0.1:8002','-t','public',fixtureFile],{cwd:laravel,windowsHide:true,stdio:'ignore'});
 for(let i=0;i<100;i++){try{if((await get(origin+'/login')).status===200)break;}catch{}await sleep(100);}
 profile=fs.mkdtempSync(path.join(output,'gym-chrome-profile-'));
 browser=cp.spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:'ignore'});
 const active=path.join(profile,'DevToolsActivePort');for(let i=0;i<150&&!fs.existsSync(active);i++)await sleep(100);check(fs.existsSync(active),'Chrome started');
 let port;for(let attempt=0;attempt<100;attempt++){try{port=fs.readFileSync(active,'utf8').split('\n')[0];if(Number(port)>0)break;}catch{}await sleep(100);}check(!!Number(port),'Chrome debug port available');const targets=JSON.parse((await get('http://127.0.0.1:'+port+'/json/list')).body);
 ws=new WebSocket(targets.find(target=>target.type==='page').webSocketDebuggerUrl);await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
 ws.addEventListener('message',event=>{const message=JSON.parse(event.data);if(message.id){const task=pending.get(message.id);if(task){pending.delete(message.id);message.error?task.reject(new Error(message.error.message)):task.resolve(message.result);}return;}if(message.method==='Runtime.exceptionThrown')report.exceptions.push(message.params.exceptionDetails.text);if(message.method==='Network.loadingFailed'&&message.params.corsErrorStatus)report.corsErrors.push(message.params.corsErrorStatus.corsError);});
 ws.addEventListener('message', event => {
  const message = JSON.parse(event.data);
  if (message.method === 'Page.javascriptDialogOpening') {
   report.dialogs ??= [];
   report.dialogs.push(message.params.message);
   call('Page.handleJavaScriptDialog', {accept:true}).catch(() => {});
  }
 });
 await call('Page.enable');await call('Runtime.enable');await call('Network.enable');
 const ownerPages=['/owner/dashboard','/manage/accounts/managers','/manage/accounts/clients','/manage/staff/trainers','/manage/staff/workers','/manage/staff-operations/schedules','/manage/staff-operations/attendance','/manage/membership-plans','/manage/memberships','/manage/finance','/manage/fitness-plans/workouts','/manage/fitness-plans/diets','/manage/fitness-plans/workouts/create','/manage/fitness-plans/diets/create','/manage/reports','/messages','/notifications','/gym-settings','/audit-logs'];
 const clientPages=['/client/dashboard','/client/account-profile','/client/memberships','/client/plans','/client/payments','/client/progress','/client/ai','/messages','/notifications'];
 for(const [role,pages]of [['owner',ownerPages],['manager',['/manager/dashboard','/manage/staff/trainers','/manage/finance']],['client',clientPages]]){
  await signIn(role);
  for(const uri of process.argv.includes('--interactions-only')?[]:pages){
   await viewport(1440);await navigate(uri);await run(()=>document.querySelectorAll('details').forEach(element=>element.open=true));await run(()=>Promise.race([document.fonts.ready,new Promise(resolve=>setTimeout(resolve,5000))]));
   for(const width of [320,375,390,768,992,1024,1440,1920]){
    await viewport(width);const layout=await run(()=>{const width=document.documentElement.clientWidth;const fields=[...document.querySelectorAll('.dash-page .dash-field')].filter(el=>el.offsetHeight>0&&!el.closest('td'));return {viewport:width,scrollWidth:document.documentElement.scrollWidth,formFits:fields.every(el=>{const r=el.getBoundingClientRect();return r.left>=-1&&r.right<=width+1;}),sidebar:!!document.querySelector('#dashSidebar'),topbar:!!document.querySelector('.dash-topbar')};});
    report.layouts.push({role,uri,width,...layout});check(layout.scrollWidth<=layout.viewport+1&&layout.formFits&&layout.sidebar&&layout.topbar,'Layout '+role+' '+uri+' '+width);
   }
   await viewport(390);await click('#dashToggle');check(await run(()=>document.querySelector('#dashSidebar').classList.contains('open')&&document.querySelector('#dashToggle').getAttribute('aria-expanded')==='true'),'Mobile sidebar opens '+uri);
   await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape',windowsVirtualKeyCode:27});check(await run(()=>!document.querySelector('#dashSidebar').classList.contains('open')&&document.activeElement===document.querySelector('#dashToggle')),'Mobile sidebar Escape '+uri);
   console.log(JSON.stringify({stage:'dashboard-layout',role,page:uri,checks:report.checks.length}));save();
  }
 }
 await signIn('owner');await viewport(390);await navigate('/manage/staff/trainers');
 await click('form[data-confirm] button');check(await run(()=>document.querySelector('#dashConfirm').open),'Archive confirmation modal');await click('#dashCancel');check(await run(()=>!document.querySelector('#dashConfirm').open),'Cancel preserves record');
 await viewport(1440);await navigate('/manage/reports');
 await run(()=>{const input=document.querySelector('#months');input.value='3';input.dispatchEvent(new Event('change',{bubbles:true}));});await waitFor('document.querySelector("#chartStatus").textContent==="Report updated."');
 check(await run(()=>document.querySelectorAll('#reportTable tbody tr').length===3),'Report period updates database table');
 await run(()=>{const input=document.querySelector('#chartMetric');input.value='net';input.dispatchEvent(new Event('change',{bubbles:true}));});check(await run(()=>document.querySelector('canvas').getAttribute('aria-label').includes('net chart')),'Chart metric changes');await screenshot('owner-reports-desktop-1440');
 await signIn('client');await viewport(390);await navigate('/client/dashboard');await screenshot('client-dashboard-mobile-390');
 await navigate('/BMI.html');await run(()=>{document.querySelector('#bmiHeight').value='175';document.querySelector('#bmiWeight').value='70';document.querySelector('#bmiForm').requestSubmit();});
 check(await run(()=>document.querySelector('#bmiValue').textContent==='22.9'&&!document.querySelector('#bmiSavePanel').hidden),'Existing BMI result and approved save option');
 await click('#bmiSave');check(await run(()=>document.querySelector('#bmiSaveStatus').textContent==='Please allow saving first.'),'BMI consent enforced in interface');
 await run(()=>document.querySelector('#bmiSaveConsent').checked=true);await click('#bmiSave');await waitFor('document.querySelector("#bmiSaveStatus").textContent==="Saved to your fitness progress."');check(true,'BMI saves through Laravel');
 await navigate('/contact.html');await run(()=>{const form=document.querySelector('#contactForm');const values={fullName:'Browser Client',email:'browser-contact@example.test',phone:'123456789',subject:'Browser contact question',message:'Please explain the membership renewal process.'};for(const [key,value]of Object.entries(values))form.elements[key].value=value;form.requestSubmit();});await waitFor('document.querySelector("#formSuccess").style.display==="block"');check(true,'Contact form submits to Laravel');
 await navigate('/index.html');
 await waitFor('!!document.querySelector(".newsletter-form[data-newsletter-bound]")');
 await run(() => {
  const originalFetch = window.fetch.bind(window);
  window.fetch = async (...args) => {
   const response = await originalFetch(...args);
   if (String(args[0]).includes('/api/newsletter')) window.newsletterReply = {status:response.status};
   return response;
  };
  document.querySelector('.newsletter-form input[type="email"]').value = 'browser-newsletter@example.test';
 });
 await click('.newsletter-form button');
 await waitFor('window.newsletterReply?.status===200 && document.querySelector(".newsletter-form input").value===""');
 check(await run(() => !document.querySelector('.newsletter-form button').disabled), 'Visible newsletter form submits and resets');
 check((await api('/api/contact',{},'POST',false)).status===419,'Public submissions require CSRF');
 const ids=JSON.parse(fs.readFileSync(path.join(output,'.browser-fixture.json'),'utf8')).ids;
 await navigate('/client/ai/'+ids.ai_conversations);
 await run(()=>{const originalFetch=window.fetch.bind(window);window.fetch=async(...args)=>{const response=await originalFetch(...args);if(String(args[0]).includes('/client/ai')&&args[1]?.method==='POST'){const data=await response.clone().json().catch(()=>({}));window.historyReply={status:response.status,source:data.source||'provider',replyLength:data.reply?.length||0};}return response;};});await waitFor('document.querySelectorAll(".chat-message:not(.typing-message)").length>=2');
 check(await run(()=>document.querySelector('#storedAiHistory').textContent.includes('Hello')),'Saved AI messages loaded from database');
 await run(()=>{document.querySelector('#chatInput').value='Give one short general workout tip.';document.querySelector('#chatInput').dispatchEvent(new Event('input',{bubbles:true}));});
 await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});
 // Focus the existing input and use the same Enter interaction as the public chatbot.
 await run(()=>document.querySelector('#chatInput').focus());await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});
 await waitFor('document.querySelectorAll(".chat-message:not(.typing-message)").length>=4 && !document.querySelector(".typing-indicator")',35000);let chatResult=await run(()=>window.historyReply||{status:0,source:'client-timeout',replyLength:0});
 if(chatResult.status!==200){report.firstChatAttempt=chatResult;if(await run(()=>!!document.querySelector('.chat-retry-btn'))){await click('.chat-retry-btn');await waitFor('!document.querySelector(".typing-indicator") && !document.querySelector("#chatInput").disabled',35000);chatResult=await run(()=>window.historyReply||{status:0,source:'client-timeout',replyLength:0});}}
 report.clientHistoryReply=chatResult;check(chatResult.status===200&&chatResult.replyLength>0&&await run(()=>!document.querySelector('.chat-error')),'Client chatbot displays a successful Laravel history response');await screenshot('client-ai-mobile-390');
 await signIn('other');await navigate('/client/plans');check(await run(()=>!document.querySelector('.dash-page').textContent.includes('Browser private workout')),'Other Client cannot see personal plans');
 for(const role of ['owner','manager','client']){
  for(const width of [1440,390]){
   await signIn(role);await viewport(width);await navigate('/index.html');if(width<1200)await click('#navToggle');
   const menu=width<1200?'.navbar-auth-mobile':'.navbar-auth-desktop';await click(menu+' a[href$="/dashboard"]');await waitFor('location.pathname==="/'+role+'/dashboard" && !!document.querySelector(".dash-page")');check(true,'Navbar Dashboard route '+role+' '+width);
   await navigate('/index.html');check((await api('/logout',{},'POST',false)).status===419,'Logout CSRF '+role+' '+width);if(width<1200)await click('#navToggle');await click(menu+' .navbar-logout-form button');await waitFor('location.pathname==="/login" && !!document.querySelector("#mainNav")');check(true,'Secure navbar logout '+role+' '+width);
  }
 }
 check(report.exceptions.length===0,'No JavaScript exceptions');check(report.corsErrors.length===0,'No CORS errors');report.databasePreserved=JSON.stringify(baseline)===JSON.stringify(fixture('snapshot'));check(report.databasePreserved,'All existing database rows preserved');report.passed=true;save();console.log(JSON.stringify({passed:true,checks:report.checks.length,layouts:report.layouts.length,databasePreserved:report.databasePreserved,exceptions:report.exceptions,corsErrors:report.corsErrors},null,2));
}
main().catch(async error=>{report.passed=false;report.failure=error.message;if(ws?.readyState===1){report.failureGeometry=await run(()=>[...document.querySelectorAll('.dash-page *')].map(el=>{const r=el.getBoundingClientRect();return {tag:el.tagName,class:el.className,left:r.left,right:r.right,height:r.height};}).filter(r=>r.height>0&&(r.left < -1||r.right>document.documentElement.clientWidth+1)).slice(-30)).catch(()=>[]);await screenshot('dashboard-browser-failure').catch(()=>{});}save();console.error(error.message);process.exitCode=1;}).finally(async()=>{
 if(ws?.readyState===1){ws.send(JSON.stringify({id:++sequence,method:'Browser.close'}));ws.close();}if(browser)browser.kill();if(server)server.kill();
 for(const directory of [profile,path.join(laravel,'storage/framework/sessions/gym-verification')]){if(directory&&path.resolve(directory).startsWith(laravel+path.sep)&&fs.existsSync(directory)){await sleep(200);try{fs.rmSync(directory,{recursive:true,force:true});}catch{report.cleanupPending=true;save();}}}
});









