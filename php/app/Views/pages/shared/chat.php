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

<?php if ($canCreateGroup): ?>
<div class="d-flex justify-content-end mb-2">
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newGroupModal">
    <i class="bi bi-people me-1"></i>New Group Chat
  </button>
</div>
<?php endif; ?>

<div class="card chat-card" style="height:calc(100vh - 130px);min-height:420px;overflow:hidden;">
  <div class="d-flex h-100 chat-shell" id="chatShell" style="min-height:0;">

    <!-- Left panel -->
    <div class="border-end d-flex flex-column chat-left-panel" style="width:300px;flex-shrink:0;">
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
          <div class="chat-convo-item position-relative"
                  style="border-bottom:1px solid var(--border);"
                  data-id="<?= $c['id'] ?>" data-search="<?= e(mb_strtolower($c['display_name'])) ?>"
                  data-photo="<?= $c['other_photo'] ? e(base_url('uploads/avatars/' . $c['other_photo'])) : '' ?>"
                  data-other-role="<?= e($c['other_role'] ?? '') ?>"
                  data-other-id="<?= (int) ($c['other_id'] ?? 0) ?>"
                  data-type="<?= e($c['type']) ?>"
                  data-member-count="<?= (int) $c['member_count'] ?>"
                  data-muted="<?= $c['muted'] ? '1' : '0' ?>">
            <div class="w-100 text-start align-items-center" role="button" tabindex="0"
                  style="display:flex;gap:.5rem;padding:.7rem 2.4rem .7rem 1rem;cursor:pointer;"
                  onclick="openConversation(<?= $c['id'] ?>)">
            <?php if ($c['type'] === 'direct' && ! empty($c['other_photo'])): ?>
            <img src="<?= e(base_url('uploads/avatars/' . $c['other_photo'])) ?>" alt=""
                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;<?= $otherIsAdmin ? 'box-shadow:0 0 0 2px #800000;' : '' ?>">
            <?php else: ?>
            <div style="width:38px;height:38px;border-radius:50%;background:<?= $c['type']==='group' ? '#fff0f0' : ($otherIsAdmin ? '#fff0f0' : 'var(--surface-hover)') ?>;color:<?= $c['type']==='group' ? '#800000' : ($otherIsAdmin ? '#800000' : 'var(--text-secondary)') ?>;font-size:.75rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <?= $c['type'] === 'group' ? '<i class="bi bi-people-fill"></i>' : e(chatInitials($c['display_name'])) ?>
            </div>
            <?php endif; ?>
            <div class="flex-grow-1" style="min-width:0;">
              <div class="d-flex justify-content-between align-items-center gap-1">
                <span class="fw-semibold text-truncate" style="font-size:.82rem;color:var(--text);">
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
            </div>
            <button type="button" class="chat-convo-delete-btn" title="<?= $c['type'] === 'group' ? 'Leave group' : 'Delete chat' ?>"
                    onclick="event.stopPropagation(); deleteConversationFromList(<?= $c['id'] ?>, '<?= $c['type'] === 'group' ? 'group' : 'direct' ?>');">
              <i class="bi bi-trash3"></i>
            </button>
          </div>
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
    <div class="flex-grow-1 d-flex flex-column chat-thread-pane" style="min-width:0;">
      <div id="threadEmpty" class="d-flex align-items-center justify-content-center h-100 text-muted">
        <div class="text-center"><i class="bi bi-chat-square-text fs-1 d-block mb-2"></i>Select a conversation to start chatting</div>
      </div>
      <div id="threadActive" class="d-none h-100 d-flex flex-column" style="min-height:0;">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2" style="min-width:0;">
            <button type="button" class="btn btn-sm btn-ghost chat-mobile-back-btn" onclick="closeConversationMobile()" title="Back to chats">
              <i class="bi bi-arrow-left"></i>
            </button>
            <div id="threadHeader" class="fw-semibold" style="font-size:.88rem;"></div>
          </div>
          <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-ghost" onclick="openInfoPanel()" title="Conversation info">
              <i class="bi bi-info-circle"></i>
            </button>
            <div class="dropdown">
              <button class="btn btn-sm btn-ghost" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Conversation options">
                <i class="bi bi-three-dots-vertical"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li id="viewMembersItem" class="d-none"><a class="dropdown-item" href="#" onclick="openInfoPanel();return false;"><i class="bi bi-people me-2"></i>View Members</a></li>
                <li><a class="dropdown-item" href="#" id="muteMenuLink" onclick="toggleMute();return false;"><i class="bi bi-bell-slash me-2"></i><span id="muteMenuLabel">Mute Notifications</span></a></li>
                <li><hr class="dropdown-divider"><a class="dropdown-item text-danger" href="#" onclick="confirmLeaveGroup();return false;"><i class="bi bi-box-arrow-right me-2" id="leaveGroupIcon"></i><span id="leaveGroupLabel">Delete Chat</span></a></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="flex-grow-1 overflow-auto p-3" id="threadMessages" style="background:var(--bg);"></div>
        <div id="typingIndicator" class="px-3 pb-1 text-muted d-none" style="font-size:.72rem;"></div>
        <div id="replyBanner" class="d-none px-2 pt-2">
          <div class="d-flex align-items-center justify-content-between chat-reply-banner">
            <div style="font-size:.75rem;min-width:0;overflow:hidden;">
              <div class="fw-semibold text-truncate" id="replyBannerName" style="color:var(--primary);"></div>
              <div class="text-muted text-truncate" id="replyBannerText"></div>
            </div>
            <button type="button" onclick="cancelReply()" class="chat-icon-btn" title="Cancel reply">&times;</button>
          </div>
        </div>
        <div id="attachPreview" class="d-none px-2 pt-2">
          <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-2" style="font-size:.72rem;font-weight:500;padding:.4rem .6rem;">
            <i class="bi bi-paperclip"></i>
            <span id="attachPreviewName"></span>
            <button type="button" onclick="clearAttachment()" style="background:none;border:0;padding:0;line-height:1;color:var(--muted);font-size:.9rem;">&times;</button>
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

    <!-- Chat info panel: slides in on the right (Messenger's "chat details"
         layout) instead of a centered modal — conversation avatar, mute
         toggle, member list (admins can add/remove), leave/delete action. -->
    <div class="chat-info-panel d-none" id="chatInfoPanel">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <span class="fw-semibold" style="font-size:.85rem;">Chat Info</span>
        <button type="button" class="chat-icon-btn" onclick="closeInfoPanel()" title="Close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="overflow-auto flex-grow-1">
        <div class="p-3 border-bottom text-center" id="chatInfoHeader"></div>

        <div class="p-2 border-bottom">
          <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start" onclick="toggleMute()">
            <i class="bi bi-bell-slash me-2"></i><span id="panelMuteLabel">Mute Notifications</span>
          </button>
        </div>

        <div id="panelMembersSection" class="border-bottom d-none">
          <button type="button" class="w-100 text-start p-2 fw-semibold d-flex justify-content-between align-items-center bg-transparent border-0"
                  style="font-size:.8rem;" onclick="togglePanelSection('panelMembersBody', this)">
            <span><i class="bi bi-people me-2 text-muted"></i>Members</span>
            <i class="bi bi-chevron-up" id="panelMembersChevron"></i>
          </button>
          <div id="panelMembersBody" class="px-3 pb-3">
            <div id="membersList"><p class="text-muted text-center small mb-0">Loading…</p></div>
            <?php if (hasRole('admin')): ?>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <label class="form-label mb-0">Add Members</label>
              <a href="#" id="showAddMembersLink" onclick="toggleAddMembersPanel();return false;" style="font-size:.78rem;">
                <i class="bi bi-plus-circle me-1"></i>Add people
              </a>
            </div>
            <div id="addMembersPanel" class="d-none">
              <div class="input-group input-group-sm mb-2">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="addMemberSearchInput" class="form-control border-start-0 ps-0" placeholder="Search users..." autocomplete="off">
              </div>
              <div style="max-height:200px;overflow-y:auto;" id="addMemberList"></div>
              <button type="button" class="btn btn-sm btn-primary mt-2" onclick="submitAddMembers()">
                <i class="bi bi-check2 me-1"></i>Add Selected
              </button>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="p-2">
          <button type="button" class="btn btn-sm btn-outline-danger w-100 text-start" onclick="confirmLeaveGroup()">
            <i class="bi bi-box-arrow-right me-2" id="panelLeaveIcon"></i><span id="panelLeaveLabel">Delete Chat</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</div>

