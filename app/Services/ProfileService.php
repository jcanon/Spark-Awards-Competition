<?php

namespace App\Services;

use App\Models\Accounts\UserModel;
use App\Models\Auth\PasswordHistoryModel;
use App\Models\Accounts\UserTypeModel;
use App\Models\Geo\StateModel;
use App\Models\Geo\CountryModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Entity-first ProfileService.
 * - Uses User entities for CRUD.
 * - Keeps array outputs only where used for dropdowns/exports.
 */
class ProfileService
{
    /**
     * Create a new user using modern password_hash.
     * Returns the new user_id.
     */
    public function createNewUser(string $email, string $password): string
    {
        $email = strtolower(trim($email));
        if (service('auth')->checkEmailExists($email) === 'yes') {
            throw new DatabaseException('User already exists for this email.');
        }

        $userId = $this->uuidV4();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $model = new UserModel();
        $model->insert([
            'user_id' => $userId,
            'email_address' => $email,
            'password' => $hash,
            'salt' => '',
            'password_changed_at' => date('Y-m-d H:i:s'),
            'account_active' => 'Yes',
            'account_created' => date('Y-m-d H:i:s'),
            'last_updated' => date('Y-m-d H:i:s'),
            'profile_completed' => 'No',
            'is_admin' => 'No',
            'is_editor' => 'No',
            'is_judge' => 'No',
        ], false);
        (new PasswordHistoryModel())->insert([
            'user_id' => $userId,
            'password_hash' => $hash,
            'changed_at' => date('Y-m-d H:i:s'),
            'source' => 'register',
        ], false);

        return $userId;
    }

    /**
     * Reset password. Uses modern hashing; clears legacy salt.
     */
    public function resetPassword(string $userId, string $newPassword): bool
    {
        return service('auth')->setUserPassword($userId, $newPassword, true, 'reset') === 'ok';
    }

    /**
     * Update a user by ID with whitelisted form fields.
     * Returns 'success' or throws on error.
     */
    public function updateUser(array $form, string $targetUserId, ?string $actorRole = null): string
    {
        $form = $this->normalizeUserForm($form);

        $data = [
            'last_updated' => date('Y-m-d H:i:s'),
            'email_address' => $form['email_address'] ?? null,
            'title' => $form['title'] ?? null,
            'first_name' => $form['first_name'] ?? null,
            'last_name' => $form['last_name'] ?? null,
            'website' => $form['website'] ?? null,
            'phone' => $form['phone'] ?? null,
            'mobile' => $form['mobile'] ?? null,
            'user_type_id' => isset($form['user_type_id']) ? (int)$form['user_type_id'] : null,
            'company_name' => $form['company_name'] ?? null,
            'address1' => $form['address1'] ?? null,
            'address2' => $form['address2'] ?? null,
            'city' => $form['city'] ?? null,
            'state' => $form['state'] ?? null,
            'zipcode' => $form['zipcode'] ?? null,
            'country' => $form['country'] ?? null,
            'how_did_you_find_us' => $form['how_did_you_find_us'] ?? null,
            'how_did_you_find_us_other' => $form['how_did_you_find_us_other'] ?? null,
        ];

        if (array_key_exists('email_newsletters', $form)) {
            $data['email_newsletters'] = $form['email_newsletters'];
        }
        if (array_key_exists('internal_notes', $form)) {
            $data['internal_notes'] = $form['internal_notes'];
        }
        if (array_key_exists('is_admin', $form)) {
            $data['is_admin'] = $form['is_admin'];
        }
        if (array_key_exists('is_editor', $form)) {
            $data['is_editor'] = $form['is_editor'];
        }
        if (array_key_exists('is_judge', $form)) {
            $data['is_judge'] = $form['is_judge'];
        }
        if (array_key_exists('account_active', $form)) {
            $data['account_active'] = $form['account_active'];
        } else {
            $data['profile_completed'] = 'Yes';
        }

        $effectiveActorRole = strtolower(trim((string)($actorRole ?? (string)session('role'))));
        $this->enforceRoleUpdatePolicy($data, $targetUserId, $effectiveActorRole);

        if (($data['is_admin'] ?? 'No') === 'Yes' && ($data['is_editor'] ?? 'No') === 'Yes') {
            throw new DatabaseException('User cannot be both Admin and Editor.');
        }

        if (!empty($form['password'])) {
            $source = array_key_exists('account_active', $form) ? 'admin_update' : 'profile_update';
            $pwStatus = service('auth')->setUserPassword($targetUserId, (string)$form['password'], true, $source);
            if ($pwStatus === 'reused') {
                throw new DatabaseException('Password reuse is not allowed.');
            }
            if ($pwStatus !== 'ok') {
                throw new DatabaseException('Password update failed.');
            }
        }

        $model = new UserModel();
        if (!$model->update($targetUserId, $data)) {
            throw new DatabaseException('User update failed');
        }

        return 'success';
    }

