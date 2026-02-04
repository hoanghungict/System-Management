<table>
    <thead>
        <tr>
            <th colspan="{{ count($columns) + 3 }}" style="font-size: 16pt; font-weight: bold; text-align: center;">
                BẢNG TỔNG QUAN ĐIỂM DANH HỌC KỲ
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($columns) + 3 }}" style="text-align: center;">
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
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
            <tr>
                <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $student['student_code'] }}</td>
                <td style="border: 1px solid #000000;">{{ $student['name'] }}</td>
                <td style="border: 1px solid #000000;">{{ $student['class'] }}</td>
                @foreach($columns as $col)
                    @php
                        $key = "{$student['id']}-{$col['date']}-{$col['shift']}";
                        $status = $attendance[$key] ?? null;
                        $statusText = '';
                        $bgColor = '#ffffff';
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
            </tr>
        @endforeach
    </tbody>
</table>