<?php if ($canCreateGroup): ?>
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
const CHAT_QUICK_REACTIONS = " . json_encode($quickReactions) . ";
const CHAT_ALL_USERS = " . json_encode(array_map(static fn ($u) => ['id' => (int) $u['id'], 'name' => $u['name'], 'role' => $u['role']], $users)) . ";
let activeConversationId = null;
let activeConversationType = 'direct';
let activeConversationOtherId = 0;
let pollTimer = null;
let replyToMessage = null;
let editingMessageId = null;
let lastTypingPingAt = 0;

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

/* \"Active now\" / \"Active 5m ago\" / \"Active 3h ago\" / \"Active yesterday\", from an
   ISO-ish \"Y-m-d H:i:s\" timestamp string (or null if never active). */
function formatLastActive(timestamp) {
    if (!timestamp) return '';
    const then = new Date(timestamp.replace(' ', 'T'));
    const diffMin = Math.floor((Date.now() - then.getTime()) / 60000);
    if (diffMin < 1) return 'Active now';
    if (diffMin < 60) return 'Active ' + diffMin + 'm ago';
    const diffHr = Math.floor(diffMin / 60);
    if (diffHr < 24) return 'Active ' + diffHr + 'h ago';
    const diffDay = Math.floor(diffHr / 24);
    if (diffDay === 1) return 'Active yesterday';
    return 'Active ' + diffDay + 'd ago';
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

function buildReplyPreviewEl(replyTo) {
    const box = document.createElement('div');
    box.className = 'chat-reply-quote';
    const who = document.createElement('div');
    who.className = 'chat-reply-quote-name';
    who.textContent = replyTo.sender_name;
    box.appendChild(who);
    const text = document.createElement('div');
    text.className = 'chat-reply-quote-text';
    text.textContent = replyTo.body || (replyTo.attachment_name ? '📎 ' + replyTo.attachment_name : 'This message was removed');
    box.appendChild(text);
    return box;
}

function buildReactionsEl(messageId, reactions) {
    const row = document.createElement('div');
    row.className = 'chat-reactions-row';
    reactions.forEach(r => {
        const pill = document.createElement('button');
        pill.type = 'button';
        pill.className = 'chat-reaction-pill' + (r.mine ? ' mine' : '');
        pill.textContent = r.emoji + ' ' + r.count;
        pill.title = r.mine ? 'Remove your reaction' : 'React with ' + r.emoji;
        pill.onclick = () => reactToMessage(messageId, r.emoji);
        row.appendChild(pill);
    });
    return row;
}

/* Closes any open reaction popover except the one being opened (if any). */
function closeReactionPopovers(exceptWrap) {
    document.querySelectorAll('.chat-react-popover').forEach(el => {
        if (!exceptWrap || !exceptWrap.contains(el)) el.remove();
    });
}
document.addEventListener('click', () => closeReactionPopovers());

function toggleReactionPicker(wrapEl, messageId) {
    const existing = wrapEl.querySelector('.chat-react-popover');
    closeReactionPopovers(wrapEl);
    if (existing) return;

    const pop = document.createElement('div');
    pop.className = 'chat-react-popover';
    CHAT_QUICK_REACTIONS.forEach(emoji => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'chat-react-popover-btn';
        btn.textContent = emoji;
        btn.onclick = (e) => {
            e.stopPropagation();
            reactToMessage(messageId, emoji);
            closeReactionPopovers();
        };
        pop.appendChild(btn);
    });
    wrapEl.appendChild(pop);
}

