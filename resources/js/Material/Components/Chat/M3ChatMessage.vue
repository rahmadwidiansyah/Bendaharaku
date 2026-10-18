<script setup>
/**
 * M3ChatMessage — bubble tunggal riwayat percakapan (paritas ChatMessage Default).
 *
 * Bot  : avatar kiri + kartu surface-container-high (error → error-container).
 * User : bubble primary-container, avatar kanan; foto+caption dalam satu bubble.
 * Alur review DIHAPUS (keputusan scope M3); retry evidence tetap ada.
 */
import M3MessageRenderer from './Messages/M3MessageRenderer.vue'
import M3ResponseMeta from './Messages/M3ResponseMeta.vue'
import M3MessageToolbar from './Messages/M3MessageToolbar.vue'
import M3TypingIndicator from './M3TypingIndicator.vue'
import M3BotAvatar from './M3BotAvatar.vue'
import ChatTimestamp from '@/Components/Chat/ChatTimestamp.vue'
import { useMessageContent } from '@/Composables/useMessageContent.js'
import { computed } from 'vue'

const props = defineProps({
    message:    { type: Object,  required: true },
    botAvatar:  { type: String,  default: null },
    botName:    { type: String,  default: 'Ken-Chan' },
    userAvatar: { type: String,  default: null },
    userName:   { type: String,  default: 'Kamu' },
    showAvatar: { type: Boolean, default: true },
})

const emit = defineEmits(['retry', 'regenerate', 'suggest', 'retryEvidence'])

const isPending = computed(() =>
    props.message?.role === 'assistant' &&
    (props.message.status === 'pending' || props.message.status === 'processing'),
)

const {
    isUser,
    isBot,
    isErrorMessage,
    userText,
    filteredInline,
    cardComponents,
} = useMessageContent(() => props.message)

const userImageComponents = computed(() => filteredInline.value.filter(c => c.type === 'image'))
const userTextComponents = computed(() => filteredInline.value.filter(c => c.type !== 'image'))
const hasUserImage = computed(() => userImageComponents.value.length > 0)
</script>

<template>
    <!-- Bot pending: bubble mengetik -->
    <M3TypingIndicator v-if="isPending" :bot-avatar="botAvatar" :bot-name="botName" />

    <!-- Pesan bot -->
    <div v-else-if="isBot" class="m3-msg m3-msg--bot">
        <div class="m3-msg__avatar">
            <M3BotAvatar v-if="showAvatar" :src="botAvatar" :name="botName" size="sm" variant="bot" shape="circle" />
        </div>

        <div class="m3-msg__bubble" :class="isErrorMessage ? 'm3-msg__bubble--error' : 'm3-msg__bubble--bot'">
            <!-- Kartu (transaction_card, summary_card, report_section, bar_chart, dst.) -->
            <template v-for="(comp, i) in cardComponents" :key="'card-' + i">
                <M3MessageRenderer :component="comp" :metadata="message.metadata ?? {}" @suggest="emit('suggest', $event)" />
            </template>

            <!-- Inline (text, divider, suggestion, image) -->
            <div v-if="filteredInline.length > 0" class="m3-msg__inline">
                <template v-for="(comp, i) in filteredInline" :key="i">
                    <M3MessageRenderer :component="comp" @suggest="emit('suggest', $event)" />
                </template>
            </div>

            <!-- Footer: meta respons + timestamp -->
            <div class="m3-msg__footer">
                <M3ResponseMeta :metadata="message.metadata" :content="message.content ?? []" />
                <ChatTimestamp :datetime="message.created_at" class="m3-msg__time" />
            </div>
        </div>

        <div v-if="filteredInline.length > 0 || cardComponents.length > 0" class="m3-msg__toolbar">
            <M3MessageToolbar :message="message" :is-error="isErrorMessage" @retry="emit('retry', message)" @regenerate="emit('regenerate', message)" />
        </div>
    </div>

    <!-- Pesan user -->
    <div v-else-if="isUser" class="m3-msg m3-msg--user">
        <div class="m3-msg__user-col" :style="hasUserImage ? 'max-width: min(280px, 82%)' : 'max-width: 82%'">
            <!-- Foto + caption (satu bubble, WA-style) -->
            <template v-if="hasUserImage">
                <div class="m3-msg__bubble m3-msg__bubble--user m3-msg__bubble--media">
                    <div v-for="(comp, i) in userImageComponents" :key="'img-' + i" class="m3-msg__media">
                        <M3MessageRenderer
                            :component="comp"
                            @suggest="emit('suggest', $event)"
                            @retry="emit('retryEvidence', $event)"
                        />
                    </div>
                    <div v-if="userTextComponents.length > 0" class="m3-msg__caption">
                        <template v-for="(comp, i) in userTextComponents" :key="'txt-' + i">
                            <M3MessageRenderer
                                :component="comp"
                                @suggest="emit('suggest', $event)"
                                @retry="emit('retryEvidence', $event)"
                            />
                        </template>
                        <div class="m3-msg__caption-time">
                            <ChatTimestamp :datetime="message.created_at" class="m3-msg__time" />
                        </div>
                    </div>
                    <div v-else class="m3-msg__media-time">
                        <ChatTimestamp :datetime="message.created_at" class="m3-msg__time" />
                    </div>
                </div>
                <!-- Backward compat: teks tanpa filteredInline -->
                <div v-if="filteredInline.length === 0 && userText" class="m3-msg__bubble m3-msg__bubble--user">
                    <p class="m3-msg__raw">{{ userText }}</p>
                    <div class="m3-msg__caption-time">
                        <ChatTimestamp :datetime="message.created_at" class="m3-msg__time" />
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="m3-msg__bubble m3-msg__bubble--user">
                    <template v-for="(comp, i) in filteredInline" :key="i">
                        <M3MessageRenderer
                            :component="comp"
                            @suggest="emit('suggest', $event)"
                            @retry="emit('retryEvidence', $event)"
                        />
                    </template>
                    <p v-if="filteredInline.length === 0 && userText" class="m3-msg__raw">{{ userText }}</p>
                    <div class="m3-msg__caption-time">
                        <ChatTimestamp :datetime="message.created_at" class="m3-msg__time" />
                    </div>
                </div>
            </template>
        </div>

        <div class="m3-msg__avatar">
            <M3BotAvatar v-if="showAvatar" :src="userAvatar" :name="userName" size="sm" variant="user" shape="circle" />
        </div>
    </div>
