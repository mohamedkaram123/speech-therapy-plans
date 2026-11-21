<?php

return [
    'center_name' => env('CENTER_NAME', 'مركز السنابل المكية'),
    'center_dept' => env('CENTER_DEPT', 'قسم التربية الخاصة'),

    // Relative paths are resolved against base_path().
    'goals_bank_path' => env('GOALS_BANK_PATH', 'project-inputs/برنامج تنمية المهارات اللغوية.docx'),

    'pdf' => [
        'arabic_font_path' => env('PDF_ARABIC_FONT_PATH', '/usr/share/fonts/truetype/noto/NotoNaskhArabic-Regular.ttf'),
        'arabic_font_bold_path' => env('PDF_ARABIC_FONT_BOLD_PATH', '/usr/share/fonts/truetype/noto/NotoNaskhArabic-Bold.ttf'),
    ],
];
