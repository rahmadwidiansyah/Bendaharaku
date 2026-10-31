<?php

/*
|--------------------------------------------------------------------------
| Assistant — conversational response fallback (layer D)
|--------------------------------------------------------------------------
| Used by AssistantFallback when the model fails to produce a valid
| AssistantResponse. The actual status always comes from the backend.
| Only the Formatter/assistant layer may call trans() here.
*/

return [
    'fallback' => [
        'success'            => '✅ Transaction recorded successfully.',
        'draft'              => '📝 I prepared the transaction draft — review the details before saving.',
        'needs_confirmation' => '⏳ One part needs your confirmation before saving.',
        'failed'             => '⚠️ The transaction could not be processed yet. Try clarifying the amount and wallet.',
        'conversation'       => "I didn't quite catch that — could you rephrase it?",
    ],
];
