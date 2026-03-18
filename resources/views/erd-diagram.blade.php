<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ERD - HPC System</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:#fff;height:100vh;display:flex;flex-direction:column}
.topbar{display:flex;align-items:center;gap:8px;padding:7px 14px;background:#2c3e50;color:#fff;flex-shrink:0;border-bottom:1px solid #ddd;flex-wrap:wrap}
.topbar h1{font-size:13.5px;font-weight:700;flex:1;min-width:160px}
.badge{padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;color:#fff}
.btn{padding:5px 12px;border:none;border-radius:5px;font-size:11.5px;cursor:pointer;font-weight:600;transition:.2s}
.btn-fit{background:#10b981;color:#fff}.btn-fit:hover{background:#059669}
.btn-fit.on{background:#ef4444}
.btn-pr{background:#3b82f6;color:#fff}
.wrap{flex:1;overflow:auto;padding:10px;display:flex;align-items:flex-start;justify-content:center}
.inner{transform-origin:top center;transition:transform .15s}
svg{display:block;margin:0 auto}
@media print{.topbar .btn{display:none}.wrap{padding:2px;justify-content:flex-start}.inner{transform-origin:top left}}
</style>
</head>
<body>
<div class="topbar">
  <h1>🗃️ ERD – Hệ thống Quản lý HPC (Crow's Foot Notation)</h1>
  <span class="badge" style="background:#1e40af">👤 Người dùng</span>
  <span class="badge" style="background:#14532d">📅 Học vụ</span>
  <span class="badge" style="background:#6d28d9">✅ Điểm danh</span>
  <span class="badge" style="background:#0e7490">🔔 Thông báo</span>
  <span class="badge" style="background:#374151">⚙️ Hệ thống</span>
  <button class="btn btn-fit" id="fitBtn" onclick="toggleFit()">⛶ Fit màn hình</button>
  <button class="btn btn-pr" onclick="window.print()">🖨️ In</button>
</div>
<div class="wrap" id="wrap">
  <div class="inner" id="inner">
    <svg id="erd" xmlns="http://www.w3.org/2000/svg" font-family="'Segoe UI',Arial,sans-serif"></svg>
  </div>
</div>
<script>
// ═══════════════════════════════════════════════════
//  CONSTANTS
// ═══════════════════════════════════════════════════
const EW  = 178;  // entity width
const RH  = 17;   // row height
const HH  = 26;   // header height
const PAD = 7;
const GAP = 255;  // horizontal center-to-center — wider gap for visible relations
const PAD_TOP = 32; // space above each row (for banner)

const COLORS = {
  user:   {h:'#1e40af', b:'#dbeafe', s:'#93c5fd'},
  acad:   {h:'#14532d', b:'#dcfce7', s:'#4ade80'},
  attend: {h:'#4c1d95', b:'#ede9fe', s:'#a78bfa'},
  notif:  {h:'#0e4f63', b:'#cffafe', s:'#22d3ee'},
  sys:    {h:'#1f2937', b:'#f3f4f6', s:'#9ca3af'},
};

function bh(attrs){ return HH + attrs.length*RH + 4; }

// ═══════════════════════════════════════════════════
//  ENTITIES
// ═══════════════════════════════════════════════════
const E = [
  // ── ROW 0: USERS ─────────────────────────────────
  {id:'department', c:'user', row:0, col:0, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'name : varchar'},
    {n:'type : enum(school/faculty/dept)'},
    {n:'FK  parent_id → department',k:'fk'},
    {n:'staff_count : int'},
  ]},
  {id:'lecturer', c:'user', row:0, col:1, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'full_name : varchar'},
    {n:'birth_date : date'},
    {n:'gender : enum(m/f/other)'},
    {n:'address : varchar'},
    {n:'email : varchar ◆'},
    {n:'phone : varchar(20)'},
    {n:'experience_number : int'},
    {n:'lecturer_code : varchar ◆'},
    {n:'FK  department_id',        k:'fk'},
    {n:'bang_cap : varchar'},
    {n:'ngay_bat_dau_lam_viec : date'},
    {n:'hinh_anh : varchar'},
  ]},
  {id:'lecturer_account', c:'user', row:0, col:2, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  lecturer_id → lecturer',k:'fk'},
    {n:'username : varchar ◆'},
    {n:'password : varchar'},
    {n:'is_admin : tinyint(0/1)'},
  ]},
  {id:'class', c:'user', row:0, col:3, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'class_name : varchar'},
    {n:'class_code : varchar ◆'},
    {n:'FK  department_id',        k:'fk'},
    {n:'FK  lecturer_id',          k:'fk'},
    {n:'school_year : varchar(20)'},
  ]},
  {id:'student', c:'user', row:0, col:4, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'full_name : varchar'},
    {n:'birth_date : date'},
    {n:'gender : enum(m/f/other)'},
    {n:'address : varchar'},
    {n:'email : varchar ◆'},
    {n:'phone : varchar(20)'},
    {n:'student_code : varchar ◆'},
    {n:'FK  class_id → class',     k:'fk'},
    {n:'account_status : enum'},
    {n:'FK  import_job_id',        k:'fk'},
    {n:'imported_at : timestamp'},
  ]},
  {id:'student_account', c:'user', row:0, col:5, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  student_id → student', k:'fk'},
    {n:'username : varchar ◆'},
    {n:'password : varchar'},
  ]},

  // ── ROW 1: ACADEMIC (col 0-3) + ATTENDANCE (col 4-5) ──
  {id:'semesters', c:'acad', row:1, col:0, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'code : varchar(20) ◆'},
    {n:'name : varchar(100)'},
    {n:'academic_year : varchar'},
    {n:'start_date / end_date : date'},
    {n:'is_active : boolean'},
  ]},
  {id:'courses', c:'acad', row:1, col:1, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'code : varchar(50)'},
    {n:'name : varchar'},
    {n:'credits : int'},
    {n:'description : text'},
    {n:'FK  semester_id → semesters',k:'fk'},
    {n:'FK  lecturer_id',           k:'fk'},
    {n:'FK  department_id',         k:'fk'},
    {n:'schedule_days : json'},
    {n:'start_time / end_time : time'},
    {n:'room : varchar(50)'},
    {n:'start_date / end_date : date'},
    {n:'total_sessions : int'},
    {n:'max_absences : int'},
    {n:'absence_warning : int'},
    {n:'late_threshold_minutes : int'},
    {n:'status : enum'},
    {n:'sessions_generated : bool'},
    {n:'◆ UQ(code, semester_id)'},
  ]},
  {id:'course_enrollments', c:'acad', row:1, col:2, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  course_id → courses',  k:'fk'},
    {n:'FK  student_id → student', k:'fk'},
    {n:'enrolled_at : date'},
    {n:'status : enum'},
    {n:'◆ UQ(course_id, student_id)'},
  ]},
  {id:'holidays', c:'acad', row:1, col:3, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'name : varchar'},
    {n:'date : date'},
    {n:'is_recurring : boolean'},
    {n:'description : text'},
  ]},
  {id:'attendance_sessions', c:'attend', row:1, col:4, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  course_id → courses',  k:'fk'},
    {n:'session_number : int'},
    {n:'session_date : date'},
    {n:'day_of_week : tinyint'},
    {n:'start_time / end_time : time'},
    {n:'topic : varchar'},
    {n:'room : varchar(50)'},
    {n:'notes : text'},
    {n:'status : enum'},
    {n:'started_at / completed_at'},
    {n:'FK  marked_by → lecturer', k:'fk'},
    {n:'◆ UQ(course_id, session_number)'},
  ]},
  {id:'attendances', c:'attend', row:1, col:5, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  session_id → att_sessions',k:'fk'},
    {n:'FK  student_id → student', k:'fk'},
    {n:'FK  marked_by → lecturer', k:'fk'},
    {n:'status : enum'},
    {n:'minutes_late : int'},
    {n:'check_in_time : time'},
    {n:'reason : text'},
    {n:'note / excuse_reason'},
    {n:'◆ UQ(session_id, student_id)'},
  ]},

  // ── ROW 2: NOTIFICATIONS (col 0-2) + SYSTEM (col 3-4) centered
  {id:'notification_templates', c:'notif', row:2, col:0, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'name : varchar ◆'},
    {n:'title / subject : varchar'},
    {n:'email_template : text'},
    {n:'sms_template : text'},
    {n:'push_template : text'},
    {n:'in_app_template : text'},
    {n:'channels : json'},
    {n:'priority : varchar'},
    {n:'category : varchar'},
    {n:'description : text'},
    {n:'is_active : boolean'},
  ]},
  {id:'notifications', c:'notif', row:2, col:1, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  template_id',          k:'fk'},
    {n:'title : varchar'},
    {n:'content : text'},
    {n:'type : varchar'},
    {n:'priority : varchar'},
    {n:'data : json'},
    {n:'sender_id (polymorphic)'},
    {n:'sender_type : varchar'},
    {n:'scheduled_at : datetime'},
    {n:'sent_at : datetime'},
    {n:'status : enum'},
  ]},
  {id:'user_notifications', c:'notif', row:2, col:2, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  notification_id',      k:'fk'},
    {n:'user_id (polymorphic)'},
    {n:'user_type : varchar'},
    {n:'is_read : boolean'},
    {n:'read_at : datetime'},
    {n:'email_sent : boolean'},
    {n:'push_sent : boolean'},
    {n:'sms_sent : boolean'},
    {n:'in_app_sent : boolean'},
    {n:'email_sent_at : datetime'},
    {n:'push_sent_at : datetime'},
    {n:'sms_sent_at : datetime'},
    {n:'in_app_sent_at : datetime'},
  ]},
  {id:'import_jobs', c:'sys', row:2, col:3, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  user_id',              k:'fk'},
    {n:'entity_type : enum(student/lecturer)'},
    {n:'file_path : varchar'},
    {n:'status : enum'},
    {n:'total / success / failed : int'},
    {n:'processed_rows : int'},
    {n:'error : text'},
  ]},
  {id:'import_failures', c:'sys', row:2, col:4, attrs:[
    {n:'PK  id',                   k:'pk'},
    {n:'FK  import_job_id',        k:'fk'},
    {n:'row_number : int'},
    {n:'attribute : varchar(100)'},
    {n:'errors : text'},
    {n:'values : json'},
  ]},
];


