# Desktop Chat Popup Parity & M3 Expressive Redesign

## 1. Overview & Context

Bendaharaku features a desktop floating action button (FAB) located in the lower-right corner of the screen when viewing dashboard or financial management pages. Clicking this FAB opens a side panel popup (`M3ChatSheet.vue`). 

Currently, this popup has several functional regressions and missing capabilities compared to the full chat page (`Material/Pages/Chat/Index.vue`), as well as styling shortcomings against the Material 3 Expressive design language:
- **No Jump to Latest / Auto-Scroll**: The "Terbaru" jump button is absent. Opening the popup starts at the top (scroll 0) instead of scrolling to the most recent message at the bottom.
- **Incomplete History & State Sync**: `conversationId` is not stored from the history API, `hasMore` is ignored, and pending bot messages are not resumed (`resumePending()`). Background toasts fire redundantly because the chat surface flag is not toggled.
- **Unclickable Command Button**: The `/` command button does nothing because `@open-commands` is unhandled and `M3ChatCommandSheet` is not loaded.
- **Missing Interaction Features**: Receipt upload (`M3ChatUploadSheet`), empty state recommendations (`M3ChatEmptyState`), and bubble interactions (`@suggest`, `@regenerate`, `@retry`, `@retryEvidence`) are missing or unlinked.
- **M3 Expressive Compliance**: The popup is a hard-edged 0px rectangle without inner border radii, lacks a full-page expansion shortcut, has no dynamic typing indicator in the header, and lacks an outside-click dismiss interaction for non-modal presentation.

This specification details the comprehensive fix and M3 Expressive enhancement for the desktop chat popup.

---

## 2. Architecture & Data Flow

### 2.1 DOM Scroll Linking & Auto-Scroll
1. In `M3ChatSheet.vue`, define a template ref `chatAreaComp` on `<M3ChatArea ref="chatAreaComp">`.
2. Extract `chatAreaRef` from `useChat()`.
3. In `loadLatest()` and upon the sheet opening:
   - Wait for `nextTick()` after `messages.value` is populated.
   - Assign `chatAreaRef.value = chatAreaComp.value?.el`.
   - Call `scrollToBottom(false, true)` immediately so the viewport renders at the bottom bubble without delay.
4. When `sendMessage`, `sendEvidenceMessage`, or new bot responses arrive, `scrollToBottom(true)` smoothly scrolls the container down.

### 2.2 History Synchronization & Pending Polling
1. Fetch latest history using `GET /chat/history` with `{ limit: 30 }`.
2. Update state from the API response:
   - `conversationId.value = data.conversation_id`
   - `hasMore.value = data.has_more ?? false`
   - `messages.value = (data.messages ?? []).map(normalizeMessage)`
3. Call `resumePending()` to continue polling any assistant messages that are still `pending` or `processing`.
4. Manage surface presence via `useChatPending`:
   - Call `setChatSurfaceOpen(true)` when `show` becomes `true`.
   - Call `setChatSurfaceOpen(false)` when `show` becomes `false` or on unmount.

---

## 3. Component & Feature Parity

### 3.1 Jump-to-Latest Button (`M3JumpButton`)
- Render `<M3JumpButton :show="showJumpBtn" :unread-count="unreadCount" :bottom="fabStyle.bottom" @click="jumpToLatest" />`.
- Track composer element height dynamically via `ResizeObserver` on `composerRef` to adjust `:bottom="composerHeight + 16px"` so the button floats accurately above multi-line text or receipt previews.

### 3.2 Command Palette (`M3ChatCommandSheet`)
- Import `useChatCommands` and register commands from `page.props.commands ?? []` (or fetch from `GET /chat/commands` if empty).
- Listen to `@open-commands` on `<M3ChatComposer>` to set `isCommandSheetOpen = true`.
- On `@select(commandText)`: close command sheet and dispatch `sendMessage(commandText)`.

