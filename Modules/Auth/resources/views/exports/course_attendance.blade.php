<table>
    <thead>
        <tr>
            <th colspan="{{ count($sessions) + 9 }}" style="font-size: 16pt; font-weight: bold; text-align: center;">
                BẢNG TỔNG HỢP ĐIỂM DANH MÔN HỌC
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($sessions) + 9 }}" style="text-align: center;">
                Môn: {{ $course['name'] }} ({{ $course['code'] }}) - Giảng viên: {{ $course['lecturer'] }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($sessions) + 9 }}" style="text-align: center;">
                Học kỳ: {{ $course['semester'] }} - Số buổi vắng tối đa: {{ $course['max_absences'] ?? 5 }} - Ngày xuất: {{ date('d/m/Y') }}
            </th>
        </tr>
        <tr></tr>
        <tr>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">STT</th>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">MSSV</th>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">Họ và tên</th>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">Lớp</th>
            @foreach($sessions as $session)
                <th style="background-color: #e0e0e0; border: 1px solid #000000; font-weight: bold; text-align: center;">
                    {{ \Carbon\Carbon::parse($session['date'])->format('d/m') }}<br>
                    B.{{ $session['session_number'] }}
                </th>
            @endforeach
            <th style="background-color: #dff0d8; border: 1px solid #000000; font-weight: bold; text-align: center;">Có mặt</th>
            <th style="background-color: #fcf8e3; border: 1px solid #000000; font-weight: bold; text-align: center;">Muộn</th>
            <th style="background-color: #d9edf7; border: 1px solid #000000; font-weight: bold; text-align: center;">Phép</th>
            <th style="background-color: #f2dede; border: 1px solid #000000; font-weight: bold; text-align: center;">Vắng</th>
            <th style="background-color: #d9edf7; border: 1px solid #000000; font-weight: bold; text-align: center;">Tỷ lệ</th>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold; text-align: center;">Trạng thái</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
            @php
                $isAtRisk = $student['summary']['is_at_risk'] ?? false;
                $isExceeded = $student['summary']['is_exceeded'] ?? false;
                $rowBg = $isExceeded ? '#fde2e2' : ($isAtRisk ? '#fff8e1' : '#ffffff');
                $maxAbs = $course['max_absences'] ?? 5;
            @endphp
            <tr>
                <td style="border: 1px solid #000000; text-align: center; background-color: {{ $rowBg }};">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000; background-color: {{ $rowBg }};">{{ $student['student_code'] }}</td>
                <td style="border: 1px solid #000000; background-color: {{ $rowBg }};">{{ $student['name'] }}</td>
                <td style="border: 1px solid #000000; background-color: {{ $rowBg }};">{{ $student['class'] }}</td>
                @foreach($sessions as $session)
                    @php
                        $status = $student['attendance'][$session['id']] ?? 'not_enrolled';
                        $statusText = '';
                        $bgColor = $rowBg;
                        switch($status) {
                            case 'present': $statusText = 'V'; $bgColor = '#dff0d8'; break;
                            case 'absent': $statusText = 'X'; $bgColor = '#f2dede'; break;
                            case 'late': $statusText = 'M'; $bgColor = '#fcf8e3'; break;
                            case 'excused': $statusText = 'P'; $bgColor = '#d9edf7'; break;
                            case 'not_marked': $statusText = '-'; break;
                            default: $statusText = ''; break;
                        }
                    @endphp
                    <td style="border: 1px solid #000000; text-align: center; background-color: {{ $bgColor }};">
                        {{ $statusText }}
                    </td>
                @endforeach
                <td style="border: 1px solid #000000; text-align: center; background-color: #dff0d8; font-weight: bold;">{{ $student['summary']['present'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #fcf8e3; font-weight: bold;">{{ $student['summary']['late'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #d9edf7; font-weight: bold;">{{ $student['summary']['excused'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: {{ $isAtRisk ? '#f2dede' : '#ffffff' }}; font-weight: bold; color: {{ $isAtRisk ? '#cc0000' : '#000000' }};">{{ $student['summary']['absent'] }}/{{ $maxAbs }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: {{ $student['summary']['attendance_rate'] >= 80 ? '#006600' : ($student['summary']['attendance_rate'] >= 60 ? '#cc6600' : '#cc0000') }};">{{ round($student['summary']['attendance_rate']) }}%</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; background-color: {{ $isExceeded ? '#f2dede' : ($isAtRisk ? '#fcf8e3' : '#dff0d8') }}; color: {{ $isExceeded ? '#cc0000' : ($isAtRisk ? '#cc6600' : '#006600') }};">
                    {{ $isExceeded ? 'CẤM THI' : ($isAtRisk ? 'CẢNH BÁO' : 'Bình thường') }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<table>
    <tr></tr>
    <tr>
        <td colspan="3" style="font-weight: bold;">Chú thích:</td>
    </tr>
    <tr>
        <td style="background-color: #dff0d8; text-align: center; border: 1px solid #000000;">V</td>
        <td colspan="2">Có mặt</td>
    </tr>
    <tr>
        <td style="background-color: #f2dede; text-align: center; border: 1px solid #000000;">X</td>
        <td colspan="2">Vắng mặt (không phép)</td>
    </tr>
    <tr>
        <td style="background-color: #fcf8e3; text-align: center; border: 1px solid #000000;">M</td>
        <td colspan="2">Đi muộn</td>
    </tr>
    <tr>
        <td style="background-color: #d9edf7; text-align: center; border: 1px solid #000000;">P</td>
        <td colspan="2">Vắng có phép</td>
    </tr>
    <tr></tr>
    <tr>
        <td colspan="3" style="font-weight: bold;">Trạng thái:</td>
    </tr>
    <tr>
        <td style="background-color: #dff0d8; text-align: center; border: 1px solid #000000;">Bình thường</td>
        <td colspan="2">Số buổi vắng trong phạm vi cho phép</td>
    </tr>
    <tr>
        <td style="background-color: #fcf8e3; text-align: center; border: 1px solid #000000;">CẢNH BÁO</td>
        <td colspan="2">Sắp hết số buổi vắng cho phép</td>
    </tr>
    <tr>
        <td style="background-color: #f2dede; text-align: center; border: 1px solid #000000;">CẤM THI</td>
        <td colspan="2">Vượt quá số buổi vắng cho phép</td>
    </tr>
</table>