// ═══════════════════════════════════════════════════
//  LAYOUT CALCULATION
// ═══════════════════════════════════════════════════
// Max columns per row
const ROW_COLS = {0:6, 1:6, 2:5};
const ROW_OFF  = {0:0, 1:0, 2:0.5}; // col offset to center row 2
const START_X = 40;

// Compute row heights dynamically
function rowMaxH(r){
  return E.filter(e=>e.row===r).reduce((m,e)=>Math.max(m,bh(e.attrs)),0);
}

const ROW_GAP = 100; // gap between rows — more room for relations

function rowY(r){
  let y = 10;
  for(let i=0;i<r;i++) y += rowMaxH(i) + ROW_GAP + PAD_TOP;
  y += PAD_TOP;
  return y;
}

function cx(e){
  return START_X + EW/2 + (e.col + ROW_OFF[e.row]) * GAP;
}
function cy(e){ return rowY(e.row); }

// ═══════════════════════════════════════════════════
//  RELATIONSHIPS
// ═══════════════════════════════════════════════════
// [fromId, toId, label, cardFrom, cardTo, laneOffset]
// laneOffset = horizontal pixel offset so parallel lines spread apart
const RELS = [
  // ── Users ──────────────────────────────────
  ['department','lecturer',         'has',          'one','many',  -30],
  ['department','class',            'has',          'one','many',  +30],
  ['lecturer',  'lecturer_account', 'has account',  'one','one',     0],
  ['class',     'student',          'contains',     'one','many',    0],
  ['student',   'student_account',  'has account',  'one','one',     0],
  // ── Academic ───────────────────────────────
  ['semesters', 'courses',          'contains',     'one','many',  -20],
  ['lecturer',  'courses',          'teaches',      'one','many',  +20],
  ['courses',   'course_enrollments','has',         'one','many',  -15],
  ['student',   'course_enrollments','enrolls',     'one','many',  +15],
  // ── Attendance ─────────────────────────────
  ['courses',   'attendance_sessions','schedules',  'one','many',    0],
  ['attendance_sessions','attendances','records',   'one','many',    0],
  ['student',   'attendances',      'has',          'one','many',    0],
  // ── Notifications ──────────────────────────
  ['notification_templates','notifications','uses', 'one','many',    0],
  ['notifications','user_notifications','to',       'one','many',    0],
  // ── System ─────────────────────────────────
  ['import_jobs','import_failures', 'has errors',   'one','many',    0],
];

