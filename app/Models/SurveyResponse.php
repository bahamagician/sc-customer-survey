<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use App\Enums\SurveySource;
use Carbon\CarbonImmutable;
use Database\Factories\SurveyResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property SurveySource $source
 * @property int|null $survey_location_id
 * @property int|null $survey_subscriber_id
 * @property int|null $overall_score
 * @property int|null $found_what_looking_for
 * @property int|null $staff_knowledgeable
 * @property int|null $staff_helpful
 * @property int|null $store_clean
 * @property int|null $prices_fair
 * @property array<string, mixed> $answers
 * @property string|null $name
 * @property string|null $email
 * @property bool $follow_up_requested
 * @property FollowUpStatus|null $follow_up_status
 * @property string|null $last_question_key
 * @property CarbonImmutable|null $completed_at
 * @property string|null $ip_hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SurveyLocation|null $location
 * @property-read SurveySubscriber|null $subscriber
 */
#[Fillable([
    'source',
    'survey_location_id',
    'survey_subscriber_id',
    'overall_score',
    'found_what_looking_for',
    'staff_knowledgeable',
    'staff_helpful',
    'store_clean',
    'prices_fair',
    'answers',
    'name',
    'email',
    'follow_up_requested',
    'follow_up_status',
    'last_question_key',
    'completed_at',
    'ip_hash',
])]
class SurveyResponse extends Model
{
    /** @use HasFactory<SurveyResponseFactory> */
    use HasFactory, HasUuids;

    /**
     * MySQL cannot default a JSON column, so the default lives here.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'answers' => '[]',
    ];

    /**
     * @return BelongsTo<SurveyLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(SurveyLocation::class, 'survey_location_id');
    }

    /**
     * @return BelongsTo<SurveySubscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(SurveySubscriber::class, 'survey_subscriber_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => SurveySource::class,
            'answers' => 'array',
            'follow_up_requested' => 'boolean',
            'follow_up_status' => FollowUpStatus::class,
            'completed_at' => 'datetime',
        ];
    }
}
