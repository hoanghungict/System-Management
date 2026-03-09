<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sơ Đồ Trạng Thái UML</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#e0e0e0;font-family:'Segoe UI',Arial,sans-serif}
        .nav{background:#333;padding:10px 20px;position:sticky;top:0;z-index:10;display:flex;gap:8px}
        .nav a{color:#fff;text-decoration:none;padding:5px 14px;border-radius:4px;font-size:13px;background:rgba(255,255,255,.1)}
        .nav a:hover{background:rgba(255,255,255,.25)}
        .card{background:#fff;margin:30px auto;max-width:800px;padding:30px 20px 20px;box-shadow:0 1px 4px rgba(0,0,0,.12)}
        .card h2{text-align:center;font-size:15px;font-weight:600;margin-bottom:15px;color:#222}
        .card svg{display:block;margin:0 auto}
        @media print{body{background:#fff}.nav{display:none}.card{box-shadow:none;page-break-after:always}}
    </style>
</head>
<body>
<div class="nav">
    <a href="#d1">1. Buổi học</a>
    <a href="#d2">2. Điểm danh</a>
    <a href="#d3">3. Thông báo</a>
</div>

<!-- ========== 1. AttendanceSession ========== -->
<div class="card" id="d1">
    <h2>Sơ đồ trạng thái: Buổi học (AttendanceSession)</h2>
    <svg viewBox="0 0 700 440" xmlns="http://www.w3.org/2000/svg" style="font-family:'Segoe UI',Arial,sans-serif;max-width:700px">
        <defs>
            <marker id="a1" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#333"/></marker>
        </defs>
        <!-- Initial -->
        <circle cx="350" cy="22" r="9" fill="#000"/>
        <line x1="350" y1="31" x2="350" y2="58" stroke="#333" stroke-width="1.5" marker-end="url(#a1)"/>
        <!-- Đã lên lịch -->
        <rect x="265" y="58" width="170" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="350" y="82" text-anchor="middle" font-size="14" fill="#333">Đã lên lịch</text>
        <!-- → Đã hủy -->
        <path d="M290,102 Q200,135 150,162" stroke="#333" stroke-width="1.5" fill="none" marker-end="url(#a1)"/>
        <text x="190" y="122" text-anchor="middle" font-size="11" fill="#555">hủy buổi học</text>
        <!-- → Đang điểm danh -->
        <line x1="350" y1="102" x2="350" y2="162" stroke="#333" stroke-width="1.5" marker-end="url(#a1)"/>
        <text x="460" y="137" text-anchor="start" font-size="11" fill="#555">GV bắt đầu điểm danh</text>
        <!-- → Nghỉ lễ -->
        <path d="M410,102 Q500,135 555,162" stroke="#333" stroke-width="1.5" fill="none" marker-end="url(#a1)"/>
        <text x="510" y="122" text-anchor="middle" font-size="11" fill="#555">đánh dấu nghỉ lễ</text>
        <!-- Đã hủy -->
        <rect x="55" y="162" width="170" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="140" y="186" text-anchor="middle" font-size="14" fill="#333">Đã hủy</text>
        <!-- Đang điểm danh -->
        <rect x="265" y="162" width="170" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="350" y="186" text-anchor="middle" font-size="14" fill="#333">Đang điểm danh</text>
        <!-- Nghỉ lễ -->
        <rect x="475" y="162" width="170" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="560" y="186" text-anchor="middle" font-size="14" fill="#333">Nghỉ lễ</text>
        <!-- → Hoàn thành -->
        <line x1="350" y1="206" x2="350" y2="278" stroke="#333" stroke-width="1.5" marker-end="url(#a1)"/>
        <text x="445" y="248" text-anchor="start" font-size="11" fill="#555">GV hoàn thành điểm danh</text>
        <!-- Hoàn thành -->
        <rect x="265" y="278" width="170" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="350" y="302" text-anchor="middle" font-size="14" fill="#333">Hoàn thành</text>
        <!-- Final -->
        <circle cx="350" cy="392" r="14" fill="none" stroke="#000" stroke-width="2"/>
        <circle cx="350" cy="392" r="8" fill="#000"/>
        <!-- HoanThanh → Final -->
        <line x1="350" y1="322" x2="350" y2="378" stroke="#333" stroke-width="1.5" marker-end="url(#a1)"/>
        <!-- DaHuy → Final -->
        <path d="M140,206 Q140,392 336,392" stroke="#333" stroke-width="1.5" fill="none" marker-end="url(#a1)"/>
        <!-- NghiLe → Final -->
        <path d="M560,206 Q560,392 364,392" stroke="#333" stroke-width="1.5" fill="none" marker-end="url(#a1)"/>
    </svg>
</div>

<!-- ========== 2. Attendance ========== -->
<div class="card" id="d2">
    <h2>Sơ đồ trạng thái: Điểm danh sinh viên (Attendance)</h2>
    <svg viewBox="0 0 700 460" xmlns="http://www.w3.org/2000/svg" style="font-family:'Segoe UI',Arial,sans-serif;max-width:700px">
        <defs>
            <marker id="a2" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#333"/></marker>
            <marker id="a2s" markerWidth="10" markerHeight="7" refX="0" refY="3.5" orient="auto"><polygon points="10 0,0 3.5,10 7" fill="#333"/></marker>
        </defs>
        <!-- Initial -->
        <circle cx="350" cy="22" r="9" fill="#000"/>
        <line x1="350" y1="31" x2="350" y2="55" stroke="#333" stroke-width="1.5" marker-end="url(#a2)"/>
        <!-- Chưa điểm danh -->
        <rect x="250" y="55" width="200" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="350" y="79" text-anchor="middle" font-size="14" fill="#333">Chưa điểm danh</text>
        <!-- Arrow down to composite -->
        <line x1="350" y1="99" x2="350" y2="140" stroke="#333" stroke-width="1.5" marker-end="url(#a2)"/>
        <text x="475" y="125" text-anchor="start" font-size="11" fill="#555">GV điểm danh</text>
        <!-- Composite: Đã điểm danh -->
        <rect x="70" y="140" width="560" height="220" rx="12" fill="#fafafa" stroke="#555" stroke-width="1.5" stroke-dasharray="6,3"/>
        <text x="350" y="162" text-anchor="middle" font-size="12" fill="#555" font-style="italic">Đã điểm danh (GV sửa tự do khi buổi học chưa hoàn thành)</text>
        <!-- 4 states in 2x2 grid -->
        <!-- Có mặt -->
        <rect x="110" y="180" width="140" height="42" rx="8" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="180" y="203" text-anchor="middle" font-size="13" fill="#333">Có mặt</text>
        <!-- Vắng mặt -->
        <rect x="380" y="180" width="140" height="42" rx="8" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="450" y="203" text-anchor="middle" font-size="13" fill="#333">Vắng mặt</text>
        <!-- Đi muộn -->
        <rect x="110" y="290" width="140" height="42" rx="8" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="180" y="313" text-anchor="middle" font-size="13" fill="#333">Đi muộn</text>
        <!-- Vắng có phép -->
        <rect x="380" y="290" width="140" height="42" rx="8" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="450" y="313" text-anchor="middle" font-size="13" fill="#333">Vắng có phép</text>
        <!-- Bidirectional arrows: horizontal -->
        <line x1="258" y1="201" x2="372" y2="201" stroke="#333" stroke-width="1.2" marker-start="url(#a2s)" marker-end="url(#a2)"/>
        <line x1="258" y1="311" x2="372" y2="311" stroke="#333" stroke-width="1.2" marker-start="url(#a2s)" marker-end="url(#a2)"/>
        <!-- Bidirectional arrows: vertical -->
        <line x1="180" y1="230" x2="180" y2="282" stroke="#333" stroke-width="1.2" marker-start="url(#a2s)" marker-end="url(#a2)"/>
        <line x1="450" y1="230" x2="450" y2="282" stroke="#333" stroke-width="1.2" marker-start="url(#a2s)" marker-end="url(#a2)"/>
        <!-- Bidirectional arrows: diagonal -->
        <line x1="245" y1="226" x2="385" y2="288" stroke="#333" stroke-width="1" stroke-dasharray="4,3" marker-start="url(#a2s)" marker-end="url(#a2)"/>
        <line x1="385" y1="226" x2="245" y2="288" stroke="#333" stroke-width="1" stroke-dasharray="4,3" marker-start="url(#a2s)" marker-end="url(#a2)"/>
        <!-- Labels on some arrows -->
        <text x="315" y="195" text-anchor="middle" font-size="10" fill="#777">GV sửa</text>
        <text x="315" y="305" text-anchor="middle" font-size="10" fill="#777">GV sửa</text>
        <text x="160" y="260" text-anchor="end" font-size="10" fill="#777">GV sửa</text>
        <text x="470" y="260" text-anchor="start" font-size="10" fill="#777">GV sửa</text>
    </svg>
</div>

<!-- ========== 3. Notification ========== -->
<div class="card" id="d3">
    <h2>Sơ đồ trạng thái: Thông báo (Notification)</h2>
    <svg viewBox="0 0 700 420" xmlns="http://www.w3.org/2000/svg" style="font-family:'Segoe UI',Arial,sans-serif;max-width:700px">
        <defs>
            <marker id="a3" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto"><polygon points="0 0,10 3.5,0 7" fill="#333"/></marker>
        </defs>
        <!-- Initial -->
        <circle cx="80" cy="100" r="9" fill="#000"/>
        <!-- → Chờ gửi (scheduled path) -->
        <line x1="89" y1="100" x2="148" y2="100" stroke="#333" stroke-width="1.5" marker-end="url(#a3)"/>
        <text x="118" y="92" text-anchor="middle" font-size="10" fill="#555">có lịch hẹn</text>
        <!-- Chờ gửi -->
        <rect x="148" y="78" width="130" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="213" y="102" text-anchor="middle" font-size="14" fill="#333">Chờ gửi</text>
        <!-- → Đang gửi from Chờ gửi -->
        <line x1="278" y1="100" x2="340" y2="100" stroke="#333" stroke-width="1.5" marker-end="url(#a3)"/>
        <text x="310" y="92" text-anchor="middle" font-size="10" fill="#555">đến giờ gửi</text>
        <!-- Initial → Đang gửi directly -->
        <path d="M80,109 Q80,175 340,175" stroke="#333" stroke-width="1.5" fill="none" marker-end="url(#a3)"/>
        <text x="180" y="168" text-anchor="middle" font-size="10" fill="#555">gửi ngay</text>
        <!-- Đang gửi (composite) -->
        <rect x="340" y="55" width="200" height="165" rx="12" fill="#fafafa" stroke="#555" stroke-width="1.5"/>
        <text x="440" y="74" text-anchor="middle" font-size="12" fill="#555" font-style="italic">Đang gửi</text>
        <!-- Sub-states inside -->
        <rect x="365" y="82" width="80" height="30" rx="6" fill="#fff" stroke="#333" stroke-width="1"/>
        <text x="405" y="100" text-anchor="middle" font-size="11" fill="#333">Email</text>
        <rect x="460" y="82" width="60" height="30" rx="6" fill="#fff" stroke="#333" stroke-width="1"/>
        <text x="490" y="100" text-anchor="middle" font-size="11" fill="#333">Push</text>
        <rect x="365" y="130" width="60" height="30" rx="6" fill="#fff" stroke="#333" stroke-width="1"/>
        <text x="395" y="148" text-anchor="middle" font-size="11" fill="#333">SMS</text>
        <rect x="440" y="130" width="80" height="30" rx="6" fill="#fff" stroke="#333" stroke-width="1"/>
        <text x="480" y="148" text-anchor="middle" font-size="11" fill="#333">In-App</text>
        <!-- Sub-arrows -->
        <line x1="445" y1="100" x2="455" y2="100" stroke="#999" stroke-width="1" marker-end="url(#a3)"/>
        <path d="M490,112 Q490,125 425,130" stroke="#999" stroke-width="1" fill="none" marker-end="url(#a3)"/>
        <line x1="425" y1="145" x2="435" y2="145" stroke="#999" stroke-width="1" marker-end="url(#a3)"/>
        <!-- → Đã gửi -->
        <line x1="540" y1="105" x2="590" y2="105" stroke="#333" stroke-width="1.5" marker-end="url(#a3)"/>
        <text x="565" y="97" text-anchor="middle" font-size="10" fill="#555">thành công</text>
        <!-- → Thất bại -->
        <line x1="540" y1="155" x2="590" y2="260" stroke="#333" stroke-width="1.5" marker-end="url(#a3)"/>
        <text x="585" y="210" text-anchor="start" font-size="10" fill="#555">lỗi</text>
        <!-- Đã gửi -->
        <rect x="590" y="82" width="90" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="635" y="106" text-anchor="middle" font-size="14" fill="#333">Đã gửi</text>
        <!-- Thất bại -->
        <rect x="590" y="240" width="90" height="44" rx="10" fill="#fff" stroke="#333" stroke-width="1.5"/>
        <text x="635" y="264" text-anchor="middle" font-size="14" fill="#333">Thất bại</text>
        <!-- Final states -->
        <circle cx="635" cy="355" r="14" fill="none" stroke="#000" stroke-width="2"/>
        <circle cx="635" cy="355" r="8" fill="#000"/>
        <!-- DaGui → Final -->
        <line x1="635" y1="126" x2="635" y2="341" stroke="#333" stroke-width="1.5" marker-end="url(#a3)"/>
        <!-- ThatBai → Final -->
        <line x1="635" y1="284" x2="635" y2="341" stroke="#333" stroke-width="1.5" marker-end="url(#a3)"/>
    </svg>
</div>

</body>
</html>