// ═══════════════════════════════════════════════════
//  SVG DRAWING
// ═══════════════════════════════════════════════════
function getEnt(id){ return E.find(e=>e.id===id); }

function entCx(id){ const e=getEnt(id); return e?cx(e):0; }
function entCy(id){ const e=getEnt(id); return e?cy(e)+bh(e.attrs)/2:0; }

function getPort(id, tx, ty){
  const e=getEnt(id); if(!e) return null;
  const ecx=cx(e), ecy=cy(e)+bh(e.attrs)/2;
  const dx=tx-ecx, dy=ty-ecy;
  const W=EW/2, H=bh(e.attrs)/2;
  let px,py,ang;
  if(Math.abs(dx/W) > Math.abs(dy/H)){
    if(dx>0){px=ecx+W;py=ecy;ang=0;}
    else    {px=ecx-W;py=ecy;ang=Math.PI;}
  } else {
    if(dy>0){px=ecx;py=ecy+H;ang=Math.PI/2;}
    else    {px=ecx;py=ecy-H;ang:-Math.PI/2;}
    ang=dy>0?Math.PI/2:-Math.PI/2;
  }
  return {x:px,y:py,ang};
}

function crowFoot(px,py,ang,type){
  const cos=Math.cos(ang),sin=Math.sin(ang);
  const nx=-sin,ny=cos;
  const sz=6,d1=8,d2=15;
  let s='';
  const clr='#fbbf24';
  if(type==='one'){
    for(const d of [d1,d2]){
      const bx=px+cos*d,by=py+sin*d;
      s+=`<line x1="${bx-nx*sz}" y1="${by-ny*sz}" x2="${bx+nx*sz}" y2="${by+ny*sz}" stroke="${clr}" stroke-width="1.8"/>`;
    }
  } else {
    const tipx=px+cos*d1,tipy=py+sin*d1;
    const barx=px+cos*d2,bary=py+sin*d2;
    s+=`<line x1="${px}" y1="${py}" x2="${tipx}" y2="${tipy}" stroke="${clr}" stroke-width="1.2"/>`;
    s+=`<line x1="${tipx}" y1="${tipy}" x2="${barx+nx*sz}" y2="${bary+ny*sz}" stroke="${clr}" stroke-width="1.4"/>`;
    s+=`<line x1="${tipx}" y1="${tipy}" x2="${barx-nx*sz}" y2="${bary-ny*sz}" stroke="${clr}" stroke-width="1.4"/>`;
    s+=`<line x1="${barx-nx*sz}" y1="${bary-ny*sz}" x2="${barx+nx*sz}" y2="${bary+ny*sz}" stroke="${clr}" stroke-width="1.8"/>`;
  }
  return s;
}