</template>

<style scoped>
.m3-msg {
    display: flex;
    align-items: flex-end;
    padding: var(--m3-space-0-5) var(--m3-space-3);
    animation: m3-msg-in var(--m3-motion-default-effects-duration) var(--m3-motion-default-effects);
}

@keyframes m3-msg-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

.m3-msg--bot {
    gap: var(--m3-space-2);
}

.m3-msg--user {
    justify-content: flex-end;
    gap: var(--m3-space-2);
}

.m3-msg__avatar {
    width: 1.5rem;
    height: 1.5rem;
    flex-shrink: 0;
    align-self: flex-end;
    margin-bottom: var(--m3-space-0-5);
}

/* ── Bubble ─────────────────────────────────────────────────────── */
.m3-msg__bubble {
    display: flex;
    flex-direction: column;
    min-width: 0;
    max-width: 80%;
    border-radius: var(--m3-radius-lg-inc);
    overflow: hidden;
}

.m3-msg__bubble--bot {
    background: var(--m3-color-surface-container-high);
    border-top-left-radius: var(--m3-radius-xs);
    box-shadow: var(--m3-elevation-1);
}

.m3-msg__bubble--error {
    background: var(--m3-color-error-container);
    color: var(--m3-color-on-error-container);
    border-top-left-radius: var(--m3-radius-xs);
}

.m3-msg__bubble--user {
    background: var(--m3-color-primary-container);
    color: var(--m3-color-on-primary-container);
    border-top-right-radius: var(--m3-radius-xs);
    box-shadow: var(--m3-elevation-1);
}

.m3-msg__bubble--media {
    width: 100%;
    max-width: 280px;
}

.m3-msg__user-col {
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
    align-items: flex-end;
    min-width: 0;
}

.m3-msg__inline {
    padding: var(--m3-space-3) var(--m3-space-3) var(--m3-space-1);
    display: flex;
    flex-direction: column;
    gap: var(--m3-space-1);
}

.m3-msg__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--m3-space-2);
    padding: var(--m3-space-1) var(--m3-space-3) var(--m3-space-1-5);
}

.m3-msg__time {
    font-size: var(--m3-type-label-small-size);
    line-height: var(--m3-type-label-small-line-height);
    color: var(--m3-color-on-surface-variant);
    flex-shrink: 0;
}

/* Waktu di bubble user mengikuti warna on-primary-container */
.m3-msg__bubble--user .m3-msg__time {
    color: inherit;
    opacity: 0.72;
}

.m3-msg__media {
    width: 100%;
}

.m3-msg__caption {
    padding: var(--m3-space-2) var(--m3-space-3) var(--m3-space-1-5);
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    overflow-wrap: break-word;
}

.m3-msg__caption-time,
.m3-msg__media-time {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--m3-space-1);
}

.m3-msg__media-time {
    padding: var(--m3-space-1) var(--m3-space-3) var(--m3-space-1-5);
    margin-top: 0;
}

.m3-msg__raw {
    padding: var(--m3-space-2) var(--m3-space-3) 0;
    font-size: var(--m3-type-body-medium-size);
    line-height: var(--m3-type-body-medium-line-height);
    white-space: pre-wrap;
    overflow-wrap: break-word;
}

/* Toolbar: reveal hover + focus-within (a11y keyboard) */
.m3-msg__toolbar {
    display: flex;
    align-items: center;
    margin-left: var(--m3-space-1);
    opacity: 0;
    transition: opacity var(--m3-motion-fast-effects-duration) var(--m3-motion-fast-effects);
}

.m3-msg:hover .m3-msg__toolbar,
.m3-msg:focus-within .m3-msg__toolbar {
    opacity: 1;
}

/* Markdown di bubble user mengikuti on-primary-container */
.m3-msg__bubble--user :deep(.m3-md),
.m3-msg__bubble--user :deep(.m3-md p),
.m3-msg__bubble--user :deep(.m3-md li),
.m3-msg__bubble--user :deep(.m3-md span),
.m3-msg__bubble--user :deep(.m3-md-inline) {
    color: var(--m3-color-on-primary-container);
}

.m3-msg__bubble--user :deep(.m3-md strong),
.m3-msg__bubble--user :deep(.m3-md b) {
    color: var(--m3-color-on-primary-container);
    font-weight: var(--m3-type-emphasized-body-medium-weight);
}

.m3-msg__bubble--user :deep(.m3-md a) {
    color: var(--m3-color-on-primary-container);
    text-decoration-color: color-mix(in srgb, var(--m3-color-on-primary-container) 50%, transparent);
}

.m3-msg__bubble--user :deep(.m3-md code) {
    background: color-mix(in srgb, var(--m3-color-on-primary-container) 12%, transparent);
    color: var(--m3-color-on-primary-container);
}
</style>