/* Compact 3-icon cluster beside the bubble (kebab / reply / react) — same
   spot Messenger puts it, not stacked above the message. */
function buildToolbarEl(m) {
    const toolbar = document.createElement('div');
    toolbar.className = 'chat-msg-toolbar';

    if (m.is_me) {
        const kebabWrap = document.createElement('div');
        kebabWrap.className = 'dropdown';
        const kebabBtn = document.createElement('button');
        kebabBtn.type = 'button';
        kebabBtn.className = 'chat-icon-btn';
        kebabBtn.innerHTML = '<i class=\"bi bi-three-dots\"></i>';
        kebabBtn.title = 'More';
        kebabBtn.setAttribute('data-bs-toggle', 'dropdown');
        kebabWrap.appendChild(kebabBtn);

        const menu = document.createElement('ul');
        menu.className = 'dropdown-menu';
        if (m.body) {
            const editLi = document.createElement('li');
            const editA = document.createElement('a');
            editA.className = 'dropdown-item';
            editA.href = '#';
            editA.innerHTML = '<i class=\"bi bi-pencil-fill me-2\"></i>Edit';
            editA.onclick = (e) => { e.preventDefault(); startEditMessage(m.id, m.body); };
            editLi.appendChild(editA);
            menu.appendChild(editLi);
        }
        const delLi = document.createElement('li');
        const delA = document.createElement('a');
        delA.className = 'dropdown-item text-danger';
        delA.href = '#';
        delA.innerHTML = '<i class=\"bi bi-trash-fill me-2\"></i>Unsend';
        delA.onclick = (e) => { e.preventDefault(); deleteMessageConfirm(m.id); };
        delLi.appendChild(delA);
        menu.appendChild(delLi);

        kebabWrap.appendChild(menu);
        toolbar.appendChild(kebabWrap);
    }

    const replyBtn = document.createElement('button');
    replyBtn.type = 'button';
    replyBtn.className = 'chat-icon-btn';
    replyBtn.innerHTML = '<i class=\"bi bi-reply-fill\"></i>';
    replyBtn.title = 'Reply';
    replyBtn.onclick = () => startReply(m);
    toolbar.appendChild(replyBtn);

    const reactWrap = document.createElement('div');
    reactWrap.className = 'chat-react-popover-wrap';
    const reactBtn = document.createElement('button');
    reactBtn.type = 'button';
    reactBtn.className = 'chat-icon-btn';
    reactBtn.innerHTML = '<i class=\"bi bi-emoji-smile\"></i>';
    reactBtn.title = 'React';
    reactBtn.onclick = (e) => { e.stopPropagation(); toggleReactionPicker(reactWrap, m.id); };
    reactWrap.appendChild(reactBtn);
    toolbar.appendChild(reactWrap);

    return toolbar;
}