    public function deleteUser(string $userId): bool
    {
        $model = new UserModel();
        $existing = $model->find($userId); // entity check
        if (!$existing) {
            return false;
        }
        return (bool)$model->delete($userId);
    }

    /**
     * Lists (arrays) for admin/search screens. Kept as arrays for table rendering.
     */
    public function listUsers(array $opts): array
    {
        $orderBy = $this->whitelistOrderBy($opts['orderBy'] ?? 'last_updated');
        $direction = $this->normalizeDirection($opts['sortBy'] ?? 'DESC');
        $filter = trim((string)($opts['filterBy'] ?? ''));
        $userType = (string)($opts['userType'] ?? 'ALL');
        $searchType = (string)($opts['searchType'] ?? 'ALL');

        $builder = (new UserModel())->builder()->from('comp_users a')
            ->select('a.*, b.user_type_name, b.user_type_pricing')
            ->join('comp_user_type b', 'a.user_type_id = b.user_type_id', 'left')
            ->where('a.profile_completed', 'Yes')
            ->where('a.last_updated > DATE_SUB(NOW(), INTERVAL 5 YEAR)');

        if (strcasecmp($userType, 'Admin') === 0) {
            $builder->where('a.is_admin', 'Yes');
        } elseif (strcasecmp($userType, 'Editor') === 0) {
            $builder->where('a.is_editor', 'Yes');
        } elseif (strcasecmp($userType, 'Judge') === 0) {
            $builder->where('a.is_judge', 'Yes');
        } elseif (strcasecmp($userType, 'User') === 0) {
            $builder->where('a.is_admin', 'No')->where('a.is_editor', 'No')->where('a.is_judge', 'No');
        }

        if (strcasecmp($searchType, 'ALL') === 0 && $filter !== '') {
            $like = $this->escapeLike($filter);
            $builder->groupStart()
                ->like('a.email_address', $like, 'both', null, true)
                ->orLike('a.first_name', $like, 'both', null, true)
                ->orLike('a.last_name', $like, 'both', null, true)
                ->orLike('a.company_name', $like, 'both', null, true)
                ->groupEnd();
        } elseif (strcasecmp($searchType, 'alpha') === 0 && $filter !== '') {
            $builder->like('a.last_name', $this->escapeLike($filter), 'after', null, true);
        }

        $builder->orderBy("a.{$orderBy} {$direction}");

        return $builder->get()->getResultArray();
    }

    /**
     * DataTables-friendly paged listing for admin users.
     */
    public function listUsersDataTable(
        array $opts,
        int $start,
        int $length,
        string $search,
        string $orderBy,
        string $direction
    ): array {
        $start = max(0, $start);
        $length = max(1, min(1000, $length));
        $orderBy = $this->whitelistOrderBy($orderBy);
        $direction = $this->normalizeDirection($direction);
        $search = trim($search);

        $recordsTotal = $this->buildUsersDataTableBase($opts)->countAllResults();

        $filteredBuilder = $this->buildUsersDataTableBase($opts);
        if ($search !== '') {
            $like = $this->escapeLike($search);
            $filteredBuilder->groupStart()
                ->like('a.email_address', $like, 'both', null, true)
                ->orLike('a.first_name', $like, 'both', null, true)
                ->orLike('a.last_name', $like, 'both', null, true)
                ->orLike('a.company_name', $like, 'both', null, true)
                ->groupEnd();
        }

        $recordsFiltered = $filteredBuilder->countAllResults(false);

        $rows = $filteredBuilder
            ->select('a.user_id, a.first_name, a.last_name, a.email_address, a.company_name, a.user_type_id, a.is_admin, a.is_editor, a.is_judge, a.account_active, b.user_type_name')
            ->orderBy("a.{$orderBy}", $direction)
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        return [
            'recordsTotal' => (int)$recordsTotal,
            'recordsFiltered' => (int)$recordsFiltered,
            'rows' => $rows,
        ];
    }

