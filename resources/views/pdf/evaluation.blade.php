<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    @include('pdf._styles')
</head>
<body dir="rtl">
@php
    $plan = $data['plan'];
    $student = $plan['student'];
    $eval = $data['evaluation'];
    $report = $data['report'] ?? [];
    $results = collect($data['results'] ?? []);
    $byDomain = $results->groupBy(fn ($g) => $g['domain_name'] ?: 'أخرى');
    $n = 0;
    $sections = [
        'performance_summary' => 'ملخص الأداء',
        'strengths' => 'نقاط القوة',
        'skills_needing_training' => 'المهارات التي تحتاج إلى تدريب',
        'observed_progress' => 'التقدم الملحوظ',
        'recommendations' => 'التوصيات',
        'final_notes' => 'ملاحظات ختامية',
    ];
@endphp

@include('pdf._header')

<div class="doc-title">تقرير تقييم الخطة الفردية للتخاطب — {{ $plan['plan']['semester_label'] ?? '' }} {{ $plan['plan']['academic_year'] ?? '' }}</div>
<div class="student-name">الطالب: <strong>{{ $student['full_name'] }}</strong></div>

<table class="info">
    <tr>
        <td class="label">اسم الطالب</td><td>{{ $student['full_name'] }}</td>
        <td class="label">الفترة</td><td>{{ $student['period_label'] ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">المعلم</td><td>{{ $plan['teacher']['name'] ?? '—' }}</td>
        <td class="label">تاريخ إتمام التقييم (هجري)</td><td>{{ $eval['evaluation_completed_at_hijri'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">بداية الخطة (هجري)</td><td>{{ $plan['plan']['start_date_hijri'] ?? '' }}</td>
        <td class="label">نهاية الخطة (هجري)</td><td>{{ $plan['plan']['end_date_hijri'] ?? '' }}</td>
    </tr>
</table>

@foreach ($byDomain as $domain => $items)
    <div class="domain-title">{{ $domain }}</div>
    <table class="grid">
        <thead>
        <tr>
            <th style="width:6%">م</th>
            <th>الهدف</th>
            <th style="width:12%">أتقن</th>
            <th style="width:14%">في طور التدريب</th>
            <th style="width:12%">غير متقن</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($items as $r)
            @php $n++; @endphp
            <tr>
                <td class="num">{{ $r['printed_code'] ?: $n }}</td>
                <td>
                    {{ $r['goal_text'] }}
                    @if (! empty($r['note']))
                        <div class="meta">ملاحظة: {{ $r['note'] }}</div>
                    @endif
                </td>
                <td class="mark">@if (($r['result'] ?? null) === 'mastered')<span class="check">&#10004;</span>@endif</td>
                <td class="mark">@if (($r['result'] ?? null) === 'in_training')<span class="check">&#10004;</span>@endif</td>
                <td class="mark">@if (($r['result'] ?? null) === 'not_mastered')<span class="check">&#10004;</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach

@foreach ($sections as $key => $title)
    @if (! empty($report[$key]))
        <div class="section-title">{{ $title }}</div>
        <div class="section-body">{!! nl2br(e($report[$key])) !!}</div>
    @endif
@endforeach

<table class="sign">
    <tr>
        <td>المعلم<br>{{ $plan['teacher']['name'] ?? '' }}</td>
        <td>المشرف<br>{{ $data['approver']['name'] ?? '' }}</td>
    </tr>
</table>
</body>
</html>
