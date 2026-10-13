// Run: node tests/verify-auth-browser.cjs (Laravel :8000 and original Node :3000).
const fs = require('fs'), path = require('path'), http = require('http');
const cp = require('child_process'), crypto = require('crypto');
const laravel = path.resolve(__dirname, '..'), origin = 'http://127.0.0.1:8003';
const output = path.join(laravel, 'storage/app/private/phase4-4-verification');
fs.mkdirSync(output, {recursive:true});
const report = {pages:[],layouts:[],checks:[],exceptions:[],corsErrors:[]};
const email = 'browser-'+crypto.randomUUID()+'@example.test';
let password = 'Aa1!'+crypto.randomBytes(12).toString('hex');
let server, browser, ws, id=0, pending=new Map(), profile;
const sleep = ms => new Promise(resolve => setTimeout(resolve,ms));
const save = () => fs.writeFileSync(path.join(output,'browser-tests.json'),JSON.stringify(report,null,2));
function check(value,name){if(!value)throw new Error('Check failed: '+name);report.checks.push(name);}
function get(url,cookies=''){return new Promise((resolve,reject)=>{const req=http.get(url,{agent:false,headers:cookies?{Cookie:cookies}:{}},res=>{let body='';res.on('data',chunk=>body+=chunk);res.on('end',()=>resolve({status:res.statusCode,body,location:res.headers.location}));});req.on('error',reject);req.setTimeout(35000,()=>req.destroy(new Error('HTTP timeout')));});}
function call(method,params={}){return new Promise((resolve,reject)=>{const requestId=++id;const timer=setTimeout(()=>{pending.delete(requestId);reject(new Error('Browser command timeout: '+method));},25000);pending.set(requestId,{resolve:value=>{clearTimeout(timer);resolve(value);},reject:error=>{clearTimeout(timer);reject(error);}});ws.send(JSON.stringify({id:requestId,method,params}));});}
async function evaluate(expression){const result=await call('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(result.exceptionDetails)throw new Error(result.exceptionDetails.text);return result.result.value;}
const run=(fn,...args)=>evaluate('('+fn.toString()+')(...'+JSON.stringify(args)+')');
async function waitFor(expression,timeout=20000){const started=Date.now();while(Date.now()-started<timeout){if(await evaluate(expression))return;await sleep(100);}report.waitFailure=await run(()=>({uri:location.pathname,readyState:document.readyState,navbar:!!document.querySelector('#mainNav'),footer:!!document.querySelector('#footer'),title:document.title}));throw new Error('Browser condition timed out');}
async function navigate(uri){await call('Page.stopLoading');await call('Page.navigate',{url:uri.startsWith('http')?uri:origin+uri});await waitFor('document.readyState!=="loading" && !!document.querySelector("#mainNav") && !!document.querySelector("#footer")');await sleep(200);}
async function viewport(width){const height=width<768?844:900;await call('Emulation.setDeviceMetricsOverride',{width,height,screenWidth:width,screenHeight:height,deviceScaleFactor:1,mobile:width<768});await sleep(1000);}
async function fill(values){await run(values=>{for(const[name,value]of Object.entries(values)){const input=document.querySelector('[name="'+name+'"]');input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));}},values);}
async function submit(){await run(()=>document.querySelector('.auth-form button[type="submit"], .dash-topbar form button').click());}
async function cookies(){return (await call('Network.getCookies',{urls:[origin]})).cookies;}
function fixture(action){const result=cp.spawnSync('C:\\xampp\\php\\php.exe',[path.join(__dirname,'auth-browser-fixture.php'),email,action],{cwd:laravel,windowsHide:true,encoding:'utf8'});if(result.status!==0)throw new Error('Browser fixture operation failed');return JSON.parse(result.stdout);}
async function api(uri,payload,method='POST',csrf=true){return run(async(uri,payload,method,csrf)=>{const headers={'Content-Type':'application/json','Accept':'application/json'};if(csrf)headers['X-CSRF-TOKEN']=document.querySelector('meta[name="csrf-token"]').content;const response=await fetch(uri,{method,headers,credentials:'same-origin',body:JSON.stringify(payload)});return {status:response.status,body:await response.json().catch(()=>null)};},uri,payload,method,csrf);}
async function screenshot(name){const result=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});fs.writeFileSync(path.join(output,name+'.png'),Buffer.from(result.data,'base64'));}
async function main(){
 server=cp.spawn('C:\\xampp\\php\\php.exe',['-S','127.0.0.1:8003','-t','.',path.join(laravel,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],{cwd:path.join(laravel,'public'),windowsHide:true,stdio:'ignore'});
 for(let attempt=0;attempt<100;attempt++){try{if((await get(origin+'/login')).status===200)break;}catch{}await sleep(200);}
 for(const uri of ['/','/index.html','/about.html','/dietplans.html','/workouts.html','/BMI.html','/fusion-ai.html','/contact.html','/login','/register','/forgot-password','/reset-password/test-token']){const result=await get(origin+uri);report.pages.push({uri,status:result.status});check(result.status===200,'HTTP 200 '+uri);}
 profile=fs.mkdtempSync(path.join(output,'chrome-profile-'));
 browser=cp.spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:'ignore'});
 const active=path.join(profile,'DevToolsActivePort');for(let attempt=0;attempt<150&&!fs.existsSync(active);attempt++)await sleep(100);check(fs.existsSync(active),'Headless Chrome started');
 let port;for(let attempt=0;attempt<100;attempt++){try{port=fs.readFileSync(active,'utf8').split('\n')[0];if(Number(port)>0)break;}catch{}await sleep(100);}check(!!Number(port),'Chrome debug port available');const targets=JSON.parse((await get('http://127.0.0.1:'+port+'/json/list')).body);
 ws=new WebSocket(targets.find(target=>target.type==='page').webSocketDebuggerUrl);
 await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
 ws.addEventListener('message',event=>{const message=JSON.parse(event.data);if(message.id){const task=pending.get(message.id);if(task){pending.delete(message.id);message.error?task.reject(new Error(message.error.message)):task.resolve(message.result);}return;}
  if(message.method==='Runtime.exceptionThrown')report.exceptions.push(message.params.exceptionDetails.text);
  if(message.method==='Network.loadingFailed'&&message.params.corsErrorStatus)report.corsErrors.push(message.params.corsErrorStatus.corsError);
 });
 await call('Page.enable');await call('Runtime.enable');await call('Network.enable');
 const pages=['/index.html','/about.html','/dietplans.html','/workouts.html','/BMI.html','/fusion-ai.html','/contact.html','/login','/register','/forgot-password','/reset-password/test-token'];
 for(const uri of process.argv.includes('--interactions-only')?[]:pages){
  await viewport(1440);await navigate(uri);
  // Scroll through the real sections so existing once-only AOS animations can finish.
  const animated=await run(()=>document.querySelectorAll('[data-aos]').length);
  for(let index=0;index<animated;index++){await run(index=>document.querySelectorAll('[data-aos]')[index].scrollIntoView({behavior:'instant',block:'center'}),index);await sleep(60);}
  await run(()=>window.scrollTo({top:0,left:0,behavior:'instant'}));await sleep(1100);
  for(const width of [320,375,390,768,992,1024,1440,1920]){
   await viewport(width);const layout=await run(()=>{const width=document.documentElement.clientWidth,inputs=[...document.querySelectorAll('.auth-form input:not([type="hidden"]), .auth-form button')].map(el=>el.getBoundingClientRect());return {viewport:width,scrollWidth:document.documentElement.scrollWidth,formFits:inputs.every(rect=>rect.left>=-1&&rect.right<=width+1),navbar:!!document.querySelector('#mainNav'),footer:!!document.querySelector('#footer')};});
   report.layouts.push({uri,width,...layout});check(layout.scrollWidth<=layout.viewport+1&&layout.formFits,'Responsive layout '+uri+' '+width);
  }
  await viewport(390);
  check(await run(()=>{document.querySelector('#navToggle').click();const open=document.querySelector('#navbarMobile').classList.contains('active')&&document.querySelector('#navToggle').getAttribute('aria-expanded')==='true';document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));return open&&!document.querySelector('#navbarMobile').classList.contains('active')&&document.activeElement===document.querySelector('#navToggle');}),'Mobile navbar keyboard '+uri);
  if(uri==='/login'){await viewport(390);await screenshot('login-mobile-390');}
  if(uri==='/register'){await viewport(1440);await screenshot('register-desktop-1440');}
  console.log(JSON.stringify({stage:'layouts',page:uri,checks:report.checks.length}));save();
 }
 await viewport(390);await navigate('/login');
 const cookieBefore=(await cookies()).find(cookie=>cookie.name==='fitfusion_laravel_session');
 check(cookieBefore.httpOnly&&cookieBefore.sameSite==='Lax','HttpOnly SameSite session cookie');
 check((await api('/login',{email,password},'POST',false)).status===419,'Login CSRF protection');
 await navigate('/register');
 await fill({name:'Browser Verification',email,password,password_confirmation:password});await submit();await waitFor('location.pathname==="/client/dashboard"');await waitFor('document.readyState!=="loading"');
 check(await run(()=>document.querySelector('.dash-role')?.textContent.includes('Client')),'Browser client registration');
 const cookieRegistered=(await cookies()).find(cookie=>cookie.name==='fitfusion_laravel_session');
 check(cookieRegistered.value!==cookieBefore.value,'Registration rotates session cookie');
 const ownProfile=await run(async()=>{const response=await fetch('/client/profile',{headers:{Accept:'application/json'}});const data=await response.json();return {status:response.status,hasProfile:!!data.profile?.user_id};});
 check(ownProfile.status===200&&ownProfile.hasProfile,'Browser authenticated profile');
 check((await api('/owner/managers',{name:'Unauthorized manager',email:'unauthorized@example.test',password,password_confirmation:password})).status===403,'Client cannot create managers');
 await submit();await waitFor('location.pathname==="/login"');await waitFor('document.readyState!=="loading"');
 const cookieLoggedOut=(await cookies()).find(cookie=>cookie.name==='fitfusion_laravel_session');
 check(cookieLoggedOut.value!==cookieRegistered.value,'Logout rotates session cookie');
 await navigate('/register');check((await api('/register',{name:'Browser Verification',email:'forged-'+email,password,password_confirmation:password,role:'owner'})).status===422,'Public role injection rejected');
 await navigate('/login');
 await fill({email,password});await run(()=>document.querySelector('#password').focus());await call('Input.dispatchKeyEvent',{type:'rawKeyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});await call('Input.dispatchKeyEvent',{type:'char',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,text:'\r',unmodifiedText:'\r'});await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});await waitFor('location.pathname==="/client/dashboard"');await waitFor('document.readyState!=="loading"');
 check(true,'Browser client login');
 const oldSession=(await cookies()).map(cookie=>cookie.name+'='+cookie.value).join('; ');
 check((await get(origin+'/account',oldSession)).status===200,'Earlier authenticated session works before reset');
 await call('Network.clearBrowserCookies');
 console.log('Stage: password-reset navigation');await navigate('/forgot-password');await fill({email});await submit();await waitFor('!!document.querySelector(".auth-status")');
 check(await run(()=>document.querySelector('.auth-status').textContent.includes('If an active account')),'Browser forgot password neutral response');
 const previewFolder=path.join(laravel,'storage/app/private/.password-resets');
 const previewFile=fs.readdirSync(previewFolder).find(name=>name.endsWith('.json')&&JSON.parse(fs.readFileSync(path.join(previewFolder,name),'utf8')).to===email);
 const preview=previewFile&&JSON.parse(fs.readFileSync(path.join(previewFolder,previewFile),'utf8'));
 check(!!preview,'Private reset email preview generated');
 check((await get('http://127.0.0.1:3000/laravel/storage/app/private/.password-resets/'+previewFile)).status===404,'Original Express cannot serve the private reset preview');
 check([403,404].includes((await get(origin+'/storage/.password-resets/'+previewFile)).status),'Laravel cannot serve the private reset preview');
 check((await get(origin+'/.env')).status===404&&(await get('http://127.0.0.1:3000/laravel/.env')).status===404,'Environment credentials cannot be served');
 await navigate(new URL(preview.url).pathname+new URL(preview.url).search);
 check(await run(()=>document.querySelector('input[name="token"]').value.length>20&&document.querySelector('#email').value!==''),'Reset form populated');
 const newPassword='Bb2!'+crypto.randomBytes(12).toString('hex');
 await fill({password:newPassword,password_confirmation:newPassword});await submit();await waitFor('location.pathname==="/login" && !!document.querySelector(".auth-status")');
 check(true,'Browser password reset');
 check((await get(origin+'/account',oldSession)).status===302,'Password reset invalidates the earlier authenticated session');
 await fill({email,password});await submit();await waitFor('!!document.querySelector(".auth-errors")');check(true,'Old password rejected in browser');
 password=newPassword;await fill({email,password});await submit();await waitFor('location.pathname==="/client/dashboard"');await waitFor('document.readyState!=="loading"');
 check(true,'New password logs in through browser');
 fixture('deactivate');await navigate('/account');
 check(await run(()=>location.pathname==='/login'&&!!document.querySelector('.auth-errors')),'Inactive account logs out an existing browser session');
 fixture('activate');await call('Network.clearBrowserCookies');
 await navigate('/BMI.html');
 check(await run(()=>{document.querySelector('#bmiHeight').value='180';document.querySelector('#bmiWeight').value='75';document.querySelector('#bmiForm').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));return document.querySelector('#bmiValue').textContent==='23.1'&&document.querySelector('#bmiCategory').textContent==='Normal Weight';}),'Existing BMI calculator result');
 await navigate('/workouts.html');await waitFor('document.querySelectorAll(".workout-card").length===57');
 check(await run(()=>{const button=document.querySelector('.workout-filter-btn:not([data-target="all"])');button.click();const sections=[...document.querySelectorAll('.workout-category-section')];const filtered=sections.filter(section=>section.style.display!=='none');document.querySelector('[data-target="all"]').click();return filtered.length===1&&filtered[0].id===button.dataset.target&&sections.every(section=>section.style.display==='block');}),'Existing workout category filters');
 await navigate('/dietplans.html');await waitFor('document.querySelectorAll(".diet-card").length===8');check(true,'All existing diet cards render');
 await navigate('/fusion-ai.html');
 const live=await api('/api/chat',{messages:[{role:'user',content:'Give one short general workout tip for a healthy beginner.'}]});
 report.ai={laravel:{status:live.status,source:live.body?.source||'provider',replyLength:live.body?.reply?.length||0}};
 check(live.status===200&&!!live.body?.reply&&!live.body.source,'Laravel real Groq response');
 const node=await run(async()=>{const response=await fetch('http://127.0.0.1:3000/api/chat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({messages:[{role:'user',content:'Give one short general workout tip for a healthy beginner.'}]})});const data=await response.json();return {status:response.status,source:data.source||'provider',replyLength:data.reply?.length||0};});
 report.ai.node=node;check(node.status===200&&node.replyLength>0&&node.source==='provider','Original Node real provider response');
 check(report.exceptions.length===0,'No browser JavaScript exceptions');check(report.corsErrors.length===0,'No browser CORS errors');
 report.passed=true;report.completedAt=new Date().toISOString();save();
 console.log(JSON.stringify({passed:true,checks:report.checks.length,layouts:report.layouts.length,ai:report.ai,corsErrors:report.corsErrors,exceptions:report.exceptions},null,2));
}
main().catch(async error=>{if(ws&&ws.readyState===1){report.failureGeometry=await run(()=>[...document.querySelectorAll('body *')].map(el=>{const r=el.getBoundingClientRect(),s=getComputedStyle(el);return {tag:el.tagName,id:el.id,class:el.className,left:r.left,right:r.right,height:r.height,position:s.position,transform:s.transform};}).filter(r=>r.height>0&&(r.left < -1||r.right > document.documentElement.clientWidth+1)).slice(0,20)).catch(()=>[]);await screenshot('browser-failure').catch(()=>{});}report.passed=false;report.failure=error.message;save();console.error(error.message);process.exitCode=1;}).finally(async()=>{
 try{report.fixtureCleanup=fixture('cleanup');save();}catch(error){console.error('Test account cleanup needs attention.');process.exitCode=1;}
 if(ws&&ws.readyState===1){await call('Browser.close').catch(()=>{});ws.close();}
 if(browser)browser.kill(); if(server)server.kill();
});





