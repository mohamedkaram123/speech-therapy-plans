<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    @include('pdf._styles')
</head>
<body dir="rtl">
@php
    $student = $data['student'];
    $plan = $data['plan'];
    $goals = collect($data['goals'] ?? []);
    $byDomain = $goals->groupBy(fn ($g) => $g['domain_name'] ?: 'أخرى');
    $n = 0;
@endphp

@include('pdf._header')

<div class="doc-title">الخطة الفردية للتخاطب — {{ $plan['semester_label'] ?? '' }} {{ $plan['academic_year'] ?? '' }}</div>
<div class="student-name">الطالب: <strong>{{ $student['full_name'] }}</strong></div>

<table class="info">
    <tr>
        <td class="label">اسم الطالب</td><td>{{ $student['full_name'] }}</td>
        <td class="label">تاريخ الميلاد (هجري)</td><td>{{ $student['birth_date_hijri'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">الفترة</td><td>{{ $student['period_label'] ?? '—' }}</td>
        <td class="label">المعلم</td><td>{{ $data['teacher']['name'] ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">التشخيص</td><td colspan="3">{{ $student['diagnosis'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">بداية الخطة (هجري)</td><td>{{ $plan['start_date_hijri'] }}</td>
        <td class="label">نهاية الخطة (هجري)</td><td>{{ $plan['end_date_hijri'] }}</td>
    </tr>
    <tr>
        <td class="label">عدد الجلسات</td><td>{{ $plan['sessions_count'] }}</td>
        <td class="label">الجلسات في الأسبوع</td><td>{{ $plan['sessions_per_week'] }}</td>
    </tr>
</table>

@foreach ($byDomain as $domain => $items)
    <div class="domain-title">{{ $domain }}</div>
    <table class="grid">
        <thead>
        <tr>
            <th style="width:6%">م</th>
            <th style="width:34%">الهدف</th>
            <th style="width:32%">عناصر التدريب</th>
            <th style="width:28%">استراتيجيات التعميم</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($items as $g)
            @php $n++; @endphp
            <tr>
                <td class="num">{{ $g['printed_code'] ?: $n }}</td>
                <td>
                    {{ $g['goal_text'] }}
                    @if (! empty($g['level']) || ! empty($g['subcategory']))
                        <div class="meta">{{ collect([$g['level'] ?? null, $g['subcategory'] ?? null])->filter()->implode(' — ') }}</div>
                    @endif
                    @if (! empty($g['teacher_note']))
                        <div class="meta">ملاحظة المعلم: {{ $g['teacher_note'] }}</div>
                    @endif
                </td>
                <td>{!! nl2br(e($g['training_elements'] ?? '')) !!}</td>
                <td>{!! nl2br(e($g['generalization'] ?? '')) !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach

<table class="sign">
    <tr>
        <td>المعلم<br>{{ $data['teacher']['name'] ?? '' }}</td>
        <td>المشرف<br>{{ $data['approver']['name'] ?? '' }}</td>
    </tr>
</table>
</body>
</html>
