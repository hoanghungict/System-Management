<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Component Diagram — HPC System</title>
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
    <h2>Biểu đồ thành phần (Component Diagram) — Hệ thống HPC</h2>
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
const W=1520, H=920;

function render(){
const svg=document.getElementById('dg');
svg.setAttribute('width',W);svg.setAttribute('height',H);
svg.setAttribute('viewBox',`0 0 ${W} ${H}`);

let s=`<defs>
<marker id="arr" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#555"/></marker>
<marker id="arrD" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#888"/></marker>
</defs>`;

// ── Background grid ──
for(let i=0;i<W;i+=40) s+=`<line x1="${i}" y1="0" x2="${i}" y2="${H}" stroke="#f5f5f5" stroke-width="1"/>`;
for(let i=0;i<H;i+=40) s+=`<line x1="0" y1="${i}" x2="${W}" y2="${i}" stroke="#f5f5f5" stroke-width="1"/>`;

// ══════════════════════════════════════════════
// HELPER FUNCTIONS
// ══════════════════════════════════════════════
function box(x,y,w,h,title,color,items,icon){
  const hdrH=32;
  s+=`<rect x="${x+2}" y="${y+2}" width="${w}" height="${h}" rx="8" fill="rgba(0,0,0,.08)"/>`;
  s+=`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="8" fill="#fff" stroke="${color}" stroke-width="2"/>`;
  s+=`<rect x="${x}" y="${y}" width="${w}" height="${hdrH}" rx="8" fill="${color}"/>`;
  s+=`<rect x="${x}" y="${y+hdrH-8}" width="${w}" height="8" fill="${color}"/>`;
  s+=`<line x1="${x}" y1="${y+hdrH}" x2="${x+w}" y2="${y+hdrH}" stroke="${color}" stroke-width="2"/>`;
  s+=`<text x="${x+w/2}" y="${y+hdrH/2+1}" text-anchor="middle" dominant-baseline="middle" font-size="14" font-weight="bold" fill="#fff">${icon?icon+' ':''}${title}</text>`;
  items.forEach((it,i)=>{
    const prefix=it.startsWith('«')?'':'• ';
    const isStereotype=it.startsWith('«');
    s+=`<text x="${x+14}" y="${y+hdrH+14+(i)*20}" font-size="12.5" fill="${isStereotype?'#888':'#333'}" font-weight="${isStereotype?'normal':'500'}" ${isStereotype?'font-style="italic"':''}>${prefix}${it}</text>`;
  });
}

function compIcon(x,y){
  s+=`<rect x="${x}" y="${y}" width="18" height="12" rx="2" fill="none" stroke="#666" stroke-width="1.5"/>`;
  s+=`<rect x="${x-4}" y="${y+2}" width="8" height="3" rx="1" fill="#666"/>`;
  s+=`<rect x="${x-4}" y="${y+7}" width="8" height="3" rx="1" fill="#666"/>`;
}

function conn(x1,y1,x2,y2,label,dashed,color){
  const cl=color||'#555';
  const da=dashed?'stroke-dasharray="8,4"':'';
  const mk=dashed?'marker-end="url(#arrD)"':'marker-end="url(#arr)"';
  // Bezier
  const dx=x2-x1,dy=y2-y1;
  if(Math.abs(dx)>Math.abs(dy)){
    const cx=dx/2;
    s+=`<path d="M${x1},${y1} C${x1+cx},${y1} ${x2-cx},${y2} ${x2},${y2}" fill="none" stroke="${cl}" stroke-width="1.8" ${da} ${mk}/>`;
  } else {
    const cy=dy/2;
    s+=`<path d="M${x1},${y1} C${x1},${y1+cy} ${x2},${y2-cy} ${x2},${y2}" fill="none" stroke="${cl}" stroke-width="1.8" ${da} ${mk}/>`;
  }
  if(label){
    const mx=(x1+x2)/2,my=(y1+y2)/2;
    const lw=label.length*6.5+14;
    s+=`<rect x="${mx-lw/2}" y="${my-10}" width="${lw}" height="16" rx="3" fill="#fff" stroke="#ddd" stroke-width=".5"/>`;
    s+=`<text x="${mx}" y="${my+2}" text-anchor="middle" font-size="10.5" fill="#666" font-style="italic">${label}</text>`;
  }
}

// ══════════════════════════════════════════════
// 1. CLIENT COMPONENTS (left)
// ══════════════════════════════════════════════
box(40,320, 220,180, 'Client Components','#3b82f6',[
  '«framework» Next.js (React)',
  '',
  'Web Portal',
  'Dashboard',
  'Attendance UI',
  'Notification UI',
  'User Management UI',
],'🖥️');
compIcon(40,320);

// ══════════════════════════════════════════════
// 2. BACKEND COMPONENTS (center) - Auth Module
// ══════════════════════════════════════════════
// Auth Module box
box(380,40, 380,340, 'Auth Module','#10b981',[
  '«module» Modules/Auth',
  '',
  'Authentication Sub-module',
  'User Management Sub-module',
  'Organization Sub-module (Dept, Class)',
  'Academic Sub-module (Semester, Course)',
  'Attendance Sub-module',
  'Enrollment Sub-module',
  'Import Sub-module (Excel)',
  '',
  '«pattern» Repository + Service Layer',
  '«auth» JWT Token',
],'🔐');
compIcon(380,40);

// Notification Module box
box(380,420, 380,260, 'Notification Module','#f59e0b',[
  '«module» Modules/Notifications',
  '',
  'Notification Service',
  'Template Management',
  'Kafka Producer Service',
  'Kafka Consumer Service',
  'Kafka Router Service',
  'Event Handlers (Student, Course, Task...)',
  'Channel Services (Email, Push, SMS)',
],'🔔');
compIcon(380,420);

// Backend wrapper label
s+=`<rect x="360" y="20" width="420" height="680" rx="12" fill="none" stroke="#555" stroke-width="2" stroke-dasharray="10,5"/>`;
s+=`<text x="570" y="715" text-anchor="middle" font-size="13" fill="#555" font-weight="600">«component» Backend (Laravel)</text>`;

// ══════════════════════════════════════════════
// 3. DATABASE COMPONENTS (bottom-right)
// ══════════════════════════════════════════════
box(900,500, 240,170, 'Database Components','#8b5cf6',[
  '',
  '🗄️  MySQL Database',
  '     17 bảng nghiệp vụ',
  '',
  '⚡  Redis',
  '     Cache / Session / Queue',
],'💾');
compIcon(900,500);

// ══════════════════════════════════════════════
// 4. MESSAGE QUEUE COMPONENTS (top-right)
// ══════════════════════════════════════════════
box(900,40, 280,200, 'Message Queue Components','#ef4444',[
  '',
  '🐘  Zookeeper',
  '     Cluster management',
  '',
  '📨  Kafka Broker',
  '     Topic: notifications',
  '',
  '⚙️  Kafka Consumer Worker',
  '     artisan kafka:consume',
],'📡');
compIcon(900,40);

// ══════════════════════════════════════════════
// 5. EXTERNAL SERVICES (far right)
// ══════════════════════════════════════════════
box(1280,140, 200,140, 'External Services','#6b7280',[
  '',
  '📧  SMTP (Email)',
  '📱  Firebase (Push)',
  '💬  SMS Gateway',
],'🌐');

// ══════════════════════════════════════════════
// CONNECTIONS
// ══════════════════════════════════════════════
// Client → Backend (Auth)
conn(260,400, 380,210, 'REST API + JWT', true, '#3b82f6');

// Client → Backend (Notification)  
conn(260,460, 380,540, 'REST API', true, '#3b82f6');

// Auth Module → Database
conn(760,280, 900,560, 'Eloquent ORM', false, '#8b5cf6');

// Notification Module → Database
conn(760,580, 900,620, 'Read/Write', false, '#8b5cf6');

// Auth Module → Notification Module (internal event)
conn(570,380, 570,420, 'Event Dispatch', true, '#f59e0b');

// Notification Module → Kafka
conn(760,480, 900,140, 'Produce Events', false, '#ef4444');

// Kafka → Notification Module (consume back)
conn(900,200, 760,520, 'Consume Events', false, '#ef4444');

// Backend → Redis
conn(760,400, 900,640, 'Cache/Session', true, '#8b5cf6');

// Kafka Consumer → External Services
conn(1180,140, 1280,200, 'Send', false, '#6b7280');

// ══════════════════════════════════════════════
// LEGEND
// ══════════════════════════════════════════════
s+=`<rect x="40" y="780" width="600" height="100" rx="8" fill="#fafafa" stroke="#e5e7eb" stroke-width="1"/>`;
s+=`<text x="55" y="800" font-size="12" font-weight="bold" fill="#333">Chú thích:</text>`;
// Solid line
s+=`<line x1="55" y1="820" x2="115" y2="820" stroke="#555" stroke-width="1.8" marker-end="url(#arr)"/>`;
s+=`<text x="125" y="824" font-size="11" fill="#555">Dependency (đồng bộ)</text>`;
// Dashed line
s+=`<line x1="55" y1="845" x2="115" y2="845" stroke="#888" stroke-width="1.8" stroke-dasharray="8,4" marker-end="url(#arrD)"/>`;
s+=`<text x="125" y="849" font-size="11" fill="#555">Dependency (bất đồng bộ / API call)</text>`;
// Component icon
compIcon(55,862);
s+=`<text x="85" y="872" font-size="11" fill="#555">UML Component</text>`;
// Colors
s+=`<rect x="310" y="812" width="14" height="14" rx="3" fill="#3b82f6"/>`;s+=`<text x="330" y="824" font-size="11" fill="#555">Client</text>`;
s+=`<rect x="310" y="835" width="14" height="14" rx="3" fill="#10b981"/>`;s+=`<text x="330" y="847" font-size="11" fill="#555">Auth Module</text>`;
s+=`<rect x="310" y="858" width="14" height="14" rx="3" fill="#f59e0b"/>`;s+=`<text x="330" y="870" font-size="11" fill="#555">Notification Module</text>`;
s+=`<rect x="450" y="812" width="14" height="14" rx="3" fill="#8b5cf6"/>`;s+=`<text x="470" y="824" font-size="11" fill="#555">Database</text>`;
s+=`<rect x="450" y="835" width="14" height="14" rx="3" fill="#ef4444"/>`;s+=`<text x="470" y="847" font-size="11" fill="#555">Message Queue</text>`;
s+=`<rect x="450" y="858" width="14" height="14" rx="3" fill="#6b7280"/>`;s+=`<text x="470" y="870" font-size="11" fill="#555">External Services</text>`;

svg.innerHTML=s;
}
render();

