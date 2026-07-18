<?php
$roleCfg = [
    'admin'     => ['#fff0f0', '#800000'],
    'teacher'   => ['#dbeafe', '#1e40af'],
    'secretary' => ['#eff6ff', '#3730a3'],
    'adas'      => ['#f3f4f6', '#374151'],
];

if (! function_exists('chatInitials')) {
    function chatInitials(string $name): string
    {
        $parts = array_slice(explode(' ', trim($name)), 0, 2);

        return strtoupper(implode('', array_map(static fn ($w) => $w[0] ?? '', $parts))) ?: 'U';
    }
}

include APPPATH . 'Views/layout/header.php';
?>

<div class="page-header">
  <h4><i class="bi bi-chat-dots-fill me-2"></i>Chat</h4>
  <p>Direct messages and group chats</p>
</div>

<?php if ($canCreate): ?>
<div class="d-flex justify-content-end gap-2 mb-3">
  <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#newChatModal">
    <i class="bi bi-person-plus me-1"></i>New Chat
  </button>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newGroupModal">
    <i class="bi bi-people me-1"></i>New Group Chat
  </button>
</div>
<?php endif; ?>

<div class="card" style="height:calc(100vh - 210px);min-height:420px;overflow:hidden;">
  <div class="d-flex h-100" style="min-height:0;">

    <!-- Conversation list -->
    <div class="border-end d-flex flex-column" style="width:300px;flex-shrink:0;">
      <div class="p-3 border-bottom fw-semibold" style="font-size:.85rem;">
        Conversations
      </div>
      <div class="overflow-auto flex-grow-1" id="conversationList">
        <?php foreach ($conversations as $c): ?>
        <button type="button" class="chat-convo-item w-100 text-start border-0 bg-transparent d-flex gap-2 align-items-center"
                style="padding:.7rem 1rem;border-bottom:1px solid #f3f4f6;cursor:pointer;"
                data-id="<?= $c['id'] ?>" onclick="openConversation(<?= $c['id'] ?>)">
          <?php if ($c['type'] === 'direct' && ! empty($c['other_photo'])): ?>
          <img src="<?= e(base_url('uploads/avatars/' . $c['other_photo'])) ?>" alt=""
               style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;">
          <?php else: ?>
          <div style="width:38px;height:38px;border-radius:50%;background:<?= $c['type']==='group' ? '#fff0f0' : '#f3f4f6' ?>;color:<?= $c['type']==='group' ? '#800000' : '#374151' ?>;font-size:.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <?= $c['type'] === 'group' ? '<i class="bi bi-people-fill"></i>' : e(chatInitials($c['display_name'])) ?>
          </div>
          <?php endif; ?>
          <div class="flex-grow-1" style="min-width:0;">
            <div class="d-flex justify-content-between align-items-center gap-1">
              <span class="fw-semibold text-truncate" style="font-size:.82rem;"><?= e($c['display_name']) ?></span>
              <span class="text-muted flex-shrink-0" style="font-size:.65rem;"><?= date('M d', strtotime($c['last_time'])) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center gap-1">
              <span class="text-muted text-truncate" style="font-size:.75rem;">
                <?= $c['last_message'] !== null ? e(mb_strimwidth($c['last_message'], 0, 40, '…')) : 'No messages yet' ?>
              </span>
              <?php if ($c['unread'] > 0): ?>
              <span class="badge bg-danger flex-shrink-0" style="font-size:.62rem;"><?= $c['unread'] ?></span>
              <?php endif; ?>
            </div>
          </div>
        </button>
        <?php endforeach; ?>
        <?php if (empty($conversations)): ?>
        <p class="text-muted text-center small p-4 mb-0">
          No conversations yet.
          <?php if (! $canCreate): ?>Wait for the principal to start a chat with you.<?php endif; ?>
        </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Thread pane -->
    <div class="flex-grow-1 d-flex flex-column" style="min-width:0;">
      <div id="threadEmpty" class="d-flex align-items-center justify-content-center h-100 text-muted">
        <div class="text-center"><i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>Select a conversation to start chatting</div>
      </div>
      <div id="threadActive" class="d-none h-100 d-flex flex-column" style="min-height:0;">
        <div class="p-3 border-bottom fw-semibold" id="threadHeader" style="font-size:.88rem;"></div>
        <div class="flex-grow-1 overflow-auto p-3" id="threadMessages" style="background:#f9fafb;"></div>
        <form id="sendForm" class="p-2 border-top d-flex gap-2">
          <input type="text" id="messageInput" class="form-control form-control-sm" placeholder="Type a message..."
                 autocomplete="off" maxlength="2000" required>
          <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-send"></i></button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php if ($canCreate): ?>
<!-- New Chat Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-person-plus me-2"></i>Start a Chat</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('chat') ?>" class="ajax-form" data-confirm-title="Start this chat?">
        <input type="hidden" name="action" value="create_direct">
        <div class="modal-body" style="max-height:360px;overflow-y:auto;">
          <?php foreach ($users as $u): [$rbg, $rtc] = $roleCfg[$u['role']] ?? ['#f3f4f6', '#374151']; ?>
          <label class="d-flex align-items-center gap-2 p-2 rounded-3" style="cursor:pointer;">
            <input type="radio" name="user_id" value="<?= $u['id'] ?>" required>
            <div style="width:30px;height:30px;border-radius:50%;background:<?= $rbg ?>;color:<?= $rtc ?>;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <?= e(chatInitials($u['name'])) ?>
            </div>
            <div>
              <div class="fw-semibold" style="font-size:.82rem;"><?= e($u['name']) ?></div>
              <div class="text-muted" style="font-size:.7rem;"><?= e(ucfirst($u['role'])) ?></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Start Chat</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- New Group Modal -->
