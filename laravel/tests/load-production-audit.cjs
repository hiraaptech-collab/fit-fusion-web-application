// Local, isolated load test. It never targets the real FitFusion database or Groq.
const fs=require('fs'),path=require('path'),cp=require('child_process');
const root=path.resolve(__dirname,'..'),folder=path.join(root,'storage/app/private/production-audit');
const credentials=JSON.parse(fs.readFileSync(path.join(folder,'.database.json'),'utf8'));
if(!/^fitfusion_audit_/.test(credentials.database))throw new Error('Unsafe load-test database.');
const count=Number(process.argv[2]||100),label=process.argv[3]||'load';
if(!Number.isInteger(count)||count<1||count>110)throw new Error('Client count must be 1–110.');
const origin='http://127.0.0.1:8006',samples=[],failures=[],resources=[];
let active=0,peak=0,aborted=false;const abort=new AbortController();
const sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms));
const samplerCode=`$ErrorActionPreference='SilentlyContinue';$rows=@(Get-CimInstance Win32_Process|Where-Object{$_.Name -eq 'httpd.exe' -and $_.CommandLine -like '*production-audit*'});$ids=@($rows.ProcessId);while($true){$p=@(Get-Process -Id $ids);$db=@(Get-Process -Name mysqld);$os=Get-CimInstance Win32_OperatingSystem;[pscustomobject]@{time=[DateTime]::UtcNow.ToString('o');apacheCpuSeconds=($p|Measure-Object CPU -Sum).Sum;apacheWorkingSet=($p|Measure-Object WorkingSet64 -Sum).Sum;apachePrivateBytes=($p|Measure-Object PrivateMemorySize64 -Sum).Sum;databaseCpuSeconds=($db|Measure-Object CPU -Sum).Sum;databaseWorkingSet=($db|Measure-Object WorkingSet64 -Sum).Sum;freeMemoryKB=$os.FreePhysicalMemory}|ConvertTo-Json -Compress;Start-Sleep -Seconds 2}`;
const sampler=cp.spawn('powershell.exe',['-NoProfile','-Command',samplerCode],{windowsHide:true,stdio:['ignore','pipe','ignore']});let buffer='';
sampler.stdout.on('data',chunk=>{buffer+=chunk;let end;while((end=buffer.indexOf('\n'))>=0){const line=buffer.slice(0,end).trim();buffer=buffer.slice(end+1);try{const r=JSON.parse(line);resources.push(r);if(r.freeMemoryKB<393216&&!aborted){aborted=true;abort.abort();}}catch{}}});
function jar(){return {cookies:new Map(),token:null};}
async function request(actor,state,method,uri,data,expected){
 const headers={'X-Audit-Client':String(actor),Cookie:[...state.cookies.values()].join('; ')};
 if(data){headers.Accept='application/json';headers['Content-Type']='application/json';headers['X-CSRF-TOKEN']=state.token;}
 const started=performance.now();active++;peak=Math.max(peak,active);
 try{
  const response=await fetch(origin+uri,{method,headers,body:data?JSON.stringify(data):undefined,redirect:'manual',signal:abort.signal});
  for(const cookie of response.headers.getSetCookie()){const pair=cookie.split(';')[0];state.cookies.set(pair.slice(0,pair.indexOf('=')),pair);}
  const body=await response.text();const match=body.match(/name="csrf-token" content="([^"]+)"/);if(match)state.token=match[1];
  const result={actor,method,uri,status:response.status,ms:performance.now()-started,bytes:Buffer.byteLength(body),queries:Number(response.headers.get('X-Audit-Query-Count')||0),queryMs:Number(response.headers.get('X-Audit-Query-Ms')||0),phpPeakMB:Number(response.headers.get('X-Audit-PHP-Peak-MB')||0)};
  samples.push(result);if(!expected.includes(response.status))failures.push({...result,expected});return {status:response.status,body};
 }catch(error){failures.push({actor,method,uri,status:0,type:error.name,ms:performance.now()-started});return {status:0,body:''};}finally{active--;}
}
async function client(index){
 const state=jar();await request(index,state,'GET','/login',null,[200]);
 const login=await request(index,state,'POST','/login',{email:'client'+index+'@example.test',password:credentials.client_password},[302]);if(login.status!==302)return;
 await request(index,state,'GET','/client/dashboard',null,[200]);
 await request(index,state,'GET','/client/memberships',null,[200]);
 await request(index,state,'GET','/client/plans',null,[200]);
 await request(index,state,'POST','/client/progress',{recorded_on:new Date().toISOString().slice(0,10),height_cm:175,weight_kg:70,consent:true},[200]);
 await request(index,state,'GET','/client/payments',null,[200]);
 if(!label.includes('no-ai'))await request(index,state,'POST','/client/ai',{message:'Give one short beginner workout tip.',consent:true},[200,429]);
 await request(index,state,'POST','/logout',{},[302]);
}
function summary(items){const values=items.map(s=>s.ms).sort((a,b)=>a-b),percent=p=>values[Math.min(values.length-1,Math.ceil(values.length*p)-1)]||0;return {requests:items.length,meanMs:items.reduce((sum,r)=>sum+r.ms,0)/(items.length||1),p50Ms:percent(.5),p95Ms:percent(.95),p99Ms:percent(.99),maxMs:values.at(-1)||0,meanQueries:items.reduce((sum,r)=>sum+r.queries,0)/(items.length||1),meanQueryMs:items.reduce((sum,r)=>sum+r.queryMs,0)/(items.length||1),maxPhpPeakMB:Math.max(0,...items.map(r=>r.phpPeakMB)),statuses:Object.fromEntries([...new Set(items.map(r=>r.status))].map(status=>[status,items.filter(r=>r.status===status).length]))};}
(async()=>{
 await sleep(1500);const started=performance.now();await Promise.all(Array.from({length:count},(_,i)=>client(i+1)));const seconds=(performance.now()-started)/1000;
 const endpoint={};for(const uri of [...new Set(samples.map(r=>r.uri))])endpoint[uri]=summary(samples.filter(r=>r.uri===uri));
 const report={label,testDatabase:credentials.database,virtualClients:count,peakConcurrentHttpRequests:peak,seconds,throughputRequestsPerSecond:samples.length/seconds,unexpectedErrors:failures.length,unexpectedErrorRate:failures.length/(samples.length||1),rateLimitedRequests:samples.filter(s=>s.status===429).length,resourceSafetyAbort:aborted,summary:summary(samples),endpoints:endpoint,resources,failures,environment:{server:'Separate Apache WinNT / PHP ZTS, 16 threads, localhost',database:'Isolated MariaDB schema and test-only DB user',passwordHash:'bcrypt cost 12',ai:'Laravel HTTP client fake, 150ms latency; zero real provider calls',simulatedIps:'Distinct TEST-NET client IPs; modified only by private test harness'}};
 fs.writeFileSync(path.join(folder,label+'-load.json'),JSON.stringify(report,null,2));console.log(JSON.stringify({...report,resources:resources.length,failures:failures.slice(0,5)},null,2));if(failures.length||aborted)process.exitCode=1;
})().catch(error=>{console.error(error.name);process.exitCode=1;}).finally(()=>sampler.kill());
