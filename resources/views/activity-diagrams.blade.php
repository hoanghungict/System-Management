<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Biểu đồ hoạt động - Hệ thống Quản lý HPC</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;display:flex;height:100vh;background:#f5f5f5}

/* ===== SIDEBAR ===== */
.sidebar{width:310px;background:#fff;border-right:2px solid #e0e0e0;overflow-y:auto;flex-shrink:0}
.sidebar h2{padding:18px 20px;background:#1a1a2e;color:#fff;font-size:16px;text-align:center;position:sticky;top:0;z-index:10;letter-spacing:.5px}
.sidebar .group-title{padding:10px 20px;background:#16213e;color:#e0e0e0;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px}
.sidebar .item{padding:12px 20px 12px 38px;cursor:pointer;border-bottom:1px solid #f0f0f0;font-size:14px;color:#333;transition:all .2s;user-select:none}
.sidebar .item:hover{background:#e8f0fe;color:#1a73e8;padding-left:42px}
.sidebar .item.active{background:#1a73e8;color:#fff;font-weight:600}

/* ===== MAIN CONTENT ===== */
.main{flex:1;overflow:auto;padding:30px;display:flex;flex-direction:column;align-items:center}
.main h1{font-size:20px;margin-bottom:20px;color:#1a1a2e;text-align:center;font-weight:700}
.diagram-wrap{background:#fff;border:1px solid #bbb;display:inline-block;box-shadow:0 2px 12px rgba(0,0,0,.08);border-radius:4px;overflow:visible}
.btn-print{margin-top:16px;padding:10px 28px;background:#1a73e8;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer;font-weight:600;transition:background .2s}
.btn-print:hover{background:#1557b0}

svg text{font-family:'Segoe UI',Arial,sans-serif}
@media print{.sidebar{display:none}.main{padding:10px}.btn-print{display:none}}
</style>
</head>
<body>

{{-- ===== SIDEBAR MENU ===== --}}
<div class="sidebar">
  <h2>📊 Biểu đồ hoạt động</h2>

  <div class="group-title">🔐 Xác thực</div>
  <div class="item active" onclick="show('login',this)">Đăng nhập hệ thống</div>
  <div class="item" onclick="show('logout',this)">Đăng xuất</div>

  <div class="group-title">👨‍🎓 Quản lý Sinh viên</div>
  <div class="item" onclick="show('addStudent',this)">Thêm sinh viên</div>
  <div class="item" onclick="show('editStudent',this)">Sửa sinh viên</div>
  <div class="item" onclick="show('deleteStudent',this)">Xóa sinh viên</div>
  <div class="item" onclick="show('importStudent',this)">Import SV từ Excel</div>

  <div class="group-title">👨‍🏫 Quản lý Giảng viên</div>
  <div class="item" onclick="show('addLecturer',this)">Thêm giảng viên</div>
  <div class="item" onclick="show('editLecturer',this)">Sửa giảng viên</div>
  <div class="item" onclick="show('deleteLecturer',this)">Xóa giảng viên</div>
  <div class="item" onclick="show('importLecturer',this)">Import GV từ Excel</div>

  <div class="group-title">📅 Quản lý Học kỳ</div>
  <div class="item" onclick="show('addSemester',this)">Thêm học kỳ</div>
  <div class="item" onclick="show('deleteSemester',this)">Xóa học kỳ</div>

  <div class="group-title">📚 Quản lý Môn học</div>
  <div class="item" onclick="show('addCourse',this)">Tạo môn học</div>
  <div class="item" onclick="show('deleteCourse',this)">Xóa môn học</div>
  <div class="item" onclick="show('enrollStudent',this)">Đăng ký SV vào môn</div>

  <div class="group-title">✅ Điểm danh</div>
  <div class="item" onclick="show('attendance',this)">Thực hiện điểm danh</div>

  <div class="group-title">📝 Quản lý Nhiệm vụ</div>
  <div class="item" onclick="show('createTask',this)">Tạo nhiệm vụ</div>
  <div class="item" onclick="show('submitTask',this)">Nộp nhiệm vụ</div>
  <div class="item" onclick="show('gradeTask',this)">Chấm điểm nhiệm vụ</div>
</div>

{{-- ===== MAIN AREA ===== --}}
<div class="main">
  <h1 id="title">Biểu đồ hoạt động: Đăng nhập hệ thống</h1>
  <div class="diagram-wrap" id="canvas"></div>
  <button class="btn-print" onclick="window.print()">🖨️ In biểu đồ</button>
</div>

<script>
// ============================================================
//  SVG RENDERING HELPERS
// ============================================================
const LW = 380;   // Lane width
const RH = 78;    // Row height
const HH = 42;    // Header height
const NW = 290;   // Node width
const NH = 42;    // Node height
const DS = 26;    // Decision half-size
const SR = 13;    // Start/End radius

function cx(lane) { return lane * LW + LW / 2; }
function cy(row)  { return HH + row * RH + RH / 2; }

// SVG document header with swimlanes
function svgHead(lanes, maxRow) {
  const w = LW * lanes.length, h = HH + (maxRow + 1) * RH + 30;
  let s = `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" style="font-family:'Segoe UI',Arial,sans-serif;font-size:13px">`;
  s += `<defs><marker id="ah" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#000"/></marker></defs>`;
  s += `<rect width="${w}" height="${h}" fill="#fff" stroke="#000" stroke-width="1.5"/>`;
  s += `<line x1="0" y1="${HH}" x2="${w}" y2="${HH}" stroke="#000" stroke-width="1"/>`;
  for (let i = 0; i < lanes.length; i++) {
    if (i > 0) s += `<line x1="${i*LW}" y1="0" x2="${i*LW}" y2="${h}" stroke="#000" stroke-width="1"/>`;
    s += `<text x="${cx(i)}" y="${HH/2+5}" text-anchor="middle" font-size="15" font-weight="700">${lanes[i]}</text>`;
  }
  return s;
}

// Start node (filled circle)
function startN(l, r) {
  return `<circle cx="${cx(l)}" cy="${cy(r)}" r="${SR}" fill="#000"/>`;
}

// End node (ring + filled circle)
function endN(l, r) {
  const x = cx(l), y = cy(r);
  return `<circle cx="${x}" cy="${y}" r="${SR+3}" fill="none" stroke="#000" stroke-width="2.5"/><circle cx="${x}" cy="${y}" r="${SR-2}" fill="#000"/>`;
}

// Action node (rounded rectangle with text)
function actN(l, r, txt, h) {
  h = h || NH;
  const x = cx(l) - NW/2, y = cy(r) - h/2;
  let s = `<rect x="${x}" y="${y}" width="${NW}" height="${h}" rx="12" ry="12" fill="#fff" stroke="#000" stroke-width="1.5"/>`;
  s += `<foreignObject x="${x+4}" y="${y+2}" width="${NW-8}" height="${h-4}"><div xmlns="http://www.w3.org/1999/xhtml" style="display:flex;align-items:center;justify-content:center;height:100%;text-align:center;font-size:12.5px;line-height:1.3;overflow:hidden">${txt}</div></foreignObject>`;
  return s;
}

// Decision node (diamond)
function decN(l, r) {
  const x = cx(l), y = cy(r);
  return `<polygon points="${x},${y-DS} ${x+DS},${y} ${x},${y+DS} ${x-DS},${y}" fill="#fff" stroke="#000" stroke-width="1.5"/>`;
}

// Simple arrow line
function arr(x1,y1,x2,y2) {
  return `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#000" stroke-width="1.2" marker-end="url(#ah)"/>`;
}

// Path arrow
function pathArr(d) {
  return `<path d="${d}" fill="none" stroke="#000" stroke-width="1.2" marker-end="url(#ah)"/>`;
}

// Text label
function lbl(x, y, t, anchor) {
  return `<text x="${x}" y="${y}" text-anchor="${anchor||'middle'}" font-size="11.5" font-style="italic">${t}</text>`;
}

// ===== ARROW SHORTHAND HELPERS =====
// Same lane, action down to action
function downA(l,r1,r2)  { return arr(cx(l), cy(r1)+NH/2, cx(l), cy(r2)-NH/2); }
// Start to action
function downAS(l,r1,r2) { return arr(cx(l), cy(r1)+SR, cx(l), cy(r2)-NH/2); }
// Action to end
function downAE(l,r1,r2) { return arr(cx(l), cy(r1)+NH/2, cx(l), cy(r2)-SR-3); }
// Action to decision
function downAD(l,r1,r2) { return arr(cx(l), cy(r1)+NH/2, cx(l), cy(r2)-DS); }
// Decision to action
function downDA(l,r1,r2) { return arr(cx(l), cy(r1)+DS, cx(l), cy(r2)-NH/2); }
// Decision to end
function downDE(l,r1,r2) { return arr(cx(l), cy(r1)+DS, cx(l), cy(r2)-SR-3); }
// Right arrow (same row, lane l1 to l2)
function rightA(l1,l2,r) { return arr(cx(l1)+NW/2, cy(r), cx(l2)-NW/2, cy(r)); }
// Left arrow
function leftA(l1,l2,r)  { return arr(cx(l1)-NW/2, cy(r), cx(l2)+NW/2, cy(r)); }

// Cross-lane down (action to action)
function crossDown(fl,fr,tl,tr,h) {
  h = h || NH;
  const fy = cy(fr)+h/2, ty = cy(tr)-NH/2, mx = (fy+ty)/2;
  return pathArr(`M${cx(fl)},${fy} L${cx(fl)},${mx} L${cx(tl)},${mx} L${cx(tl)},${ty}`);
}

// Cross-lane down (decision to action)
function crossDownDA(fl,fr,tl,tr) {
  const fy = cy(fr)+DS, ty = cy(tr)-NH/2, mx = (fy+ty)/2;
  return pathArr(`M${cx(fl)},${fy} L${cx(fl)},${mx} L${cx(tl)},${mx} L${cx(tl)},${ty}`);
}

// Loop-back right side (action to action going upward)
function loopRight(fl,fr,tl,tr,lanes) {
  const rx = LW*lanes - 25, fy = cy(fr), ty = cy(tr);
  return pathArr(`M${cx(fl)+NW/2},${fy} L${rx},${fy} L${rx},${ty} L${cx(tl)+NW/2},${ty}`);
}

// Loop-back from decision going right then up
function loopRightD(fl,fr,tl,tr,lanes) {
  const rx = LW*lanes - 25, fy = cy(fr), ty = cy(tr);
  return pathArr(`M${cx(fl)+DS},${fy} L${rx},${fy} L${rx},${ty} L${cx(tl)+NW/2},${ty}`);
}

// Decision labels
function decRightLabel(l,r,t) { return lbl(cx(l)+DS+5, cy(r)-7, t, 'start'); }
function decDownLabel(l,r,t)  { return lbl(cx(l)-DS-5, cy(r)+DS+3, t, 'end'); }


// ============================================================
//  DIAGRAM DEFINITIONS
// ============================================================
const diagrams = {

// ─────────── ĐĂNG NHẬP ───────────
login() {
  let s = svgHead(['Người dùng','Hệ Thống'], 10);
  s += startN(0,0);
  s += actN(0,1,'Truy cập trang đăng nhập');
  s += actN(0,2,'Nhập username và password');
  s += actN(0,3,'Chọn loại tài khoản (Sinh viên / Giảng viên)');
  s += actN(0,4,'Click "Đăng nhập"');
  s += actN(1,5,'Xác thực thông tin đăng nhập');
  s += decN(1,6);
  s += actN(1,7,'Tạo JWT Token');
  s += actN(1,8,'Trả về thông tin người dùng và token');
  s += actN(0,9,'Chuyển đến trang chính');
  s += endN(0,10);
  // Error branch
  s += actN(0,6,'Thông báo "Đăng nhập không chính xác"');
  // Arrows
  s += downAS(0,0,1) + downA(0,1,2) + downA(0,2,3) + downA(0,3,4);
  s += crossDown(0,4,1,5);
  s += downAD(1,5,6);
  s += downDA(1,6,7) + decDownLabel(1,6,'Hợp lệ');
  s += downA(1,7,8);
  s += crossDown(1,8,0,9);
  s += downAE(0,9,10);
  // Không hợp lệ → loop back
  s += arr(cx(1)-DS, cy(6), cx(0)+NW/2, cy(6));
  s += lbl(cx(0)+NW/2+35, cy(6)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,6,0,2,1);
  s += '</svg>'; return s;
},

// ─────────── ĐĂNG XUẤT ───────────
logout() {
  let s = svgHead(['Người dùng','Hệ Thống'], 6);
  s += startN(0,0);
  s += actN(0,1,'Click "Đăng xuất"');
  s += actN(1,2,'Nhận yêu cầu đăng xuất');
  s += actN(1,3,'Hủy JWT Token (Invalidate)');
  s += actN(1,4,'Trả về "Đăng xuất thành công"');
  s += actN(0,5,'Chuyển về trang đăng nhập');
  s += endN(0,6);
  s += downAS(0,0,1); s += crossDown(0,1,1,2);
  s += downA(1,2,3); s += downA(1,3,4);
  s += crossDown(1,4,0,5); s += downAE(0,5,6);
  s += '</svg>'; return s;
},

// ─────────── THÊM SINH VIÊN ───────────
addStudent() {
  let s = svgHead(['Admin','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý người dùng"');
  s += actN(0,2,'Chọn "Sinh Viên"');
  s += actN(1,2,'Hiển thị danh sách sinh viên (bảng, ô tìm kiếm...)',52);
  s += actN(0,3,'Click "Thêm sinh viên"');
  s += actN(1,4,'Hiển thị form thêm sinh viên');
  s += actN(0,5,'Nhập thông tin sinh viên (mã SV, họ tên, email, lớp)');
  s += actN(0,6,'Click "Lưu"');
  s += actN(1,7,'Validate dữ liệu');
  s += decN(1,8);
  s += actN(1,9,'Tạo sinh viên và tự động tạo tài khoản (sv_[mã], pass: 123456)',52);
  s += actN(1,10,'Thông báo "Tạo sinh viên thành công"');
  s += actN(1,11,'Cập nhật lại danh sách sinh viên');
  s += endN(1,12);
  s += actN(0,8,'Hiển thị thông báo lỗi validation');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3,52); s += crossDown(0,3,1,4);
  s += crossDown(1,4,0,5); s += downA(0,5,6);
  s += crossDown(0,6,1,7); s += downAD(1,7,8);
  s += downDA(1,8,9) + decDownLabel(1,8,'Hợp lệ');
  s += downA(1,9,10); s += downA(1,10,11); s += downAE(1,11,12);
  // Không hợp lệ
  s += arr(cx(1)-DS, cy(8), cx(0)+NW/2, cy(8));
  s += lbl(cx(0)+NW/2+35, cy(8)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,8,0,5,1);
  s += '</svg>'; return s;
},

// ─────────── SỬA SINH VIÊN ───────────
editStudent() {
  let s = svgHead(['Admin','Hệ Thống'], 13);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý người dùng"');
  s += actN(0,2,'Chọn "Sinh Viên"');
  s += actN(1,2,'Hiển thị danh sách sinh viên (bảng, ô tìm kiếm...)',52);
  s += actN(0,3,'Tìm (lọc) và chọn sinh viên cần sửa');
  s += actN(0,4,'Click "Sửa"');
  s += actN(1,5,'Hiển thị form chỉnh sửa với thông tin hiện tại',52);
  s += actN(0,6,'Chỉnh sửa thông tin sinh viên');
  s += actN(0,7,'Click "Lưu"');
  s += actN(1,8,'Validate dữ liệu');
  s += decN(1,9);
  s += actN(1,10,'Cập nhật thông tin sinh viên trong CSDL');
  s += actN(1,11,'Thông báo "Cập nhật sinh viên thành công"');
  s += actN(1,12,'Cập nhật lại danh sách sinh viên');
  s += endN(1,13);
  s += actN(0,9,'Hiển thị thông báo lỗi validation');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3,52); s += downA(0,3,4);
  s += crossDown(0,4,1,5); s += crossDown(1,5,0,6,52);
  s += downA(0,6,7); s += crossDown(0,7,1,8);
  s += downAD(1,8,9);
  s += downDA(1,9,10) + decDownLabel(1,9,'Hợp lệ');
  s += downA(1,10,11); s += downA(1,11,12); s += downAE(1,12,13);
  s += arr(cx(1)-DS, cy(9), cx(0)+NW/2, cy(9));
  s += lbl(cx(0)+NW/2+35, cy(9)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,9,0,6,1);
  s += '</svg>'; return s;
},

// ─────────── XÓA SINH VIÊN ───────────
deleteStudent() {
  let s = svgHead(['Admin','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý người dùng"');
  s += actN(0,2,'Chọn "Sinh Viên"');
  s += actN(1,2,'Hiển thị danh sách sinh viên (bảng, ô tìm kiếm...)',52);
  s += actN(0,3,'Tìm (lọc) và chọn sinh viên cần xóa');
  s += actN(0,4,'Click "Xóa"');
  s += actN(1,4,'Hiện hộp thoại xác nhận');
  s += actN(0,5,'Chọn Đồng ý hay Hủy');
  s += decN(0,6);
  s += actN(1,7,'Thực hiện xóa (hoặc soft delete) bản ghi sinh viên trong CSDL',52);
  s += actN(1,8,'Ghi log xóa sinh viên');
  s += actN(1,9,'Thông báo "Xóa sinh viên thành công"');
  s += actN(1,10,'Cập nhật lại danh sách sinh viên, không còn bản ghi vừa xóa',52);
  s += endN(1,11);
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3,52); s += downA(0,3,4); s += rightA(0,1,4);
  s += crossDown(1,4,0,5); s += downAD(0,5,6);
  s += crossDownDA(0,6,1,7) + decDownLabel(0,6,'Đồng ý');
  s += downA(1,7,8); s += downA(1,8,9); s += downA(1,9,10); s += downAE(1,10,11);
  // Không đồng ý → loop back
  s += loopRightD(0,6,0,3,2) + decRightLabel(0,6,'Không đồng ý');
  s += '</svg>'; return s;
},

// ─────────── THÊM GIẢNG VIÊN ───────────
addLecturer() {
  let s = svgHead(['Admin','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý người dùng"');
  s += actN(0,2,'Chọn "Giảng Viên"');
  s += actN(1,2,'Hiển thị danh sách giảng viên (bảng, ô tìm kiếm...)',52);
  s += actN(0,3,'Click "Thêm giảng viên"');
  s += actN(1,4,'Hiển thị form thêm giảng viên');
  s += actN(0,5,'Nhập thông tin (mã GV, họ tên, email, khoa)');
  s += actN(0,6,'Click "Lưu"');
  s += actN(1,7,'Validate dữ liệu');
  s += decN(1,8);
  s += actN(1,9,'Tạo giảng viên và tự động tạo tài khoản',52);
  s += actN(1,10,'Thông báo "Tạo giảng viên thành công"');
  s += actN(1,11,'Cập nhật lại danh sách giảng viên');
  s += endN(1,12);
  s += actN(0,8,'Hiển thị thông báo lỗi validation');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3,52); s += crossDown(0,3,1,4);
  s += crossDown(1,4,0,5); s += downA(0,5,6);
  s += crossDown(0,6,1,7); s += downAD(1,7,8);
  s += downDA(1,8,9) + decDownLabel(1,8,'Hợp lệ');
  s += downA(1,9,10); s += downA(1,10,11); s += downAE(1,11,12);
  s += arr(cx(1)-DS, cy(8), cx(0)+NW/2, cy(8));
  s += lbl(cx(0)+NW/2+35, cy(8)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,8,0,5,1);
  s += '</svg>'; return s;
},

// ─────────── SỬA GIẢNG VIÊN ───────────
editLecturer() {
  let s = svgHead(['Admin','Hệ Thống'], 13);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý người dùng"');
  s += actN(0,2,'Chọn "Giảng Viên"');
  s += actN(1,2,'Hiển thị danh sách giảng viên',52);
  s += actN(0,3,'Tìm và chọn giảng viên cần sửa');
  s += actN(0,4,'Click "Sửa"');
  s += actN(1,5,'Hiển thị form chỉnh sửa với thông tin hiện tại',52);
  s += actN(0,6,'Chỉnh sửa thông tin giảng viên');
  s += actN(0,7,'Click "Lưu"');
  s += actN(1,8,'Validate dữ liệu');
  s += decN(1,9);
  s += actN(1,10,'Cập nhật thông tin giảng viên trong CSDL');
  s += actN(1,11,'Thông báo "Cập nhật giảng viên thành công"');
  s += actN(1,12,'Cập nhật lại danh sách giảng viên');
  s += endN(1,13);
  s += actN(0,9,'Hiển thị thông báo lỗi validation');
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3,52); s += downA(0,3,4);
  s += crossDown(0,4,1,5); s += crossDown(1,5,0,6,52);
  s += downA(0,6,7); s += crossDown(0,7,1,8);
  s += downAD(1,8,9);
  s += downDA(1,9,10) + decDownLabel(1,9,'Hợp lệ');
  s += downA(1,10,11); s += downA(1,11,12); s += downAE(1,12,13);
  s += arr(cx(1)-DS, cy(9), cx(0)+NW/2, cy(9));
  s += lbl(cx(0)+NW/2+35, cy(9)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,9,0,6,1);
  s += '</svg>'; return s;
},

// ─────────── XÓA GIẢNG VIÊN ───────────
deleteLecturer() {
  let s = svgHead(['Admin','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý người dùng"');
  s += actN(0,2,'Chọn "Giảng Viên"');
  s += actN(1,2,'Hiển thị danh sách giảng viên',52);
  s += actN(0,3,'Tìm và chọn giảng viên cần xóa');
  s += actN(0,4,'Click "Xóa"');
  s += actN(1,4,'Hiện hộp thoại xác nhận');
  s += actN(0,5,'Chọn Đồng ý hay Hủy');
  s += decN(0,6);
  s += actN(1,7,'Thực hiện xóa giảng viên và tài khoản liên quan trong CSDL',52);
  s += actN(1,8,'Ghi log xóa giảng viên');
  s += actN(1,9,'Thông báo "Xóa giảng viên thành công"');
  s += actN(1,10,'Cập nhật lại danh sách giảng viên');
  s += endN(1,11);
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3,52); s += downA(0,3,4); s += rightA(0,1,4);
  s += crossDown(1,4,0,5); s += downAD(0,5,6);
  s += crossDownDA(0,6,1,7) + decDownLabel(0,6,'Đồng ý');
  s += downA(1,7,8); s += downA(1,8,9); s += downA(1,9,10); s += downAE(1,10,11);
  s += loopRightD(0,6,0,3,2) + decRightLabel(0,6,'Không đồng ý');
  s += '</svg>'; return s;
},

// ─────────── THÊM HỌC KỲ ───────────
addSemester() {
  let s = svgHead(['Admin','Hệ Thống'], 11);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý học kỳ"');
  s += actN(1,1,'Hiển thị danh sách học kỳ');
  s += actN(0,2,'Click "Thêm học kỳ"');
  s += actN(1,3,'Hiển thị form tạo học kỳ');
  s += actN(0,4,'Nhập thông tin (tên, ngày bắt đầu, ngày kết thúc)');
  s += actN(0,5,'Click "Lưu"');
  s += actN(1,6,'Validate dữ liệu');
  s += decN(1,7);
  s += actN(1,8,'Tạo học kỳ mới trong CSDL');
  s += actN(1,9,'Thông báo "Tạo học kỳ thành công"');
  s += actN(1,10,'Cập nhật lại danh sách học kỳ');
  s += endN(1,11);
  s += actN(0,7,'Hiển thị lỗi validation');
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,1);
  s += crossDown(0,2,1,3); s += crossDown(1,3,0,4);
  s += downA(0,4,5); s += crossDown(0,5,1,6); s += downAD(1,6,7);
  s += downDA(1,7,8) + decDownLabel(1,7,'Hợp lệ');
  s += downA(1,8,9); s += downA(1,9,10); s += downAE(1,10,11);
  s += arr(cx(1)-DS, cy(7), cx(0)+NW/2, cy(7));
  s += lbl(cx(0)+NW/2+35, cy(7)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,7,0,4,1);
  s += '</svg>'; return s;
},

// ─────────── XÓA HỌC KỲ ───────────
deleteSemester() {
  let s = svgHead(['Admin','Hệ Thống'], 10);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý học kỳ"');
  s += actN(1,1,'Hiển thị danh sách học kỳ');
  s += actN(0,2,'Chọn học kỳ cần xóa');
  s += actN(0,3,'Click "Xóa"');
  s += actN(1,3,'Hiện hộp thoại xác nhận');
  s += actN(0,4,'Chọn Đồng ý hay Hủy');
  s += decN(0,5);
  s += actN(1,6,'Xóa học kỳ trong CSDL');
  s += actN(1,7,'Thông báo "Xóa học kỳ thành công"');
  s += actN(1,8,'Cập nhật lại danh sách học kỳ');
  s += endN(1,9);
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,1);
  s += downA(0,2,3); s += rightA(0,1,3);
  s += crossDown(1,3,0,4); s += downAD(0,4,5);
  s += crossDownDA(0,5,1,6) + decDownLabel(0,5,'Đồng ý');
  s += downA(1,6,7); s += downA(1,7,8); s += downAE(1,8,9);
  s += loopRightD(0,5,0,2,2) + decRightLabel(0,5,'Không đồng ý');
  s += '</svg>'; return s;
},

// ─────────── TẠO MÔN HỌC ───────────
addCourse() {
  let s = svgHead(['Giảng viên / Admin','Hệ Thống'], 13);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý môn học"');
  s += actN(1,1,'Hiển thị danh sách môn học');
  s += actN(0,2,'Click "Tạo môn học mới"');
  s += actN(1,3,'Hiển thị form tạo môn học');
  s += actN(0,4,'Nhập thông tin (tên, mã, GV, học kỳ, lịch học, ngày BD/KT)',52);
  s += actN(0,5,'Chọn có tự động sinh lịch điểm danh');
  s += actN(0,6,'Click "Lưu"');
  s += actN(1,7,'Validate dữ liệu');
  s += decN(1,8);
  s += actN(1,9,'Tạo môn học trong CSDL');
  s += decN(1,10);
  s += actN(1,11,'Tự động sinh lịch buổi học (attendance sessions)',52);
  s += actN(1,12,'Thông báo "Tạo môn học thành công"');
  s += endN(1,13);
  s += actN(0,8,'Hiển thị lỗi validation');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,1);
  s += crossDown(0,2,1,3); s += crossDown(1,3,0,4);
  s += downA(0,4,5); s += downA(0,5,6);
  s += crossDown(0,6,1,7); s += downAD(1,7,8);
  s += downDA(1,8,9) + decDownLabel(1,8,'Hợp lệ');
  s += downAD(1,9,10);
  s += downDA(1,10,11) + decDownLabel(1,10,'Có sinh lịch');
  s += downA(1,11,12); s += downAE(1,12,13);
  // Không sinh lịch → skip to thông báo
  s += pathArr(`M${cx(1)+DS},${cy(10)} L${cx(1)+NW/2+15},${cy(10)} L${cx(1)+NW/2+15},${cy(12)} L${cx(1)+NW/2},${cy(12)}`);
  s += lbl(cx(1)+DS+5, cy(10)-7, 'Không sinh lịch', 'start');
  // Validation error
  s += arr(cx(1)-DS, cy(8), cx(0)+NW/2, cy(8));
  s += lbl(cx(0)+NW/2+35, cy(8)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,8,0,4,1);
  s += '</svg>'; return s;
},

// ─────────── XÓA MÔN HỌC ───────────
deleteCourse() {
  let s = svgHead(['Admin','Hệ Thống'], 10);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý môn học"');
  s += actN(1,1,'Hiển thị danh sách môn học');
  s += actN(0,2,'Chọn môn học cần xóa');
  s += actN(0,3,'Click "Xóa"');
  s += actN(1,3,'Hiện hộp thoại xác nhận');
  s += actN(0,4,'Chọn Đồng ý hay Hủy');
  s += decN(0,5);
  s += actN(1,6,'Xóa môn học và các buổi điểm danh liên quan',52);
  s += actN(1,7,'Thông báo "Xóa môn học thành công"');
  s += actN(1,8,'Cập nhật lại danh sách môn học');
  s += endN(1,9);
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,1);
  s += downA(0,2,3); s += rightA(0,1,3);
  s += crossDown(1,3,0,4); s += downAD(0,4,5);
  s += crossDownDA(0,5,1,6) + decDownLabel(0,5,'Đồng ý');
  s += downA(1,6,7); s += downA(1,7,8); s += downAE(1,8,9);
  s += loopRightD(0,5,0,2,2) + decRightLabel(0,5,'Không đồng ý');
  s += '</svg>'; return s;
},

// ─────────── ĐĂNG KÝ SV VÀO MÔN ───────────
enrollStudent() {
  let s = svgHead(['Giảng viên / Admin','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập chi tiết môn học');
  s += actN(1,1,'Hiển thị thông tin môn học');
  s += actN(0,2,'Chọn tab "Danh sách sinh viên"');
  s += actN(1,2,'Hiển thị danh sách SV đã đăng ký');
  s += actN(0,3,'Click "Thêm sinh viên"');
  s += actN(1,4,'Hiển thị danh sách SV chưa đăng ký môn');
  s += actN(0,5,'Chọn sinh viên cần đăng ký');
  s += actN(0,6,'Click "Đăng ký"');
  s += actN(1,7,'Kiểm tra sinh viên đã đăng ký chưa');
  s += decN(1,8);
  s += actN(1,9,'Đăng ký sinh viên vào môn học');
  s += actN(1,10,'Thông báo "Đăng ký SV thành công"');
  s += actN(1,11,'Cập nhật danh sách sinh viên trong môn');
  s += endN(1,12);
  s += actN(0,8,'Thông báo "SV đã đăng ký rồi"');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,1); s += rightA(0,1,2);
  s += downA(0,2,3); s += crossDown(0,3,1,4);
  s += crossDown(1,4,0,5); s += downA(0,5,6);
  s += crossDown(0,6,1,7); s += downAD(1,7,8);
  s += downDA(1,8,9) + decDownLabel(1,8,'Chưa đăng ký');
  s += downA(1,9,10); s += downA(1,10,11); s += downAE(1,11,12);
  s += arr(cx(1)-DS, cy(8), cx(0)+NW/2, cy(8));
  s += lbl(cx(0)+NW/2+35, cy(8)-8, 'Đã đăng ký', 'middle');
  s += loopRight(0,8,0,5,1);
  s += '</svg>'; return s;
},

// ─────────── THỰC HIỆN ĐIỂM DANH ───────────
attendance() {
  let s = svgHead(['Giảng viên','Hệ Thống'], 14);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Điểm danh"');
  s += actN(0,2,'Chọn môn học');
  s += actN(1,2,'Hiển thị danh sách buổi học của môn');
  s += actN(0,3,'Chọn buổi học cần điểm danh');
  s += actN(0,4,'Click "Bắt đầu điểm danh"');
  s += actN(1,5,'Kiểm tra quyền giảng viên (JWT)');
  s += decN(1,6);
  s += actN(1,7,'Chuyển trạng thái buổi học sang "in_progress"',52);
  s += actN(1,8,'Hiển thị danh sách SV với trạng thái điểm danh');
  s += actN(0,9,'Cập nhật trạng thái từng SV (có mặt / vắng / muộn / có phép)',52);
  s += actN(0,10,'Click "Hoàn thành điểm danh"');
  s += actN(1,11,'Lưu kết quả điểm danh vào CSDL');
  s += actN(1,12,'Khóa buổi điểm danh (không được sửa nữa)',52);
  s += actN(1,13,'Thông báo "Hoàn thành điểm danh"');
  s += endN(0,14);
  // Error branch
  s += actN(0,6,'Thông báo "Không có quyền"');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3); s += downA(0,3,4);
  s += crossDown(0,4,1,5); s += downAD(1,5,6);
  s += downDA(1,6,7) + decDownLabel(1,6,'Có quyền');
  s += downA(1,7,8); s += crossDown(1,8,0,9);
  s += downA(0,9,10); s += crossDown(0,10,1,11);
  s += downA(1,11,12); s += downA(1,12,13);
  s += crossDown(1,13,0,14);
  // Không có quyền
  s += arr(cx(1)-DS, cy(6), cx(0)+NW/2, cy(6));
  s += lbl(cx(0)+NW/2+35, cy(6)-8, 'Không có quyền', 'middle');
  // End from no permission - go down to end directly
  const endY = cy(14);
  s += pathArr(`M${cx(0)},${cy(6)+NH/2} L${cx(0)},${endY-SR-3}`);
  s += '</svg>'; return s;
},

// ─────────── TẠO NHIỆM VỤ ───────────
createTask() {
  let s = svgHead(['Giảng viên','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý nhiệm vụ"');
  s += actN(1,1,'Hiển thị danh sách nhiệm vụ');
  s += actN(0,2,'Click "Tạo nhiệm vụ mới"');
  s += actN(1,3,'Hiển thị form tạo nhiệm vụ');
  s += actN(0,4,'Nhập thông tin (tiêu đề, mô tả, hạn nộp, file đính kèm)',52);
  s += actN(0,5,'Chọn sinh viên / nhóm được giao');
  s += actN(0,6,'Click "Lưu"');
  s += actN(1,7,'Validate dữ liệu nhiệm vụ');
  s += decN(1,8);
  s += actN(1,9,'Tạo nhiệm vụ trong CSDL và lưu file đính kèm',52);
  s += actN(1,10,'Thông báo "Tạo nhiệm vụ thành công"');
  s += actN(1,11,'Cập nhật danh sách nhiệm vụ');
  s += endN(1,12);
  s += actN(0,8,'Hiển thị lỗi validation');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,1);
  s += crossDown(0,2,1,3); s += crossDown(1,3,0,4);
  s += downA(0,4,5); s += downA(0,5,6);
  s += crossDown(0,6,1,7); s += downAD(1,7,8);
  s += downDA(1,8,9) + decDownLabel(1,8,'Hợp lệ');
  s += downA(1,9,10); s += downA(1,10,11); s += downAE(1,11,12);
  s += arr(cx(1)-DS, cy(8), cx(0)+NW/2, cy(8));
  s += lbl(cx(0)+NW/2+35, cy(8)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,8,0,4,1);
  s += '</svg>'; return s;
},

// ─────────── NỘP NHIỆM VỤ ───────────
submitTask() {
  let s = svgHead(['Sinh viên','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Danh sách nhiệm vụ"');
  s += actN(1,1,'Hiển thị danh sách nhiệm vụ được giao');
  s += actN(0,2,'Chọn nhiệm vụ cần nộp');
  s += actN(1,3,'Hiển thị chi tiết nhiệm vụ và form nộp bài');
  s += actN(0,4,'Nhập nội dung bài làm / Đính kèm file');
  s += actN(0,5,'Click "Nộp bài"');
  s += actN(1,6,'Validate dữ liệu nộp bài');
  s += decN(1,7);
  s += actN(1,8,'Lưu bài nộp vào CSDL');
  s += actN(1,9,'Dispatch background job xử lý post-submission',52);
  s += actN(1,10,'Thông báo "Nộp bài thành công"');
  s += actN(0,11,'Xem trạng thái bài nộp đã cập nhật');
  s += endN(0,12);
  s += actN(0,7,'Hiển thị lỗi nộp bài');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,1);
  s += crossDown(0,2,1,3); s += crossDown(1,3,0,4);
  s += downA(0,4,5); s += crossDown(0,5,1,6); s += downAD(1,6,7);
  s += downDA(1,7,8) + decDownLabel(1,7,'Hợp lệ');
  s += downA(1,8,9); s += downA(1,9,10);
  s += crossDown(1,10,0,11); s += downAE(0,11,12);
  s += arr(cx(1)-DS, cy(7), cx(0)+NW/2, cy(7));
  s += lbl(cx(0)+NW/2+35, cy(7)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,7,0,4,1);
  s += '</svg>'; return s;
},

// ─────────── CHẤM ĐIỂM NHIỆM VỤ ───────────
gradeTask() {
  let s = svgHead(['Giảng viên','Hệ Thống'], 12);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý nhiệm vụ"');
  s += actN(0,2,'Chọn nhiệm vụ cần chấm điểm');
  s += actN(1,2,'Hiển thị danh sách bài nộp');
  s += actN(0,3,'Chọn bài nộp của sinh viên');
  s += actN(1,4,'Hiển thị chi tiết bài nộp (nội dung, file)');
  s += actN(0,5,'Xem bài làm của sinh viên');
  s += actN(0,6,'Nhập điểm và nhận xét');
  s += actN(0,7,'Click "Chấm điểm"');
  s += actN(1,8,'Validate dữ liệu chấm điểm');
  s += decN(1,9);
  s += actN(1,10,'Lưu điểm và nhận xét vào CSDL');
  s += actN(1,11,'Thông báo "Chấm điểm thành công"');
  s += endN(0,12);
  s += actN(0,9,'Hiển thị lỗi');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3); s += crossDown(0,3,1,4);
  s += crossDown(1,4,0,5); s += downA(0,5,6); s += downA(0,6,7);
  s += crossDown(0,7,1,8); s += downAD(1,8,9);
  s += downDA(1,9,10) + decDownLabel(1,9,'Hợp lệ');
  s += downA(1,10,11);
  s += crossDown(1,11,0,12);
  s += arr(cx(1)-DS, cy(9), cx(0)+NW/2, cy(9));
  s += lbl(cx(0)+NW/2+35, cy(9)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,9,0,6,1);
  s += '</svg>'; return s;
},

// ─────────── IMPORT SINH VIÊN TỪ EXCEL ───────────
importStudent() {
  let s = svgHead(['Admin','Hệ Thống'], 14);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý sinh viên"');
  s += actN(0,2,'Click "Import từ Excel"');
  s += actN(1,2,'Hiển thị form upload file Excel');
  s += actN(0,3,'Chọn file Excel (.xlsx, .xls)');
  s += actN(0,4,'Click "Upload"');
  s += actN(1,5,'Validate file (định dạng, kích thước)',52);
  s += decN(1,6);
  s += actN(1,7,'Lưu file và tạo ImportJob (status: pending)');
  s += actN(1,8,'Dispatch Background Job (AddListStudent)',52);
  s += actN(1,9,'Validate từng dòng Excel (mã SV, email, lớp...)',52);
  s += decN(1,10);
  s += actN(1,11,'Import SV vào CSDL, tự động tạo tài khoản',52);
  s += actN(1,12,'Cập nhật ImportJob (thành công/thất bại, số lượng)',52);
  s += actN(0,13,'Hiển thị kết quả import (thành công, lỗi)');
  s += endN(0,14);
  s += actN(0,6,'Thông báo lỗi file không hợp lệ');
  s += actN(0,10,'Hiển thị danh sách lỗi validation');
  // Arrows
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3); s += downA(0,3,4);
  s += crossDown(0,4,1,5); s += downAD(1,5,6);
  s += downDA(1,6,7) + decDownLabel(1,6,'Hợp lệ');
  s += downA(1,7,8); s += downA(1,8,9); s += downAD(1,9,10);
  s += downDA(1,10,11) + decDownLabel(1,10,'Dữ liệu OK');
  s += downA(1,11,12); s += crossDown(1,12,0,13); s += downAE(0,13,14);
  // File invalid
  s += arr(cx(1)-DS, cy(6), cx(0)+NW/2, cy(6));
  s += lbl(cx(0)+NW/2+35, cy(6)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,6,0,3,1);
  // Data validation fail
  s += arr(cx(1)-DS, cy(10), cx(0)+NW/2, cy(10));
  s += lbl(cx(0)+NW/2+35, cy(10)-8, 'Có lỗi dữ liệu', 'middle');
  s += downAE(0,10,14);
  s += '</svg>'; return s;
},

// ─────────── IMPORT GIẢNG VIÊN TỪ EXCEL ───────────
importLecturer() {
  let s = svgHead(['Admin','Hệ Thống'], 14);
  s += startN(0,0);
  s += actN(0,1,'Truy cập "Quản lý giảng viên"');
  s += actN(0,2,'Click "Import từ Excel"');
  s += actN(1,2,'Hiển thị form upload file Excel');
  s += actN(0,3,'Chọn file Excel (.xlsx, .xls)');
  s += actN(0,4,'Click "Upload"');
  s += actN(1,5,'Validate file (định dạng, kích thước)',52);
  s += decN(1,6);
  s += actN(1,7,'Lưu file và tạo ImportJob (status: pending)');
  s += actN(1,8,'Dispatch Background Job (AddListLecturer)',52);
  s += actN(1,9,'Validate từng dòng Excel (mã GV, email, khoa...)',52);
  s += decN(1,10);
  s += actN(1,11,'Import GV vào CSDL, tự động tạo tài khoản',52);
  s += actN(1,12,'Cập nhật ImportJob (thành công/thất bại, số lượng)',52);
  s += actN(0,13,'Hiển thị kết quả import (thành công, lỗi)');
  s += endN(0,14);
  s += actN(0,6,'Thông báo lỗi file không hợp lệ');
  s += actN(0,10,'Hiển thị danh sách lỗi validation');
  s += downAS(0,0,1); s += downA(0,1,2); s += rightA(0,1,2);
  s += crossDown(1,2,0,3); s += downA(0,3,4);
  s += crossDown(0,4,1,5); s += downAD(1,5,6);
  s += downDA(1,6,7) + decDownLabel(1,6,'Hợp lệ');
  s += downA(1,7,8); s += downA(1,8,9); s += downAD(1,9,10);
  s += downDA(1,10,11) + decDownLabel(1,10,'Dữ liệu OK');
  s += downA(1,11,12); s += crossDown(1,12,0,13); s += downAE(0,13,14);
  s += arr(cx(1)-DS, cy(6), cx(0)+NW/2, cy(6));
  s += lbl(cx(0)+NW/2+35, cy(6)-8, 'Không hợp lệ', 'middle');
  s += loopRight(0,6,0,3,1);
  s += arr(cx(1)-DS, cy(10), cx(0)+NW/2, cy(10));
  s += lbl(cx(0)+NW/2+35, cy(10)-8, 'Có lỗi dữ liệu', 'middle');
  s += downAE(0,10,14);
  s += '</svg>'; return s;
}

}; // end diagrams

// ============================================================
//  TITLES MAP
// ============================================================
const titles = {
  login:          'Đăng nhập hệ thống',
  logout:         'Đăng xuất',
  addStudent:     'Thêm sinh viên',
  editStudent:    'Sửa sinh viên',
  deleteStudent:  'Xóa sinh viên',
  addLecturer:    'Thêm giảng viên',
  editLecturer:   'Sửa giảng viên',
  deleteLecturer: 'Xóa giảng viên',
  addSemester:    'Thêm học kỳ',
  deleteSemester: 'Xóa học kỳ',
  addCourse:      'Tạo môn học',
  deleteCourse:   'Xóa môn học',
  enrollStudent:  'Đăng ký sinh viên vào môn học',
  attendance:     'Thực hiện điểm danh',
  createTask:     'Tạo nhiệm vụ',
  submitTask:     'Nộp nhiệm vụ',
  gradeTask:      'Chấm điểm nhiệm vụ',
  importStudent:  'Import sinh viên từ Excel',
  importLecturer: 'Import giảng viên từ Excel'
};

// ============================================================
//  UI LOGIC
// ============================================================
function show(id, el) {
  document.getElementById('title').textContent = 'Biểu đồ hoạt động: ' + titles[id];
  document.getElementById('canvas').innerHTML = diagrams[id]();
  document.querySelectorAll('.item').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');
}

// Load default diagram
show('login', document.querySelector('.item.active'));
</script>
</body>
</html>