function drawRel(fromId, toId, lbl, cf, ct, lane){
  lane = lane || 0;
  const e1=getEnt(fromId), e2=getEnt(toId);
  if(!e1||!e2) return '';
  const tc={x:cx(e2), y:cy(e2)+bh(e2.attrs)/2};
  const fc={x:cx(e1), y:cy(e1)+bh(e1.attrs)/2};
  const fp=getPort(fromId, tc.x, tc.y);
  const tp=getPort(toId,   fc.x, fc.y);
  if(!fp||!tp) return '';

  // Bezier control point distance
  const dist = Math.sqrt((fp.x-tp.x)**2 + (fp.y-tp.y)**2);
  const cpLen = Math.min(dist*0.45, 110);

  // Direction vectors from angles
  const fpDx = Math.cos(fp.ang)*cpLen;
  const fpDy = Math.sin(fp.ang)*cpLen;
  const tpDx = Math.cos(tp.ang)*cpLen;
  const tpDy = Math.sin(tp.ang)*cpLen;

  // Apply lane offset perpendicular to line direction
  const cpx1 = fp.x + fpDx + lane * Math.sin(fp.ang);
  const cpy1 = fp.y + fpDy - lane * Math.cos(fp.ang);
  const cpx2 = tp.x + tpDx + lane * Math.sin(tp.ang);
  const cpy2 = tp.y + tpDy - lane * Math.cos(tp.ang);

  const d = `M${fp.x},${fp.y} C${cpx1},${cpy1} ${cpx2},${cpy2} ${tp.x},${tp.y}`;

  let s = `<path d="${d}" fill="none" stroke="#fbbf24" stroke-width="1.4" opacity=".6"/>`;

  // Label at curve midpoint (t=0.5)
  const mx = 0.125*fp.x + 0.375*cpx1 + 0.375*cpx2 + 0.125*tp.x;
  const my = 0.125*fp.y + 0.375*cpy1 + 0.375*cpy2 + 0.125*tp.y;
  const lw = lbl.length*5+10;
  s+=`<rect x="${mx-lw/2}" y="${my-8}" width="${lw}" height="13" fill="#fff" rx="3" opacity=".9" stroke="#ddd" stroke-width=".5"/>`;
  s+=`<text x="${mx}" y="${my+2}" text-anchor="middle" fill="#555" font-size="9.5" font-style="italic">${lbl}</text>`;

  s+=crowFoot(fp.x,fp.y,fp.ang,cf);
  s+=crowFoot(tp.x,tp.y,tp.ang,ct);
  return s;
}

