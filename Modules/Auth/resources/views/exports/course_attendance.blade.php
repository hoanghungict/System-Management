<table>
    <thead>
        <tr>
            <th colspan="{{ count($sessions) + 6 }}" style="font-size: 16pt; font-weight: bold; text-align: center;">
                BẢNG TỔNG HỢP ĐIỂM DANH MÔN HỌC
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($sessions) + 6 }}" style="text-align: center;">
                Môn: {{ $course['name'] }} ({{ $course['code'] }}) - Giảng viên: {{ $course['lecturer'] }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($sessions) + 6 }}" style="text-align: center;">
                Học kỳ: {{ $course['semester'] }} - Ngày xuất: {{ date('d/m/Y') }}
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
            <th style="background-color: #dff0d8; border: 1px solid #000000; font-weight: bold;">Có mặt</th>
            <th style="background-color: #f2dede; border: 1px solid #000000; font-weight: bold;">Vắng</th>
            <th style="background-color: #d9edf7; border: 1px solid #000000; font-weight: bold;">Tỷ lệ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
            <tr>
                <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $student['student_code'] }}</td>
                <td style="border: 1px solid #000000;">{{ $student['name'] }}</td>
                <td style="border: 1px solid #000000;">{{ $student['class'] }}</td>
                @foreach($sessions as $session)
                    @php
                        $status = $student['attendance'][$session['id']] ?? 'not_enrolled';
                        $statusText = '';
                        $bgColor = '#ffffff';
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
                <td style="border: 1px solid #000000; text-align: center;">{{ $student['summary']['present'] + $student['summary']['late'] }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $student['summary']['absent'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">{{ round($student['summary']['attendance_rate']) }}%</td>
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
</table>
