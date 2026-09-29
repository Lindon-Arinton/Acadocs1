<?php

namespace App\Database\Seeds\Demo;

/**
 * Announcements (with the per-user notifications posting one sends) and
 * Chat: department/grade-level group chats and one-on-one conversations
 * among the real staff accounts, with replies, reactions and read state.
 */
class CommunicationSeeder extends DemoSeeder
{
    /** [type, title, content] — dates are assigned spread over the last ~4 months. */
    private const ANNOUNCEMENTS = [
        ['Announcement', 'Faculty Meeting', 'All teaching and non-teaching personnel are expected at the faculty meeting in the AVR at 3:00 PM. Agenda: quarter updates and upcoming school activities.'],
        ['Announcement', 'Submission of Daily Lesson Logs', 'Please make sure your DLLs for the coming week are uploaded on the To Do List on or before Friday.'],
        ['Forms', 'Updated Class Record Form', 'The updated class record form is now available under Templates. Kindly use this version starting this quarter.'],
        ['Announcement', 'Quarterly Examination Schedule', 'Quarterly examinations will run for two days. Test papers must be submitted to the department head for checking one week before.'],
        ['Questionnaires', 'Teacher Wellness Survey', 'Kindly answer the short wellness survey. Your responses are confidential and will help plan the next INSET.'],
        ['Announcement', 'Brigada Eskwela Volunteers', 'We are looking for volunteers for the upcoming Brigada Eskwela activities. Please coordinate with the school coordinator.'],
        ['Announcement', 'In-Service Training (INSET)', 'The school-based INSET will be held in the AVR. Attendance is required; certificates will be issued.'],
        ['Forms', 'Leave Application Reminder', 'Leave applications must be filed at least five working days before the intended date, except for sick leave.'],
        ['Announcement', 'Card Distribution Day', 'Report cards will be distributed to parents on the scheduled day. Advisers, please prepare your class records and SF9 forms.'],
        ['Questionnaires', 'Learner Support Needs Assessment', 'Advisers are requested to complete the learner support needs assessment for their advisory class.'],
        ['Announcement', 'Classroom Inventory Update', 'Please update your classroom property inventory in Property Management before the end of the month.'],
        ['Announcement', 'School Clean-up Drive', 'A school-wide clean-up drive will be held this Friday afternoon. Classes will end at 2:00 PM.'],
        ['Forms', 'IPCRF Mid-Year Review Forms', 'The IPCRF mid-year review forms are now available. Please accomplish and submit them to your rater.'],
        ['Announcement', 'Parent-Teacher Conference', 'Advisers are reminded to send invitation letters to parents for the upcoming parent-teacher conference.'],
        ['Announcement', 'Earthquake Drill', 'The quarterly nationwide simultaneous earthquake drill will be conducted in the morning. Please brief your learners beforehand.'],
        ['Questionnaires', 'ICT Resource Survey', 'Help us plan ICT purchases by answering the short survey on classroom devices and connectivity.'],
        ['Announcement', 'Reading Program Launch', 'The school reading program starts next week. Language teachers will receive the reading materials from the library.'],
        ['Announcement', 'Submission of MPS Results', 'Subject teachers, please encode your MPS results for the quarter on the Enter MPS Scores page.'],
        ['Forms', 'Travel Authority Form', 'Personnel attending seminars outside the division must secure an approved travel authority form in advance.'],
        ['Announcement', 'Sports Fest Committee Meeting', 'Members of the sports fest committee will meet at the MAPEH room after classes.'],
        ['Announcement', 'Class Observation Schedule', 'The schedule for classroom observations has been posted. Please prepare your lesson plans accordingly.'],
        ['Announcement', 'Payday Advisory', 'Salaries will be credited on the regular schedule. Please report any discrepancies to the ADAS office.'],
        ['Questionnaires', 'School Improvement Plan Feedback', 'Share your inputs for the School Improvement Plan through the short questionnaire.'],
        ['Announcement', 'Uniform Policy Reminder', 'Advisers are reminded to check learners\' compliance with the school uniform policy.'],
    ];

    /** [name, members] — members is 'all', a grade level, or a list of subject codes. */
    private const GROUPS = [
        ['Matabungkay NHS Faculty', 'all'],
        ['Grade 7 Teachers', 'Grade 7'],
        ['Grade 8 Teachers', 'Grade 8'],
        ['Grade 9 Teachers', 'Grade 9'],
        ['Grade 10 Teachers', 'Grade 10'],
        ['Math & Science Dept.', ['MATH', 'SCIENCE']],
        ['Languages Dept.', ['ENGLISH', 'FILIPINO', 'SPFL', 'SPFL-CM']],
        ['MAPEH / TLE Dept.', ['MAPEH', 'TLE']],
        ['AP / ESP / Values Ed', ['AP', 'ESP', 'V.E', 'HG']],
    ];