let lastRenderedMessages = [];
let lastRenderedParticipants = [];

function renderMessages(messages, participants) {
    lastRenderedMessages = messages;
    lastRenderedParticipants = participants || [];

    const container = document.getElementById('threadMessages');
    const wasAtBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) < 60;
    const prevScrollTop = container.scrollTop;
    container.innerHTML = '';

    if (messages.length === 0) {
        container.innerHTML = '<p class=\"text-muted text-center small mt-3\">No messages yet. Say hello!</p>';
        return;
    }

    messages.forEach(m => {
        const row = document.createElement('div');
        row.className = 'chat-msg-row d-flex mb-3 gap-2 ' + (m.is_me ? 'justify-content-end' : 'justify-content-start');

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
            row.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.style.cssText = 'max-width:65%;';

        if (m.deleted) {
            // Placeholder only — nothing left to react to, reply to, edit,
            // or quote, so no toolbar/reactions/reply-preview for this one.
            const bubbleInner = document.createElement('div');
            bubbleInner.style.cssText = 'font-style:italic;color:var(--muted);border:1px dashed var(--border);border-radius:14px;padding:.5rem .85rem;font-size:.82rem;display:flex;align-items:center;gap:.4rem;';
            bubbleInner.innerHTML = '<i class=\"bi bi-slash-circle\"></i>' + (m.is_me ? 'You unsent a message' : 'This message was unsent');
            bubble.appendChild(bubbleInner);

            const meta = document.createElement('div');
            meta.style.cssText = 'font-size:.65rem;color:var(--muted);margin-top:2px;' + (m.is_me ? 'text-align:right;' : 'text-align:left;');
            meta.textContent = m.time;
            bubble.appendChild(meta);
            row.appendChild(bubble);
            container.appendChild(row);
            return;
        }

        if (m.reply_to) {
            bubble.appendChild(buildReplyPreviewEl(m.reply_to));
        }

        const bubbleInner = document.createElement('div');
        bubbleInner.id = 'msgBubble-' + m.id;
        bubbleInner.style.cssText = m.is_me
            ? 'background:var(--primary);color:#fff;border-radius:14px 14px 2px 14px;padding:.55rem .85rem;font-size:.85rem;'
            : 'background:var(--card);color:var(--text);border:1px solid var(--border);border-radius:14px 14px 14px 2px;padding:.55rem .85rem;font-size:.85rem;';
        if (m.body) bubbleInner.textContent = m.body;
        if (m.attachment_url) bubbleInner.appendChild(buildAttachmentEl(m));
        bubble.appendChild(bubbleInner);

        if (m.reactions && m.reactions.length) {
            bubble.appendChild(buildReactionsEl(m.id, m.reactions));
        }

        // Toolbar sits beside the bubble (left for your own, right for
        // others'), vertically centered on it — not stacked above it.
        const toolbar = buildToolbarEl(m);
        toolbar.style.alignSelf = 'center';

        const meta = document.createElement('div');
        meta.style.cssText = 'font-size:.65rem;color:var(--muted);margin-top:2px;' + (m.is_me ? 'text-align:right;' : 'text-align:left;');

        if (m.is_me) {
            meta.textContent = m.time + (m.edited ? ' · edited' : '');
            meta.id = 'msgMeta-' + m.id;
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
            meta.appendChild(document.createTextNode(' · ' + m.time + (m.edited ? ' · edited' : '')));
        }

        bubble.appendChild(meta);
        if (m.is_me) {
            row.appendChild(toolbar);
            row.appendChild(bubble);
        } else {
            row.appendChild(bubble);
            row.appendChild(toolbar);
        }
        container.appendChild(row);
    });

    if (wasAtBottom) {
        container.scrollTop = container.scrollHeight;
    } else {
        container.scrollTop = prevScrollTop;
    }

    updateSeenIndicator();
}

function updateSeenIndicator() {
    const mine = [...lastRenderedMessages].filter(m => m.is_me);
    if (!mine.length) return;
    const last = mine[mine.length - 1];
    const metaEl = document.getElementById('msgMeta-' + last.id);
    if (!metaEl) return;

    const seenBy = lastRenderedParticipants.filter(p => p.last_read_at && new Date(p.last_read_at.replace(' ', 'T')).getTime() >= new Date(last.created_at.replace(' ', 'T')).getTime());
    document.querySelectorAll('.chat-seen-tag').forEach(el => el.remove());
    if (seenBy.length > 0) {
        const seenSpan = document.createElement('div');
        seenSpan.className = 'chat-seen-tag';
        seenSpan.style.cssText = 'font-size:.65rem;color:var(--muted);text-align:right;margin-top:1px;';
        seenSpan.textContent = 'Seen';
        metaEl.parentElement.appendChild(seenSpan);
    }
}