### 3.3 Receipt Upload & Evidence Handling (`M3ChatUploadSheet`)
- Import `useEvidenceUpload` and mount `<M3ChatUploadSheet>`.
- Listen to `@open-upload` on `<M3ChatComposer>` to open the upload sheet.
- Bind `attachmentPreview`, `attachmentName`, and `isUploading` props to `<M3ChatComposer>`.
- Handle `@camera="handleFileSelected"` and `@remove-attachment="resetUpload"`.
- In `handleSend(text)`: if an attachment exists, upload the file and dispatch `sendEvidenceMessage(uuid, previewUrl, text)`.

### 3.4 Empty State & Message Interactions
- Render `<M3ChatEmptyState>` in `<M3ChatArea>`'s default slot when `messages.length === 0 && !isLoading`.
- Handle message events:
  - `@suggest="handleSuggestionSelect"`: inserts text into composer and focuses textarea.
  - `@regenerate="handleRegenerate"`: regenerates the last assistant response.
  - `@retry="retryLastMessage"`: retries the failed user prompt.
  - `@retryEvidence="handleRetryEvidence"`: retries failed receipt OCR processing.

---

## 4. Material 3 Expressive UI/UX Design

### 4.1 Non-Modal Side Sheet Container
- **Geometry & Shape**:
  - Attached to the right edge: `top: 0; right: 0; bottom: 0; position: fixed; z-index: 70;`.
  - Inner left corners: `border-top-left-radius: var(--m3-radius-xl)` (28dp) and `border-bottom-left-radius: var(--m3-radius-xl)` (28dp).
  - Border: `1px solid var(--m3-color-outline-variant)` on the left edge.
  - Shadow: `var(--m3-elevation-3)`.
  - Max width: `min(28rem, calc(100vw - var(--m3-size-rail) - var(--m3-space-8)))`.
- **Non-Modal Interaction**:
  - No opaque or dark scrim overlay, allowing users to reference background figures.
  - Click-outside listener: clicking outside the `.m3-chat-sheet` container emits `close`.
  - Keyboard listener: `Escape` key emits `close`.

### 4.2 Header Enhancement
- Retain bot avatar with circular clipping (`--m3-radius-full`).
- Title: `botProfile.name` in `title-medium` emphasized weight.
- Dynamic subtitle:
  - If `isTyping` is true: shows *"Mengetik..."* in `var(--m3-color-primary)`.
  - Otherwise: shows `t('chat.assistant')` in `var(--m3-color-on-surface-variant)`.
- Actions row:
  - **Open Full Page**: `Link` to `route('chat.index')` with tooltip/aria-label *"Buka Halaman Penuh"*.
  - **Close Button**: `M3IconButton` with close icon (`M6 18L18 6...`).

### 4.3 Floating Action Button (FAB) Tooltip
- Add native or M3 tooltip (`title="Tanya Asisten AI"`) to `.m3-chat-bubble` in `AuthenticatedLayout.vue`.

---

## 5. Implementation Verification Plan

1. **Auto-Scroll & Jump Button**:
   - Open popup: verify it scrolls to bottom immediately.
   - Scroll up > 200px: verify `M3JumpButton` appears with "Terbaru" or unread counter.
   - Click "Terbaru": verify it scrolls down smoothly.
2. **Commands**:
   - Click command icon in composer: verify `M3ChatCommandSheet` opens.
   - Select a command (e.g. `/saldo`): verify it executes or populates composer.
3. **Evidence Upload**:
   - Click attachment icon: verify `M3ChatUploadSheet` opens and preview shows in composer.
4. **History & State**:
   - Verify `conversationId` is maintained across messages.
   - Verify pending messages continue polling.
5. **M3 Non-Modal Experience**:
   - Verify left corners are rounded at 28dp.
   - Verify clicking outside the sheet closes the popup.
   - Click "Open Full Page" link and verify smooth transition to `/chat`.
