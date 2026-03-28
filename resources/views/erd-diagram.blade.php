<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ERD - HPC System</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f8f9fa;height:100vh;display:flex;flex-direction:column;overflow:hidden}
.topbar{display:flex;align-items:center;gap:8px;padding:8px 16px;background:#2c3e50;color:#fff;flex-shrink:0;flex-wrap:wrap}
.topbar h1{font-size:14px;font-weight:700;flex:1;min-width:160px}
.badge{padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;color:#fff}
.btn{padding:6px 14px;border:none;border-radius:5px;font-size:12px;cursor:pointer;font-weight:600;transition:.2s}
.btn-fit{background:#10b981;color:#fff}.btn-fit:hover{background:#059669}
.btn-fit.on{background:#ef4444}
.btn-pr{background:#3b82f6;color:#fff}
.canvas{flex:1;overflow:hidden;background:#fff;cursor:grab;position:relative}
.canvas:active{cursor:grabbing}
.inner{transform-origin:0 0;position:absolute;top:0;left:0}
svg{display:block}
@media print{.topbar .btn{display:none}.canvas{overflow:visible}.inner{transform:none!important}}
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
  <button class="btn btn-fit" id="fitBtn" onclick="toggleFit()">⛶ Fit</button>
  <button class="btn" style="background:#6366f1;color:#fff" onclick="zoomIn()">🔍+</button>
  <button class="btn" style="background:#6366f1;color:#fff" onclick="zoomOut()">🔍−</button>
  <button class="btn btn-pr" onclick="window.print()">🖨️ In</button>
</div>
<div class="canvas" id="cv">
  <div class="inner" id="inner">
    <svg id="erd" xmlns="http://www.w3.org/2000/svg" font-family="'Segoe UI',Arial,sans-serif"></svg>
  </div>
</div>
<script>
// ═══════════════════════════════════════════════════
//  CONSTANTS — BIGGER, BOLDER
// ═══════════════════════════════════════════════════
const EW  = 240;   // entity width (wider)
const RH  = 22;    // row height (taller)
const HH  = 32;    // header height
const PAD = 8;
const FS_ATTR = 13;  // attribute font size
const FS_HDR  = 15;  // header font size

const COLORS = {
  user:   {h:'#1e40af', b:'#dbeafe', s:'#93c5fd', t:'#fff'},
  acad:   {h:'#14532d', b:'#dcfce7', s:'#4ade80', t:'#fff'},
  attend: {h:'#4c1d95', b:'#ede9fe', s:'#a78bfa', t:'#fff'},
  notif:  {h:'#0e4f63', b:'#cffafe', s:'#22d3ee', t:'#fff'},
  sys:    {h:'#1f2937', b:'#f3f4f6', s:'#9ca3af', t:'#fff'},
};

function bh(attrs){ return HH + attrs.length*RH + 6; }

// ═══════════════════════════════════════════════════
//  ENTITIES — with explicit x,y positions for clean layout
// ═══════════════════════════════════════════════════
const E = [
  // ── ROW 0: USERS (y ~ 60) ─────────────────────
  {id:'department', c:'user', x:40, y:60, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'name : varchar'},
    {n:'type : enum(school/faculty/dept)'},
    {n:'FK  parent_id → department', k:'fk'},
    {n:'staff_count : int'},
  ]},
  {id:'lecturer', c:'user', x:340, y:60, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'full_name : varchar'},
    {n:'birth_date : date'},
    {n:'gender : enum(m/f/other)'},
    {n:'address : varchar'},
    {n:'email : varchar ◆'},
    {n:'phone : varchar(20)'},
    {n:'experience_number : int'},
    {n:'lecturer_code : varchar ◆'},
    {n:'FK  department_id', k:'fk'},
    {n:'bang_cap : varchar'},
    {n:'ngay_bat_dau_lam_viec : date'},
    {n:'hinh_anh : varchar'},
  ]},
  {id:'lecturer_account', c:'user', x:640, y:60, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  lecturer_id → lecturer', k:'fk'},
    {n:'username : varchar ◆'},
    {n:'password : varchar'},
    {n:'is_admin : tinyint(0/1)'},
  ]},
  {id:'class', c:'user', x:940, y:60, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'class_name : varchar'},
    {n:'class_code : varchar ◆'},
    {n:'FK  department_id', k:'fk'},
    {n:'FK  lecturer_id', k:'fk'},
    {n:'school_year : varchar(20)'},
  ]},
  {id:'student', c:'user', x:1240, y:60, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'full_name : varchar'},
    {n:'birth_date : date'},
    {n:'gender : enum(m/f/other)'},
    {n:'address : varchar'},
    {n:'email : varchar ◆'},
    {n:'phone : varchar(20)'},
    {n:'student_code : varchar ◆'},
    {n:'FK  class_id → class', k:'fk'},
    {n:'account_status : enum'},
    {n:'FK  import_job_id', k:'fk'},
    {n:'imported_at : timestamp'},
  ]},
  {id:'student_account', c:'user', x:1540, y:60, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  student_id → student', k:'fk'},
    {n:'username : varchar ◆'},
    {n:'password : varchar'},
  ]},

  // ── ROW 1: ACADEMIC + ATTENDANCE (y ~ 470) ────
  {id:'semesters', c:'acad', x:40, y:480, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'code : varchar(20) ◆'},
    {n:'name : varchar(100)'},
    {n:'academic_year : varchar'},
    {n:'start_date / end_date : date'},
    {n:'is_active : boolean'},
  ]},
  {id:'courses', c:'acad', x:340, y:480, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'code : varchar(50)'},
    {n:'name : varchar'},
    {n:'credits : int'},
    {n:'description : text'},
    {n:'FK  semester_id → semesters', k:'fk'},
    {n:'FK  lecturer_id', k:'fk'},
    {n:'FK  department_id', k:'fk'},
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
  {id:'course_enrollments', c:'acad', x:640, y:480, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  course_id → courses', k:'fk'},
    {n:'FK  student_id → student', k:'fk'},
    {n:'enrolled_at : date'},
    {n:'status : enum'},
    {n:'◆ UQ(course_id, student_id)'},
  ]},
  {id:'holidays', c:'acad', x:640, y:680, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'name : varchar'},
    {n:'date : date'},
    {n:'is_recurring : boolean'},
    {n:'description : text'},
  ]},
  {id:'attendance_sessions', c:'attend', x:940, y:480, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  course_id → courses', k:'fk'},
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
  {id:'attendances', c:'attend', x:1240, y:480, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  session_id → att_sessions', k:'fk'},
    {n:'FK  student_id → student', k:'fk'},
    {n:'FK  marked_by → lecturer', k:'fk'},
    {n:'status : enum'},
    {n:'minutes_late : int'},
    {n:'check_in_time : time'},
    {n:'reason : text'},
    {n:'note / excuse_reason'},
    {n:'◆ UQ(session_id, student_id)'},
  ]},

  // ── ROW 2: NOTIFICATIONS + SYSTEM (y ~ 920) ──
  {id:'notification_templates', c:'notif', x:40, y:930, attrs:[
    {n:'PK  id', k:'pk'},
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
  {id:'notifications', c:'notif', x:340, y:930, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  template_id', k:'fk'},
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
  {id:'user_notifications', c:'notif', x:640, y:930, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  notification_id', k:'fk'},
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
  {id:'import_jobs', c:'sys', x:1040, y:930, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  user_id', k:'fk'},
    {n:'entity_type : enum(student/lecturer)'},
    {n:'file_path : varchar'},
    {n:'status : enum'},
    {n:'total / success / failed : int'},
    {n:'processed_rows : int'},
    {n:'error : text'},
  ]},
  {id:'import_failures', c:'sys', x:1340, y:930, attrs:[
    {n:'PK  id', k:'pk'},
    {n:'FK  import_job_id', k:'fk'},
    {n:'row_number : int'},
    {n:'attribute : varchar(100)'},
    {n:'errors : text'},
    {n:'values : json'},
  ]},
];

// ═══════════════════════════════════════════════════
//  RELATIONSHIPS — orthogonal routing
// ═══════════════════════════════════════════════════
// [fromId, toId, cardFrom, cardTo, routing hints]
// routing: 'auto' | {fromSide, toSide, waypoints[]}
const RELS = [
  // Users
  {f:'department', t:'lecturer',          cf:'1',ct:'N', fs:'r', ts:'l', fy:0.5, ty:0.7},
  {f:'lecturer',   t:'lecturer_account',  cf:'1',ct:'1', fs:'r', ts:'l', fy:0.15, ty:0.5},
  {f:'department', t:'class',             cf:'1',ct:'N', fs:'r', ts:'l', fy:0.3, ty:0.6,
    wp:[[290,100],[310,100],[310,30],[930,30],[930,145]]},
  {f:'lecturer',   t:'class',             cf:'1',ct:'N', fs:'r', ts:'l', fy:0.08, ty:0.8,
    wp:[[590,84],[610,84],[610,36],[926,36],[926,185]]},
  {f:'class',      t:'student',           cf:'1',ct:'N', fs:'r', ts:'l', fy:0.5, ty:0.6},
  {f:'student',    t:'student_account',   cf:'1',ct:'1', fs:'r', ts:'l', fy:0.15, ty:0.5},

  // Academic
  {f:'semesters',  t:'courses',           cf:'1',ct:'N', fs:'r', ts:'l', fy:0.5, ty:0.35},
  {f:'lecturer',   t:'courses',           cf:'1',ct:'N', fs:'b', ts:'t', fx:0.7, tx:0.5,
    wp:[[508,400],[508,450],[460,450],[460,470]]},
  {f:'courses',    t:'course_enrollments',cf:'1',ct:'N', fs:'r', ts:'l', fy:0.2, ty:0.4},
  {f:'student',    t:'course_enrollments',cf:'1',ct:'N', fs:'b', ts:'t', fx:0.5, tx:0.7,
    wp:[[1360,354],[1360,430],[808,430],[808,470]]},
  {f:'courses',    t:'attendance_sessions',cf:'1',ct:'N', fs:'r', ts:'l', fy:0.5, ty:0.3},
  {f:'attendance_sessions',t:'attendances',cf:'1',ct:'N', fs:'r', ts:'l', fy:0.3, ty:0.3},
  {f:'student',    t:'attendances',       cf:'1',ct:'N', fs:'b', ts:'t', fx:0.7, tx:0.5,
    wp:[[1414,354],[1414,440],[1360,440],[1360,470]]},

  // Notifications
  {f:'notification_templates',t:'notifications',cf:'1',ct:'N', fs:'r', ts:'l', fy:0.4, ty:0.15},
  {f:'notifications',t:'user_notifications',   cf:'1',ct:'N', fs:'r', ts:'l', fy:0.4, ty:0.15},

  // System
  {f:'import_jobs',t:'import_failures',   cf:'1',ct:'N', fs:'r', ts:'l', fy:0.5, ty:0.5},
];

// ═══════════════════════════════════════════════════
//  DRAWING — Entity boxes
// ═══════════════════════════════════════════════════
function getE(id){ return E.find(e=>e.id===id); }

function drawEnt(e){
  const x=e.x, y=e.y, h=bh(e.attrs), co=COLORS[e.c];
  let s='';
  // shadow
  s+=`<rect x="${x+3}" y="${y+3}" width="${EW}" height="${h}" rx="6" fill="rgba(0,0,0,.1)"/>`;
  // body
  s+=`<rect x="${x}" y="${y}" width="${EW}" height="${h}" rx="6" fill="${co.b}" stroke="${co.s}" stroke-width="2"/>`;
  // header
  s+=`<rect x="${x}" y="${y}" width="${EW}" height="${HH}" rx="6" fill="${co.h}"/>`;
  s+=`<rect x="${x}" y="${y+HH-6}" width="${EW}" height="6" fill="${co.h}"/>`;
  s+=`<text x="${x+EW/2}" y="${y+HH-9}" text-anchor="middle" fill="${co.t}" font-weight="800" font-size="${FS_HDR}">${e.id}</text>`;
  s+=`<line x1="${x}" y1="${y+HH}" x2="${x+EW}" y2="${y+HH}" stroke="${co.s}" stroke-width="2"/>`;

  // PK separator
  const pkN=e.attrs.filter(a=>a.k==='pk').length;
  if(pkN<e.attrs.length){
    const sepY=y+HH+3+pkN*RH;
    s+=`<line x1="${x+6}" y1="${sepY}" x2="${x+EW-6}" y2="${sepY}" stroke="${co.s}" stroke-width="1" stroke-dasharray="4,3" opacity=".5"/>`;
  }

  // Attributes
  e.attrs.forEach((a,i)=>{
    const ry=y+HH+3+i*RH;
    if(i%2===0) s+=`<rect x="${x+1}" y="${ry}" width="${EW-2}" height="${RH}" fill="rgba(0,0,0,.04)"/>`;
    const fc=a.k==='pk'?'#92400e':a.k==='fk'?'#1e3a8a':'#1e293b';
    const fw=(a.k==='pk'||a.k==='fk')?'700':'600';
    s+=`<text x="${x+PAD}" y="${ry+15}" fill="${fc}" font-size="${FS_ATTR}" font-weight="${fw}">${a.n}</text>`;
  });
  return s;
}

// ═══════════════════════════════════════════════════
//  DRAWING — Crow's Foot (standard ERD notation)
// ═══════════════════════════════════════════════════
function crowFoot(px, py, side, card){
  const sw=2;
  const clr='#444';
  let s='';

  // Direction vector pointing AWAY from entity
  let dx=0, dy=0;
  if(side==='r'){dx=1;} else if(side==='l'){dx=-1;} else if(side==='b'){dy=1;} else {dy=-1;}
  const nx=-dy, ny=dx; // perpendicular normal

  if(card==='1'){
    // "Exactly one" — two perpendicular bars ||
    const sz=8;
    const d1=6, d2=12;
    for(const d of [d1,d2]){
      const bx=px+dx*d, by=py+dy*d;
      s+=`<line x1="${bx+nx*sz}" y1="${by+ny*sz}" x2="${bx-nx*sz}" y2="${by-ny*sz}" stroke="${clr}" stroke-width="${sw}"/>`;
    }
  } else {
    // "Many" — crow's foot:  fork opens TOWARD entity
    //   Entity edge  <──|──  Line
    //   Prongs spread near entity, converge away on line side
    const sz=9;

    // Convergence point (single point, FURTHER from entity, on line side)
    const dConv=15;
    const cx=px+dx*dConv, cy=py+dy*dConv;

    // Spread endpoints (NEAR entity edge, spread apart)
    const dSpread=3;
    const topX=px+dx*dSpread+nx*sz, topY=py+dy*dSpread+ny*sz;
    const botX=px+dx*dSpread-nx*sz, botY=py+dy*dSpread-ny*sz;

    // Two prongs: from convergence point → spread out toward entity
    s+=`<line x1="${cx}" y1="${cy}" x2="${topX}" y2="${topY}" stroke="${clr}" stroke-width="${sw}"/>`;
    s+=`<line x1="${cx}" y1="${cy}" x2="${botX}" y2="${botY}" stroke="${clr}" stroke-width="${sw}"/>`;

    // Bar at the spread end (near entity)
    s+=`<line x1="${topX}" y1="${topY}" x2="${botX}" y2="${botY}" stroke="${clr}" stroke-width="${sw}"/>`;
  }
  return s;
}

// ═══════════════════════════════════════════════════
//  DRAWING — Orthogonal relationships (right-angle lines)
// ═══════════════════════════════════════════════════
function getAnchor(e, side, frac){
  // frac: 0-1 position along side
  const h=bh(e.attrs);
  if(side==='l') return {x:e.x,         y:e.y+h*frac};
  if(side==='r') return {x:e.x+EW,      y:e.y+h*frac};
  if(side==='t') return {x:e.x+EW*frac, y:e.y};
  if(side==='b') return {x:e.x+EW*frac, y:e.y+h};
  return {x:e.x, y:e.y};
}

function drawRel(rel){
  const e1=getE(rel.f), e2=getE(rel.t);
  if(!e1||!e2) return '';

  const fs=rel.fs||'r', ts=rel.ts||'l';
  const fy=rel.fy!==undefined?rel.fy:0.5;
  const ty=rel.ty!==undefined?rel.ty:0.5;
  const fx=rel.fx!==undefined?rel.fx:0.5;
  const tx=rel.tx!==undefined?rel.tx:0.5;

  const fFrac = (fs==='t'||fs==='b') ? fx : fy;
  const tFrac = (ts==='t'||ts==='b') ? tx : ty;

  const fp = getAnchor(e1, fs, fFrac);
  const tp = getAnchor(e2, ts, tFrac);

  let s='';
  const clr='#444';
  const sw=1.6;

  if(rel.wp){
    // Manual waypoints
    let d=`M${fp.x},${fp.y}`;
    rel.wp.forEach(p=>d+=` L${p[0]},${p[1]}`);
    d+=` L${tp.x},${tp.y}`;
    s+=`<path d="${d}" fill="none" stroke="${clr}" stroke-width="${sw}"/>`;
  } else {
    // Auto orthogonal routing
    if((fs==='l'||fs==='r')&&(ts==='l'||ts==='r')){
      // Horizontal → horizontal: L-shape or Z-shape
      const mx=(fp.x+tp.x)/2;
      if(Math.abs(fp.y-tp.y)<2){
        // Straight horizontal
        s+=`<line x1="${fp.x}" y1="${fp.y}" x2="${tp.x}" y2="${tp.y}" stroke="${clr}" stroke-width="${sw}"/>`;
      } else {
        // Z-shape: go horizontal to midpoint, then vertical, then horizontal
        s+=`<path d="M${fp.x},${fp.y} L${mx},${fp.y} L${mx},${tp.y} L${tp.x},${tp.y}" fill="none" stroke="${clr}" stroke-width="${sw}"/>`;
      }
    } else if((fs==='t'||fs==='b')&&(ts==='t'||ts==='b')){
      // Vertical → vertical
      const my=(fp.y+tp.y)/2;
      s+=`<path d="M${fp.x},${fp.y} L${fp.x},${my} L${tp.x},${my} L${tp.x},${tp.y}" fill="none" stroke="${clr}" stroke-width="${sw}"/>`;
    } else {
      // Mixed: L-shape
      if(fs==='b'||fs==='t'){
        s+=`<path d="M${fp.x},${fp.y} L${fp.x},${tp.y} L${tp.x},${tp.y}" fill="none" stroke="${clr}" stroke-width="${sw}"/>`;
      } else {
        s+=`<path d="M${fp.x},${fp.y} L${tp.x},${fp.y} L${tp.x},${tp.y}" fill="none" stroke="${clr}" stroke-width="${sw}"/>`;
      }
    }
  }

  // Crow's foot symbols
  s+=crowFoot(fp.x, fp.y, fs, rel.cf);
  s+=crowFoot(tp.x, tp.y, ts, rel.ct);
  return s;
}

// ═══════════════════════════════════════════════════
//  RENDER
// ═══════════════════════════════════════════════════
function render(){
  let mxX=0, mxY=0;
  E.forEach(e=>{
    mxX=Math.max(mxX, e.x+EW+40);
    mxY=Math.max(mxY, e.y+bh(e.attrs)+40);
  });

  const svg=document.getElementById('erd');
  svg.setAttribute('width', mxX);
  svg.setAttribute('height', mxY);

  let s='';
  // Background
  s+=`<rect width="${mxX}" height="${mxY}" fill="#fff"/>`;
  // Subtle grid
  for(let i=0;i<mxX;i+=50) s+=`<line x1="${i}" y1="0" x2="${i}" y2="${mxY}" stroke="#f0f0f0" stroke-width="1"/>`;
  for(let i=0;i<mxY;i+=50) s+=`<line x1="0" y1="${i}" x2="${mxX}" y2="${i}" stroke="#f0f0f0" stroke-width="1"/>`;

  // Domain banners
  function banner(lbl, color, x, y, w){
    return `<rect x="${x}" y="${y}" width="${w}" height="24" rx="4" fill="${color}" opacity=".15"/>
    <text x="${x+8}" y="${y+16}" fill="${color}" font-size="12" font-weight="800">${lbl}</text>`;
  }
  s+=banner('👤  NGƯỜI DÙNG & TỔ CHỨC', '#3b82f6', 36, 34, 1748);
  s+=banner('📅  HỌC VỤ', '#22c55e', 36, 454, 898);
  s+=banner('✅  ĐIỂM DANH', '#a855f7', 936, 454, 598);
  s+=banner('🔔  THÔNG BÁO', '#06b6d4', 36, 904, 898);
  s+=banner('⚙️  HỆ THỐNG', '#9ca3af', 1036, 904, 548);

  // Relationships (behind entities)
  RELS.forEach(r=>{ s+=drawRel(r); });
  // Entities (on top)
  E.forEach(e=>{ s+=drawEnt(e); });

  svg.innerHTML=s;
}

// ═══════════════════════════════════════════════════
//  PAN & ZOOM
// ═══════════════════════════════════════════════════
let scale=1, panX=0, panY=0, dragging=false, startX, startY;
const cv=document.getElementById('cv');
const inner=document.getElementById('inner');

function applyTransform(){
  inner.style.transform=`translate(${panX}px,${panY}px) scale(${scale})`;
}

function zoomIn(){ scale=Math.min(scale*1.2, 5); applyTransform(); }
function zoomOut(){ scale=Math.max(scale/1.2, 0.1); applyTransform(); }

function fitToScreen(){
  const svg=document.getElementById('erd');
  const sw=parseFloat(svg.getAttribute('width'));
  const sh=parseFloat(svg.getAttribute('height'));
  const cw=cv.clientWidth;
  const ch=cv.clientHeight;
  scale=Math.min(cw/sw, ch/sh)*0.95;
  panX=(cw-sw*scale)/2;
  panY=(ch-sh*scale)/2;
  applyTransform();
}

let fitMode=false;
function toggleFit(){
  fitMode=!fitMode;
  if(fitMode) fitToScreen();
  else { scale=1; panX=0; panY=0; applyTransform(); }
  const btn=document.getElementById('fitBtn');
  btn.textContent=fitMode?'🔍 Thực tế':'⛶ Fit';
  btn.classList.toggle('on',fitMode);
}

// Mouse pan
cv.addEventListener('mousedown',e=>{dragging=true;startX=e.clientX-panX;startY=e.clientY-panY;});
cv.addEventListener('mousemove',e=>{if(!dragging)return;panX=e.clientX-startX;panY=e.clientY-startY;applyTransform();});
cv.addEventListener('mouseup',()=>dragging=false);
cv.addEventListener('mouseleave',()=>dragging=false);

// Scroll zoom
cv.addEventListener('wheel',e=>{
  e.preventDefault();
  const rect=cv.getBoundingClientRect();
  const mx=e.clientX-rect.left, my=e.clientY-rect.top;
  const os=scale;
  scale=e.deltaY<0?Math.min(scale*1.1,5):Math.max(scale/1.1,0.1);
  panX=mx-(mx-panX)*(scale/os);
  panY=my-(my-panY)*(scale/os);
  applyTransform();
},{passive:false});

window.addEventListener('resize',()=>{if(fitMode)fitToScreen();});

render();
// Auto fit on load
setTimeout(()=>{ fitMode=true; fitToScreen(); document.getElementById('fitBtn').textContent='🔍 Thực tế'; document.getElementById('fitBtn').classList.add('on'); },100);
</script>
</body>
</html>
