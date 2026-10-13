// Run: node tests/verify-navbar-browser.cjs. Requires local PHP, Chrome and MariaDB.
const fs = require('fs'), path = require('path'), http = require('http');
const cp = require('child_process');
const laravel = path.resolve(__dirname, '..'), origin = 'http://127.0.0.1:8001';
const fixtureFile = path.join(__dirname, 'navbar-browser-fixture.php');
const output = path.join(laravel, 'storage/app/private/navbar-verification');
const php = 'C:\\xampp\\php\\php.exe';
fs.mkdirSync(output, {recursive:true});
const report = {checks:[], layouts:[], exceptions:[], corsErrors:[], failures:[]};
let server, browser, ws, profile, sequence = 0, baseline;
const pending = new Map(), sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
const save = () => fs.writeFileSync(path.join(output, 'browser-tests.json'), JSON.stringify(report, null, 2));
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
 await call('Page.navigate',{url:origin+uri});
 await waitFor('document.readyState!=="loading" && !!document.querySelector("#mainNav") && !!document.querySelector("#footer")');
 await sleep(150);
}
async function viewport(width,height=width<768?844:900) {
 await call('Emulation.setDeviceMetricsOverride',{width,height,screenWidth:width,screenHeight:height,deviceScaleFactor:1,mobile:width<768});
 await sleep(300);
}
async function click(selector) {
 await run(()=>{window.navbarClickEvents=[]; document.addEventListener('click',event=>window.navbarClickEvents.push({x:event.clientX,y:event.clientY,tag:event.target.tagName,class:event.target.className}),{once:true,capture:true});}); const point=await run(selector=>{
  const element=document.querySelector(selector); element.scrollIntoView({behavior:'instant',block:'center'});
  const rect=element.getBoundingClientRect(); return {x:rect.left+rect.width/2-visualViewport.offsetLeft,y:rect.top+rect.height/2-visualViewport.offsetTop};
 },selector);
 const before=await run(point=>({hit:document.elementFromPoint(point.x+visualViewport.offsetLeft,point.y+visualViewport.offsetTop)?.className,scale:visualViewport.scale,width:visualViewport.width,height:visualViewport.height,offsetTop:visualViewport.offsetTop,scrollY:scrollY}),point); await call('Input.dispatchMouseEvent',{type:'mouseMoved',...point}); await call('Input.dispatchMouseEvent',{type:'mousePressed',...point,button:'left',clickCount:1});
 await call('Input.dispatchMouseEvent',{type:'mouseReleased',...point,button:'left',clickCount:1}); await sleep(100); report.clicks??=[]; report.clicks.push({selector,point,before,events:await run(()=>window.navbarClickEvents).catch(()=>[])});
}
async function screenshot(name, mobile=false) {
 const clip=await run(mobile=>{
  const rect=document.querySelector(mobile?'#navbarMobile':'#mainNav').getBoundingClientRect();
  return {x:0,y:0,width:Math.min(rect.width,innerWidth),height:Math.min(rect.height,innerHeight),scale:1};
 },mobile);
 const image=await call('Page.captureScreenshot',{format:'png',clip,captureBeyondViewport:false});
 fs.writeFileSync(path.join(output,name+'.png'),Buffer.from(image.data,'base64'));
}
async function signInFixture() {
 await call('Network.clearBrowserCookies');
 const cookie=fixture('session');
 await call('Network.setCookie',{...cookie,url:origin,httpOnly:true,sameSite:'Lax'});
}
async function checkLayouts(signedIn) {
 const pages=['/index.html','/about.html','/dietplans.html','/workouts.html','/BMI.html','/fusion-ai.html','/contact.html'];
 const expected=signedIn?['Dashboard','Logout']:['Login','Register'];
 for(const uri of pages) {
  await viewport(1440); await navigate(uri);
  for(const width of [320,375,390,768,992,1024,1200,1280,1440,1920]) {
   await viewport(width);
   if(width<1200) await click('#navToggle');
   const layout=await run(width=>{
    const root=document.querySelector(width<1200?'#navbarMobile':'#mainNav');
    const actions=root.querySelector('.navbar-auth-actions');
    if(width<1200) actions.scrollIntoView({behavior:'instant',block:'end'});
    const buttons=[...actions.querySelectorAll('.navbar-auth-link')];
    const within=element=>{const r=element.getBoundingClientRect();return r.width>0&&r.height>=43&&r.left>=-1&&r.right<=innerWidth+1&&r.top>=-1&&r.bottom<=innerHeight+1;};
    const blocks=[...document.querySelector('#mainNav .navbar-container').children].filter(element=>getComputedStyle(element).display!=='none').map(element=>{const r=element.getBoundingClientRect();return {name:element.className,left:r.left,right:r.right};});
    return {names:buttons.map(element=>element.textContent.trim()),buttonsFit:buttons.every(within),
     desktopFits:blocks.every((r,i)=>r.left>=-1&&r.right<=innerWidth+1&&(i===0||r.left>=blocks[i-1].right-1)),
     originalLinks:[...root.querySelectorAll(width<1200?'a.mobile-link, a.mobile-sublink':'a.nav-link, a.nav-dropdown-link')].map(element=>new URL(element.href).pathname),
     cta:!!root.querySelector(width<1200?'.mobile-cta':'.navbar-cta'),
     logoutMethod:actions.querySelector('form')?.method,logoutPath:actions.querySelector('form')?new URL(actions.querySelector('form').action).pathname:null,
     hasCsrf:!!actions.querySelector('input[name="_token"]'),blocks};
   },width);
   report.layouts.push({uri,width,state:signedIn?'authenticated':'guest',...layout});
   check(JSON.stringify(layout.names)===JSON.stringify(expected),'Correct auth actions '+uri+' '+width+' '+signedIn);
   check(layout.buttonsFit && (width<1200||layout.desktopFits),'Navbar fits '+uri+' '+width+' '+signedIn);
   const expectedNavigation = [...pages, '/feedback'];
   check(layout.originalLinks.length===8 && layout.cta,'Original links and Get Started preserved '+uri+' '+width+' '+signedIn);
   check(JSON.stringify([...layout.originalLinks].sort())===JSON.stringify(expectedNavigation.sort()),'Original navigation URLs preserved '+uri+' '+width+' '+signedIn);
   if(width>=1200) {
    check(await run(()=>{const button=document.querySelector('.nav-dropdown-toggle'),menu=button?.parentElement.querySelector('.nav-dropdown-menu');if(!button||!menu)return false;button.click();const opened=button.getAttribute('aria-expanded')==='true'&&getComputedStyle(menu).display!=='none';button.click();return opened&&button.getAttribute('aria-expanded')==='false'&&getComputedStyle(menu).display==='none';}),'Desktop dropdown toggles '+uri+' '+width+' '+signedIn);
   } else {
    check(await run(()=>{const button=document.querySelector('.mobile-group-toggle'),group=button?.closest('.mobile-nav-group');if(!button||!group)return false;const initial=group.classList.contains('open');button.click();const changed=group.classList.contains('open')!==initial;button.click();return changed&&group.classList.contains('open')===initial&&button.getAttribute('aria-expanded')===String(initial);}),'Mobile grouped menu toggles '+uri+' '+width+' '+signedIn);
   }
   if(signedIn) check(layout.logoutMethod==='post'&&layout.logoutPath==='/logout'&&layout.hasCsrf,'Secure logout form '+uri+' '+width);
   if(width<1200) {
    check(await run(()=>{
     const menu=document.querySelector('#navbarMobile'),first=menu.querySelector('.mobile-close');
     const items=[...menu.querySelectorAll('button,a[href]')], last=items[items.length-1];last.focus();
     document.dispatchEvent(new KeyboardEvent('keydown',{key:'Tab',bubbles:true}));const trapped=document.activeElement===first;
     document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
     return trapped&&!menu.classList.contains('active')&&document.activeElement===document.querySelector('#navToggle');
    }),'Mobile keyboard navigation '+uri+' '+width+' '+signedIn);
   }
  }
  console.log(JSON.stringify({stage:'navbar-layouts',page:uri,state:signedIn?'authenticated':'guest',checks:report.checks.length}));save();
 }
}
async function main() {
 baseline=fixture('snapshot');
 check(baseline.tables===35,'Existing 35-table database');
 // The second server isolates sessions and rolls back every database transaction.
 server=cp.spawn(php,['-S','127.0.0.1:8001','-t',path.join(laravel,'public'),fixtureFile],{cwd:laravel,windowsHide:true,stdio:'ignore'});
 for(let attempt=0;attempt<100;attempt++){if((await get(origin+'/').catch(()=>null))?.status===200)break;await sleep(100);}
 check((await get(origin+'/')).status===200,'Local transaction-backed test server starts');
 profile=fs.mkdtempSync(path.join(output,'chrome-profile-'));
 browser=cp.spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:'ignore'});
 const active=path.join(profile,'DevToolsActivePort');
 for(let attempt=0;attempt<150&&!fs.existsSync(active);attempt++)await sleep(100);
 check(fs.existsSync(active),'Headless Chrome starts');
 const port=fs.readFileSync(active,'utf8').split('\n')[0];
 const targets=JSON.parse((await get('http://127.0.0.1:'+port+'/json/list')).body);
 ws=new WebSocket(targets.find(target=>target.type==='page').webSocketDebuggerUrl);
 await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
 ws.addEventListener('message',event=>{
  const message=JSON.parse(event.data);
  if(message.id){const task=pending.get(message.id);if(task){pending.delete(message.id);message.error?task.reject(new Error(message.error.message)):task.resolve(message.result);}return;}
  if(message.method==='Runtime.exceptionThrown')report.exceptions.push(message.params.exceptionDetails.text);
  if(message.method==='Network.loadingFailed'&&message.params.corsErrorStatus)report.corsErrors.push(message.params.corsErrorStatus.corsError);
 });
 await call('Page.enable');await call('Runtime.enable');await call('Network.enable');await call('Network.setCacheDisabled',{cacheDisabled:true});
 if(!process.argv.includes('--navigation-only')) await checkLayouts(false);
 // Guest links open the existing authentication pages on desktop and mobile.
 for(const width of [1440,390]) {
  await viewport(width);await navigate('/index.html');if(width<1200)await click('#navToggle');
  const menu=width<1200?'.navbar-auth-mobile':'.navbar-auth-desktop';
   await click(menu+' a[href$="/login"]');await waitFor('location.pathname==="/login" && !!document.querySelector("#mainNav")');
   check(true,'Guest Login link '+width);
   await viewport(width);await navigate('/index.html');if(width<1200)await click('#navToggle');
   await click(menu+' a[href$="/register"]');await waitFor('location.pathname==="/register" && !!document.querySelector("#mainNav")');
  check(true,'Guest Register link '+width);
 }
 await signInFixture();if(!process.argv.includes('--navigation-only')) await checkLayouts(true);
 // Fallback navigation also retains Laravel-supplied authentication actions.
 for(const signedIn of [true,false]) {
  if(!signedIn)await call('Network.clearBrowserCookies');
  await viewport(1440);await navigate('/index.html');await run(()=>renderFallbackNavbar());
  check(await run(signedIn=>{
   const expected=signedIn?'Dashboard,Logout':'Login,Register';
   return [...document.querySelectorAll('.navbar-auth-actions')].every(group=>[...group.querySelectorAll('.navbar-auth-link')].map(link=>link.textContent.trim()).join(',')===expected);
  },signedIn),'Fallback auth actions '+signedIn);
 }
 await viewport(1440);await navigate('/index.html');await screenshot('guest-desktop-1440');
 await viewport(390);await click('#navToggle');await screenshot('guest-mobile-390',true);
 // Use the actual POST route and CSRF middleware; the test server rolls back DB writes.
 for(const width of [1440,390]) {
  await signInFixture();await viewport(width);await navigate('/index.html');if(width<1200)await click('#navToggle');
  const menu=width<1200?'.navbar-auth-mobile':'.navbar-auth-desktop';
   await click(menu+' a[href$="/dashboard"]');await waitFor('location.pathname.endsWith("/dashboard") && !!document.querySelector("#dashSidebar")');
   check(true,'Dashboard opens existing authenticated landing page '+width);
   const cookie=(await call('Network.getCookies',{urls:[origin]})).cookies.find(cookie=>cookie.name==='fitfusion_laravel_session');
   const before=await get(origin+'/owner/dashboard',cookie.name+'='+cookie.value);
  check(before.status===200,'Authenticated session works before logout '+width);
  check(await run(async()=>{
   const response=await fetch('/logout',{method:'POST',headers:{Accept:'application/json'},credentials:'same-origin'});
   return response.status===419;
  }),'Logout rejects missing CSRF token '+width);
  check(await run(async()=>{const response=await fetch('/logout',{headers:{Accept:'application/json'}});return response.status===405;}),'Logout disallows GET '+width);
   await navigate('/index.html');if(width<1200)await click('#navToggle');
  await screenshot('authenticated-'+(width<1200?'mobile-390':'desktop-1440'),width<1200);
  await click(menu+' .navbar-logout-form button');
  await waitFor('location.pathname==="/login" && !!document.querySelector("#mainNav")');
  check(await run(()=>[...document.querySelectorAll('.navbar-auth-desktop .navbar-auth-link')].map(link=>link.textContent.trim()).join(',')==='Login,Register'),'Navbar returns to guest actions after logout '+width);
  check((await get(origin+'/owner/dashboard',cookie.name+'='+cookie.value)).status===302,'Old authenticated session invalidated '+width);
  await navigate('/owner/dashboard');check(await run(()=>location.pathname==='/login'),'Dashboard redirects to login after logout '+width);
 }
 await viewport(390,400);await navigate('/index.html');await click('#navToggle');
 await run(()=>document.querySelector('.navbar-auth-mobile').scrollIntoView({behavior:'instant',block:'end'})); await sleep(300); report.shortViewport=await run(()=>{const menu=document.querySelector('#navbarMobile'),rect=document.querySelector('.navbar-auth-mobile').getBoundingClientRect();return {scrollHeight:menu.scrollHeight,clientHeight:menu.clientHeight,top:rect.top,bottom:rect.bottom,innerHeight,visualTop:visualViewport.offsetTop,visualHeight:visualViewport.height};});
 check(await run(()=>{
  const menu=document.querySelector('#navbarMobile'),rect=document.querySelector('.navbar-auth-mobile').getBoundingClientRect();
  return menu.scrollHeight>menu.clientHeight&&rect.top>=0&&rect.bottom<=innerHeight;
 }),'Auth actions reachable in short mobile viewport');
 check(report.exceptions.length===0,'No browser JavaScript exceptions');
 check(report.corsErrors.length===0,'No browser CORS errors');
 report.databasePreserved=JSON.stringify(baseline)===JSON.stringify(fixture('snapshot'));
 check(report.databasePreserved,'All 35 database tables retain identical stored rows');
 report.passed=true;report.completedAt=new Date().toISOString();save();
 console.log(JSON.stringify({passed:true,checks:report.checks.length,layouts:report.layouts.length,failed:report.failures.length,databasePreserved:report.databasePreserved,exceptions:report.exceptions,corsErrors:report.corsErrors},null,2));
}
main().catch(error=>{
 report.passed=false;report.failure=error.stack||error.message;save();console.error(error.stack||error.message);process.exitCode=1;
}).finally(async()=>{
 if(ws&&ws.readyState===1){ws.send(JSON.stringify({id:++sequence,method:'Browser.close'}));ws.close();}
 if(browser)browser.kill();if(server)server.kill();
 for(const directory of [profile,path.join(laravel,'storage/framework/sessions/navbar-verification')]){
  if(directory&&path.resolve(directory).startsWith(laravel+path.sep)&&fs.existsSync(directory)){
   await sleep(200);try{fs.rmSync(directory,{recursive:true,force:true});}catch{report.cleanupPending=true;save();}
  }
 }
});
