<table>
    <thead>
        <tr>
            <th colspan="{{ count($columns) + 8 }}" style="font-size: 16pt; font-weight: bold; text-align: center;">
                BẢNG TỔNG QUAN ĐIỂM DANH HỌC KỲ
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($columns) + 8 }}" style="text-align: center;">
                Học kỳ: {{ $semester_name }} - Ngày xuất: {{ date('d/m/Y') }}
            </th>
        </tr>
        <tr></tr>
        <tr>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">STT</th>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">MSSV</th>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">Họ và tên</th>
            <th style="background-color: #f0f0f0; border: 1px solid #000000; font-weight: bold;">Lớp</th>
            @foreach($columns as $col)
                <th style="background-color: #e0e0e0; border: 1px solid #000000; font-weight: bold; text-align: center;">
                    {{ \Carbon\Carbon::parse($col['date'])->format('d/m') }}<br>
                    @php
                        $shiftLabel = '';
                        switch($col['shift']) {
                            case 'morning': $shiftLabel = 'Sáng'; break;
                            case 'afternoon': $shiftLabel = 'Chiều'; break;
                            case 'evening': $shiftLabel = 'Tối'; break;
                        }
                    @endphp
                    {{ $shiftLabel }}
                </th>
            @endforeach
            <th style="background-color: #dff0d8; border: 1px solid #000000; font-weight: bold; text-align: center;">Có mặt</th>
            <th style="background-color: #fcf8e3; border: 1px solid #000000; font-weight: bold; text-align: center;">Muộn</th>
            <th style="background-color: #f2dede; border: 1px solid #000000; font-weight: bold; text-align: center;">Vắng</th>
            <th style="background-color: #d9edf7; border: 1px solid #000000; font-weight: bold; text-align: center;">Tỷ lệ</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
            @php
                // Tính thống kê cho SV này
                $present = 0;
                $absent = 0;
                $late = 0;
                $excused = 0;
                $total = 0;
                
                foreach ($columns as $col) {
                    $key = "{$student['id']}-{$col['date']}-{$col['shift']}";
                    $status = $attendance[$key] ?? null;
                    if ($status && $status !== 'not_enrolled' && $status !== 'not_marked') {
                        $total++;
                        switch($status) {
                            case 'present': $present++; break;
                            case 'absent': $absent++; break;
                            case 'late': $late++; break;
                            case 'excused': $excused++; break;
                        }
                    }
                }
                
                $attended = $present + $late;
                $rate = $total > 0 ? round(($attended / $total) * 100) : 100;
                $isAtRisk = $absent >= 3;
                $rowBg = $isAtRisk ? '#fde2e2' : '#ffffff';
            @endphp
            <tr>
                <td style="border: 1px solid #000000; text-align: center; background-color: {{ $rowBg }};">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000; background-color: {{ $rowBg }};">{{ $student['student_code'] }}</td>
                <td style="border: 1px solid #000000; background-color: {{ $rowBg }};">{{ $student['name'] }}</td>
                <td style="border: 1px solid #000000; background-color: {{ $rowBg }};">{{ $student['class'] }}</td>
                @foreach($columns as $col)
                    @php
                        $key = "{$student['id']}-{$col['date']}-{$col['shift']}";
                        $status = $attendance[$key] ?? null;
                        $statusText = '';
                        $bgColor = $rowBg;
                        if ($status) {
                            switch($status) {
                                case 'present': $statusText = 'V'; $bgColor = '#dff0d8'; break;
                                case 'absent': $statusText = 'X'; $bgColor = '#f2dede'; break;
                                case 'late': $statusText = 'M'; $bgColor = '#fcf8e3'; break;
                                case 'excused': $statusText = 'P'; $bgColor = '#d9edf7'; break;
                                case 'not_marked': $statusText = '-'; break;
                            }
                        }
                    @endphp
                    <td style="border: 1px solid #000000; text-align: center; background-color: {{ $bgColor }};">
                        {{ $statusText }}
                    </td>
                @endforeach
                <td style="border: 1px solid #000000; text-align: center; background-color: #dff0d8; font-weight: bold;">{{ $present }}</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #fcf8e3; font-weight: bold;">{{ $late }}</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: {{ $isAtRisk ? '#f2dede' : '#ffffff' }}; font-weight: bold; color: {{ $isAtRisk ? '#cc0000' : '#000000' }};">{{ $absent }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: {{ $rate >= 80 ? '#006600' : ($rate >= 60 ? '#cc6600' : '#cc0000') }};">{{ $rate }}%</td>
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