    private const GROUP_LINES = [
        'Good morning po! Reminder lang sa meeting mamaya.',
        'Noted po, thank you.',
        'Pa-share naman po ng updated template.',
        'Uploaded na po sa Templates.',
        'Salamat po!',
        'Anong oras po ulit yung deadline?',
        'Friday po, 5 PM.',
        'May extension po ba for the class records?',
        'Will check with the principal po.',
        'Sino po may extra copy ng DLL format?',
        'Ako po, send ko dito.',
        'Paalala: submission ng MPS this week.',
        'Done na po ako mag-encode.',
        'Pakicheck po yung schedule ng quarterly exam.',
        'Na-post na po sa Announcements.',
        'Congrats po sa lahat for the successful activity!',
        'Pwede po ba magpalit ng schedule sa class observation?',
        'Kindly coordinate po with your department head.',
        'Thank you po sa update.',
        'Ingat po sa pag-uwi, malakas ulan.',
        'Nasa faculty room po yung mga test papers.',
        'Sige po, kukunin ko mamaya.',
        'Please bring your laptops sa INSET bukas.',
        'Copy po.',
        'Meron po bang signal sa AVR? Para sa presentation.',
        'Mahina po, better download na lang in advance.',
        'Good job everyone!',
        'Ok po.',
    ];

    private const DIRECT_LINES = [
        'Hi! Pwede po magtanong?',
        'Yes po, ano yun?',
        'Tapos na po ba kayo sa class record?',
        'Halos tapos na, bukas ko i-submit.',
        'Sige po, salamat!',
        'Pa-send naman po ng copy ng lesson plan niyo.',
        'Sure, i-upload ko mamaya.',
        'Nakita niyo na po ba yung bagong task?',
        'Oo, due next week pa naman.',
        'Pwede po ba tayo mag-swap ng duty sa Friday?',
        'Pwede, basta ikaw sa Monday.',
        'Deal po. Thank you!',
        'Nasa office po ba kayo?',
        'Nasa classroom ako, punta ka na lang.',
        'Ok po, papunta na.',
        'Salamat sa tulong kanina!',
        'Walang anuman.',
    ];

    private const REACTIONS = ['👍', '❤️', '😆', '😮', '😢', '🙏'];

    public function run()
    {
        $this->seedAnnouncements();
        $this->seedChats();
        $this->flushNotifications();
    }

    private function seedAnnouncements(): void
    {
        $posters = [$this->principal(), $this->adas()];
        $users   = $this->users();
        $count   = count(self::ANNOUNCEMENTS);

        foreach (self::ANNOUNCEMENTS as $i => [$type, $title, $content]) {
            // Oldest first, ~120 days ago up to ~2 weeks ahead; posted 0-5 days before the date.
            $dateTs   = $this->day(-120 + (int) round($i * 134 / ($count - 1)));
            $postedTs = min($dateTs - mt_rand(0, 5) * 86400 + mt_rand(7, 16) * 3600, time() - 3600);
            $poster   = $this->chance(75) ? $posters[0] : $posters[1];

            $this->db->table('announcements')->insert([
                'type'       => $type,
                'title'      => $title,
                'content'    => $content,
                'date'       => date('Y-m-d', $dateTs),
                'status'     => $dateTs < $this->day(-60) && $this->chance(40) ? 'inactive' : 'active',
                'created_by' => $poster['id'],
                'created_at' => $this->at($postedTs),
            ]);
            $announcementId = (int) $this->db->insertID();

            foreach ($users as $user) {
                if ((int) $user['id'] !== (int) $poster['id']) {
                    $this->notify((int) $user['id'], 'announcement', $title, $type . ' · ' . date('M d', $dateTs),
                        base_url('announcements') . '?id=' . $announcementId, null, null, $postedTs);
                }
            }
        }
    }