function drawEnt(e){
  const x=cx(e)-EW/2, y=cy(e), h=bh(e.attrs);
  const co=COLORS[e.c];
  let s='';
  // drop shadow
  s+=`<rect x="${x+3}" y="${y+3}" width="${EW}" height="${h}" rx="5" fill="rgba(0,0,0,.12)"/>`;
  // body
  s+=`<rect x="${x}" y="${y}" width="${EW}" height="${h}" rx="5" fill="${co.b}" stroke="${co.s}" stroke-width="1.8"/>`;
  // header
  s+=`<rect x="${x}" y="${y}" width="${EW}" height="${HH}" rx="5" fill="${co.h}"/>`;
  s+=`<rect x="${x}" y="${y+HH-5}" width="${EW}" height="5" fill="${co.h}"/>`;
  s+=`<text x="${x+EW/2}" y="${y+HH-7}" text-anchor="middle" fill="#fff" font-weight="700" font-size="12.5">${e.id}</text>`;
  s+=`<line x1="${x}" y1="${y+HH}" x2="${x+EW}" y2="${y+HH}" stroke="${co.s}" stroke-width="1.5"/>`;

  // PK separator
  const pkN=e.attrs.filter(a=>a.k==='pk').length;
  if(pkN<e.attrs.length){
    const sepY=y+HH+2+pkN*RH;
    s+=`<line x1="${x+6}" y1="${sepY}" x2="${x+EW-6}" y2="${sepY}" stroke="${co.s}" stroke-width="1" stroke-dasharray="4,3" opacity=".4"/>`;
  }

  // Attributes
  e.attrs.forEach((a,i)=>{
    const ry=y+HH+2+i*RH;
    if(i%2===0) s+=`<rect x="${x+1}" y="${ry}" width="${EW-2}" height="${RH}" fill="rgba(0,0,0,.04)"/>`;
    const fc=a.k==='pk'?'#92400e':a.k==='fk'?'#1e3a8a':'#334155';
    const fw=(a.k==='pk'||a.k==='fk')?'bold':'normal';
    s+=`<text x="${x+PAD}" y="${ry+12}" fill="${fc}" font-size="10" font-weight="${fw}">${a.n}</text>`;
  });
  return s;
}