function updateTypingIndicator(typing) {
    const el = document.getElementById('typingIndicator');
    if (!typing || typing.length === 0) {
        el.classList.add('d-none');
        el.textContent = '';
        return;
    }
    const names = typing.map(t => t.name.split(' ')[0]);
    el.textContent = (names.length === 1 ? names[0] + ' is typing…' : names.join(', ') + ' are typing…');
    el.classList.remove('d-none');
}

function openConversation(id) {
    activeConversationId = id;
    replyToMessage = null;
    editingMessageId = null;
    if (typeof clearAttachment === 'function') clearAttachment();
    if (typeof cancelReply === 'function') cancelReply();

    document.getElementById('chatShell')?.classList.add('chat-mobile-thread-active');

    document.querySelectorAll('.chat-convo-item').forEach(el => {
        el.style.background = String(el.dataset.id) === String(id) ? '#fff0f0' : '';
    });

    const item = document.querySelector('.chat-convo-item[data-id=\"' + id + '\"]');
    const name = item ? item.querySelector('.fw-semibold').textContent.replace(/\s*\(Principal\)\s*$/, '').trim() : 'Conversation';
    const isAdmin = item ? item.dataset.otherRole === 'admin' : false;
    const photo = item ? item.dataset.photo : '';
    activeConversationType = item ? item.dataset.type : 'direct';
    activeConversationOtherId = item ? parseInt(item.dataset.otherId || '0', 10) : 0;

    const header = document.getElementById('threadHeader');
    header.innerHTML = '';
    header.style.cssText = 'display:flex;align-items:center;gap:.6rem;';

    const avatarWrap = document.createElement('div');
    avatarWrap.style.cssText = 'position:relative;flex-shrink:0;';
    const avatar = document.createElement('div');
    avatar.style.cssText = 'width:32px;height:32px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;';
    if (photo) {
        avatar.innerHTML = '<img src=\"' + photo + '\" style=\"width:100%;height:100%;object-fit:cover;\">';
    } else {
        const colors = CHAT_ROLE_COLORS[item ? item.dataset.otherRole : ''] || ['#f3f4f6', '#374151'];
        avatar.style.background = colors[0];
        avatar.style.color = colors[1];
        avatar.textContent = chatInitialsJs(name);
    }
    avatarWrap.appendChild(avatar);
    if (activeConversationType === 'direct') {
        const dot = document.createElement('span');
        dot.id = 'threadPresenceDot';
        dot.className = 'chat-presence-dot d-none';
        avatarWrap.appendChild(dot);
    }

    const nameCol = document.createElement('div');
    const nameSpan = document.createElement('div');
    if (isAdmin) {
        nameSpan.style.color = '#800000';
        nameSpan.textContent = name + ' (Principal)';
    } else {
        nameSpan.textContent = name;
    }
    nameCol.appendChild(nameSpan);

    const subSpan = document.createElement('div');
    subSpan.id = 'threadSubtext';
    subSpan.className = 'text-muted';
    subSpan.style.cssText = 'font-size:.68rem;font-weight:400;';
    subSpan.textContent = activeConversationType === 'group'
        ? (item ? item.dataset.memberCount : '') + ' members'
        : '';
    nameCol.appendChild(subSpan);

    header.appendChild(avatarWrap);
    header.appendChild(nameCol);

    document.getElementById('viewMembersItem').classList.toggle('d-none', activeConversationType !== 'group');
    document.getElementById('panelMembersSection').classList.toggle('d-none', activeConversationType !== 'group');

    const leaveLabel = activeConversationType === 'group' ? 'Leave Group' : 'Delete Chat';
    const leaveIconClass = 'bi me-2 ' + (activeConversationType === 'group' ? 'bi-box-arrow-right' : 'bi-trash-fill');
    document.getElementById('leaveGroupLabel').textContent = leaveLabel;
    document.getElementById('leaveGroupIcon').className = leaveIconClass;
    document.getElementById('panelLeaveLabel').textContent = leaveLabel;
    document.getElementById('panelLeaveIcon').className = leaveIconClass;

    const muteLabel = (item && item.dataset.muted === '1') ? 'Unmute Notifications' : 'Mute Notifications';
    document.getElementById('muteMenuLabel').textContent = muteLabel;
    document.getElementById('panelMuteLabel').textContent = muteLabel;

    closeInfoPanel();

    const badge = item ? item.querySelector('.badge') : null;
    if (badge) badge.remove();

    document.getElementById('threadEmpty').classList.add('d-none');
    document.getElementById('threadActive').classList.remove('d-none');
    document.getElementById('threadMessages').innerHTML = '<p class=\"text-muted text-center small mt-3\">Loading…</p>';

    fetchMessages();

    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(fetchMessages, 4000);
}