<div class="modal fade" id="newGroupModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header gradient">
        <h6 class="modal-title"><i class="bi bi-people me-2"></i>New Group Chat</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= base_url('chat') ?>" class="ajax-form" data-confirm-title="Create this group chat?">
        <input type="hidden" name="action" value="create_group">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Group Name</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Grade 7 Teachers">
          </div>
          <label class="form-label">Members</label>
          <div style="max-height:280px;overflow-y:auto;">
            <?php foreach ($users as $u): [$rbg, $rtc] = $roleCfg[$u['role']] ?? ['#f3f4f6', '#374151']; ?>
            <label class="d-flex align-items-center gap-2 p-2 rounded-3" style="cursor:pointer;">
              <input type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>">
              <div style="width:30px;height:30px;border-radius:50%;background:<?= $rbg ?>;color:<?= $rtc ?>;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?= e(chatInitials($u['name'])) ?>
              </div>
              <div>
                <div class="fw-semibold" style="font-size:.82rem;"><?= e($u['name']) ?></div>
                <div class="text-muted" style="font-size:.7rem;"><?= e(ucfirst($u['role'])) ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Group</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$extraScript = "<script>
const CHAT_BASE = '" . base_url('chat/') . "';
let activeConversationId = null;
let lastMessageId = 0;
let pollTimer = null;

function chatEscapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function chatInitialsJs(name) {
    return (name || 'U').trim().split(' ').slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || 'U';
}

function renderMessages(messages, append) {
    const container = document.getElementById('threadMessages');
    if (!append) container.innerHTML = '';

    messages.forEach(m => {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex mb-3 ' + (m.is_me ? 'justify-content-end' : 'justify-content-start');

        const bubble = document.createElement('div');
        bubble.style.cssText = 'max-width:70%;';

        const bubbleInner = document.createElement('div');
        bubbleInner.style.cssText = m.is_me
            ? 'background:var(--primary);color:#fff;border-radius:14px 14px 2px 14px;padding:.55rem .85rem;font-size:.85rem;'
            : 'background:#fff;color:#111;border:1px solid #e5e7eb;border-radius:14px 14px 14px 2px;padding:.55rem .85rem;font-size:.85rem;';
        bubbleInner.textContent = m.body;

        const meta = document.createElement('div');
        meta.style.cssText = 'font-size:.65rem;color:#9ca3af;margin-top:2px;' + (m.is_me ? 'text-align:right;' : 'text-align:left;');
        meta.textContent = (m.is_me ? '' : m.sender_name + ' · ') + m.time;

        bubble.appendChild(bubbleInner);
        bubble.appendChild(meta);
        wrap.appendChild(bubble);
        container.appendChild(wrap);

        lastMessageId = Math.max(lastMessageId, m.id);
    });

    if (messages.length) {
        container.scrollTop = container.scrollHeight;
    }
}

function openConversation(id) {
    activeConversationId = id;
    lastMessageId = 0;

    document.querySelectorAll('.chat-convo-item').forEach(el => {
        el.style.background = String(el.dataset.id) === String(id) ? '#fff0f0' : '';
    });

    const item = document.querySelector('.chat-convo-item[data-id=\"' + id + '\"]');
    const headerText = item ? item.querySelector('.fw-semibold').textContent : 'Conversation';
    document.getElementById('threadHeader').textContent = headerText;

    const badge = item ? item.querySelector('.badge') : null;
    if (badge) badge.remove();

    document.getElementById('threadEmpty').classList.add('d-none');
    document.getElementById('threadActive').classList.remove('d-none');
    document.getElementById('threadMessages').innerHTML = '<p class=\"text-muted text-center small mt-3\">Loading…</p>';

    fetchMessages(true);

    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => fetchMessages(false), 4000);
}

function fetchMessages(isInitialLoad) {
    if (!activeConversationId) return;

    fetch(CHAT_BASE + activeConversationId + '/messages?after=' + (isInitialLoad ? 0 : lastMessageId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;
            if (isInitialLoad) {
                if (data.messages.length === 0) {
                    document.getElementById('threadMessages').innerHTML = '<p class=\"text-muted text-center small mt-3\">No messages yet. Say hello!</p>';
                } else {
                    renderMessages(data.messages, false);
                }
            } else if (data.messages.length > 0) {
                if (document.getElementById('threadMessages').querySelector('.text-muted.text-center')) {
                    document.getElementById('threadMessages').innerHTML = '';
                }
                renderMessages(data.messages, true);
            }
        })
        .catch(() => {});
}

document.getElementById('sendForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!activeConversationId) return;

    const input = document.getElementById('messageInput');
    const body = input.value.trim();
    if (!body) return;

    input.disabled = true;

    fetch(CHAT_BASE + activeConversationId + '/send', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'body=' + encodeURIComponent(body),
    })
        .then(res => res.json())
        .then(data => {
            input.disabled = false;
            if (data.status === 'success') {
                input.value = '';
                fetchMessages(false);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not send message.' });
            }
        })
        .catch(() => {
            input.disabled = false;
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
        });
});
</script>";
include APPPATH . 'Views/layout/footer.php';
?>