    private function buildUsersDataTableBase(array $opts)
    {
        $builder = (new UserModel())->builder('comp_users a')
            ->join('comp_user_type b', 'a.user_type_id = b.user_type_id', 'left');

        $userType = (string)($opts['userType'] ?? 'ALL');
        if (strcasecmp($userType, 'Admin') === 0) {
            $builder->where('a.is_admin', 'Yes');
        } elseif (strcasecmp($userType, 'Editor') === 0) {
            $builder->where('a.is_editor', 'Yes');
        } elseif (strcasecmp($userType, 'Judge') === 0) {
            $builder->where('a.is_judge', 'Yes');
        } elseif (strcasecmp($userType, 'User') === 0) {
            $builder->where('a.is_admin', 'No')->where('a.is_editor', 'No')->where('a.is_judge', 'No');
        }

        $profileCompleted = (string)($opts['profileCompleted'] ?? 'Yes');
        if ($profileCompleted === 'Yes' || $profileCompleted === 'No') {
            $builder->where('a.profile_completed', $profileCompleted);
        }

        return $builder;
    }

    public function listUsersForSubmission(array $opts): array
    {
        $orderBy = $this->whitelistOrderBy($opts['orderBy'] ?? 'last_updated');
        $direction = $this->normalizeDirection($opts['sortBy'] ?? 'DESC');

        $builder = (new UserModel())->builder()->from('comp_users a')
            ->select('a.*, b.user_type_name, b.user_type_pricing')
            ->join('comp_user_type b', 'a.user_type_id = b.user_type_id', 'left')
            ->where('a.profile_completed', 'Yes')
            ->where('a.last_updated > DATE_SUB(NOW(), INTERVAL 5 YEAR)')
            ->orderBy("a.{$orderBy} {$direction}");

        return $builder->get()->getResultArray();
    }

