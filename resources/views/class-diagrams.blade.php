<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Biểu Đồ Lớp Tổng Quát</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#f5f5f5;font-family:'Segoe UI',Arial,sans-serif;overflow:hidden;height:100vh}
.toolbar{background:#2c3e50;color:#fff;padding:8px 15px;display:flex;align-items:center;gap:10px;z-index:10;position:relative}
.toolbar h2{font-size:13px;font-weight:600;flex:1}
.toolbar .legend{font-size:10px;color:#bbb;flex:1}
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
    <h2>Biểu đồ lớp tổng quát — Auth & Notification Module</h2>
    <span class="legend">◆ Composition &nbsp; ◇ Aggregation &nbsp; — Association &nbsp; ⇢ Dependency</span>
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
const BW=240,LH=17,HDR=28,PAD=6,SEP=4,FS=13,RAD=8;
const CLS=[
{id:'Department',x:50,y:50,
 a:['-int id','-string name','-string type','-int parent_id'],
 m:['+parent(): Department','+children(): Collection','+lecturers(): Collection','+classes(): Collection']},
{id:'Lecturer',x:50,y:270,
 a:['-int id','-string full_name','-string gender','-date birth_date','-string email','-string phone','-string lecturer_code','-int department_id','-string address','-string bang_cap','-int experience_number','-string hinh_anh'],
 m:['+department(): Department','+account(): LecturerAccount','+classes(): Collection']},
{id:'LecturerAccount',x:50,y:680,
 a:['-int id','-int lecturer_id','-string username','-string password','-string role'],m:[]},
{id:'Classroom',x:430,y:50,
 a:['-int id','-string class_name','-string class_code','-int department_id','-int lecturer_id','-string school_year'],
 m:['+department(): Department','+lecturer(): Lecturer','+students(): Collection']},
{id:'Student',x:430,y:330,
 a:['-int id','-string full_name','-string gender','-date birth_date','-string email','-string phone','-string student_code','-int class_id','-string address','-string account_status','-int import_job_id'],
 m:['+classroom(): Classroom','+account(): StudentAccount']},
{id:'StudentAccount',x:430,y:710,
 a:['-int id','-int student_id','-string username','-string password','-string role'],m:[]},
{id:'Semester',x:810,y:50,
 a:['-int id','-string name','-string academic_year','-date start_date','-date end_date','-boolean is_active'],
 m:['+courses(): Collection','+isActive(): boolean','+activate(): void']},
{id:'Course',x:810,y:330,
 a:['-int id','-string code','-string name','-int semester_id','-int lecturer_id','-array schedule_days','-time start_time','-time end_time','-date start_date','-date end_date','-string room','-int total_sessions','-int max_absences','-boolean sessions_generated','-string status'],
 m:['+semester(): Semester','+lecturer(): Lecturer','+sessions(): Collection','+enrollments(): Collection']},
{id:'Holiday',x:810,y:760,
 a:['-int id','-string name','-date date','-boolean is_recurring','-text description'],
 m:['+isHoliday(date): boolean','+getHolidaysInYear(): array']},
{id:'CourseEnrollment',x:1190,y:50,
 a:['-int id','-int course_id','-int student_id','-string status','-datetime enrolled_at','-datetime dropped_at','-string drop_reason'],
 m:['+course(): Course','+student(): Student','+isActive(): boolean','+drop(reason): void']},
{id:'AttendanceSession',x:1190,y:340,
 a:['-int id','-int course_id','-int session_number','-date session_date','-int day_of_week','-time start_time','-time end_time','-string room','-string status','-datetime started_at','-datetime completed_at','-int marked_by','-text notes'],
 m:['+course(): Course','+attendances(): Collection','+start(lecturerId): boolean','+complete(): boolean','+cancel(): boolean','+markAsHoliday(): boolean','+canEdit(): boolean']},
{id:'Attendance',x:1190,y:780,
 a:['-int id','-int session_id','-int student_id','-string status','-int minutes_late','-text reason','-int marked_by','-datetime marked_at'],
 m:['+session(): Session','+student(): Student','+markPresent(): void','+markAbsent(): void','+markLate(): void','+markExcused(): void']},
{id:'NotificationTemplate',x:1570,y:50,
 a:['-int id','-string name','-string title','-string subject','-text email_template','-text sms_template','-text push_template','-text in_app_template','-array channels','-string priority','-string category','-string description','-boolean is_active'],
 m:['+notifications(): Collection','+scopeActive(query)']},
{id:'Notification',x:1570,y:460,
 a:['-int id','-string title','-text content','-string type','-string priority','-json data','-int template_id','-int sender_id','-string sender_type','-datetime scheduled_at','-datetime sent_at','-string status'],
 m:['+template(): Template','+userNotifications(): Collection']},
{id:'UserNotification',x:1570,y:810,
 a:['-int id','-int notification_id','-int user_id','-string user_type','-boolean is_read','-datetime read_at','-boolean email_sent','-boolean push_sent','-boolean sms_sent','-boolean in_app_sent','-datetime email_sent_at','-datetime push_sent_at','-datetime sms_sent_at','-datetime in_app_sent_at'],
 m:['+notification(): Notification','+markAsRead(): void','+markEmailAsSent(): void','+markPushAsSent(): void','+markSmsAsSent(): void','+markInAppAsSent(): void']}
];
const RELS=[
 {f:'Department',t:'Department',type:'agg',fc:'1',tc:'*',label:'cha-con',self:true},
 {f:'Department',t:'Lecturer',type:'agg',fc:'1',tc:'*',fs:'b',fp:.3,ts:'t',tp:.3},
 {f:'Department',t:'Classroom',type:'agg',fc:'1',tc:'*',fs:'r',fp:.4,ts:'l',tp:.25},
 {f:'Lecturer',t:'LecturerAccount',type:'comp',fc:'1',tc:'1',fs:'b',fp:.4,ts:'t',tp:.4},
 {f:'Lecturer',t:'Classroom',type:'assoc',fc:'1',tc:'*',label:'GVCN',fs:'r',fp:.08,ts:'l',tp:.85},
 {f:'Lecturer',t:'Course',type:'assoc',fc:'1',tc:'*',label:'giảng dạy',fs:'r',fp:.4,ts:'l',tp:.1},
 {f:'Classroom',t:'Student',type:'agg',fc:'1',tc:'*',fs:'b',fp:.5,ts:'t',tp:.3},
 {f:'Student',t:'StudentAccount',type:'comp',fc:'1',tc:'1',fs:'b',fp:.4,ts:'t',tp:.4},
 {f:'Student',t:'CourseEnrollment',type:'assoc',fc:'1',tc:'*',fs:'r',fp:.08,ts:'l',tp:.5},
 {f:'Student',t:'Attendance',type:'assoc',fc:'1',tc:'*',fs:'r',fp:.88,ts:'l',tp:.2},
 {f:'Semester',t:'Course',type:'agg',fc:'1',tc:'*',fs:'b',fp:.5,ts:'t',tp:.3},
 {f:'Course',t:'CourseEnrollment',type:'comp',fc:'1',tc:'*',fs:'r',fp:.08,ts:'l',tp:.88},
 {f:'Course',t:'AttendanceSession',type:'comp',fc:'1',tc:'*',fs:'r',fp:.4,ts:'l',tp:.18},
 {f:'AttendanceSession',t:'Attendance',type:'comp',fc:'1',tc:'*',fs:'b',fp:.5,ts:'t',tp:.5},
 {f:'Lecturer',t:'AttendanceSession',type:'dep',label:'điểm danh',fs:'r',fp:.78,ts:'l',tp:.75,
   wp:[[310,560],[420,660],[1170,660]]},
 {f:'Holiday',t:'AttendanceSession',type:'dep',label:'ảnh hưởng',fs:'r',fp:.3,ts:'b',tp:.25},
 {f:'NotificationTemplate',t:'Notification',type:'agg',fc:'1',tc:'*',fs:'b',fp:.5,ts:'t',tp:.5},
 {f:'Notification',t:'UserNotification',type:'comp',fc:'1',tc:'*',fs:'b',fp:.5,ts:'t',tp:.5}
];
function gh(c){const mh=c.m&&c.m.length>0?SEP+c.m.length*LH:0;return HDR+c.a.length*LH+PAD+mh;}
function getC(id){return CLS.find(c=>c.id===id);}
function anchor(c,side,pos){const h=gh(c);if(side==='t')return[c.x+BW*pos,c.y];if(side==='b')return[c.x+BW*pos,c.y+h];if(side==='l')return[c.x,c.y+h*pos];return[c.x+BW,c.y+h*pos];}
function render(){
 const svg=document.getElementById('dg');let mx=0,my=0;
 CLS.forEach(c=>{mx=Math.max(mx,c.x+BW+30);my=Math.max(my,c.y+gh(c)+30);});
 svg.setAttribute('width',mx);svg.setAttribute('height',my);svg.setAttribute('viewBox',`0 0 ${mx} ${my}`);
 let h=`<defs><marker id="aE" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#444"/></marker><marker id="aD" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#888"/></marker><marker id="dF" markerWidth="14" markerHeight="10" refX="1" refY="5" orient="auto"><polygon points="7 0,14 5,7 10,0 5" fill="#444"/></marker><marker id="dO" markerWidth="14" markerHeight="10" refX="1" refY="5" orient="auto"><polygon points="7 0,14 5,7 10,0 5" fill="#fff" stroke="#444" stroke-width="1.5"/></marker></defs>`;
 RELS.forEach(r=>{const fc=getC(r.f),tc=getC(r.t);if(!fc||!tc)return;
  if(r.self){const rx=fc.x+BW,ry=fc.y+15;h+=`<path d="M${rx},${ry} C${rx+50},${ry-10} ${rx+50},${ry+50} ${rx},${ry+40}" fill="none" stroke="#444" stroke-width="1.8" marker-start="url(#dO)" marker-end="url(#aE)"/>`;h+=`<text x="${rx+55}" y="${ry+20}" font-size="11" fill="#555" font-style="italic">${r.label||''}</text>`;h+=`<text x="${rx+4}" y="${ry-5}" font-size="10" fill="#333" font-weight="bold">${r.fc||''}</text>`;h+=`<text x="${rx+4}" y="${ry+52}" font-size="10" fill="#333" font-weight="bold">${r.tc||''}</text>`;return;}
  const[x1,y1]=anchor(fc,r.fs||'r',r.fp||.5),[x2,y2]=anchor(tc,r.ts||'l',r.tp||.5);
  let ms='',me='marker-end="url(#aE)"',da='',cl='#444';
  if(r.type==='comp')ms='marker-start="url(#dF)"';if(r.type==='agg')ms='marker-start="url(#dO)"';
  if(r.type==='dep'){da='stroke-dasharray="7,4"';cl='#888';me='marker-end="url(#aD)"';}
  if(r.wp){let d=`M${x1},${y1}`;r.wp.forEach(p=>d+=` L${p[0]},${p[1]}`);d+=` L${x2},${y2}`;h+=`<path d="${d}" fill="none" stroke="${cl}" stroke-width="1.8" ${da} ${ms} ${me}/>`;}
  else{const dx=x2-x1,dy=y2-y1,hz=(r.fs==='l'||r.fs==='r')&&(r.ts==='l'||r.ts==='r'),vt=(r.fs==='t'||r.fs==='b')&&(r.ts==='t'||r.ts==='b');
   if(hz){const cx=dx/2;h+=`<path d="M${x1},${y1} C${x1+cx},${y1} ${x2-cx},${y2} ${x2},${y2}" fill="none" stroke="${cl}" stroke-width="1.8" ${da} ${ms} ${me}/>`;}
   else if(vt){const cy=dy/2;h+=`<path d="M${x1},${y1} C${x1},${y1+cy} ${x2},${y2-cy} ${x2},${y2}" fill="none" stroke="${cl}" stroke-width="1.8" ${da} ${ms} ${me}/>`;}
   else h+=`<path d="M${x1},${y1} C${x1+(x2-x1)*.5},${y1} ${x2-(x2-x1)*.5},${y2} ${x2},${y2}" fill="none" stroke="${cl}" stroke-width="1.8" ${da} ${ms} ${me}/>`;
  }
  if(r.fc)h+=`<text x="${x1+(r.fs==='r'?20:r.fs==='l'?-22:8)}" y="${y1+(r.fs==='b'?18:r.fs==='t'?-8:r.fp<.5?-8:14)}" font-size="11" fill="#222" font-weight="bold">${r.fc}</text>`;
  if(r.tc)h+=`<text x="${x2+(r.ts==='l'?-22:r.ts==='r'?20:8)}" y="${y2+(r.ts==='t'?-8:r.ts==='b'?18:r.tp<.5?-8:14)}" font-size="11" fill="#222" font-weight="bold">${r.tc}</text>`;
  if(r.label&&!r.wp){const lx=(x1+x2)/2,ly=(y1+y2)/2;h+=`<rect x="${lx-35}" y="${ly-16}" width="70" height="14" rx="3" fill="#fff" opacity="0.9"/>`;h+=`<text x="${lx}" y="${ly-6}" text-anchor="middle" font-size="10" fill="#666" font-style="italic">${r.label}</text>`;}
  if(r.label&&r.wp){const mp=r.wp[Math.floor(r.wp.length/2)];h+=`<rect x="${mp[0]-35}" y="${mp[1]-16}" width="70" height="14" rx="3" fill="#fff" opacity="0.9"/>`;h+=`<text x="${mp[0]}" y="${mp[1]-6}" text-anchor="middle" font-size="10" fill="#666" font-style="italic">${r.label}</text>`;}
 });
 CLS.forEach(c=>{const ht=gh(c),aeY=c.y+HDR+c.a.length*LH+PAD;
  h+=`<rect x="${c.x+3}" y="${c.y+3}" width="${BW}" height="${ht}" rx="${RAD}" fill="#ccc" opacity="0.3"/>`;
  h+=`<rect x="${c.x}" y="${c.y}" width="${BW}" height="${ht}" rx="${RAD}" fill="#fff" stroke="#333" stroke-width="1.8"/>`;
  h+=`<rect x="${c.x}" y="${c.y}" width="${BW}" height="${HDR}" rx="${RAD}" fill="#34495e"/>`;
  h+=`<rect x="${c.x}" y="${c.y+HDR-RAD}" width="${BW}" height="${RAD}" fill="#34495e"/>`;
  h+=`<line x1="${c.x}" y1="${c.y+HDR}" x2="${c.x+BW}" y2="${c.y+HDR}" stroke="#333" stroke-width="1.8"/>`;
  h+=`<text x="${c.x+BW/2}" y="${c.y+HDR/2+1}" text-anchor="middle" dominant-baseline="middle" font-size="15" font-weight="bold" fill="#fff">${c.id}</text>`;
  c.a.forEach((a,i)=>{h+=`<text x="${c.x+8}" y="${c.y+HDR+PAD/2+(i+1)*LH-3}" font-size="${FS}" fill="#222" font-weight="500">${a}</text>`;});
  if(c.m&&c.m.length>0){
   h+=`<line x1="${c.x}" y1="${aeY}" x2="${c.x+BW}" y2="${aeY}" stroke="#bbb" stroke-width="1"/>`;
   c.m.forEach((m,i)=>{h+=`<text x="${c.x+8}" y="${aeY+SEP/2+(i+1)*LH-3}" font-size="${FS}" fill="#2471a3" font-weight="600">${m}</text>`;});
  }
 });
 svg.innerHTML=h;
}
render();
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
