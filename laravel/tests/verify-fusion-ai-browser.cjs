const fs = require('fs');
const path = require('path');
const http = require('http');
const cp = require('child_process');
const laravel = path.resolve(__dirname, '..');
const output = path.join(laravel, process.env.FITFUSION_VERIFICATION_DIR || 'storage/app/private/phase4-3-verification');
fs.mkdirSync(output, {recursive:true});
const report = {pages:[], layouts:[], features:{}, apiCalls:[], exceptions:[], consoleErrors:[], networkFailures:[]};
const save = () => fs.writeFileSync(path.join(output, 'browser-tests.json'), JSON.stringify(report,null,2));
const sleep = ms => new Promise(resolve => setTimeout(resolve,ms));
let browser, ws, sequence=0, pending=new Map(), requests=new Map(), currentPage='';
function get(url) { return new Promise((resolve,reject) => { const req=http.get(url,{agent:false},res=>{const chunks=[];res.on('data',chunk=>chunks.push(chunk));res.on('end',()=>resolve({status:res.statusCode,body:Buffer.concat(chunks)}));});req.on('error',reject);req.setTimeout(35000,()=>req.destroy(new Error('HTTP timeout'))); }); }
function call(method,params={}) { return new Promise((resolve,reject)=>{const id=++sequence;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));}); }
async function evaluate(expression) { const result=await call('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(result.exceptionDetails)throw new Error(result.exceptionDetails.exception?.description||result.exceptionDetails.text);return result.result.value; }
const run = (fn,...args) => evaluate('('+fn.toString()+')(...'+JSON.stringify(args)+')');
async function waitFor(expression,timeout=18000) { const started=Date.now();while(Date.now()-started<timeout){if(await evaluate(expression))return;await sleep(100);}throw new Error('Browser condition timed out'); }
async function viewport(width) { const height=width<768?844:900;await call('Emulation.setDeviceMetricsOverride',{width,height,screenWidth:width,screenHeight:height,deviceScaleFactor:1,mobile:width<768});await sleep(300); }
async function navigate(port=8000) { currentPage=port===8000?'Laravel':'Original Node';await call('Page.navigate',{url:'http://127.0.0.1:'+port+'/fusion-ai.html'});await waitFor('document.readyState !== "loading" && !!document.querySelector("#mainNav") && !!document.querySelector("#footer")');await sleep(1400); }
async function screenshot(name) {const result=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false,fromSurface:true});fs.writeFileSync(path.join(output,name+'.png'),Buffer.from(result.data,'base64'));}
async function instrument() { await run(()=>{window.__qaApi=[];window.__qaFetch=window.fetch.bind(window);window.fetch=async(...args)=>{const response=await window.__qaFetch(...args);if(String(args[0]).endsWith('/api/chat')){const data=await response.clone().json();window.__qaApi.push({url:new URL(String(args[0]),location.href).href,status:response.status,source:data.source||null,replyLength:typeof data.reply==='string'?data.reply.length:0,code:data.code||null});}return response;};}); }
async function main() {
 for(const page of ['', 'index.html','about.html','dietplans.html','workouts.html','BMI.html','fusion-ai.html','contact.html']){const response=await get('http://127.0.0.1:8000/'+page);report.pages.push({url:'/'+page,status:response.status});if(response.status!==200)throw new Error('Public page did not return 200');}
 const profile=fs.mkdtempSync(path.join(output,'chrome-profile-'));
 browser=cp.spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--remote-debugging-port=0','--user-data-dir='+profile,'about:blank'],{windowsHide:true,stdio:'ignore'});
 const active=path.join(profile,'DevToolsActivePort');for(let i=0;i<120&&!fs.existsSync(active);i++)await sleep(100);if(!fs.existsSync(active))throw new Error('Chrome did not start');
 const port=fs.readFileSync(active,'utf8').split('\n')[0],targets=JSON.parse((await get('http://127.0.0.1:'+port+'/json/list')).body.toString());
 ws=new WebSocket(targets.find(target=>target.type==='page').webSocketDebuggerUrl);await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
 ws.addEventListener('message',event=>{const message=JSON.parse(event.data);if(message.id){const task=pending.get(message.id);if(task){pending.delete(message.id);message.error?task.reject(new Error(message.error.message)):task.resolve(message.result);}return;}
  if(message.method==='Runtime.exceptionThrown')report.exceptions.push({page:currentPage,text:message.params.exceptionDetails.exception?.description||message.params.exceptionDetails.text});
  if(message.method==='Runtime.consoleAPICalled'&&message.params.type==='error')report.consoleErrors.push({page:currentPage,text:message.params.args.map(arg=>arg.value||arg.description)});
  if(message.method==='Network.requestWillBeSent')requests.set(message.params.requestId,message.params.request.url);
  if(message.method==='Network.loadingFailed')report.networkFailures.push({page:currentPage,url:requests.get(message.params.requestId),error:message.params.errorText});
  if(message.method==='Network.responseReceived'&&message.params.response.status>=400)report.networkFailures.push({page:currentPage,url:message.params.response.url,status:message.params.response.status});
 });
 await call('Page.enable');await call('Runtime.enable');await call('Network.enable');await viewport(390);await navigate();await instrument();
 report.features.csrfMeta=await run(()=>!!document.querySelector('meta[name="csrf-token"]')?.content);
 report.features.emptyDisabled=await run(()=>document.querySelector('#sendBtn').disabled);
 await run(()=>{const input=document.querySelector('#chatInput');input.focus();input.value='Give one short general workout tip for a healthy beginner.';input.dispatchEvent(new Event('input',{bubbles:true}));});
 await call('Input.dispatchKeyEvent',{type:'rawKeyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,modifiers:8});
 await call('Input.dispatchKeyEvent',{type:'char',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,modifiers:8,text:'\r',unmodifiedText:'\r'});
 await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,modifiers:8});
 report.features.shiftEnter=await run(()=>window.__qaApi.length===0&&document.querySelector('#chatInput').value.includes('\n'));
 if(!report.features.shiftEnter)throw new Error('Shift+Enter keyboard verification failed');
 await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});
 report.features.loading=await run(()=>!!document.querySelector('#typingIndicator')&&document.querySelector('#chatInput').disabled);
 await waitFor('window.__qaApi.length > 0 && !document.querySelector("#typingIndicator") && !document.querySelector("#chatInput").disabled',35000);
 report.features.laravelLive=await run(()=>({response:window.__qaApi[0],userMessages:document.querySelectorAll('.chat-message.user').length,aiMessages:document.querySelectorAll('.chat-message.ai').length,offline:!!document.querySelector('.chat-source-badge'),error:document.querySelector('.chat-error')?.textContent.trim()||null}));
 save();console.log(JSON.stringify({stage:'Laravel live',result:report.features.laravelLive}));
 if(report.features.laravelLive.response.status!==200||report.features.laravelLive.response.replyLength===0||report.features.laravelLive.offline)throw new Error('Laravel real-provider test did not succeed');

 for(const width of [320,375,390,768,992,1024,1440,1920]){await viewport(width);await run(()=>document.querySelector('.fusion-ai-container').scrollIntoView({behavior:'instant',block:'center',inline:'nearest'}));await sleep(250);const geometry=await run(()=>{const panel=document.querySelector('.fusion-ai-container').getBoundingClientRect(),input=document.querySelector('.fusion-ai-input-area').getBoundingClientRect(),messages=document.querySelector('.fusion-ai-messages').getBoundingClientRect(),w=document.documentElement.clientWidth;return {clientWidth:w,scrollWidth:document.documentElement.scrollWidth,panelWidth:panel.width,inputInside:input.left>=panel.left-1&&input.right<=panel.right+1&&input.bottom<=panel.bottom+1,messageInputOverlap:messages.bottom>input.top+1};});report.layouts.push({width,...geometry});}
 await viewport(390);await run(()=>document.querySelector('.fusion-ai-container').scrollIntoView({behavior:'instant',block:'center'}));await sleep(200);await screenshot('laravel-chat-mobile-390');
 report.features.mobileKeyboard=await run(()=>{const input=document.querySelector('#chatInput'),view=visualViewport,panel=document.querySelector('.fusion-ai-container');input.focus();Object.defineProperty(view,'height',{configurable:true,value:420});view.dispatchEvent(new Event('resize'));const fitted=panel.style.height==='396px'&&document.body.classList.contains('chat-keyboard-open');input.blur();const restored=panel.style.height===''&&!document.body.classList.contains('chat-keyboard-open');delete view.height;return {simulated:true,fitted,restored};});

 report.features.validation=await run(async()=>{const headers={'Content-Type':'application/json','Accept':'application/json'},body=JSON.stringify({messages:[]});const without=await window.__qaFetch('/api/chat',{method:'POST',headers,body});const missing=await without.json();const wrong=await window.__qaFetch('/api/chat',{method:'POST',headers:{...headers,'X-CSRF-TOKEN':'wrong-token'},body});const wrongBody=await wrong.json();const token=document.querySelector('meta[name="csrf-token"]').content;const invalid=await window.__qaFetch('/api/chat',{method:'POST',headers:{...headers,'X-CSRF-TOKEN':token},body});const invalidBody=await invalid.json();const malformed=await window.__qaFetch('/api/chat',{method:'POST',headers:{...headers,'X-CSRF-TOKEN':token},body:'{bad json'});const malformedBody=await malformed.json();return {missingCsrf:{status:without.status,code:missing.code},wrongCsrf:{status:wrong.status,code:wrongBody.code},emptyMessages:{status:invalid.status,code:invalidBody.code},malformedJson:{status:malformed.status,code:malformedBody.code}};});
 report.features.clear=await run(()=>{document.querySelector('#clearChatBtn').click();return document.querySelectorAll('.chat-message').length===0&&document.querySelector('#chatWelcome').style.display===''&&document.querySelector('#sendBtn').disabled;});
 report.features.longInput=await run(()=>{const input=document.querySelector('#chatInput');input.value='x'.repeat(1001);input.dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('#sendBtn').click();const rejected=!!document.querySelector('.chat-error')&&window.__qaApi.length===1;document.querySelector('#clearChatBtn').click();return rejected;});

 await run(()=>{window.fetch=async()=>new Response(JSON.stringify({reply:'**Preserved formatting**\n- First tip\n<script>window.__fitfusionXss=true</script>',source:'local'}),{status:200,headers:{'Content-Type':'application/json'}});document.querySelector('.suggested-prompt').click();});
 await waitFor('!document.querySelector("#typingIndicator") && !document.querySelector("#chatInput").disabled');
 report.features.fallbackUi=await run(()=>({badge:!!document.querySelector('.chat-source-badge'),bold:!!document.querySelector('.chat-message.ai strong'),list:!!document.querySelector('.chat-message.ai li'),htmlEscaped:!document.querySelector('.chat-message.ai script')&&window.__fitfusionXss!==true}));
 await run(()=>{document.querySelector('#clearChatBtn').click();window.__qaSetTimeout=window.setTimeout;window.setTimeout=(fn,delay,...args)=>window.__qaSetTimeout(fn,delay===30000?30:delay,...args);window.fetch=(_url,options)=>new Promise((_resolve,reject)=>options.signal.addEventListener('abort',()=>reject(new DOMException('Aborted','AbortError'))));const input=document.querySelector('#chatInput');input.value='Timeout interface check';input.dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('#sendBtn').click();});
 await waitFor('!!document.querySelector(".chat-retry-btn") && !document.querySelector("#chatInput").disabled');
 report.features.clientTimeout=await run(()=>({error:document.querySelector('.chat-error').textContent.trim(),retry:!!document.querySelector('.chat-retry-btn'),inputEnabled:!document.querySelector('#chatInput').disabled}));
 await run(()=>{window.setTimeout=window.__qaSetTimeout;window.fetch=async()=>new Response(JSON.stringify({reply:'Retry fixture reply',source:'local'}),{status:200,headers:{'Content-Type':'application/json'}});document.querySelector('.chat-retry-btn').click();});
 await waitFor('!document.querySelector(".chat-retry-btn") && !document.querySelector("#typingIndicator") && !document.querySelector("#chatInput").disabled');
 report.features.retry=true;await run(()=>{window.fetch=window.__qaFetch;document.querySelector('#clearChatBtn').click();});
 await viewport(1440);await run(()=>document.querySelector('.fusion-ai-container').scrollIntoView({behavior:'instant',block:'center'}));await screenshot('laravel-chat-desktop-1440');
 report.apiCalls=await run(()=>window.__qaApi);save();
 await navigate(3000);await instrument();
 await run(()=>{const input=document.querySelector('#chatInput');input.value='Give one short general workout tip for a healthy beginner.';input.dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('#sendBtn').click();});
 await waitFor('window.__qaApi.length > 0 && !document.querySelector("#typingIndicator") && !document.querySelector("#chatInput").disabled',35000);
 report.features.nodeLive=await run(()=>({response:window.__qaApi[0],offline:!!document.querySelector('.chat-source-badge'),error:document.querySelector('.chat-error')?.textContent.trim()||null}));
 report.networkFailures=report.networkFailures.map(item=>({...item,expected:item.page==='Laravel'&&item.url?.endsWith('/api/chat')&&[400,419].includes(item.status)||item.page==='Original Node'&&item.url?.endsWith('/favicon.ico')&&item.status===404}));
 report.completedAt=new Date().toISOString();save();
 console.log(JSON.stringify({report:path.join(output,'browser-tests.json'),laravelLive:report.features.laravelLive,nodeLive:report.features.nodeLive,layouts:report.layouts,features:report.features,exceptions:report.exceptions,consoleErrors:report.consoleErrors,networkFailures:report.networkFailures},null,2));
}
main().catch(error=>{report.fatal=error.message;save();console.error(error.message);process.exitCode=1;}).finally(async()=>{if(ws&&ws.readyState===1){await call('Browser.close').catch(()=>{});ws.close();}if(browser)browser.kill();});
