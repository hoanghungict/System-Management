<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Deployment Diagram — HPC System</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#fff;font-family:'Segoe UI',Arial,sans-serif;overflow:hidden;height:100vh}
.toolbar{background:#2c3e50;color:#fff;padding:8px 15px;display:flex;align-items:center;gap:10px;z-index:10;position:relative}
.toolbar h2{font-size:13px;font-weight:600;flex:1}
.toolbar button{background:rgba(255,255,255,.12);color:#fff;border:none;padding:5px 12px;border-radius:4px;cursor:pointer;font-size:13px}
.toolbar button:hover{background:rgba(255,255,255,.25)}
.toolbar .zi{font-size:12px;min-width:50px;text-align:center}
.canvas{width:100%;height:calc(100vh - 40px);overflow:hidden;background:#fff;cursor:grab;position:relative}
.canvas:active{cursor:grabbing}
.ci{transform-origin:0 0;position:absolute;top:0;left:0}
</style>
</head>
<body>
<div class="toolbar">
    <h2>Biểu đồ triển khai (Deployment Diagram) — Hệ thống HPC</h2>
    <button onclick="zoomIn()">🔍+</button>
    <span class="zi" id="zl">100%</span>
    <button onclick="zoomOut()">🔍−</button>
    <button onclick="fit()">Fit</button>
</div>
<div class="canvas" id="cv">
<div class="ci" id="ci">
<svg id="dg" xmlns="http://www.w3.org/2000/svg"></svg>
</div>
</div>
<script>
const W=1500, H=1100;

function render(){
const svg=document.getElementById('dg');
svg.setAttribute('width',W);svg.setAttribute('height',H);
svg.setAttribute('viewBox',`0 0 ${W} ${H}`);

let s=`<defs>
<marker id="arr" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto"><polygon points="0 0,8 3,0 6" fill="#555"/></marker>
</defs>`;

// Grid
for(let i=0;i<W;i+=40) s+=`<line x1="${i}" y1="0" x2="${i}" y2="${H}" stroke="#f7f7f7" stroke-width="1"/>`;
for(let i=0;i<H;i+=40) s+=`<line x1="0" y1="${i}" x2="${W}" y2="${i}" stroke="#f7f7f7" stroke-width="1"/>`;

// ═══════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════
// UML Node (3D box)
function node(x,y,w,h,title,stereo,color,items){
  const d=12; // 3D depth
  const bg=color||'#f8fafc';const bc='#94a3b8';
  // 3D top face
  s+=`<polygon points="${x},${y} ${x+d},${y-d} ${x+w+d},${y-d} ${x+w},${y}" fill="#e2e8f0" stroke="${bc}" stroke-width="1.2"/>`;
  // 3D right face
  s+=`<polygon points="${x+w},${y} ${x+w+d},${y-d} ${x+w+d},${y+h-d} ${x+w},${y+h}" fill="#cbd5e1" stroke="${bc}" stroke-width="1.2"/>`;
  // Front face
  s+=`<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${bg}" stroke="${bc}" stroke-width="1.5"/>`;
  // Stereotype
  if(stereo){
    s+=`<text x="${x+w/2}" y="${y+16}" text-anchor="middle" font-size="12.5" fill="#64748b" font-weight="500" font-style="italic">«${stereo}»</text>`;
  }
  // Title
  s+=`<text x="${x+w/2}" y="${y+(stereo?32:20)}" text-anchor="middle" font-size="15.5" fill="#1e293b" font-weight="bold">${title}</text>`;
  // Separator
  const sepY=y+(stereo?40:28);
  s+=`<line x1="${x}" y1="${sepY}" x2="${x+w}" y2="${sepY}" stroke="${bc}" stroke-width="1"/>`;
  // Items
  if(items){
    items.forEach((it,i)=>{
      if(it===''){return;}
      const isArt=it.startsWith('«');
      const isSub=it.startsWith('  ');
      s+=`<text x="${x+12}" y="${sepY+18+i*20}" font-size="${isArt?'12':'13.5'}" fill="${isArt?'#64748b':isSub?'#475569':'#334155'}" font-weight="${isArt?'500':'600'}" ${isArt?'font-style="italic"':''}>${it}</text>`;
    });
  }
}

// Artifact (rectangle with doc icon)
function artifact(x,y,w,h,name,details,color){
  const bg=color||'#fffbeb';
  s+=`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="4" fill="${bg}" stroke="#d97706" stroke-width="1.2"/>`;
  // Doc icon
  const ix=x+w-20,iy=y+4;
  s+=`<rect x="${ix}" y="${iy}" width="14" height="16" fill="#fff" stroke="#d97706" stroke-width="1"/>`;
  s+=`<polygon points="${ix+8},${iy} ${ix+14},${iy+6} ${ix+8},${iy+6}" fill="#fef3c7" stroke="#d97706" stroke-width=".8"/>`;
  s+=`<text x="${x+10}" y="${y+16}" font-size="11.5" fill="#92400e" font-weight="600">${name}</text>`;
  if(details){
    details.forEach((d,i)=>{
      s+=`<text x="${x+10}" y="${y+32+i*15}" font-size="10" fill="#78716c">${d}</text>`;
    });
  }
}

// Connection line with label
function conn(x1,y1,x2,y2,label,dashed){
  const da=dashed?'stroke-dasharray="6,3"':'';
  // Determine path type
  const dx=Math.abs(x2-x1),dy=Math.abs(y2-y1);
  if(dx>5&&dy>5){
    // L-shaped or bezier
    const mx=(x1+x2)/2,my=(y1+y2)/2;
    if(dy>dx){
      s+=`<path d="M${x1},${y1} L${x1},${my} L${x2},${my} L${x2},${y2}" fill="none" stroke="#555" stroke-width="1.5" ${da}/>`;
    } else {
      s+=`<path d="M${x1},${y1} L${mx},${y1} L${mx},${y2} L${x2},${y2}" fill="none" stroke="#555" stroke-width="1.5" ${da}/>`;
    }
    if(label){
      const lw=label.length*7.5+14;
      s+=`<rect x="${mx-lw/2}" y="${my-10}" width="${lw}" height="20" rx="3" fill="#fff" stroke="#ddd" stroke-width=".5"/>`;
      s+=`<text x="${mx}" y="${my+4}" text-anchor="middle" font-size="12" fill="#444" font-weight="600" font-style="italic">${label}</text>`;
    }
  } else {
    s+=`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#555" stroke-width="1.5" ${da}/>`;
    if(label){
      const mx=(x1+x2)/2,my=(y1+y2)/2;
      const lw=label.length*7.5+14;
      const offX=(dx<5)?25:0, offY=(dy<5)?-12:0;
      s+=`<rect x="${mx-lw/2+offX}" y="${my-10+offY}" width="${lw}" height="20" rx="3" fill="#fff" stroke="#ddd" stroke-width=".5"/>`;
      s+=`<text x="${mx+offX}" y="${my+4+offY}" text-anchor="middle" font-size="12" fill="#444" font-weight="600" font-style="italic">${label}</text>`;
    }
  }
}

// ═══════════════════════════════════════
// 1. CLIENT DEVICE (top)
// ═══════════════════════════════════════
node(560,30, 260,80, 'Client Device','device','#eff6ff',[
  '🌐  Browser (Chrome, Firefox...)',
]);

// ═══════════════════════════════════════
// 2. DOCKER HOST (big container)
// ═══════════════════════════════════════
// Docker Host outer boundary
const dhX=80, dhY=200, dhW=1340, dhH=860;
s+=`<rect x="${dhX}" y="${dhY}" width="${dhW}" height="${dhH}" rx="10" fill="#fafbfc" stroke="#3b82f6" stroke-width="2.5" stroke-dasharray="12,6"/>`;
s+=`<text x="${dhX+20}" y="${dhY+24}" font-size="16" fill="#3b82f6" font-weight="bold">«Docker Host» hpc_network (bridge)</text>`;

// ═══════════════════════════════════════
// 3. NGINX WEBSERVER
// ═══════════════════════════════════════
node(530,250, 320,120, 'hpc_web','container','#ecfdf5',[
  '«image» nginx:alpine',
  '«port» 8082:80',
  '«role» Reverse Proxy → PHP-FPM',
]);

// ═══════════════════════════════════════
// 4. APP SERVER (PHP-FPM)
// ═══════════════════════════════════════
node(420,440, 280,180, 'hpc_app','container','#f0fdf4',[
  '«image» hpc_app:latest',
  '«runtime» PHP-FPM 8.x',
  '«artifact» Laravel Application',
  '  Modules/Auth',
  '  Modules/Notifications',
  '«mem» 768 MB',
]);

// ═══════════════════════════════════════
// 5. QUEUE WORKER
// ═══════════════════════════════════════
node(750,440, 250,130, 'hpc_queue','container','#fefce8',[
  '«image» hpc_app:latest',
  '«command» queue:work',
  '«role» Laravel Queue Worker',
  '«mem» 256 MB',
]);

// ═══════════════════════════════════════
// 6. REVERB (WebSocket)
// ═══════════════════════════════════════
node(750,600, 250,120, 'hpc_reverb','container','#fdf4ff',[
  '«image» hpc_app:latest',
  '«command» reverb:start',
  '«port» 8081:8080',
  '«role» WebSocket Server',
]);

// ═══════════════════════════════════════
// 7. DATABASE (MySQL)
// ═══════════════════════════════════════
node(110,440, 250,150, 'hpc_db','container','#fef2f2',[
  '«image» mysql:8.0',
  '«port» 3307:3306',
  '«database» system_services',
  '«volume» db_data',
  '«mem» 768 MB',
]);

// ═══════════════════════════════════════
// 8. REDIS
// ═══════════════════════════════════════
node(110,640, 250,100, 'hpc_redis','container','#fff7ed',[
  '«image» redis:alpine',
  '«role» Cache / Session / Queue',
  '«mem» 128 MB',
]);

// ═══════════════════════════════════════
// 9. ZOOKEEPER
// ═══════════════════════════════════════
node(1060,440, 250,100, 'hpc_zookeeper','container','#f1f5f9',[
  '«image» cp-zookeeper:7.5.3',
  '«port» 2181',
  '«mem» 256 MB',
]);

// ═══════════════════════════════════════
// 10. KAFKA BROKER
// ═══════════════════════════════════════
node(1060,580, 250,130, 'hpc_kafka','container','#fef2f2',[
  '«image» cp-kafka:7.5.3',
  '«port» 9092',
  '  3 partitions, auto-create topics',
  '«mem» 768 MB',
]);

// ═══════════════════════════════════════
// 11. KAFKA CONSUMER
// ═══════════════════════════════════════
node(1060,750, 250,120, 'hpc_kafka_consumer','container','#fff1f2',[
  '«image» hpc_app:latest',
  '«command» kafka:consume',
  '«role» Notification Worker',
  '«mem» 256 MB',
]);

// ═══════════════════════════════════════
// 12. EXTERNAL SERVICES (outside Docker)
// ═══════════════════════════════════════
node(1060,250, 270,120, 'External Services','service','#f9fafb',[
  '📧  SMTP Server (Email)',
  '📱  Firebase / APNs (Push)',
  '💬  SMS Gateway',
]);

// Polyline with waypoints + label
function path(pts,label,dashed){
  const da=dashed?'stroke-dasharray="6,3"':'';
  let d='M'+pts[0][0]+','+pts[0][1];
  for(let i=1;i<pts.length;i++) d+=' L'+pts[i][0]+','+pts[i][1];
  s+=`<path d="${d}" fill="none" stroke="#555" stroke-width="1.8" ${da}/>`;
  if(label){
    // place label at middle segment
    const mi=Math.floor(pts.length/2);
    const mx=(pts[mi-1][0]+pts[mi][0])/2, my=(pts[mi-1][1]+pts[mi][1])/2;
    const lw=label.length*7.5+14;
    s+=`<rect x="${mx-lw/2}" y="${my-11}" width="${lw}" height="20" rx="3" fill="#fff" stroke="#ddd" stroke-width=".5"/>`;
    s+=`<text x="${mx}" y="${my+3}" text-anchor="middle" font-size="12" fill="#444" font-weight="600" font-style="italic">${label}</text>`;
  }
}

// ═══════════════════════════════════════
// CONNECTIONS (routed around boxes)
// ═══════════════════════════════════════

// 1) Client → Nginx (straight down)
path([[690,110],[690,250]], 'HTTP/HTTPS :8082');

// 2) Client → Reverb (go right side, avoid nginx & queue)
path([[820,110],[820,165],[1030,165],[1030,660],[1000,660]], 'WebSocket :8081');

// 3) Nginx → App (short diagonal, stays in gap)
path([[640,370],[560,440]], 'FastCGI / PHP-FPM');

// 4) App → MySQL (horizontal left)
path([[420,520],[360,520]], 'TCP :3306');

// 5) App → Redis (go left from app bottom-left, down to redis)
path([[420,600],[395,600],[395,690],[360,690]], 'TCP :6379');

// 6) App → Queue (short horizontal in gap)
path([[700,500],[750,500]], '');

// 7) Queue → Redis (go down from queue bottom, under app, left to redis)
path([[875,570],[875,770],[235,770],[235,740]], 'Queue Jobs');

// 8) Reverb → Redis (go down, under app, left to redis)
path([[875,720],[875,790],[235,790],[235,740]], 'Pub/Sub');

// 9) App → Kafka (go down from app, right under queue/reverb to kafka)
path([[560,620],[560,760],[1060,760],[1060,650]], 'Kafka Produce :9092');

// 10) Zookeeper ↔ Kafka (straight down)
path([[1185,540],[1185,580]], '');

// 11) Kafka → Consumer (straight down)
path([[1185,710],[1185,750]], 'Consume');

// 12) Consumer → MySQL (go down, left along bottom, up to mysql)
path([[1060,830],[1060,900],[235,900],[235,590]], 'Write Notifications');

// 13) Consumer → External Services (go right, up)
path([[1310,750],[1310,370]], 'Send Notifications');

// ═══════════════════════════════════════
// LEGEND
// ═══════════════════════════════════════
s+=`<rect x="110" y="810" width="700" height="140" rx="8" fill="#fafafa" stroke="#e5e7eb" stroke-width="1"/>`;
s+=`<text x="130" y="835" font-size="12" font-weight="bold" fill="#333">Chú thích (Deployment Diagram):</text>`;

// 3D Node
node(130,850,80,40,'Node','','#f8fafc',[]);
s+=`<text x="225" y="875" font-size="10.5" fill="#555">= UML Node (máy chủ / container)</text>`;

// Connection
s+=`<line x1="130" y1="910" x2="200" y2="910" stroke="#555" stroke-width="1.5"/>`;
s+=`<text x="210" y="914" font-size="10.5" fill="#555">= Communication Path</text>`;

// Dashed
s+=`<line x1="130" y1="930" x2="200" y2="930" stroke="#555" stroke-width="1.5" stroke-dasharray="6,3"/>`;
s+=`<text x="210" y="934" font-size="10.5" fill="#555">= Async / Optional</text>`;

// Docker
s+=`<rect x="420" y="848" width="50" height="24" rx="4" fill="#fafbfc" stroke="#3b82f6" stroke-width="1.5" stroke-dasharray="6,3"/>`;
s+=`<text x="480" y="865" font-size="10.5" fill="#555">= Docker Host boundary</text>`;

// Container count
s+=`<text x="420" y="900" font-size="11" fill="#1e40af" font-weight="600">Tổng: 8 containers</text>`;
s+=`<text x="420" y="918" font-size="10" fill="#555">hpc_app, hpc_web, hpc_db, hpc_redis,</text>`;
s+=`<text x="420" y="933" font-size="10" fill="#555">hpc_reverb, hpc_queue, hpc_kafka,</text>`;
s+=`<text x="420" y="948" font-size="10" fill="#555">hpc_zookeeper, hpc_kafka_consumer</text>`;

svg.innerHTML=s;
}
render();

// Zoom/Pan
let sc=1,px=0,py=0,dr=false,sx,sy;
const cv=document.getElementById('cv'),ci=document.getElementById('ci'),zl=document.getElementById('zl');
function at(){ci.style.transform=`translate(${px}px,${py}px) scale(${sc})`;zl.textContent=Math.round(sc*100)+'%';}
function zoomIn(){sc=Math.min(sc*1.25,8);at();}
function zoomOut(){sc=Math.max(sc/1.25,.05);at();}
function fit(){const sg=document.getElementById('dg'),sw=+sg.getAttribute('width'),sh=+sg.getAttribute('height'),cw=cv.clientWidth,ch=cv.clientHeight;sc=Math.min(cw/sw,ch/sh)*.92;px=(cw-sw*sc)/2;py=(ch-sh*sc)/2;at();}
cv.addEventListener('wheel',function(e){e.preventDefault();const r=cv.getBoundingClientRect(),mx=e.clientX-r.left,my=e.clientY-r.top,os=sc;sc=e.deltaY<0?Math.min(sc*1.1,8):Math.max(sc/1.1,.05);px=mx-(mx-px)*(sc/os);py=my-(my-py)*(sc/os);at();},{passive:false});
cv.addEventListener('mousedown',function(e){dr=true;sx=e.clientX-px;sy=e.clientY-py;});
cv.addEventListener('mousemove',function(e){if(!dr)return;px=e.clientX-sx;py=e.clientY-sy;at();});
cv.addEventListener('mouseup',function(){dr=false;});
cv.addEventListener('mouseleave',function(){dr=false;});
setTimeout(fit,200);
</script>
</body>
</html>
