const fs = require('fs');
const path = require('path');
const http = require('http');
const cp = require('child_process');
const laravel = path.resolve(__dirname, '..');
const output = path.join(laravel, process.env.FITFUSION_VERIFICATION_DIR || 'storage/app/private/phase4-3-verification');
fs.mkdirSync(output, {recursive:true});
const report = JSON.parse(fs.readFileSync(path.join(output, 'browser-tests.json'),'utf8')); 
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

 await run(()=>{window.__keyboardCalls=0;window.fetch=async()=>{window.__keyboardCalls++;return new Response(JSON.stringify({reply:'Keyboard fixture reply',source:'local'}),{status:200,headers:{'Content-Type':'application/json'}});};const input=document.querySelector('#chatInput');input.focus();input.value='Keyboard line';input.dispatchEvent(new Event('input',{bubbles:true}));});
 await call('Input.dispatchKeyEvent',{type:'rawKeyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,modifiers:8});
 await call('Input.dispatchKeyEvent',{type:'char',key:'Enter',code:'Enter',text:'\r',unmodifiedText:'\r',windowsVirtualKeyCode:13,modifiers:8});
 await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,modifiers:8});
 report.features.shiftEnterDetails=await run(()=>({newline:document.querySelector('#chatInput').value.includes('\n'),noSend:window.__keyboardCalls===0&&!document.querySelector('#typingIndicator')}));
 report.features.shiftEnter=report.features.shiftEnterDetails.newline&&report.features.shiftEnterDetails.noSend;
 await call('Input.dispatchKeyEvent',{type:'rawKeyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});
 await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});
 await waitFor('window.__keyboardCalls === 1 && !document.querySelector("#typingIndicator") && !document.querySelector("#chatInput").disabled');
 report.features.enterToSend=await run(()=>window.__keyboardCalls===1&&document.querySelectorAll('.chat-message.user').length===1&&document.querySelectorAll('.chat-message.ai').length===1);
 save();console.log(JSON.stringify({shiftEnter:report.features.shiftEnter,details:report.features.shiftEnterDetails,enterToSend:report.features.enterToSend}));
 if(!report.features.shiftEnter||!report.features.enterToSend)throw new Error('Keyboard verification did not pass');
}
main().catch(error=>{report.fatal=error.message;save();console.error(error.message);process.exitCode=1;}).finally(async()=>{if(ws&&ws.readyState===1){await call('Browser.close').catch(()=>{});ws.close();}if(browser)browser.kill();});
