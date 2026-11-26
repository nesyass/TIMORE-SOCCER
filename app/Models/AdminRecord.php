<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class AdminRecord extends Model
{
    protected $table='records';
    public const COVER_SECTIONS = ['events', 'tournaments', 'achievements', 'articles', 'gallery', 'coaches', 'site-content'];
    public const REGISTRATIONS_AWAITING_ACTION = ['pending', 'waiting_payment', 'payment_review'];

    protected $fillable=['section','title','subtitle','status','amount','date','payload','student_id','billing_month','tournament_id','team_entry_key'];
    protected $casts=['payload'=>'array','date'=>'date','amount'=>'decimal:2','billing_month'=>'date'];

    public function scopeUpcomingEvents(Builder $query, string $today): Builder
    {
        return $query->where('status', 'published')->where('date', '>=', $today)
            ->where(fn (Builder $events) => $events->whereNull('payload->content->event_status')
                ->orWhereNotIn('payload->content->event_status', ['cancelled', 'finished']));
    }

    public function student()
    {
        return $this->belongsTo(self::class, 'student_id');
    }

    public function isLinkedPayment(): bool
    {
        return isset($this->payload['registration_id']) || isset($this->payload['dues_id']) || isset($this->payload['team_registration_id']);
    }

    public function account()
    {
        return $this->hasOne(AdminStudentAccount::class, 'student_id');
    }

    public static function expireRegistrations(): int
    {
        return self::where('section', 'registrations')->whereIn('status', self::REGISTRATIONS_AWAITING_ACTION)
            ->where('created_at', '<=', now()->subDay())->update(['status' => 'expired']);
    }

    public static function expireTeamRegistrations(?int $tournamentId = null): int
    {
        return self::where('section', 'team-registrations')->whereNotNull('tournament_id')
            ->when($tournamentId !== null, fn ($query) => $query->where('tournament_id', $tournamentId))
            ->whereIn('status', ['waiting_payment', 'payment_review'])
            ->where('created_at', '<=', now()->subDay())->update(['status' => 'expired']);
    }

    public function teamEntries()
    {
        return $this->hasMany(self::class, 'tournament_id')->where('section', 'team-registrations');
    }

    public function tournament()
    {
        return $this->belongsTo(self::class, 'tournament_id');
    }

    public function tournamentCategories(): array
    {
        $text = (string) data_get($this->payload, 'content.categories');
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $text)))));
    }

    public function teamEntryKey(string $club, string $team, string $category): string
    {
        $labels = array_map(fn ($value) => Str::lower(Str::squish($value)), [$club, $team, $category]);
        return hash('sha256', json_encode([$this->id, ...$labels], JSON_THROW_ON_ERROR));
    }

    public function canOpenTeamRegistration(): bool
    {
        $info = data_get($this->payload, 'content', []);
        return $this->status === 'published' && $this->date && !in_array($info['event_status'] ?? null, ['cancelled', 'finished'], true)
            && !empty($info['opens_on']) && !empty($info['closes_on'])
            && $info['opens_on'] <= $info['closes_on'] && $info['closes_on'] <= $this->date->toDateString()
            && $info['closes_on'] >= today('Asia/Jayapura')->toDateString()
            && (float) ($info['entry_fee'] ?? 0) > 0 && $this->tournamentCategories() !== [];
    }

    public function teamRegistrationState(): string
    {
        if (!data_get($this->payload, 'content.registration_open') || !$this->canOpenTeamRegistration()) return 'closed';
        return data_get($this->payload, 'content.opens_on') > today('Asia/Jayapura')->toDateString() ? 'scheduled' : 'open';
    }
}