/* Mobile only: the list and thread never share the screen, so \"back\"
   just switches which one is visible — no need to lose the open thread's
   state, but stop polling it while it's not on screen. */
function closeConversationMobile() {
    closeInfoPanel();
    document.getElementById('chatShell')?.classList.remove('chat-mobile-thread-active');
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function fetchMessages() {
    if (!activeConversationId) return;

    fetch(CHAT_BASE + activeConversationId + '/messages', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;
            renderMessages(data.messages, data.participants);
            updateTypingIndicator(data.typing);

            if (activeConversationType === 'direct' && activeConversationOtherId) {
                const state = data.participants.find(p => p.user_id === activeConversationOtherId);
                const dot = document.getElementById('threadPresenceDot');
                const sub = document.getElementById('threadSubtext');
                if (state && dot && sub) {
                    dot.classList.toggle('d-none', !state.online);
                    sub.textContent = state.online ? 'Active now' : formatLastActive(state.last_active_at);
                }
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

let isSendingMessage = false;

document.getElementById('sendForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!activeConversationId || isSendingMessage) return;

    const input = document.getElementById('messageInput');
    const body = input.value.trim();
    if (!body && !selectedAttachment) return;

    isSendingMessage = true;

    const formData = new FormData();
    formData.append('body', body);
    if (selectedAttachment) {
        formData.append('file', selectedAttachment);
    }
    if (replyToMessage) {
        formData.append('reply_to_id', replyToMessage.id);
    }

    fetch(CHAT_BASE + activeConversationId + '/send', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            isSendingMessage = false;
            if (data.status === 'success') {
                input.value = '';
                clearAttachment();
                cancelReply();
                fetchMessages();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not send message.' });
            }
            input.focus();
        })
        .catch(() => {
            isSendingMessage = false;
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
            input.focus();
        });
});

/* ── Typing indicator: ping at most every ~2.5s while the box has text ── */
document.getElementById('messageInput')?.addEventListener('input', function () {
    if (!activeConversationId || !this.value.trim()) return;
    const now = Date.now();
    if (now - lastTypingPingAt < 2500) return;
    lastTypingPingAt = now;

    fetch(CHAT_BASE + activeConversationId + '/typing', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).catch(() => {});
});

/* ── Reply ── */
function startReply(m) {
    replyToMessage = { id: m.id, name: m.is_me ? 'yourself' : m.sender_name };
    document.getElementById('replyBannerName').textContent = 'Replying to ' + (m.is_me ? 'yourself' : m.sender_name);
    document.getElementById('replyBannerText').textContent = m.body || (m.attachment_name ? '📎 ' + m.attachment_name : '');
    document.getElementById('replyBanner').classList.remove('d-none');
    document.getElementById('messageInput')?.focus();
}

function cancelReply() {
    replyToMessage = null;
    document.getElementById('replyBanner')?.classList.add('d-none');
}

/* ── Reactions ── */
function reactToMessage(messageId, emoji) {
    if (!activeConversationId) return;

    fetch(CHAT_BASE + activeConversationId + '/messages/' + messageId + '/react', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'emoji=' + encodeURIComponent(emoji),
    })
        .then(res => res.json())
        .then(data => { if (data.status === 'success') fetchMessages(); })
        .catch(() => {});
}

/* ── Edit ── */
function startEditMessage(messageId, currentBody) {
    editingMessageId = messageId;
    const bubble = document.getElementById('msgBubble-' + messageId);
    if (!bubble) return;

    bubble.innerHTML = '';
    const textarea = document.createElement('textarea');
    textarea.className = 'form-control form-control-sm';
    textarea.value = currentBody;
    textarea.rows = 2;
    textarea.style.cssText = 'font-size:.85rem;color:#111;';
    bubble.appendChild(textarea);

    const actions = document.createElement('div');
    actions.style.cssText = 'display:flex;gap:.4rem;margin-top:.35rem;justify-content:flex-end;';
    const saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'btn btn-light btn-sm py-0 px-2';
    saveBtn.style.fontSize = '.72rem';
    saveBtn.textContent = 'Save';
    saveBtn.onclick = () => saveEditMessage(messageId, textarea.value);
    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-light btn-sm py-0 px-2';
    cancelBtn.style.fontSize = '.72rem';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.onclick = () => { editingMessageId = null; fetchMessages(); };
    actions.appendChild(cancelBtn);
    actions.appendChild(saveBtn);
    bubble.appendChild(actions);
    textarea.focus();
}

function saveEditMessage(messageId, newBody) {
    newBody = newBody.trim();
    if (!newBody || !activeConversationId) return;

    fetch(CHAT_BASE + activeConversationId + '/messages/' + messageId + '/edit', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'body=' + encodeURIComponent(newBody),
    })
        .then(res => res.json())
        .then(data => {
            editingMessageId = null;
            if (data.status === 'success') {
                fetchMessages();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not edit message.' });
            }
        })
        .catch(() => { editingMessageId = null; });
}