    /**
     * Lightweight entrant search for admin submission create flow.
     */
    public function searchUsersForSubmission(string $query, int $limit = 50): array
    {
        $query = trim($query);
        $limit = max(1, min(100, $limit));
        if ($query === '' || mb_strlen($query) < 2) {
            return [];
        }

        $like = $this->escapeLike($query);
        return (new UserModel())->builder('comp_users a')
            ->select('a.user_id, a.first_name, a.last_name, a.email_address')
            ->where('a.profile_completed', 'Yes')
            ->groupStart()
                ->like('a.last_name', $like, 'both', null, true)
                ->orLike('a.first_name', $like, 'both', null, true)
                ->orLike('a.email_address', $like, 'both', null, true)
            ->groupEnd()
            ->orderBy('a.last_name', 'ASC')
            ->orderBy('a.first_name', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function exportAllUsers(array $opts): array
    {
        $orderBy = $this->whitelistOrderBy($opts['orderBy'] ?? 'last_name');
        $direction = $this->normalizeDirection($opts['sortBy'] ?? 'ASC');
        $builder = db_connect()->table('comp_users a')
            ->select([
                'a.last_name AS LastName',
                'a.first_name AS FirstName',
                'a.email_address AS EmailAddress',
                'a.company_name AS CompanyName',
                'a.title AS Title',
                'a.is_admin AS Admin',
                'a.is_editor AS Editor',
                'a.is_judge AS Judge',
                'b.user_type_name AS UserType',
                'b.user_type_pricing AS UserTypePricing',
                'a.address1 AS Address1',
                'a.address2 AS Address2',
                'a.city AS City',
                'a.state AS State',
                'a.zipcode AS Zipcode',
                'a.country AS Country',
                'a.phone AS Phone',
                'a.mobile AS Mobile',
                'a.website AS Website',
                'a.how_did_you_find_us AS HowDidYouFindUs',
                'a.how_did_you_find_us_other AS HowDidYouFindUsOther',
                'a.email_newsletters AS EmailNewsletters',
                'a.account_created AS AccountCreated',
                'a.account_active AS AccountActive',
                'a.profile_completed AS ProfileCompleted',
                'a.last_updated AS LastUpdated',
            ])
            ->join('comp_user_type b', 'a.user_type_id = b.user_type_id', 'left');

        $userType = (string)($opts['userType'] ?? 'ALL');
        if (strcasecmp($userType, 'Admin') === 0) {
            $builder->where('a.is_admin', 'Yes');
        } elseif (strcasecmp($userType, 'Editor') === 0) {
            $builder->where('a.is_editor', 'Yes');
        } elseif (strcasecmp($userType, 'Judge') === 0) {
            $builder->where('a.is_judge', 'Yes');
        } elseif (strcasecmp($userType, 'User') === 0) {
            $builder->where('a.is_admin', 'No')->where('a.is_editor', 'No')->where('a.is_judge', 'No');
        }

        $profileCompleted = (string)($opts['profileCompleted'] ?? 'ALL');
        if ($profileCompleted === 'Yes' || $profileCompleted === 'No') {
            $builder->where('a.profile_completed', $profileCompleted);
        }

        $userTypePricing = trim((string)($opts['userTypePricing'] ?? ''));
        if ($userTypePricing !== '') {
            $builder->where('b.user_type_pricing', $userTypePricing);
        }

        return $builder
            ->orderBy('a.' . $orderBy, $direction)
            ->get()
            ->getResultArray();
    }

    /**
     * Returns a User entity as an array (legacy callers). Keep for compatibility.
     */
    public function getUserDetails(string $userId): ?array
    {
        $user = (new UserModel())->find($userId);
        return $user ? $user->toArray() : null;
    }

    /**
     * Admin helper: list submissions for a specific user across all years/types.
     */
    public function getUserSubmissions(string $userId): array
    {
        return db_connect()->table('comp_entries a')
            ->select('a.entry_id, a.design_name, a.entry_status, a.winner_level, a.phase_1_payment, a.phase_2_payment, a.entry_non_finalist, a.date_created, b.comp_year, c.comp_type_name, d.winner_level_name, p1.payment_status AS phase_1_payment_status, p2.payment_status AS phase_2_payment_status')
            ->join('comp_competitions b', 'a.comp_id = b.comp_id')
            ->join('comp_type c', 'b.comp_type_id = c.comp_type_id')
            ->join('comp_winner_levels d', 'a.winner_level = d.winner_level_id', 'left')
            ->join('comp_entry_payments p1', 'p1.entry_id = a.entry_id AND p1.payment_phase = 1', 'left')
            ->join('comp_entry_payments p2', 'p2.entry_id = a.entry_id AND p2.payment_phase = 2', 'left')
            ->where('a.user_id', $userId)
            ->orderBy('b.comp_year', 'DESC')
            ->orderBy('a.date_created', 'DESC')
            ->orderBy('a.design_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Admin helper: list competition/year combos where a judge has submitted scores.
     */
    public function getUserJudgingActivity(string $userId): array
    {
        return db_connect()->table('comp_judging j')
            ->select('c.comp_id, c.comp_year, t.comp_type_name, j.entry_phase, COUNT(*) AS judging_count, MAX(j.date_judged) AS last_judged', false)
            ->join('comp_entries e', 'j.entry_id = e.entry_id')
            ->join('comp_competitions c', 'e.comp_id = c.comp_id')
            ->join('comp_type t', 'c.comp_type_id = t.comp_type_id')
            ->where('j.user_id', $userId)
            ->groupBy('c.comp_id, c.comp_year, t.comp_type_name, j.entry_phase')
            ->orderBy('c.comp_year', 'DESC')
            ->orderBy('t.comp_type_name', 'ASC')
            ->orderBy('j.entry_phase', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Lightweight lists for dropdowns (arrays on purpose).
     */
    public function getPublicUserTypes(): array
    {
        $types = (new UserTypeModel())
            ->orderBy('user_type_name', 'ASC')
            ->findAll();

        $out = [];
        foreach ($types as $t) {
            $out[] = [
                'user_type_id' => is_array($t) ? $t['user_type_id'] : (int)$t->user_type_id,
                'user_type_name' => is_array($t) ? $t['user_type_name'] : (string)$t->user_type_name,
            ];
        }
        return $out;
    }

    public function getStates(): array
    {
        $rows = (new StateModel())
            ->builder()
            ->orderBy("CASE WHEN scode = 'NA' THEN 0 ELSE 1 END", '', false)
            ->orderBy('state', 'ASC')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'scode' => is_array($r) ? $r['scode'] : (string)$r->scode,
                'state' => is_array($r) ? $r['state'] : (string)$r->state,
            ];
        }
        return $out;
    }

    public function getCountries(): array
    {
        $rows = (new CountryModel())->orderBy('country', 'ASC')->findAll();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'ccode' => is_array($r) ? $r['ccode'] : (string)$r->ccode,
                'country' => is_array($r) ? $r['country'] : (string)$r->country,
            ];
        }
        return $out;
    }

    public function getEmailListUsers(string $label): array
    {
        $label = trim($label);
        if ($label === '') {
            return [];
        }
        $targetLabels = $this->getEmailListAliases($label);
        $targetNormalized = [];
        foreach ($targetLabels as $candidate) {
            $normalized = $this->normalizeNewsletterLabel($candidate);
            if ($normalized !== '') {
                $targetNormalized[$normalized] = true;
            }
        }
        if ($targetNormalized === []) {
            return [];
        }

        $rows = (new UserModel())->builder()
            ->select('email_address, email_newsletters')
            ->where('profile_completed', 'Yes')
            ->where('last_updated > DATE_SUB(NOW(), INTERVAL 5 YEAR)')
            ->where('email_address IS NOT NULL', null, false)
            ->where('email_address !=', '')
            ->get()
            ->getResultArray();

        $emails = [];
        foreach ($rows as $row) {
            $email = trim((string)($row['email_address'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $selections = $this->extractNewsletterSelections((string)($row['email_newsletters'] ?? ''));
            foreach ($selections as $selection) {
                $selectionNormalized = $this->normalizeNewsletterLabel($selection);
                if ($selectionNormalized !== '' && isset($targetNormalized[$selectionNormalized])) {
                    $emails[strtolower($email)] = $email;
                    break;
                }
            }
        }

        if ($emails === []) {
            return [];
        }

        natcasesort($emails);
        return array_values($emails);
    }

    // ------- helpers -------

    private function extractNewsletterSelections(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $item) {
                if (!is_string($item)) {
                    continue;
                }
                $label = trim($item);
                if ($label !== '') {
                    $out[] = $label;
                }
            }

            return array_values(array_unique($out));
        }

        $parts = explode(',', $raw);
        $out = [];
        foreach ($parts as $part) {
            $label = trim($part, " \t\n\r\0\x0B\"'");
            if ($label !== '') {
                $out[] = $label;
            }
        }

        return array_values(array_unique($out));
    }

    private function normalizeNewsletterLabel(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return (string)preg_replace('/[^a-z0-9]+/', '', $value);
    }

    private function getEmailListAliases(string $label): array
    {
        $map = [
            'Student Design' => ['Student Design', 'Concept & Student', 'Student'],
            'Experience Design' => ['Experience Design', 'Experience'],
            'Graphic Design' => ['Graphic Design', 'Communication'],
            'Health & Medical Design' => ['Health & Medical Design', 'Health'],
            'Product Design' => ['Product Design', 'Product'],
            'Digital Design' => ['Digital Design', 'Digital'],
            'Spaces Design' => ['Spaces Design', 'Spaces'],
            'Transport & Mobility Design' => ['Transport & Mobility Design', 'Transport Design', 'Transport'],
            'Cleantech Design' => ['Cleantech Design', 'CleanTech Design', 'Clean Tech Design'],
            'Package Design' => ['Package Design'],
            'Spark-E Design' => ['Spark-E Design', 'Spark E Design'],
            'Wear Design' => ['Wear Design'],
            'Concept & Student' => ['Concept & Student', 'Student Design', 'Student'],
            'Experience' => ['Experience', 'Experience Design'],
            'Communication' => ['Communication', 'Graphic Design'],
            'Health' => ['Health', 'Health & Medical Design'],
            'Product' => ['Product', 'Product Design'],
            'Digital' => ['Digital', 'Digital Design'],
            'Spaces' => ['Spaces', 'Spaces Design'],
            'Transport' => ['Transport', 'Transport & Mobility Design', 'Transport Design'],
        ];

        return $map[$label] ?? [$label];
    }

    private function normalizeDirection(string $dir): string
    {
        $dir = strtoupper(trim($dir));
        return in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';
    }

    private function whitelistOrderBy(string $col): string
    {
        $allowed = [
            'last_name',
            'first_name',
            'email_address',
            'company_name',
            'last_updated',
            'account_created',
            'user_type_id',
        ];
        $col = trim($col);
        return in_array($col, $allowed, true) ? $col : 'last_updated';
    }

    private function escapeLike(string $term): string
    {
        return strtr($term, ['%' => '\%', '_' => '\_']);
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return substr(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)), 0, 35);
    }

    private function normalizeUserForm(array $form): array
    {
        if (isset($form['website']) && $form['website'] === 'http://') {
            $form['website'] = '';
        }
        return $form;
    }

    /**
     * Defense-in-depth role policy enforcement at service layer.
     * Controllers already enforce these checks, but this prevents bypasses
     * via alternate call paths.
     */
    private function enforceRoleUpdatePolicy(array $data, string $targetUserId, string $actorRole): void
    {
        if ($actorRole === 'admin') {
            return;
        }

        $wantsElevatedRole = (($data['is_admin'] ?? 'No') === 'Yes')
            || (($data['is_editor'] ?? 'No') === 'Yes');
        if ($wantsElevatedRole) {
            throw new DatabaseException('Only Admins can assign Admin or Editor roles.');
        }

        if ($actorRole === 'editor') {
            $target = (new UserModel())->builder()
                ->select('is_admin, is_editor')
                ->where('user_id', $targetUserId)
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($target && ((($target['is_admin'] ?? 'No') === 'Yes') || (($target['is_editor'] ?? 'No') === 'Yes'))) {
                throw new DatabaseException('Editors cannot update Admin or Editor accounts.');
            }
        }
    }
}
