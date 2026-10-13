// Run the complete AI backend suite with a real loopback HTTP timeout and dummy credentials.
const http = require('http'), cp = require('child_process'), fs = require('fs'), path = require('path');
const laravel = path.resolve(__dirname, '..');
let timer, requests = 0;
const server = http.createServer((request, response) => {
  requests++;
  timer = setTimeout(() => response.end('{}'), 3000);
});
server.listen(0, '127.0.0.1', () => {
  const child = cp.spawn('C:\\xampp\\php\\php.exe', ['tests/verify-fusion-ai.php'], {
    cwd: laravel, windowsHide: true,
    env: {...process.env, FITFUSION_TIMEOUT_TEST_URL: 'http://127.0.0.1:'+server.address().port}
  });
  let output = '';
  child.stdout.on('data', chunk => output += chunk);
  child.stderr.on('data', chunk => process.stderr.write(chunk));
  child.on('exit', code => {
    try {
      if (code !== 0 || requests !== 1) throw new Error('AI timeout suite failed');
      const report = JSON.parse(output);
      fs.writeFileSync(path.join(laravel, 'storage/app/private/phase4-4-verification/ai-backend-tests.json'), JSON.stringify(report, null, 2));
      console.log(JSON.stringify({passed: report.passed, fallbackCases: report.fallbackCases, realTimeoutTest: report.realTimeoutTest, loopbackRequests: requests}));
    } catch (error) { console.error(error.message); process.exitCode = 1; }
    clearTimeout(timer); server.closeAllConnections(); server.close();
  });
});