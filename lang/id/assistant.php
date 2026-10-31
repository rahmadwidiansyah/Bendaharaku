<?php

/*
|--------------------------------------------------------------------------
| Assistant — fallback respons percakapan (lapis D)
|--------------------------------------------------------------------------
| Dipakai AssistantFallback ketika model gagal menghasilkan
| AssistantResponse yang valid. Status aktual selalu berasal dari backend.
| Hanya Formatter/lapis assistant yang boleh memanggil trans() di sini.
*/

return [
    'fallback' => [
        'success'            => '✅ Transaksi berhasil dicatat.',
        'draft'              => '📝 Aku sudah siapkan draf transaksinya — cek dulu detailnya sebelum disimpan, ya.',
        'needs_confirmation' => '⏳ Ada bagian yang perlu kamu konfirmasi dulu sebelum disimpan.',
        'failed'             => '⚠️ Transaksinya belum bisa diproses. Coba perjelas nominal dan dompetnya, ya.',
        'conversation'       => 'Aku belum paham maksudmu — coba jelaskan pakai kalimat lain, ya.',
    ],
];