    private function seedChats(): void
    {
        $teachers  = $this->teachers();
        $principal = $this->principal();
        $adas      = $this->adas();

        foreach (self::GROUPS as [$name, $filter]) {
            $members = array_values(array_filter($teachers, fn ($t) => $this->matchesGroup($t, $filter)));
            if (count($members) < 2) {
                continue;
            }
            $members[] = $principal;
            if ($filter === 'all') {
                $members[] = $adas;
            }
            $this->seedConversation('group', $name, $members, mt_rand(40, 80), self::GROUP_LINES);
        }

        // One-on-ones: the principal with a handful of teachers, plus teacher pairs
        // from the same grade level — skipping any pair that already has a chat.
        $pairs = [];
        foreach (array_rand($teachers, min(8, count($teachers))) as $i) {
            $pairs[] = [$principal, $teachers[$i]];
        }
        for ($n = 0; $n < 30 && count($pairs) < 28; $n++) {
            [$a, $b] = array_map(static fn ($i) => $teachers[$i], array_rand($teachers, 2));
            if (array_intersect($this->grades($a), $this->grades($b)) !== []) {
                $pairs[] = [$a, $b];
            }
        }

        $seen = [];
        foreach ($pairs as [$a, $b]) {
            $key = min($a['id'], $b['id']) . '-' . max($a['id'], $b['id']);
            if (isset($seen[$key]) || $this->directChatExists((int) $a['id'], (int) $b['id'])) {
                continue;
            }
            $seen[$key] = true;
            $this->seedConversation('direct', null, [$a, $b], mt_rand(6, 18), self::DIRECT_LINES);
        }
    }

    private function seedConversation(string $type, ?string $name, array $members, int $messageCount, array $lines): void
    {
        $members = array_values(array_column($members, null, 'id'));
        $startTs =$this->day(-mt_rand(30, 75)) + mt_rand(7, 17) * 3600;

        $this->db->table('conversations')->insert([
            'type'       => $type,
            'name'       => $name,
            'created_by' => $members[count($members) - 1]['id'] ?? $members[0]['id'],
            'created_at' => $this->at($startTs),
        ]);
        $conversationId = (int) $this->db->insertID();

        // Messages on random days from the start to today, between 7 AM and 8 PM.
        $times = [];
        for ($n = 0; $n < $messageCount; $n++) {
            $dayStart = strtotime(date('Y-m-d', mt_rand($startTs, time())));
            $times[]  = max($startTs + 60, min($dayStart + mt_rand(7 * 3600, 20 * 3600), time() - 600));
        }
        sort($times);

        $messageIds = [];
        $lastSentBy = [];
        foreach ($times as $ts) {
            $sender  = $this->pick($members);
            $replyTo = $messageIds !== [] && $this->chance(12) ? $this->pick(array_slice($messageIds, -8)) : null;

            $this->db->table('messages')->insert([
                'conversation_id' => $conversationId,
                'sender_id'       => $sender['id'],
                'reply_to_id'     => $replyTo,
                'body'            => $this->pick($lines),
                'created_at'      => $this->at($ts),
            ]);
            $messageId               = (int) $this->db->insertID();
            $messageIds[]            = $messageId;
            $lastSentBy[$sender['id']] = $ts;

            if ($this->chance(25)) {
                $reactors = array_rand($members, min(count($members), mt_rand(1, 3)));
                foreach ((array) $reactors as $r) {
                    if ((int) $members[$r]['id'] === (int) $sender['id']) {
                        continue;
                    }
                    $this->db->table('message_reactions')->insert([
                        'message_id' => $messageId,
                        'user_id'    => $members[$r]['id'],
                        'emoji'      => $this->pick(self::REACTIONS),
                        'created_at' => $this->at(min($ts + mt_rand(60, 7200), time())),
                    ]);
                }
            }
        }

        // Most people are caught up; a few are a message or two behind.
        $lastTs = end($times) ?: $startTs;
        $rows   = [];
        foreach ($members as $member) {
            $readAt = $this->chance(80) ? $lastTs : max($lastSentBy[$member['id']] ?? $startTs, $times[max(0, count($times) - mt_rand(2, 4))] ?? $startTs);
            $rows[$member['id']] = [
                'conversation_id' => $conversationId,
                'user_id'         => $member['id'],
                'last_read_at'    => $this->at($readAt),
                'muted'           => $type === 'group' && $this->chance(8) ? 1 : 0,
                'created_at'      => $this->at($startTs),
            ];
        }
        $this->db->table('conversation_participants')->insertBatch(array_values($rows));
    }

    private function matchesGroup(array $teacher, string|array $filter): bool
    {
        if ($filter === 'all') {
            return true;
        }
        if (is_string($filter)) {
            return in_array($filter, $this->grades($teacher), true);
        }

        foreach ($teacher['subjects'] as $s) {
            if (in_array(strtoupper($s['subject']), $filter, true)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> grade levels this teacher handles */
    private function grades(array $teacher): array
    {
        return array_values(array_unique(array_column($teacher['subjects'], 'grade_level')));
    }

    private function directChatExists(int $a, int $b): bool
    {
        return $this->db->table('conversations c')
            ->join('conversation_participants pa', 'pa.conversation_id = c.id AND pa.user_id = ' . $a, 'inner', false)
            ->join('conversation_participants pb', 'pb.conversation_id = c.id AND pb.user_id = ' . $b, 'inner', false)
            ->where('c.type', 'direct')
            ->countAllResults() > 0;
    }
}
