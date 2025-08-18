const http = require('http');
const fs = require('fs');
const path = require('path');

const server = http.createServer((req, res) => {
  let filePath = path.join(__dirname, 'client/app', req.url === '/' ? 'index.html' : req.url);
  
  if (req.url.startsWith('/bower_components')) {
    filePath = path.join(__dirname, 'client', req.url);
  }
  
  fs.readFile(filePath, (err, data) => {
    if (err) {
      res.writeHead(404);
      res.end('Not found');
      return;
    }
    res.writeHead(200);
    res.end(data);
  });
});

const PORT = 9002;
server.listen(PORT, () => {
  console.log(`Frontend server running on http://localhost:${PORT}`);
});