/* ── Delete ── */
function deleteMessageConfirm(messageId) {
    if (!activeConversationId) return;

    Swal.fire({
        icon: 'warning',
        title: 'Delete this message?',
        text: 'This removes it for everyone in the conversation.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc2626',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch(CHAT_BASE + activeConversationId + '/messages/' + messageId + '/delete', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    fetchMessages();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not delete message.' });
                }
            })
            .catch(() => {});
    });
}

/* ── Mute / Leave ── */
function toggleMute() {
    if (!activeConversationId) return;

    fetch(CHAT_BASE + activeConversationId + '/mute', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;
            const label = data.muted ? 'Unmute Notifications' : 'Mute Notifications';
            document.getElementById('muteMenuLabel').textContent = label;
            document.getElementById('panelMuteLabel').textContent = label;
            const item = document.querySelector('.chat-convo-item[data-id=\"' + activeConversationId + '\"]');
            if (item) item.dataset.muted = data.muted ? '1' : '0';
        })
        .catch(() => {});
}

/* Shared by the thread-header \"Leave/Delete\" menu item and the hover delete
   button on each conversation-list row — same server action either way
   (Chat::leave removes just the caller's own participant row). */
function performLeaveConversation(id, isGroup) {
    Swal.fire({
        icon: 'warning',
        title: isGroup ? 'Leave this group?' : 'Delete this chat?',
        text: isGroup
            ? 'You will stop seeing new messages unless someone adds you back.'
            : 'This removes it from your chat list. The other person keeps their copy — messaging them again starts a new chat.',
        showCancelButton: true,
        confirmButtonText: isGroup ? 'Leave' : 'Delete',
        confirmButtonColor: '#dc2626',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch(CHAT_BASE + id + '/leave', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = CHAT_INDEX_URL;
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not complete this action.' });
                }
            })
            .catch(() => {});
    });
}

function confirmLeaveGroup() {
    if (!activeConversationId) return;
    performLeaveConversation(activeConversationId, activeConversationType === 'group');
}

/* Delete/leave straight from the conversation list, without opening it first. */
function deleteConversationFromList(id, type) {
    performLeaveConversation(id, type === 'group');
}

/* ── Right-side chat info panel ── */
let currentMembers = [];

function openInfoPanel() {
    if (!activeConversationId) return;

    const item = document.querySelector('.chat-convo-item[data-id=\"' + activeConversationId + '\"]');
    const headerEl = document.getElementById('chatInfoHeader');
    const name = item ? item.querySelector('.fw-semibold').textContent.replace(/\s*\(Principal\)\s*$/, '').trim() : 'Conversation';
    const photo = item ? item.dataset.photo : '';
    const colors = CHAT_ROLE_COLORS[item ? item.dataset.otherRole : ''] || ['#f3f4f6', '#374151'];

    headerEl.innerHTML = '';
    const avatar = document.createElement('div');
    avatar.style.cssText = 'width:56px;height:56px;border-radius:50%;margin:0 auto .5rem;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;';
    if (photo) {
        avatar.innerHTML = '<img src=\"' + photo + '\" style=\"width:100%;height:100%;object-fit:cover;\">';
    } else {
        avatar.style.background = colors[0];
        avatar.style.color = colors[1];
        avatar.textContent = activeConversationType === 'group' ? '' : chatInitialsJs(name);
        if (activeConversationType === 'group') avatar.innerHTML = '<i class=\"bi bi-people-fill\"></i>';
    }
    const nameEl = document.createElement('div');
    nameEl.className = 'fw-semibold';
    nameEl.style.fontSize = '.88rem';
    nameEl.textContent = name;
    headerEl.appendChild(avatar);
    headerEl.appendChild(nameEl);
    if (activeConversationType === 'group') {
        const subEl = document.createElement('div');
        subEl.className = 'text-muted';
        subEl.style.fontSize = '.72rem';
        subEl.textContent = (item ? item.dataset.memberCount : '') + ' members';
        headerEl.appendChild(subEl);
    }

    document.getElementById('chatInfoPanel').classList.remove('d-none');
    document.getElementById('chatShell')?.classList.add('chat-mobile-info-active');

    document.getElementById('addMembersPanel')?.classList.add('d-none');
    document.getElementById('membersList').innerHTML = '<p class=\"text-muted text-center small mb-0\">Loading…</p>';

    fetch(CHAT_BASE + activeConversationId + '/members', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;
            currentMembers = data.members;
            renderMembersList(data.members);
        })
        .catch(() => {
            document.getElementById('membersList').innerHTML = '<p class=\"text-danger text-center small mb-0\">Could not load members.</p>';
        });
}

