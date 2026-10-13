// Run: node tests/verify-reviews-browser.cjs. Requires local PHP, Chrome and MariaDB.
const fs = require('fs'), path = require('path'), http = require('http');
const cp = require('child_process');
const laravel = path.resolve(__dirname, '..'), origin = 'http://127.0.0.1:8004';
const fixtureFile = path.join(__dirname, 'reviews-browser-fixture.php');
const output = path.join(laravel, 'storage/app/private/feedback-verification');
const php = 'C:\\xampp\\php\\php.exe';
fs.mkdirSync(output, {recursive:true});
const report = {checks:[], layouts:[], exceptions:[], corsErrors:[], failures:[]};
let server, browser, ws, profile, sequence = 0, baseline;
const pending = new Map(), sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const reportFile = process.argv.includes('--interactions-only') ? 'reviews-interaction-tests.json' : 'reviews-browser-tests.json';
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
  const oldPage=await run(()=>{window.qaPageMarker=Math.random().toString(36);return {path:location.pathname,marker:window.qaPageMarker};});
  await run(selector=>{document.querySelector(selector).scrollIntoView({behavior:'instant',block:'center'});window.qaClickHit=false;document.addEventListener('click',event=>{window.qaClickHit=!!event.target.closest(selector);},{capture:true,once:true});},selector);
  await sleep(selector.startsWith('#bmi')?1400:150);
  const point=await run(selector=>{const r=document.querySelector(selector).getBoundingClientRect();return {x:r.left+r.width/2-visualViewport.offsetLeft,y:r.top+r.height/2-visualViewport.offsetTop};},selector);
  await call('Input.dispatchMouseEvent',{type:'mousePressed',...point,button:'left',clickCount:1});await call('Input.dispatchMouseEvent',{type:'mouseReleased',...point,button:'left',clickCount:1});await sleep(200);
  const after=await run(()=>({hit:window.qaClickHit,path:location.pathname,marker:window.qaPageMarker})).catch(()=>({hit:true}));const hit=after.hit||after.path!==oldPage.path||after.marker!==oldPage.marker;report.clicks??=[];report.clicks.push({selector,attempt,point,hit});if(hit)return;
 }
 throw new Error('Native click did not reach '+selector);
}async function screenshot(name){const result=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});fs.writeFileSync(path.join(output,name+'.png'),Buffer.from(result.data,'base64'));}
async function signIn(role){await call('Network.clearBrowserCookies');const cookie=fixture('session-'+role);await call('Network.setCookie',{...cookie,url:origin,httpOnly:true,sameSite:'Lax'});}
async function api(uri,payload,method='POST',csrf=true){return run(async(uri,payload,method,csrf)=>{const headers={'Content-Type':'application/json',Accept:'application/json'};if(csrf)headers['X-CSRF-TOKEN']=document.querySelector('meta[name="csrf-token"]').content;const response=await fetch(uri,{method,headers,credentials:'same-origin',body:JSON.stringify(payload)});return {status:response.status,body:await response.json().catch(()=>null)};},uri,payload,method,csrf);}
async function main(){
 fixture('prepare');baseline=fixture('snapshot');
 server=cp.spawn(php,['-S','127.0.0.1:8004','-t','public',fixtureFile],{cwd:laravel,windowsHide:true,stdio:'ignore'});
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
 const publicPages=['/index.html','/about.html','/dietplans.html','/workouts.html','/BMI.html','/fusion-ai.html','/contact.html','/feedback'];
 const groups=[['guest',publicPages],['owner',['/owner/dashboard','/manage/feedback','/manage/feedback?status=pending','/manage/accounts/managers']],['manager',['/manager/dashboard','/manage/feedback']],['client',['/client/dashboard','/client/reviews']]];
 for(const [role,pages] of groups){
  if(role==='guest')await call('Network.clearBrowserCookies');else await signIn(role);
  for(const uri of process.argv.includes('--interactions-only') ? [] : pages){
   await viewport(1440);await navigate(uri);
   await run(()=>document.querySelectorAll('details').forEach(el=>el.open=true));   // Exercise the preserved entrance animations before measuring each layout.
   const animated = await run(() => document.querySelectorAll('[data-aos]').length);
   for(let index=0; index<animated; index++) {
    await run(index => document.querySelectorAll('[data-aos]')[index].scrollIntoView({behavior:'instant',block:'center'}),index);
    await sleep(60);
   }
   await run(() => window.scrollTo({top:0,behavior:'instant'}));
   await sleep(1000);
   await run(()=>Promise.race([document.fonts.ready,new Promise(resolve=>setTimeout(resolve,4000))]));
   for(const width of [320,375,390,768,992,1024,1200,1440,1920]){
    await viewport(width);
    const layout=await run(()=>({viewport:document.documentElement.clientWidth,scrollWidth:document.documentElement.scrollWidth,fieldsFit:[...document.querySelectorAll('#reviewForm input,#reviewForm textarea')].filter(el=>el.offsetHeight>0).every(el=>{const r=el.getBoundingClientRect();return r.left>=-1&&r.right<=document.documentElement.clientWidth+1;})}));
    report.layouts.push({role,uri,width,...layout});check(layout.scrollWidth<=layout.viewport+1&&layout.fieldsFit,'Responsive '+role+' '+uri+' '+width);
    if(uri==='/feedback'&&width>=1200){
     check(await run(()=>{const brand=document.querySelector('.navbar-brand').getBoundingClientRect(),nav=document.querySelector('.navbar-nav').getBoundingClientRect(),auth=document.querySelector('.navbar-auth-desktop').getBoundingClientRect(),cta=document.querySelector('.navbar-cta').getBoundingClientRect();return brand.right<=nav.left+1&&nav.right<=Math.min(auth.left,cta.left)+1&&auth.right<=innerWidth+1;}),'Desktop navbar links fit '+width);
    }
   }
   if(role==='guest'){
    await viewport(390);await click('#navToggle');check(await run(()=>!!document.querySelector('.mobile-link[href="feedback"]')),'Mobile Feedback link '+uri);await click('.mobile-close');
   }else{
    await viewport(390);await click('#dashToggle');check(await run(()=>document.querySelector('#dashSidebar').classList.contains('open')),'Review sidebar opens '+uri);await click('#dashClose');
   }
   console.log(JSON.stringify({stage:'reviews-layout',role,page:uri,checks:report.checks.length}));save();
  }
 }
 await call('Network.clearBrowserCookies');await viewport(1440);await navigate('/feedback');
 check(await run(()=>document.querySelector('#reviewAverage').textContent==='4.1'&&document.querySelector('#reviewTotal').textContent.includes('13 approved')),'Approved average and total rendered');
 check(await run(()=>document.querySelectorAll('.review-card').length===12&&!document.body.textContent.includes('PRIVATE PENDING')&&!document.body.textContent.includes('PRIVATE REJECTED')&&!document.body.textContent.includes('PRIVATE HIDDEN')&&!document.documentElement.outerHTML.includes('@example.test')),'Public cards exclude unpublished and private account data');
 await run(()=>document.querySelector('#client-reviews').scrollIntoView({block:'start',behavior:'instant'}));await sleep(600);await screenshot('public-reviews-desktop-1440');
 await navigate('/feedback?page=2');check(await run(()=>document.querySelectorAll('.review-card').length===1),'Public pagination second page');
 await click('#writeReviewLink');await waitFor('location.pathname==="/login" && !!document.querySelector("#email")');check(true,'Guest Write a Review redirects to Login');
 await signIn('client');await viewport(390);await navigate('/client/reviews');
 check(await run(()=>document.querySelectorAll('.review-card').length===1&&!document.body.textContent.includes('PRIVATE PENDING')),'Client sees only personal review history');
 check((await api('/client/reviews',{},'POST',false)).status===419,'Review submission requires CSRF');
 await click('.review-rating-options label:last-child');await click('input[name="public_consent"]');
 await run(()=>{document.querySelector('#displayName').value='Browser Alias';document.querySelector('#reviewMessage').value='Friendly coaches helped me build a steady workout routine.';});
 check(await run(()=>document.querySelector('input[name="rating"]:checked').value==='5'&&document.querySelector('#reviewForm').checkValidity()),'Accessible star rating and consent form');
 await screenshot('client-review-form-mobile-390');await click('#reviewForm button[type="submit"]');await waitFor('!!document.querySelector(".dash-alert") && document.querySelector(".dash-alert").textContent.includes("pending approval")');check(true,'Client form submits through Laravel');
 await signIn('owner');await viewport(390);await navigate('/manage/feedback?status=pending');
 check(await run(()=>document.querySelectorAll('form.review-moderation').length===1&&document.body.textContent.includes('PRIVATE PENDING')),'Owner pending filter');
 await click('form.review-moderation button');await waitFor('document.querySelector("#dashConfirm").open');await click('#dashProceed');await waitFor('!!document.querySelector(".dash-alert") && document.querySelector(".dash-alert").textContent.includes("Feedback status updated")');check(true,'Owner approval confirmation and secure form');await screenshot('owner-feedback-mobile-390');
 await signIn('restricted');await navigate('/manager/dashboard');check(await run(()=>![...document.querySelectorAll('#dashSidebar a')].some(a=>a.textContent.includes('Feedback Management'))),'Unapproved Manager has no moderation link');
 const denial=await run(async()=>{const response=await fetch('/manage/feedback',{headers:{Accept:'application/json'}});return response.status;});check(denial===403,'Unapproved Manager backend restriction');
 await signIn('manager');await navigate('/manage/feedback');check(await run(()=>!!document.querySelector('form.review-moderation')),'Approved Manager moderation screen');
 await run(()=>document.querySelector('form.review-moderation select').value='hidden');await click('form.review-moderation button');await waitFor('document.querySelector("#dashConfirm").open');await click('#dashProceed');await waitFor('!!document.querySelector(".dash-alert") && document.querySelector(".dash-alert").textContent.includes("Feedback status updated")');check(true,'Approved Manager secure moderation form');
 for (const role of ['owner','manager','client']) {
  await signIn(role);await navigate('/feedback');
  for (const width of [1200,1440,1920]) {
   await viewport(width);
   check(await run(()=>{const brand=document.querySelector('.navbar-brand').getBoundingClientRect(),nav=document.querySelector('.navbar-nav').getBoundingClientRect(),auth=document.querySelector('.navbar-auth-desktop').getBoundingClientRect(),cta=document.querySelector('.navbar-cta').getBoundingClientRect();return brand.right<=nav.left+1&&nav.right<=Math.min(auth.left,cta.left)+1&&auth.right<=innerWidth+1;}),'Authenticated navbar fits '+role+' '+width);
  }
  await viewport(390);await click('#navToggle');
  check(await run(()=>!!document.querySelector('.navbar-auth-mobile a[href$="/dashboard"]')&&!!document.querySelector('.mobile-link[href="feedback"]')),'Authenticated mobile navigation '+role);
  await click('.navbar-auth-mobile .navbar-logout-form button');await waitFor('location.pathname==="/login"');check(true,'Public Feedback secure logout '+role);
 }
 check(report.exceptions.length===0,'No JavaScript exceptions');check(report.corsErrors.length===0,'No CORS errors');report.databasePreserved=JSON.stringify(baseline)===JSON.stringify(fixture('snapshot'));check(report.databasePreserved,'Browser tests preserve existing database rows');report.passed=true;save();console.log(JSON.stringify({passed:true,checks:report.checks.length,layouts:report.layouts.length,databasePreserved:report.databasePreserved,exceptions:report.exceptions,corsErrors:report.corsErrors},null,2));
}
main().catch(async error=>{report.passed=false;report.failure=error.message;if(ws?.readyState===1){report.failureGeometry=await run(()=>[...document.querySelectorAll('body *')].map(el=>{const r=el.getBoundingClientRect();return {tag:el.tagName,class:el.className,left:r.left,right:r.right,height:r.height};}).filter(r=>r.height>0&&(r.left < -1||r.right>document.documentElement.clientWidth+1)).slice(-30)).catch(()=>[]);await screenshot('reviews-browser-failure').catch(()=>{});}save();console.error(error.message);process.exitCode=1;}).finally(async()=>{
 if(ws?.readyState===1){ws.send(JSON.stringify({id:++sequence,method:'Browser.close'}));ws.close();}if(browser)browser.kill();if(server)server.kill();
 for(const directory of [profile,path.join(laravel,'storage/framework/sessions/review-verification')]){if(directory&&path.resolve(directory).startsWith(laravel+path.sep)&&fs.existsSync(directory)){await sleep(200);try{fs.rmSync(directory,{recursive:true,force:true});}catch{report.cleanupPending=true;save();}}}
});