// Zoom/Pan
let s=1,px=0,py=0,dr=false,sx,sy;
const cv=document.getElementById('cv'),ci=document.getElementById('ci'),zl=document.getElementById('zl');
function at(){ci.style.transform=`translate(${px}px,${py}px) scale(${s})`;zl.textContent=Math.round(s*100)+'%';}
function zoomIn(){s=Math.min(s*1.25,8);at();}
function zoomOut(){s=Math.max(s/1.25,.05);at();}
function fit(){const sg=document.getElementById('dg'),sw=+sg.getAttribute('width'),sh=+sg.getAttribute('height'),cw=cv.clientWidth,ch=cv.clientHeight;s=Math.min(cw/sw,ch/sh)*.95;px=(cw-sw*s)/2;py=(ch-sh*s)/2;at();}
cv.addEventListener('wheel',function(e){e.preventDefault();const r=cv.getBoundingClientRect(),mx=e.clientX-r.left,my=e.clientY-r.top,os=s;s=e.deltaY<0?Math.min(s*1.1,8):Math.max(s/1.1,.05);px=mx-(mx-px)*(s/os);py=my-(my-py)*(s/os);at();},{passive:false});
cv.addEventListener('mousedown',function(e){dr=true;sx=e.clientX-px;sy=e.clientY-py;});
cv.addEventListener('mousemove',function(e){if(!dr)return;px=e.clientX-sx;py=e.clientY-sy;at();});
cv.addEventListener('mouseup',function(){dr=false;});
cv.addEventListener('mouseleave',function(){dr=false;});
setTimeout(fit,200);
</script>
</body>
</html>