// ═══════════════════════════════════════════════════
//  RENDER
// ═══════════════════════════════════════════════════
function canvasSize(){
  let mxX=0,mxY=0;
  E.forEach(e=>{
    mxX=Math.max(mxX,cx(e)+EW/2+30);
    mxY=Math.max(mxY,cy(e)+bh(e.attrs)+30);
  });
  return {w:mxX,h:mxY};
}

function render(){
  const {w,h}=canvasSize();
  const svg=document.getElementById('erd');
  svg.setAttribute('width',w); svg.setAttribute('height',h);

  let s='';
  s+=`<rect width="${w}" height="${h}" fill="#fff"/>`;
  // grid
  for(let i=0;i<w;i+=50) s+=`<line x1="${i}" y1="0" x2="${i}" y2="${h}" stroke="#f0f0f0" stroke-width="1"/>`;
  for(let i=0;i<h;i+=50) s+=`<line x1="0" y1="${i}" x2="${w}" y2="${i}" stroke="#f0f0f0" stroke-width="1"/>`;

  // Row domain banners
  function banner(lbl,color,row,colStart,colSpan){
    const bx=START_X+(colStart+ROW_OFF[row])*GAP-4;
    const bw=colSpan*GAP+EW-colSpan*GAP+colSpan*GAP-4;
    const by=rowY(row)-PAD_TOP+2;
    const bw2=colSpan===6?6*GAP-GAP+EW+2:colSpan*GAP-GAP+EW+2;
    return `<rect x="${bx}" y="${by}" width="${bw2}" height="20" rx="4" fill="${color}" opacity=".18"/>
<text x="${bx+7}" y="${by+14}" fill="${color}" font-size="10.5" font-weight="700">${lbl}</text>`;
  }

  s+=banner('👤  NGƯỜI DÙNG & TỔ CHỨC', '#3b82f6', 0, 0, 6);
  s+=banner('📅  HỌC VỤ', '#22c55e', 1, 0, 4);
  s+=banner('✅  ĐIỂM DANH', '#a855f7', 1, 4, 2);
  s+=banner('🔔  THÔNG BÁO', '#06b6d4', 2, 0, 3);
  s+=banner('⚙️  HỆ THỐNG', '#9ca3af', 2, 3, 2);

  // Relations (behind)
  RELS.forEach(r=>{ s+=drawRel(r[0],r[1],r[2],r[3],r[4],r[5]||0); });
  // Entities (front)
  E.forEach(e=>{ s+=drawEnt(e); });

  svg.innerHTML=s;
}

// ═══════════════════════════════════════════════════
//  FIT TO SCREEN
// ═══════════════════════════════════════════════════
let fitMode=false;
function applyScale(){
  const inner=document.getElementById('inner');
  if(!fitMode){inner.style.transform='scale(1)';return;}
  const wrap=document.getElementById('wrap');
  const svg=document.getElementById('erd');
  const sw=parseFloat(svg.getAttribute('width'));
  const sh=parseFloat(svg.getAttribute('height'));
  const scale=Math.min((wrap.clientWidth-24)/sw,(wrap.clientHeight-24)/sh,1);
  inner.style.transform=`scale(${scale})`;
  inner.style.width=sw+'px'; inner.style.height=sh+'px';
}
function toggleFit(){
  fitMode=!fitMode; applyScale();
  const btn=document.getElementById('fitBtn');
  btn.textContent=fitMode?'🔍 Thực tế':'⛶ Fit màn hình';
  btn.classList.toggle('on',fitMode);
}
window.addEventListener('resize',()=>{if(fitMode)applyScale();});

render();
// auto-fit on load
fitMode=true; applyScale();
document.getElementById('fitBtn').textContent='🔍 Thực tế';
document.getElementById('fitBtn').classList.add('on');
</script>
</body>
</html>
