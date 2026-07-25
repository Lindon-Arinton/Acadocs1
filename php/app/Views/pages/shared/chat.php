<?php
$roleCfg = [
    'admin'   => ['#fff0f0', '#800000'],
    'teacher' => ['#dbeafe', '#1e40af'],
    'adas'    => ['#f3f4f6', '#374151'],
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
<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newGroupModal">
    <i class="bi bi-people me-1"></i>New Group Chat
  </button>
</div>
<?php endif; ?>

<div class="card" style="height:calc(100vh - 210px);min-height:420px;overflow:hidden;">
  <div class="d-flex h-100" style="min-height:0;">

    <!-- Left panel -->
    <div class="border-end d-flex flex-column" style="width:300px;flex-shrink:0;">
      <div class="p-2 border-bottom">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="chatSearchInput" class="form-control border-start-0 ps-0"
                 placeholder="Search people or chats..." autocomplete="off">
        </div>
        <?php if ($canCreate): ?>
        <div class="d-flex gap-1 flex-wrap mt-2" id="chatRoleFilter">
          <button type="button" class="chat-filter-pill active" data-role="all">All</button>
          <button type="button" class="chat-filter-pill" data-role="admin">Admin</button>
          <button type="button" class="chat-filter-pill" data-role="teacher">Teacher</button>
          <button type="button" class="chat-filter-pill" data-role="adas">ADAS</button>
        </div>
        <?php endif; ?>
      </div>

      <div class="overflow-auto flex-grow-1" id="chatListScroll">
        <?php if (! empty($conversations)): ?>
        <div class="chat-section-label" id="conversationsLabel">Conversations</div>
        <div id="conversationList">
          <?php foreach ($conversations as $c): ?>
          <?php $otherIsAdmin = $c['type'] === 'direct' && ($c['other_role'] ?? null) === 'admin'; ?>
          <button type="button" class="chat-convo-item w-100 text-start border-0 bg-transparent align-items-center"
                  style="display:flex;gap:.5rem;padding:.7rem 1rem;border-bottom:1px solid #f3f4f6;cursor:pointer;"
                  data-id="<?= $c['id'] ?>" data-search="<?= e(mb_strtolower($c['display_name'])) ?>"
                  data-photo="<?= $c['other_photo'] ? e(base_url('uploads/avatars/' . $c['other_photo'])) : '' ?>"
                  data-other-role="<?= e($c['other_role'] ?? '') ?>"
                  onclick="openConversation(<?= $c['id'] ?>)">
            <?php if ($c['type'] === 'direct' && ! empty($c['other_photo'])): ?>
            <img src="<?= e(base_url('uploads/avatars/' . $c['other_photo'])) ?>" alt=""
                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;<?= $otherIsAdmin ? 'box-shadow:0 0 0 2px #800000;' : '' ?>">
            <?php else: ?>
            <div style="width:38px;height:38px;border-radius:50%;background:<?= $c['type']==='group' ? '#fff0f0' : ($otherIsAdmin ? '#fff0f0' : '#f3f4f6') ?>;color:<?= $c['type']==='group' ? '#800000' : ($otherIsAdmin ? '#800000' : '#374151') ?>;font-size:.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <?= $c['type'] === 'group' ? '<i class="bi bi-people-fill"></i>' : e(chatInitials($c['display_name'])) ?>
            </div>
            <?php endif; ?>
            <div class="flex-grow-1" style="min-width:0;">
              <div class="d-flex justify-content-between align-items-center gap-1">
                <span class="fw-semibold text-truncate" style="font-size:.82rem;<?= $otherIsAdmin ? 'color:#800000;' : '' ?>">
                  <?= e($c['display_name']) ?><?= $otherIsAdmin ? ' <span style="font-weight:600;">(Principal)</span>' : '' ?>
                </span>
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
        </div>
        <?php endif; ?>

        <?php if ($canCreate): ?>
        <div class="chat-section-label" id="allUsersLabel">All Users</div>
        <div id="allUsersList">
          <?php foreach ($users as $u): [$rbg, $rtc] = $roleCfg[$u['role']] ?? ['#f3f4f6', '#374151']; ?>
          <button type="button" class="chat-user-item w-100 text-start border-0 bg-transparent align-items-center"
                  style="display:flex;gap:.5rem;padding:.6rem 1rem;border-bottom:1px solid #f3f4f6;cursor:pointer;"
                  data-role="<?= e($u['role']) ?>" data-search="<?= e(mb_strtolower($u['name'])) ?>"
                  onclick="startDirectChat(<?= $u['id'] ?>, this)">
            <?php if (! empty($u['photo'])): ?>
            <img src="<?= e(base_url('uploads/avatars/' . $u['photo'])) ?>" alt=""
                 style="width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <?php else: ?>
            <div style="width:34px;height:34px;border-radius:50%;background:<?= $rbg ?>;color:<?= $rtc ?>;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <?= e(chatInitials($u['name'])) ?>
            </div>
            <?php endif; ?>
            <div class="flex-grow-1" style="min-width:0;">
              <div class="fw-semibold text-truncate" style="font-size:.8rem;"><?= e($u['name']) ?></div>
              <div class="text-muted" style="font-size:.68rem;"><?= e(ucfirst($u['role'])) ?></div>
            </div>
          </button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <p id="chatNoResults" class="text-muted text-center small p-4 mb-0 d-none">No matches found.</p>

        <?php if (empty($conversations) && ! $canCreate): ?>
        <p class="text-muted text-center small p-4 mb-0">No conversations yet. Wait for the principal to start a chat with you.</p>
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
        <div id="attachPreview" class="d-none px-2 pt-2">
          <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-2" style="font-size:.72rem;font-weight:500;padding:.4rem .6rem;">
            <i class="bi bi-paperclip"></i>
            <span id="attachPreviewName"></span>
            <button type="button" onclick="clearAttachment()" style="background:none;border:0;padding:0;line-height:1;color:#6b7280;font-size:.9rem;">&times;</button>
          </span>
        </div>
        <form id="sendForm" class="p-2 border-top d-flex gap-2 align-items-center">
          <input type="file" id="attachInput" class="d-none"
                 accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar">
          <button type="button" class="btn btn-outline-secondary btn-sm" title="Attach photo or file"
                  onclick="document.getElementById('attachInput').click()">
            <i class="bi bi-paperclip"></i>
          </button>
          <input type="text" id="messageInput" class="form-control form-control-sm" placeholder="Type a message..."
                 autocomplete="off" maxlength="2000">
          <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-send"></i></button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php if ($canCreate): ?>
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

          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0">Members</label>
            <label class="d-flex align-items-center gap-1 mb-0" style="font-size:.75rem;cursor:pointer;">
              <input type="checkbox" id="groupSelectAll"> Select All
            </label>
          </div>

          <div class="input-group input-group-sm mb-2">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="groupSearchInput" class="form-control border-start-0 ps-0"
                   placeholder="Search members..." autocomplete="off">
          </div>
          <div class="d-flex gap-1 flex-wrap mb-2" id="groupRoleFilter">
            <button type="button" class="chat-filter-pill active" data-role="all">All</button>
            <button type="button" class="chat-filter-pill" data-role="admin">Admin</button>
            <button type="button" class="chat-filter-pill" data-role="teacher">Teacher</button>
              <button type="button" class="chat-filter-pill" data-role="adas">ADAS</button>
          </div>

          <div style="max-height:260px;overflow-y:auto;" id="groupMemberList">
            <?php foreach ($users as $u): [$rbg, $rtc] = $roleCfg[$u['role']] ?? ['#f3f4f6', '#374151']; ?>
            <label class="align-items-center gap-2 p-2 rounded-3 group-member-row" style="display:flex;cursor:pointer;"
                   data-role="<?= e($u['role']) ?>" data-search="<?= e(mb_strtolower($u['name'])) ?>">
              <input type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" class="group-member-checkbox">
              <div style="width:30px;height:30px;border-radius:50%;background:<?= $rbg ?>;color:<?= $rtc ?>;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?= e(chatInitials($u['name'])) ?>
              </div>
              <div>
                <div class="fw-semibold" style="font-size:.82rem;"><?= e($u['name']) ?></div>
                <div class="text-muted" style="font-size:.7rem;"><?= e(ucfirst($u['role'])) ?></div>
              </div>
            </label>
            <?php endforeach; ?>
            <p class="text-muted text-center small p-3 mb-0 d-none" id="groupNoResults">No matches found.</p>
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
const CHAT_INDEX_URL = '" . base_url('chat') . "';
let activeConversationId = null;
let lastMessageId = 0;
let pollTimer = null;

const CHAT_ROLE_COLORS = {
    admin:   ['#fff0f0', '#800000'],
    teacher: ['#dbeafe', '#1e40af'],
    adas:    ['#f3f4f6', '#374151'],
};

function chatEscapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function chatInitialsJs(name) {
    return (name || 'U').trim().split(' ').slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || 'U';
}

function buildAttachmentEl(m) {
    if (m.attachment_is_image) {
        const link = document.createElement('a');
        link.href = m.attachment_url;
        link.target = '_blank';
        link.rel = 'noopener';
        const img = document.createElement('img');
        img.src = m.attachment_url;
        img.alt = m.attachment_name || 'Image';
        img.style.cssText = 'max-width:220px;max-height:220px;border-radius:10px;display:block;margin-top:' + (m.body ? '.4rem' : '0') + ';';
        link.appendChild(img);
        return link;
    }

    const link = document.createElement('a');
    link.href = m.attachment_url;
    link.target = '_blank';
    link.rel = 'noopener';
    link.style.cssText = 'display:flex;align-items:center;gap:.4rem;margin-top:' + (m.body ? '.4rem' : '0') + ';padding:.4rem .6rem;border-radius:8px;background:rgba(0,0,0,.05);text-decoration:none;color:inherit;font-size:.78rem;';
    link.innerHTML = '<i class=\"bi bi-file-earmark-arrow-down\"></i>';
    link.appendChild(document.createTextNode(m.attachment_name || 'Download file'));
    return link;
}

function renderMessages(messages, append) {
    const container = document.getElementById('threadMessages');
    if (!append) container.innerHTML = '';

    messages.forEach(m => {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex mb-3 gap-2 ' + (m.is_me ? 'justify-content-end' : 'justify-content-start');

        if (!m.is_me) {
            const avatar = document.createElement('div');
            avatar.style.cssText = 'width:30px;height:30px;border-radius:50%;flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;margin-top:2px;';
            if (m.sender_photo) {
                avatar.innerHTML = '<img src=\"' + m.sender_photo + '\" style=\"width:100%;height:100%;object-fit:cover;\">';
            } else {
                const colors = CHAT_ROLE_COLORS[m.sender_role] || ['#f3f4f6', '#374151'];
                avatar.style.background = colors[0];
                avatar.style.color = colors[1];
                avatar.textContent = chatInitialsJs(m.sender_name);
            }
            wrap.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.style.cssText = 'max-width:65%;';

        if (m.body) {
            const bubbleInner = document.createElement('div');
            bubbleInner.style.cssText = m.is_me
                ? 'background:var(--primary);color:#fff;border-radius:14px 14px 2px 14px;padding:.55rem .85rem;font-size:.85rem;'
                : 'background:#fff;color:#111;border:1px solid #e5e7eb;border-radius:14px 14px 14px 2px;padding:.55rem .85rem;font-size:.85rem;';
            bubbleInner.textContent = m.body;
            if (m.attachment_url) {
                bubbleInner.appendChild(buildAttachmentEl(m));
            }
            bubble.appendChild(bubbleInner);
        } else if (m.attachment_url) {
            const bubbleInner = document.createElement('div');
            bubbleInner.style.cssText = m.is_me
                ? 'background:var(--primary);color:#fff;border-radius:14px 14px 2px 14px;padding:.5rem;'
                : 'background:#fff;color:#111;border:1px solid #e5e7eb;border-radius:14px 14px 14px 2px;padding:.5rem;';
            bubbleInner.appendChild(buildAttachmentEl(m));
            bubble.appendChild(bubbleInner);
        }

        const meta = document.createElement('div');
        meta.style.cssText = 'font-size:.65rem;color:#9ca3af;margin-top:2px;' + (m.is_me ? 'text-align:right;' : 'text-align:left;');

        if (m.is_me) {
            meta.textContent = m.time;
        } else {
            const nameSpan = document.createElement('span');
            if (m.sender_role === 'admin') {
                nameSpan.style.color = '#800000';
                nameSpan.style.fontWeight = '700';
                nameSpan.textContent = m.sender_name + ' (Principal)';
            } else {
                nameSpan.textContent = m.sender_name;
            }
            meta.appendChild(nameSpan);
            meta.appendChild(document.createTextNode(' · ' + m.time));
        }

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
    if (typeof clearAttachment === 'function') clearAttachment();

    document.querySelectorAll('.chat-convo-item').forEach(el => {
        el.style.background = String(el.dataset.id) === String(id) ? '#fff0f0' : '';
    });

    const item = document.querySelector('.chat-convo-item[data-id=\"' + id + '\"]');
    const name = item ? item.querySelector('.fw-semibold').textContent.replace(/\s*\(Principal\)\s*$/, '').trim() : 'Conversation';
    const isAdmin = item ? item.dataset.otherRole === 'admin' : false;
    const photo = item ? item.dataset.photo : '';

    const header = document.getElementById('threadHeader');
    header.innerHTML = '';
    header.style.cssText = 'display:flex;align-items:center;gap:.6rem;';

    const avatar = document.createElement('div');
    avatar.style.cssText = 'width:32px;height:32px;border-radius:50%;flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;';
    if (photo) {
        avatar.innerHTML = '<img src=\"' + photo + '\" style=\"width:100%;height:100%;object-fit:cover;\">';
    } else {
        const colors = CHAT_ROLE_COLORS[item ? item.dataset.otherRole : ''] || ['#f3f4f6', '#374151'];
        avatar.style.background = colors[0];
        avatar.style.color = colors[1];
        avatar.textContent = chatInitialsJs(name);
    }

    const nameSpan = document.createElement('span');
    if (isAdmin) {
        nameSpan.style.color = '#800000';
        nameSpan.textContent = name + ' (Principal)';
    } else {
        nameSpan.textContent = name;
    }

    header.appendChild(avatar);
    header.appendChild(nameSpan);

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

let selectedAttachment = null;

document.getElementById('attachInput')?.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        Swal.fire({ icon: 'error', title: 'File too large', text: 'Please choose a file under 10MB.' });
        this.value = '';
        return;
    }

    selectedAttachment = file;
    document.getElementById('attachPreviewName').textContent = file.name;
    document.getElementById('attachPreview').classList.remove('d-none');
});

function clearAttachment() {
    selectedAttachment = null;
    document.getElementById('attachInput').value = '';
    document.getElementById('attachPreview').classList.add('d-none');
}

document.getElementById('sendForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!activeConversationId) return;

    const input = document.getElementById('messageInput');
    const body = input.value.trim();
    if (!body && !selectedAttachment) return;

    input.disabled = true;

    const formData = new FormData();
    formData.append('body', body);
    if (selectedAttachment) {
        formData.append('file', selectedAttachment);
    }

    fetch(CHAT_BASE + activeConversationId + '/send', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            input.disabled = false;
            if (data.status === 'success') {
                input.value = '';
                clearAttachment();
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

/* ── Start a direct chat by clicking a user in the All Users list ── */
function startDirectChat(userId, btnEl) {
    if (btnEl) btnEl.style.opacity = '0.55';

    fetch(CHAT_INDEX_URL, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=create_direct&user_id=' + encodeURIComponent(userId),
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.conversation_id) {
                window.location.href = CHAT_INDEX_URL + '?open=' + data.conversation_id;
            } else {
                if (btnEl) btnEl.style.opacity = '';
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not start chat.' });
            }
        })
        .catch(() => {
            if (btnEl) btnEl.style.opacity = '';
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
        });
}

/* ── Left panel: search + role filter ── */
function toggleSectionLabel(labelId, selector) {
    const label = document.getElementById(labelId);
    if (!label) return;
    const visible = [...document.querySelectorAll(selector)].some(el => el.style.display !== 'none');
    label.style.display = visible ? '' : 'none';
}

function applyChatFilter() {
    const q = (document.getElementById('chatSearchInput')?.value || '').trim().toLowerCase();
    const activeRoleBtn = document.querySelector('#chatRoleFilter .chat-filter-pill.active');
    const role = activeRoleBtn ? activeRoleBtn.dataset.role : 'all';
    let anyVisible = false;

    document.querySelectorAll('#conversationList .chat-convo-item').forEach(el => {
        const match = !q || el.dataset.search.includes(q);
        el.style.display = match ? 'flex' : 'none';
        if (match) anyVisible = true;
    });

    document.querySelectorAll('#allUsersList .chat-user-item').forEach(el => {
        const matchSearch = !q || el.dataset.search.includes(q);
        const matchRole = role === 'all' || el.dataset.role === role;
        const match = matchSearch && matchRole;
        el.style.display = match ? 'flex' : 'none';
        if (match) anyVisible = true;
    });

    toggleSectionLabel('conversationsLabel', '#conversationList .chat-convo-item');
    toggleSectionLabel('allUsersLabel', '#allUsersList .chat-user-item');

    document.getElementById('chatNoResults')?.classList.toggle('d-none', anyVisible);
}

document.getElementById('chatSearchInput')?.addEventListener('input', applyChatFilter);
document.querySelectorAll('#chatRoleFilter .chat-filter-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#chatRoleFilter .chat-filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        applyChatFilter();
    });
});