function closeInfoPanel() {
    document.getElementById('chatInfoPanel')?.classList.add('d-none');
    document.getElementById('chatShell')?.classList.remove('chat-mobile-info-active');
}

function togglePanelSection(bodyId, btnEl) {
    const body = document.getElementById(bodyId);
    if (!body) return;
    const collapsed = body.classList.toggle('d-none');
    const chevron = btnEl?.querySelector('.bi');
    if (chevron) chevron.className = collapsed ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
}

function renderMembersList(members) {
    const list = document.getElementById('membersList');
    list.innerHTML = '';

    members.forEach(m => {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2 py-2';
        row.style.borderBottom = '1px solid var(--border)';

        const colors = CHAT_ROLE_COLORS[m.role] || ['#f3f4f6', '#374151'];
        const avatar = document.createElement('div');
        avatar.style.cssText = 'width:32px;height:32px;border-radius:50%;flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;background:' + colors[0] + ';color:' + colors[1] + ';';
        if (m.photo) {
            avatar.innerHTML = '<img src=\"' + m.photo + '\" style=\"width:100%;height:100%;object-fit:cover;\">';
        } else {
            avatar.textContent = chatInitialsJs(m.name);
        }
        row.appendChild(avatar);

        const info = document.createElement('div');
        info.className = 'flex-grow-1';
        info.style.minWidth = '0';
        const nameDiv = document.createElement('div');
        nameDiv.className = 'fw-semibold text-truncate';
        nameDiv.style.fontSize = '.82rem';
        nameDiv.textContent = m.name + (m.is_me ? ' (You)' : '');
        const roleDiv = document.createElement('div');
        roleDiv.className = 'text-muted';
        roleDiv.style.fontSize = '.7rem';
        roleDiv.textContent = m.role.charAt(0).toUpperCase() + m.role.slice(1);
        info.appendChild(nameDiv);
        info.appendChild(roleDiv);
        row.appendChild(info);

        " . (hasRole('admin') ? "
        if (!m.is_me) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger py-0 px-2';
            removeBtn.style.fontSize = '.7rem';
            removeBtn.textContent = 'Remove';
            removeBtn.onclick = () => removeMemberConfirm(m.user_id, m.name);
            row.appendChild(removeBtn);
        }
        " : "") . "

        list.appendChild(row);
    });
}

function removeMemberConfirm(userId, name) {
    Swal.fire({
        icon: 'warning',
        title: 'Remove ' + name + '?',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        confirmButtonColor: '#dc2626',
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch(CHAT_BASE + activeConversationId + '/members/remove', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'user_id=' + encodeURIComponent(userId),
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    openInfoPanel();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not remove member.' });
                }
            })
            .catch(() => {});
    });
}

function toggleAddMembersPanel() {
    const panel = document.getElementById('addMembersPanel');
    if (!panel) return;
    const showing = !panel.classList.contains('d-none');
    panel.classList.toggle('d-none', showing);
    if (!showing) populateAddMemberList();
}

function populateAddMemberList() {
    const list = document.getElementById('addMemberList');
    if (!list) return;
    const existingIds = currentMembers.map(m => m.user_id);
    const candidates = CHAT_ALL_USERS.filter(u => !existingIds.includes(u.id));

    list.innerHTML = '';
    candidates.forEach(u => {
        const label = document.createElement('label');
        label.className = 'd-flex align-items-center gap-2 p-2 rounded-3';
        label.style.cssText = 'cursor:pointer;font-size:.8rem;';
        label.dataset.search = u.name.toLowerCase();

        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.value = u.id;
        cb.className = 'chat-add-member-checkbox';
        label.appendChild(cb);

        const nameSpan = document.createElement('span');
        nameSpan.textContent = u.name + ' (' + u.role.charAt(0).toUpperCase() + u.role.slice(1) + ')';
        label.appendChild(nameSpan);

        list.appendChild(label);
    });
}

document.getElementById('addMemberSearchInput')?.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#addMemberList label').forEach(label => {
        label.style.display = (!q || label.dataset.search.includes(q)) ? 'flex' : 'none';
    });
});

function submitAddMembers() {
    const ids = [...document.querySelectorAll('.chat-add-member-checkbox:checked')].map(cb => cb.value);
    if (ids.length === 0 || !activeConversationId) return;

    const body = ids.map(id => 'user_ids[]=' + encodeURIComponent(id)).join('&');

    fetch(CHAT_BASE + activeConversationId + '/members/add', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body,
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('addMembersPanel').classList.add('d-none');
                openInfoPanel();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not add members.' });
            }
        })
        .catch(() => {});
}

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
