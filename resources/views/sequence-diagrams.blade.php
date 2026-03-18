<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sơ đồ tuần tự - HPC System</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;display:flex;height:100vh;background:#f0f2f5}

/* SIDEBAR */
.sidebar{width:280px;background:#fff;border-right:2px solid #dde3ec;overflow-y:auto;flex-shrink:0}
.sidebar h2{padding:15px 16px;background:#0f3460;color:#fff;font-size:14.5px;text-align:center;position:sticky;top:0;z-index:10}
.sidebar .group-title{padding:8px 16px;background:#16213e;color:#90aec8;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px}
.sidebar .item{padding:10px 16px 10px 30px;cursor:pointer;border-bottom:1px solid #f0f0f0;font-size:13px;color:#333;transition:all .15s;user-select:none}
.sidebar .item:hover{background:#e3f0ff;color:#1a73e8}
.sidebar .item.active{background:#1a73e8;color:#fff;font-weight:600}

/* MAIN */
.main{flex:1;overflow:auto;padding:18px 22px;display:flex;flex-direction:column}
.topbar{display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.topbar h1{font-size:16px;color:#0f3460;font-weight:700;flex:1;min-width:200px}
.topbar .btns{display:flex;gap:8px;flex-shrink:0}
.btn{padding:7px 16px;border:none;border-radius:5px;font-size:12.5px;cursor:pointer;font-weight:600;transition:.2s}
.btn-fit{background:#2ecc71;color:#fff}.btn-fit:hover{background:#27ae60}
.btn-fit.on{background:#e74c3c}.btn-fit.on:hover{background:#c0392b}
.btn-print{background:#0f3460;color:#fff}.btn-print:hover{background:#1a4a8a}

.diagram-wrap{background:#fff;border:1px solid #c8d4e0;box-shadow:0 2px 10px rgba(0,0,0,.07);border-radius:6px;overflow:auto;flex:1;position:relative}
.diagram-inner{transform-origin:top left;transition:transform .2s}
svg{display:block}

@media print{.sidebar,.btns{display:none}.main{padding:4px}.diagram-wrap{border:none;box-shadow:none}}
</style>
</head>
<body>

<div class="sidebar">
  <h2>🔄 Sơ đồ tuần tự</h2>

  <div class="group-title">🔐 Xác thực</div>
  <div class="item active" onclick="show('login',this)">Đăng nhập hệ thống</div>

  <div class="group-title">👨‍🎓 Sinh viên</div>
  <div class="item" onclick="show('crudStudent',this)">Thêm / Sửa / Xóa sinh viên</div>
  <div class="item" onclick="show('importStudent',this)">Import sinh viên từ Excel</div>

  <div class="group-title">👨‍🏫 Giảng viên</div>
  <div class="item" onclick="show('importLecturer',this)">Import giảng viên từ Excel</div>

  <div class="group-title">📚 Môn học</div>
  <div class="item" onclick="show('createCourse',this)">Tạo môn học & sinh lịch</div>

  <div class="group-title">✅ Điểm danh</div>
  <div class="item" onclick="show('attendance',this)">Thực hiện điểm danh</div>
</div>

<div class="main">
  <div class="topbar">
    <h1 id="title">Sơ đồ tuần tự: Đăng nhập hệ thống</h1>
    <div class="btns">
      <button class="btn btn-fit" id="fitBtn" onclick="toggleFit()">⛶ Fit màn hình</button>
      <button class="btn btn-print" onclick="window.print()">🖨️ In</button>
    </div>
  </div>
  <div class="diagram-wrap" id="wrap">
    <div class="diagram-inner" id="inner">
      <div id="canvas"></div>
    </div>
  </div>
</div>

<script>
// ================================================================
//  FIT-TO-SCREEN LOGIC
// ================================================================
let fitMode = false;
function toggleFit(){
  fitMode = !fitMode;
  applyScale();
  const btn = document.getElementById('fitBtn');
  btn.textContent = fitMode ? '🔍 Kích thước gốc' : '⛶ Fit màn hình';
  btn.classList.toggle('on', fitMode);
}
function applyScale(){
  const inner = document.getElementById('inner');
  if(!fitMode){ inner.style.transform='scale(1)'; return; }
  const wrap  = document.getElementById('wrap');
  const svg   = inner.querySelector('svg');
  if(!svg) return;
  const svgW  = parseFloat(svg.getAttribute('width'));
  const svgH  = parseFloat(svg.getAttribute('height'));
  const wrapW = wrap.clientWidth  - 16;
  const wrapH = wrap.clientHeight - 16;
  const scale = Math.min(wrapW/svgW, wrapH/svgH, 1);
  inner.style.transform = `scale(${scale})`;
  inner.style.width  = svgW + 'px';
  inner.style.height = svgH + 'px';
}
window.addEventListener('resize', ()=>{ if(fitMode) applyScale(); });

// ================================================================
//  SVG SEQUENCE DIAGRAM ENGINE
// ================================================================
const C = {
  pL:  28,    // padding left
  pT:  18,    // padding top
  hH:  50,    // header height
  hW:  140,   // header box width
  gap: 190,   // center-to-center gap between lifelines
  rH:  58,    // row height  ← key value, large enough for 1 message + label
  aW:  14,    // activation box width
  aC:  '#dbeafe',
};

function lx(i){ return C.pL + C.hW/2 + i*C.gap; }
function ry(r){ return C.pT + C.hH + r * C.rH; }

function totalW(n){ return C.pL*2 + C.hW + (n-1)*C.gap; }
function totalH(r){ return C.pT + C.hH + r*C.rH + 28; }

/* ── Header + SVG root ── */
function svgOpen(actors, rows){
  const W = totalW(actors.length), H = totalH(rows);
  let s = `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}"
    font-family="'Segoe UI',Arial,sans-serif" font-size="12">`;
  s += `<defs>
    <marker id="a1" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto"><polygon points="0 0,8 3,0 6" fill="#1a1a2e"/></marker>
    <marker id="a2" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto"><polygon points="0 0,8 3,0 6" fill="#555"/></marker>
    <marker id="a3" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto"><polygon points="0 0,8 3,0 6" fill="#c0392b"/></marker>
  </defs>`;
  s += `<rect width="${W}" height="${H}" fill="#fff"/>`;
  // actor boxes
  actors.forEach((a,i)=>{
    const bx = lx(i)-C.hW/2;
    const clr = i===0?'#34495e':'#0f3460';
    s+=`<rect x="${bx}" y="${C.pT}" width="${C.hW}" height="${C.hH}" rx="6"
         fill="${clr}" stroke="#0a2340" stroke-width="1.2"/>`;
    a.split('\n').forEach((ln,li)=>{
      s+=`<text x="${lx(i)}" y="${C.pT+18+li*16}" text-anchor="middle"
          fill="#fff" font-weight="700" font-size="12">${ln}</text>`;
    });
  });
  return s;
}

/* ── Lifelines ── */
function lines(actors, rows){
  let s='';
  actors.forEach((_,i)=>{
    s+=`<line x1="${lx(i)}" y1="${C.pT+C.hH}" x2="${lx(i)}" y2="${ry(rows)}"
        stroke="#bbb" stroke-width="1.4" stroke-dasharray="5,4"/>`;
  });
  return s;
}

/* ── Activation box ── */
function act(i,r1,r2){
  const x=lx(i)-C.aW/2, y=ry(r1), h=ry(r2)-ry(r1);
  return `<rect x="${x}" y="${y}" width="${C.aW}" height="${h}"
          fill="${C.aC}" stroke="#6090cc" stroke-width="1"/>`;
}

/* ── Regular solid arrow ── */
function msg(fi,ti,r,label,clr){
  clr=clr||'#1a1a2e';
  const mk=clr==='#c0392b'?'url(#a3)':'url(#a1)';
  const y = ry(r) + C.rH*0.52;
  const x1= lx(fi)+(fi<ti? C.aW/2:-C.aW/2);
  const x2= lx(ti)+(fi<ti?-C.aW/2: C.aW/2);
  const mx= (x1+x2)/2;
  let s=`<line x1="${x1}" y1="${y}" x2="${x2}" y2="${y}"
         stroke="${clr}" stroke-width="1.4" marker-end="${mk}"/>`;
  s+=`<text x="${mx}" y="${y-6}" text-anchor="middle" fill="${clr}" font-size="11">${label}</text>`;
  return s;
}

/* ── Dashed return arrow ── */
function ret(fi,ti,r,label){
  const y=ry(r)+C.rH*0.52;
  const x1=lx(fi)+(fi<ti? C.aW/2:-C.aW/2);
  const x2=lx(ti)+(fi<ti?-C.aW/2: C.aW/2);
  const mx=(x1+x2)/2;
  let s=`<line x1="${x1}" y1="${y}" x2="${x2}" y2="${y}"
         stroke="#666" stroke-width="1.3" stroke-dasharray="6,3" marker-end="url(#a2)"/>`;
  s+=`<text x="${mx}" y="${y-6}" text-anchor="middle" fill="#555" font-size="11">${label}</text>`;
  return s;
}

/* ── Self-call (own lifeline, drawn as short right-angle bracket) ── */
function self(i,r,label){
  const x=lx(i)+C.aW/2;
  const y1=ry(r)+C.rH*0.22;
  const y2=ry(r)+C.rH*0.58;
  const rx2=x+50;
  let s=`<path d="M${x},${y1} H${rx2} V${y2} H${x}"
        fill="none" stroke="#1a1a2e" stroke-width="1.3" marker-end="url(#a1)"/>`;
  s+=`<text x="${rx2+5}" y="${y1+16}" fill="#333" font-size="10.5">${label}</text>`;
  return s;
}

/* ── Fragment frame (alt/opt/loop) ── */
function frag(lbl,r1,r2,actors,fill,stroke){
  fill=fill||'rgba(219,234,254,.55)';
  stroke=stroke||'#5599cc';
  const fx=lx(0)-C.gap*0.38;
  const fw=lx(actors.length-1)-lx(0)+C.gap*0.38+8;
  const y=ry(r1), h=ry(r2)-ry(r1);
  let s=`<rect x="${fx}" y="${y}" width="${fw}" height="${h}"
         fill="${fill}" stroke="${stroke}" stroke-width="1.3" rx="3"/>`;
  s+=`<rect x="${fx}" y="${y}" width="44" height="18" fill="${stroke}" rx="2"/>`;
  s+=`<text x="${fx+5}" y="${y+13}" fill="#fff" font-size="10.5" font-weight="700">${lbl}</text>`;
  return s;
}

/* ── Guard text ── */
function guard(r,text,actors){
  const x=lx(0)-C.gap*0.38+6;
  return `<text x="${x}" y="${ry(r)+15}" fill="#1a3360" font-size="10.5" font-style="italic">[${text}]</text>`;
}

/* ── Alt divider ── */
function alt(r,actors){
  const x1=lx(0)-C.gap*0.38;
  const x2=lx(actors.length-1)+C.gap*0.38+8;
  return `<line x1="${x1}" y1="${ry(r)}" x2="${x2}" y2="${ry(r)}"
          stroke="#5599cc" stroke-width="1" stroke-dasharray="5,3"/>`;
}

/* ── Section banner ── */
function sec(r,text,actors){
  const x=lx(0)-C.gap*0.4;
  const w=lx(actors.length-1)-lx(0)+C.gap*0.4+12;
  const y=ry(r);
  return `<rect x="${x}" y="${y+3}" width="${w}" height="19" fill="#e2e8f0" rx="3"/>
<text x="${x+8}" y="${y+16}" fill="#334" font-size="11" font-weight="600">${text}</text>`;
}

// ================================================================
//  DIAGRAMS
// ================================================================
const diagrams={

// ─── ĐĂNG NHẬP ─────────────────────────────────────────────────
login(){
  const A=['User\n(SV/GV)','Frontend\n(Next.js)','Auth API\n(Laravel)','Database','JWT\nService'];
  const rows=17;
  let s=svgOpen(A,rows)+lines(A,rows);
  s+=act(1,1,13); s+=act(2,3,11); s+=act(3,5,6); s+=act(4,7,8);

  s+=msg(0,1,1,'1: Nhập username / password');
  s+=msg(1,2,2,'2: Validate form (client-side)');
  s+=msg(1,2,3,'3: POST /api/v1/login');
  s+=msg(2,3,4,'4: Validate request (middleware)');
  s+=msg(2,3,5,'5: query UserAccount where username');
  s+=ret(3,2,6,'6: return user data');

  s+=frag('alt',7,15,A,'rgba(219,234,254,.55)','#5599cc');
  s+=guard(7,'Tìm thấy tài khoản – mật khẩu đúng',A);
  s+=msg(2,4,7,'7: Generate JWT Token');
  s+=ret(4,2,8,'8: return JWT Token');
  s+=ret(2,1,9,'9: 200 { token, user }');
  s+=self(1,10,'10: store token localStorage + set AuthContext');
  s+=self(1,11,'11: redirect dashboard theo role');
  s+=msg(1,0,12,'12: Hiển thị trang chủ (dashboard)');

  s+=alt(13,A);
  s+=guard(13,'Không tìm thấy tài khoản / sai mật khẩu',A);
  s+=ret(2,1,14,'13: 401 { message: "Thông tin đăng nhập không chính xác" }','#c0392b');
  s+=msg(1,0,15,'14: Hiển thị thông báo lỗi (toast)');

  s+='</svg>'; return s;
},

// ─── CRUD SINH VIÊN ─────────────────────────────────────────────
crudStudent(){
  const A=['Admin','Frontend\n(Next.js)','Auth API\n(Laravel)','Database'];
  const rows=20;
  let s=svgOpen(A,rows)+lines(A,rows);
  s+=act(1,1,19); s+=act(2,2,18); s+=act(3,3,17);

  s+=sec(0,'▶ Thêm sinh viên',A);
  s+=msg(0,1,1,'1: Click "Thêm sinh viên"');
  s+=msg(1,2,2,'2: POST /api/v1/students { name, code, email, class_id }');
  s+=msg(2,3,3,'3: Validate (CreateStudentRequest)');
  s+=msg(2,3,4,'4: createStudentWithAccount(data)');
  s+=ret(3,2,5,'5: 201 { student, account: sv_[code] / 123456 }');
  s+=msg(1,0,6,'6: Thông báo "Tạo thành công", refresh danh sách');

  s+=sec(7,'▶ Sửa sinh viên',A);
  s+=msg(0,1,7,'7: Click icon "Sửa", mở form');
  s+=msg(1,2,8,'8: PUT /api/v1/students/{id} { fields }');
  s+=msg(2,3,9,'9: getStudentById → updateStudent(data)');
  s+=ret(3,2,10,'10: 200 { message, data }');
  s+=msg(1,0,11,'11: Thông báo "Cập nhật thành công"');

  s+=sec(12,'▶ Xóa sinh viên',A);
  s+=msg(0,1,12,'12: Click "Xóa" → Confirm dialog');
  s+=msg(1,2,13,'13: DELETE /api/v1/students/{id}');
  s+=msg(2,3,14,'14: getStudentById → deleteStudent()');
  s+=ret(3,2,15,'15: 200 { message: "Xóa sinh viên thành công" }');
  s+=msg(1,0,16,'16: Refresh danh sách sinh viên');

  s+='</svg>'; return s;
},

// ─── IMPORT SINH VIÊN ──────────────────────────────────────────
importStudent(){
  const A=['Admin','Frontend\n(Next.js)','Auth API\n(Laravel)','Storage','Queue\n(Job)','Database'];
  const rows=16;
  let s=svgOpen(A,rows)+lines(A,rows);
  s+=act(1,1,15); s+=act(2,2,13); s+=act(3,4,5); s+=act(4,8,12); s+=act(5,9,11);

  s+=msg(0,1,1,'1: Chọn file Excel, click "Upload"');
  s+=msg(1,2,2,'2: POST /api/v1/import/students { file }');
  s+=self(2,3,'3: Validate file (type, size)');
  s+=msg(2,3,4,'4: store file → storage/imports/');
  s+=ret(3,2,5,'5: filePath lưu thành công');
  s+=self(2,6,'6: ImportJob::create({ entity: student, status: pending })');
  s+=msg(2,4,7,'7: AddListStudent::dispatch(importJobId)');
  s+=ret(2,1,8,'8: 201 { import_job_id, status: "pending" }');

  s+=frag('loop',9,13,A,'rgba(220,252,231,.6)','#44aa66');
  s+=guard(9,'Polling mỗi 2 giây cho đến khi completed',A);
  s+=msg(4,5,10,'10: validateAllRows → importAllRows → INSERT students');
  s+=msg(4,5,11,'11: Tạo tài khoản student_account');
  s+=self(4,12,'12: ImportJob::update({ status:completed, success, failed })');
  s+=ret(2,1,13,'13: GET /import/{id}/progress → { percent, success, failed }');

  s+=msg(1,0,15,'14: Hiển thị kết quả import (thành công / thất bại / lỗi)');
  s+='<\/svg>'; return s;
},

// ─── IMPORT GIẢNG VIÊN ─────────────────────────────────────────
importLecturer(){
  const A=['Admin','Frontend\n(Next.js)','Auth API\n(Laravel)','Storage','Queue\n(Job)','Database'];
  const rows=16;
  let s=svgOpen(A,rows)+lines(A,rows);
  s+=act(1,1,15); s+=act(2,2,13); s+=act(3,4,5); s+=act(4,8,12); s+=act(5,9,11);

  s+=msg(0,1,1,'1: Chọn file Excel, click "Upload"');
  s+=msg(1,2,2,'2: POST /api/v1/import/lecturers { file }');
  s+=self(2,3,'3: Validate file (ImportLecturerRequest)');
  s+=msg(2,3,4,'4: store file → storage/imports/');
  s+=ret(3,2,5,'5: filePath lưu thành công');
  s+=self(2,6,'6: ImportJob::create({ entity: lecturer, status: pending })');
  s+=msg(2,4,7,'7: AddListLecturer::dispatch(importJobId)');
  s+=ret(2,1,8,'8: 201 { import_job_id, status: "pending" }');

  s+=frag('loop',9,13,A,'rgba(220,252,231,.6)','#44aa66');
  s+=guard(9,'Polling mỗi 2 giây cho đến khi completed',A);
  s+=msg(4,5,10,'10: validateAllRows → importAllRows → INSERT lecturers');
  s+=msg(4,5,11,'11: Tạo tài khoản lecturer_account');
  s+=self(4,12,'12: ImportJob::update({ status:completed, success, failed })');
  s+=ret(2,1,13,'13: GET /import/{id}/progress → { percent, success, failed }');

  s+=msg(1,0,15,'14: Hiển thị kết quả import (thành công / thất bại / lỗi)');
  s+='<\/svg>'; return s;
},

// ─── TẠO MÔN HỌC & SINH LỊCH ─────────────────────────────────
createCourse(){
  const A=['Admin/GV','Frontend\n(Next.js)','Auth API\n(Laravel)','CourseService','Database'];
  const rows=18;
  let s=svgOpen(A,rows)+lines(A,rows);
  s+=act(1,1,17); s+=act(2,2,16); s+=act(3,3,15); s+=act(4,5,14);

  s+=msg(0,1,1,'1: Nhập thông tin môn học, click "Tạo"');
  s+=msg(1,2,2,'2: POST /api/v1/courses { name, code, lecturer_id, semester_id, schedule_days, start/end_date }');
  s+=msg(2,3,3,'3: Validate (CreateCourseRequest)');
  s+=msg(2,3,4,'4: courseService.createCourse(data, generateSessions=true)');
  s+=msg(3,4,5,'5: DB::beginTransaction()');
  s+=msg(3,4,6,'6: courseRepository.create(data) → INSERT courses');
  s+=ret(4,3,7,'7: Course model');

  s+=frag('opt',8,13,A,'rgba(254,243,199,.7)','#d97706');
  s+=guard(8,'generate_sessions=true AND schedule_days not empty',A);
  s+=msg(3,4,9,'9: sessionRepository.deleteByCourse(id) – xóa lịch cũ');
  s+=msg(3,4,10,'10: Loop ngày start→end, check dayOfWeek ∈ schedule_days');
  s+=msg(3,4,11,'11: INSERT attendance_sessions (bulk)');
  s+=msg(3,4,12,'12: UPDATE course SET sessions_generated=true, total_sessions=N');

  s+=msg(3,4,13,'13: DB::commit()');
  s+=ret(3,2,14,'14: course.fresh([ semester, lecturer, sessions ])');
  s+=ret(2,1,15,'15: 201 { success:true, course, sessions }');
  s+=msg(1,0,16,'16: Hiển thị môn học mới + danh sách buổi học tự động');

  s+='</svg>'; return s;
},

// ─── ĐIỂM DANH ─────────────────────────────────────────────────
attendance(){
  const A=['Giảng viên','Frontend\n(Next.js)','Auth API\n(Laravel)','AttendanceService','Database'];
  const rows=22;
  let s=svgOpen(A,rows)+lines(A,rows);
  s+=act(1,1,21); s+=act(2,2,20); s+=act(3,3,19); s+=act(4,5,18);

  s+=sec(0,'▶ Bắt đầu điểm danh',A);
  s+=msg(0,1,1,'1: Chọn môn → Chọn buổi học → Click "Bắt đầu điểm danh"');
  s+=msg(1,2,2,'2: POST /api/v1/sessions/{id}/start (Bearer JWT)');
  s+=msg(2,2,3,'3: JWT Middleware → xác định lecturerId, is_admin');
  s+=msg(2,3,4,'4: attendanceService.startSession(sessionId, lecturerId)');
  s+=msg(3,4,5,'5: Kiểm tra session tồn tại, status = scheduled');
  s+=msg(3,4,6,'6: UPDATE attendance_sessions SET status="in_progress"');
  s+=ret(3,2,7,'7: session { status: in_progress }');
  s+=ret(2,1,8,'8: 200 { success:true, data: session }');
  s+=msg(1,0,9,'9: Render danh sách SV với các nút chọn trạng thái');

  s+=sec(10,'▶ Cập nhật trạng thái từng sinh viên',A);
  s+=msg(0,1,10,'10: Click trạng thái SV: present / absent / late / excused');
  s+=msg(1,2,11,'11: PATCH /api/v1/sessions/{id}/attendance { student_id, status }');
  s+=msg(2,3,12,'12: attendanceService.updateAttendance(sessionId, studentId, status)');
  s+=msg(3,4,13,'13: UPDATE attendance SET status, updated_by, updated_at');
  s+=ret(2,1,14,'14: 200 { message: "Cập nhật thành công" }');

  s+=sec(15,'▶ Hoàn thành điểm danh',A);
  s+=msg(0,1,15,'15: Click "Hoàn thành điểm danh"');
  s+=msg(1,2,16,'16: POST /api/v1/sessions/{id}/complete');
  s+=msg(2,3,17,'17: attendanceService.completeSession(sessionId, lecturerId)');
  s+=msg(3,4,18,'18: UPDATE sessions SET status="completed", completed_at=now()');
  s+=ret(3,2,19,'19: true');
  s+=ret(2,1,20,'20: 200 { message: "Hoàn thành. Không thể sửa sau khi hoàn thành." }');
  s+=msg(1,0,21,'21: Khóa UI điểm danh, hiển thị tổng kết buổi học');

  s+='</svg>'; return s;
},

}; // end diagrams

// ================================================================
const titles={
  login:          'Đăng nhập hệ thống',
  crudStudent:    'Thêm / Sửa / Xóa sinh viên',
  importStudent:  'Import sinh viên từ Excel',
  importLecturer: 'Import giảng viên từ Excel',
  createCourse:   'Tạo môn học & tự động sinh lịch điểm danh',
  attendance:     'Thực hiện điểm danh',
};

function show(id,el){
  document.getElementById('title').textContent='Sơ đồ tuần tự: '+titles[id];
  document.getElementById('canvas').innerHTML=diagrams[id]();
  document.querySelectorAll('.item').forEach(i=>i.classList.remove('active'));
  if(el) el.classList.add('active');
  // re-apply fit if active
  if(fitMode) setTimeout(applyScale,50);
}

show('login', document.querySelector('.item.active'));
</script>
</body>
</html>