/* ── New Group modal: search + role filter + select-all ── */
function syncGroupSelectAllState() {
    const selectAll = document.getElementById('groupSelectAll');
    if (!selectAll) return;
    const visibleBoxes = [...document.querySelectorAll('.group-member-row')]
        .filter(row => row.style.display !== 'none')
        .map(row => row.querySelector('.group-member-checkbox'));
    selectAll.checked = visibleBoxes.length > 0 && visibleBoxes.every(cb => cb.checked);
}

function applyGroupFilter() {
    const q = (document.getElementById('groupSearchInput')?.value || '').trim().toLowerCase();
    const activeRoleBtn = document.querySelector('#groupRoleFilter .chat-filter-pill.active');
    const role = activeRoleBtn ? activeRoleBtn.dataset.role : 'all';
    let anyVisible = false;

    document.querySelectorAll('.group-member-row').forEach(row => {
        const matchSearch = !q || row.dataset.search.includes(q);
        const matchRole = role === 'all' || row.dataset.role === role;
        const match = matchSearch && matchRole;
        row.style.display = match ? 'flex' : 'none';
        if (match) anyVisible = true;
    });

    document.getElementById('groupNoResults')?.classList.toggle('d-none', anyVisible);
    syncGroupSelectAllState();
}

document.getElementById('groupSearchInput')?.addEventListener('input', applyGroupFilter);
document.querySelectorAll('#groupRoleFilter .chat-filter-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#groupRoleFilter .chat-filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        applyGroupFilter();
    });
});
document.getElementById('groupSelectAll')?.addEventListener('change', function () {
    const checked = this.checked;
    document.querySelectorAll('.group-member-row').forEach(row => {
        if (row.style.display !== 'none') {
            row.querySelector('.group-member-checkbox').checked = checked;
        }
    });
});
document.querySelectorAll('.group-member-checkbox').forEach(cb => {
    cb.addEventListener('change', syncGroupSelectAllState);
});

" . ($openId ? "document.addEventListener('DOMContentLoaded', () => openConversation({$openId}));" : '') . "
</script>";
include APPPATH . 'Views/layout/footer.php';
?>
