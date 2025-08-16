<?php
/**
 * Organization Subcategory Model
 *
 * PHP version 7+
 *
 * @category   Model
 * @package    GetlancerV3
 * @subpackage Category
 * @author     Getlancer Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

namespace Models;

class OrganizationSubcategory extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'organization_subcategories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'subcategory_id',
        'expertise_level',
        'years_experience',
        'portfolio_items',
        'certifications',
        'min_project_value',
        'max_project_value',
        'typical_delivery_days',
        'is_featured',
        'service_description',
        'pricing_model',
        'availability_status',
        'last_project_date',
        'success_rate',
        'client_satisfaction',
        'is_active'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'years_experience' => 'integer',
        'portfolio_items' => 'integer',
        'certifications' => 'array',
        'min_project_value' => 'decimal:2',
        'max_project_value' => 'decimal:2',
        'typical_delivery_days' => 'integer',
        'is_featured' => 'boolean',
        'last_project_date' => 'date',
        'success_rate' => 'decimal:2',
        'client_satisfaction' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'organization_id' => 'required|integer|exists:organizations,id',
        'subcategory_id' => 'required|integer|exists:subcategories,id',
        'expertise_level' => 'required|in:beginner,intermediate,expert,specialist',
        'years_experience' => 'nullable|integer|min:0|max:50',
        'portfolio_items' => 'nullable|integer|min:0',
        'certifications' => 'nullable|array',
        'min_project_value' => 'nullable|numeric|min:0',
        'max_project_value' => 'nullable|numeric|min:0',
        'typical_delivery_days' => 'nullable|integer|min:1|max:365',
        'is_featured' => 'boolean',
        'service_description' => 'nullable|string|max:1000',
        'pricing_model' => 'nullable|in:fixed,hourly,project_based,retainer',
        'availability_status' => 'nullable|in:available,busy,unavailable',
        'success_rate' => 'nullable|numeric|min:0|max:100',
        'client_satisfaction' => 'nullable|numeric|min:0|max:5'
    ];

    /**
     * Get the organization that owns this subcategory relationship
     */
    public function organization()
    {
        return $this->belongsTo('Models\Organization');
    }

    /**
     * Get the subcategory
     */
    public function subcategory()
    {
        return $this->belongsTo('Models\Subcategory');
    }

    /**
     * Get the parent category through subcategory
     */
    public function category()
    {
        return $this->hasOneThrough(
            'Models\Category',
            'Models\Subcategory',
            'id',
            'id',
            'subcategory_id',
            'category_id'
        );
    }

    /**
     * Scope for active subcategories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured services
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for available organizations
     */
    public function scopeAvailable($query)
    {
        return $query->where('availability_status', 'available');
    }

    /**
     * Scope for specific expertise level
     */
    public function scopeExpertiseLevel($query, $level)
    {
        return $query->where('expertise_level', $level);
    }

    /**
     * Scope for budget range
     */
    public function scopeBudgetRange($query, $minBudget, $maxBudget = null)
    {
        return $query->where(function($q) use ($minBudget, $maxBudget) {
            $q->where(function($budgetQ) use ($minBudget) {
                $budgetQ->where('min_project_value', '<=', $minBudget)
                       ->orWhereNull('min_project_value');
            });
            
            if ($maxBudget) {
                $q->where(function($budgetQ) use ($maxBudget) {
                    $budgetQ->where('max_project_value', '>=', $maxBudget)
                           ->orWhereNull('max_project_value');
                });
            }
        });
    }

    /**
     * Scope for delivery timeline
     */
    public function scopeDeliveryTimeline($query, $maxDays)
    {
        return $query->where(function($q) use ($maxDays) {
            $q->where('typical_delivery_days', '<=', $maxDays)
              ->orWhereNull('typical_delivery_days');
        });
    }

    /**
     * Scope for high success rate
     */
    public function scopeHighSuccessRate($query, $minRate = 80)
    {
        return $query->where('success_rate', '>=', $minRate);
    }

    /**
     * Scope for high satisfaction
     */
    public function scopeHighSatisfaction($query, $minRating = 4.0)
    {
        return $query->where('client_satisfaction', '>=', $minRating);
    }

    /**
     * Get organizations by subcategory with filtering
     */
    public static function getOrganizationsBySubcategory($subcategoryId, $filters = [])
    {
        $query = static::with(['organization', 'subcategory'])
                      ->where('subcategory_id', $subcategoryId)
                      ->where('is_active', true);

        // Apply filters
        if (!empty($filters['expertise_level'])) {
            $query->where('expertise_level', $filters['expertise_level']);
        }

        if (!empty($filters['min_budget']) || !empty($filters['max_budget'])) {
            $query->budgetRange($filters['min_budget'] ?? 0, $filters['max_budget'] ?? null);
        }

        if (!empty($filters['max_delivery_days'])) {
            $query->deliveryTimeline($filters['max_delivery_days']);
        }

        if (!empty($filters['availability'])) {
            $query->where('availability_status', $filters['availability']);
        }

        if (!empty($filters['featured_only'])) {
            $query->featured();
        }

        if (!empty($filters['min_success_rate'])) {
            $query->highSuccessRate($filters['min_success_rate']);
        }

        if (!empty($filters['min_satisfaction'])) {
            $query->highSatisfaction($filters['min_satisfaction']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'expertise_level';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'experience':
                $query->orderBy('years_experience', $sortOrder);
                break;
            case 'success_rate':
                $query->orderBy('success_rate', $sortOrder);
                break;
            case 'satisfaction':
                $query->orderBy('client_satisfaction', $sortOrder);
                break;
            case 'price_low':
                $query->orderBy('min_project_value', 'asc');
                break;
            case 'price_high':
                $query->orderBy('max_project_value', 'desc');
                break;
            case 'delivery_time':
                $query->orderBy('typical_delivery_days', 'asc');
                break;
            default:
                $query->orderByRaw("
                    CASE expertise_level 
                    WHEN 'specialist' THEN 4 
                    WHEN 'expert' THEN 3 
                    WHEN 'intermediate' THEN 2 
                    WHEN 'beginner' THEN 1 
                    ELSE 0 END DESC
                ");
        }

        return $query->get();
    }

    /**
     * Get subcategory statistics
     */
    public static function getSubcategoryStats($subcategoryId)
    {
        $organizations = static::where('subcategory_id', $subcategoryId)
                              ->where('is_active', true)
                              ->get();

        if ($organizations->isEmpty()) {
            return [
                'total_organizations' => 0,
                'expertise_distribution' => [],
                'average_experience' => 0,
                'price_range' => ['min' => 0, 'max' => 0],
                'average_delivery_days' => 0,
                'average_success_rate' => 0,
                'average_satisfaction' => 0
            ];
        }

        $priceRanges = $organizations->whereNotNull('min_project_value');
        $deliveryTimes = $organizations->whereNotNull('typical_delivery_days');
        $successRates = $organizations->whereNotNull('success_rate');
        $satisfactionRates = $organizations->whereNotNull('client_satisfaction');

        return [
            'total_organizations' => $organizations->count(),
            'expertise_distribution' => [
                'specialist' => $organizations->where('expertise_level', 'specialist')->count(),
                'expert' => $organizations->where('expertise_level', 'expert')->count(),
                'intermediate' => $organizations->where('expertise_level', 'intermediate')->count(),
                'beginner' => $organizations->where('expertise_level', 'beginner')->count()
            ],
            'average_experience' => round($organizations->avg('years_experience'), 1),
            'price_range' => [
                'min' => $priceRanges->min('min_project_value'),
                'max' => $priceRanges->max('max_project_value')
            ],
            'average_delivery_days' => round($deliveryTimes->avg('typical_delivery_days'), 1),
            'average_success_rate' => round($successRates->avg('success_rate'), 1),
            'average_satisfaction' => round($satisfactionRates->avg('client_satisfaction'), 2),
            'featured_count' => $organizations->where('is_featured', true)->count(),
            'available_count' => $organizations->where('availability_status', 'available')->count()
        ];
    }

    /**
     * Update organization's subcategory specialization
     */
    public static function updateSpecialization($organizationId, $subcategoryId, $data)
    {
        return static::updateOrCreate([
            'organization_id' => $organizationId,
            'subcategory_id' => $subcategoryId
        ], array_merge($data, [
            'is_active' => true
        ]));
    }

    /**
     * Get organization's expertise score for subcategory
     */
    public function getExpertiseScore()
    {
        $score = 0;

        // Base score by expertise level
        $expertiseScores = [
            'specialist' => 40,
            'expert' => 30,
            'intermediate' => 20,
            'beginner' => 10
        ];
        $score += $expertiseScores[$this->expertise_level] ?? 0;

        // Experience bonus (up to 20 points)
        $score += min(20, $this->years_experience * 2);

        // Portfolio bonus (up to 15 points)
        $score += min(15, $this->portfolio_items * 1.5);

        // Certification bonus (up to 10 points)
        $certCount = is_array($this->certifications) ? count($this->certifications) : 0;
        $score += min(10, $certCount * 2);

        // Success rate bonus (up to 10 points)
        if ($this->success_rate) {
            $score += ($this->success_rate / 100) * 10;
        }

        // Satisfaction bonus (up to 5 points)
        if ($this->client_satisfaction) {
            $score += ($this->client_satisfaction / 5) * 5;
        }

        return min(100, $score);
    }

    /**
     * Check if organization can handle project budget
     */
    public function canHandleBudget($budget)
    {
        if (!$this->min_project_value && !$this->max_project_value) {
            return true; // No budget restrictions
        }

        if ($this->min_project_value && $budget < $this->min_project_value) {
            return false;
        }

        if ($this->max_project_value && $budget > $this->max_project_value) {
            return false;
        }

        return true;
    }

    /**
     * Check if organization can meet deadline
     */
    public function canMeetDeadline($deadline)
    {
        if (!$this->typical_delivery_days) {
            return true; // No delivery time specified
        }

        $deadlineDate = is_string($deadline) ? new \DateTime($deadline) : $deadline;
        $now = new \DateTime();
        $daysUntilDeadline = $now->diff($deadlineDate)->days;

        return $this->typical_delivery_days <= $daysUntilDeadline;
    }

    /**
     * Get pricing information
     */
    public function getPricingInfo()
    {
        return [
            'pricing_model' => $this->pricing_model,
            'min_project_value' => $this->min_project_value,
            'max_project_value' => $this->max_project_value,
            'budget_range' => $this->getBudgetRangeText(),
            'typical_delivery_days' => $this->typical_delivery_days
        ];
    }

    /**
     * Get budget range as text
     */
    public function getBudgetRangeText()
    {
        if (!$this->min_project_value && !$this->max_project_value) {
            return 'Flexible pricing';
        }

        if ($this->min_project_value && $this->max_project_value) {
            return "SAR " . number_format($this->min_project_value) . " - " . number_format($this->max_project_value);
        }

        if ($this->min_project_value) {
            return "From SAR " . number_format($this->min_project_value);
        }

        if ($this->max_project_value) {
            return "Up to SAR " . number_format($this->max_project_value);
        }

        return 'Contact for pricing';
    }

    /**
     * Get availability status with color
     */
    public function getAvailabilityStatus()
    {
        $statuses = [
            'available' => ['text' => 'Available', 'color' => 'green'],
            'busy' => ['text' => 'Busy', 'color' => 'orange'],
            'unavailable' => ['text' => 'Unavailable', 'color' => 'red']
        ];

        return $statuses[$this->availability_status] ?? ['text' => 'Unknown', 'color' => 'gray'];
    }

    /**
     * Export to array for API
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'subcategory_id' => $this->subcategory_id,
            'subcategory_name' => $this->subcategory ? $this->subcategory->name : null,
            'expertise_level' => $this->expertise_level,
            'years_experience' => $this->years_experience,
            'portfolio_items' => $this->portfolio_items,
            'certifications' => $this->certifications,
            'pricing_info' => $this->getPricingInfo(),
            'budget_range_text' => $this->getBudgetRangeText(),
            'typical_delivery_days' => $this->typical_delivery_days,
            'is_featured' => $this->is_featured,
            'service_description' => $this->service_description,
            'availability_status' => $this->getAvailabilityStatus(),
            'success_rate' => $this->success_rate,
            'client_satisfaction' => $this->client_satisfaction,
            'expertise_score' => $this->getExpertiseScore(),
            'is_active' => $this->is_active
        ];
    }
}