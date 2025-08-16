<?php
/**
 * Service Capability Model
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

class ServiceCapability extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'service_capabilities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'subcategory_id',
        'capability_name',
        'capability_type',
        'proficiency_level',
        'years_experience',
        'project_count',
        'tools_technologies',
        'certifications',
        'portfolio_examples',
        'client_testimonials',
        'pricing_model',
        'hourly_rate',
        'project_rate_min',
        'project_rate_max',
        'availability_hours',
        'response_time_hours',
        'is_featured',
        'is_verified',
        'verification_date',
        'last_updated',
        'is_active'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'years_experience' => 'integer',
        'project_count' => 'integer',
        'tools_technologies' => 'array',
        'certifications' => 'array',
        'portfolio_examples' => 'array',
        'client_testimonials' => 'array',
        'hourly_rate' => 'decimal:2',
        'project_rate_min' => 'decimal:2',
        'project_rate_max' => 'decimal:2',
        'availability_hours' => 'integer',
        'response_time_hours' => 'integer',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'verification_date' => 'datetime',
        'last_updated' => 'datetime',
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
        'capability_name' => 'required|string|max:255',
        'capability_type' => 'required|in:core_service,specialized_skill,technology,methodology,tool',
        'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert,master',
        'years_experience' => 'nullable|integer|min:0|max:50',
        'project_count' => 'nullable|integer|min:0',
        'tools_technologies' => 'nullable|array',
        'certifications' => 'nullable|array',
        'portfolio_examples' => 'nullable|array',
        'pricing_model' => 'nullable|in:hourly,fixed,project_based,retainer,negotiable',
        'hourly_rate' => 'nullable|numeric|min:0',
        'project_rate_min' => 'nullable|numeric|min:0',
        'project_rate_max' => 'nullable|numeric|min:0',
        'availability_hours' => 'nullable|integer|min:1|max:168',
        'response_time_hours' => 'nullable|integer|min:1|max:72'
    ];

    /**
     * Get the organization that owns this capability
     */
    public function organization()
    {
        return $this->belongsTo('Models\Organization');
    }

    /**
     * Get the subcategory this capability belongs to
     */
    public function subcategory()
    {
        return $this->belongsTo('Models\Subcategory');
    }

    /**
     * Get the category through subcategory
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
     * Scope for active capabilities
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified capabilities
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for featured capabilities
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for specific proficiency level
     */
    public function scopeProficiencyLevel($query, $level)
    {
        return $query->where('proficiency_level', $level);
    }

    /**
     * Scope for high proficiency (advanced, expert, master)
     */
    public function scopeHighProficiency($query)
    {
        return $query->whereIn('proficiency_level', ['advanced', 'expert', 'master']);
    }

    /**
     * Scope for capability type
     */
    public function scopeCapabilityType($query, $type)
    {
        return $query->where('capability_type', $type);
    }

    /**
     * Scope for hourly rate range
     */
    public function scopeHourlyRateRange($query, $minRate, $maxRate = null)
    {
        $query->whereNotNull('hourly_rate')
              ->where('hourly_rate', '>=', $minRate);
        
        if ($maxRate) {
            $query->where('hourly_rate', '<=', $maxRate);
        }
        
        return $query;
    }

    /**
     * Scope for project rate range
     */
    public function scopeProjectRateRange($query, $minRate, $maxRate = null)
    {
        return $query->where(function($q) use ($minRate, $maxRate) {
            $q->where(function($budgetQ) use ($minRate) {
                $budgetQ->where('project_rate_min', '<=', $minRate)
                       ->orWhereNull('project_rate_min');
            });
            
            if ($maxRate) {
                $q->where(function($budgetQ) use ($maxRate) {
                    $budgetQ->where('project_rate_max', '>=', $maxRate)
                           ->orWhereNull('project_rate_max');
                });
            }
        });
    }

    /**
     * Scope for quick response time
     */
    public function scopeQuickResponse($query, $maxHours = 24)
    {
        return $query->where('response_time_hours', '<=', $maxHours);
    }

    /**
     * Get capabilities by subcategory with filtering
     */
    public static function getCapabilitiesBySubcategory($subcategoryId, $filters = [])
    {
        $query = static::with(['organization', 'subcategory'])
                      ->where('subcategory_id', $subcategoryId)
                      ->where('is_active', true);

        // Apply filters
        if (!empty($filters['proficiency_level'])) {
            $query->where('proficiency_level', $filters['proficiency_level']);
        }

        if (!empty($filters['capability_type'])) {
            $query->where('capability_type', $filters['capability_type']);
        }

        if (!empty($filters['verified_only'])) {
            $query->verified();
        }

        if (!empty($filters['featured_only'])) {
            $query->featured();
        }

        if (!empty($filters['min_hourly_rate']) || !empty($filters['max_hourly_rate'])) {
            $query->hourlyRateRange(
                $filters['min_hourly_rate'] ?? 0,
                $filters['max_hourly_rate'] ?? null
            );
        }

        if (!empty($filters['min_project_rate']) || !empty($filters['max_project_rate'])) {
            $query->projectRateRange(
                $filters['min_project_rate'] ?? 0,
                $filters['max_project_rate'] ?? null
            );
        }

        if (!empty($filters['max_response_time'])) {
            $query->quickResponse($filters['max_response_time']);
        }

        if (!empty($filters['tools_technologies'])) {
            $tools = is_array($filters['tools_technologies']) 
                   ? $filters['tools_technologies'] 
                   : [$filters['tools_technologies']];
            
            foreach ($tools as $tool) {
                $query->whereJsonContains('tools_technologies', $tool);
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'proficiency_level';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'experience':
                $query->orderBy('years_experience', $sortOrder);
                break;
            case 'project_count':
                $query->orderBy('project_count', $sortOrder);
                break;
            case 'hourly_rate':
                $query->orderBy('hourly_rate', $sortOrder);
                break;
            case 'response_time':
                $query->orderBy('response_time_hours', 'asc');
                break;
            default:
                $query->orderByRaw("
                    CASE proficiency_level 
                    WHEN 'master' THEN 5 
                    WHEN 'expert' THEN 4 
                    WHEN 'advanced' THEN 3 
                    WHEN 'intermediate' THEN 2 
                    WHEN 'beginner' THEN 1 
                    ELSE 0 END DESC
                ");
        }

        return $query->get();
    }

    /**
     * Search capabilities by keyword
     */
    public static function searchCapabilities($keyword, $filters = [])
    {
        $query = static::with(['organization', 'subcategory'])
                      ->where('is_active', true)
                      ->where(function($q) use ($keyword) {
                          $q->where('capability_name', 'ILIKE', "%{$keyword}%")
                            ->orWhereJsonContains('tools_technologies', $keyword)
                            ->orWhereHas('subcategory', function($subQ) use ($keyword) {
                                $subQ->where('name', 'ILIKE', "%{$keyword}%");
                            });
                      });

        // Apply additional filters
        if (!empty($filters['category_id'])) {
            $query->whereHas('subcategory', function($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['proficiency_level'])) {
            $query->where('proficiency_level', $filters['proficiency_level']);
        }

        return $query->orderBy('proficiency_level', 'desc')
                    ->orderBy('years_experience', 'desc')
                    ->get();
    }

    /**
     * Get capability statistics
     */
    public static function getCapabilityStats($subcategoryId = null)
    {
        $query = static::where('is_active', true);
        
        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        }
        
        $capabilities = $query->get();

        if ($capabilities->isEmpty()) {
            return [
                'total_capabilities' => 0,
                'proficiency_distribution' => [],
                'type_distribution' => [],
                'average_experience' => 0,
                'average_hourly_rate' => 0,
                'verified_count' => 0
            ];
        }

        $hourlyRates = $capabilities->whereNotNull('hourly_rate');

        return [
            'total_capabilities' => $capabilities->count(),
            'proficiency_distribution' => [
                'master' => $capabilities->where('proficiency_level', 'master')->count(),
                'expert' => $capabilities->where('proficiency_level', 'expert')->count(),
                'advanced' => $capabilities->where('proficiency_level', 'advanced')->count(),
                'intermediate' => $capabilities->where('proficiency_level', 'intermediate')->count(),
                'beginner' => $capabilities->where('proficiency_level', 'beginner')->count()
            ],
            'type_distribution' => [
                'core_service' => $capabilities->where('capability_type', 'core_service')->count(),
                'specialized_skill' => $capabilities->where('capability_type', 'specialized_skill')->count(),
                'technology' => $capabilities->where('capability_type', 'technology')->count(),
                'methodology' => $capabilities->where('capability_type', 'methodology')->count(),
                'tool' => $capabilities->where('capability_type', 'tool')->count()
            ],
            'average_experience' => round($capabilities->avg('years_experience'), 1),
            'average_hourly_rate' => round($hourlyRates->avg('hourly_rate'), 2),
            'verified_count' => $capabilities->where('is_verified', true)->count(),
            'featured_count' => $capabilities->where('is_featured', true)->count()
        ];
    }

    /**
     * Get proficiency score
     */
    public function getProficiencyScore()
    {
        $baseScores = [
            'master' => 50,
            'expert' => 40,
            'advanced' => 30,
            'intermediate' => 20,
            'beginner' => 10
        ];

        $score = $baseScores[$this->proficiency_level] ?? 0;

        // Experience bonus (up to 25 points)
        $score += min(25, $this->years_experience * 2.5);

        // Project count bonus (up to 15 points)
        $score += min(15, $this->project_count * 0.5);

        // Certification bonus (up to 10 points)
        $certCount = is_array($this->certifications) ? count($this->certifications) : 0;
        $score += min(10, $certCount * 2);

        return min(100, $score);
    }

    /**
     * Get pricing information
     */
    public function getPricingInfo()
    {
        $info = [
            'pricing_model' => $this->pricing_model,
            'hourly_rate' => $this->hourly_rate,
            'project_rate_min' => $this->project_rate_min,
            'project_rate_max' => $this->project_rate_max,
            'pricing_text' => $this->getPricingText()
        ];

        return $info;
    }

    /**
     * Get pricing as text
     */
    public function getPricingText()
    {
        switch ($this->pricing_model) {
            case 'hourly':
                return $this->hourly_rate ? "SAR {$this->hourly_rate}/hour" : 'Hourly rate available';
            
            case 'fixed':
            case 'project_based':
                if ($this->project_rate_min && $this->project_rate_max) {
                    return "SAR " . number_format($this->project_rate_min) . " - " . number_format($this->project_rate_max);
                } elseif ($this->project_rate_min) {
                    return "From SAR " . number_format($this->project_rate_min);
                } else {
                    return 'Project-based pricing';
                }
            
            case 'retainer':
                return 'Retainer available';
            
            case 'negotiable':
                return 'Negotiable pricing';
            
            default:
                return 'Contact for pricing';
        }
    }

    /**
     * Check if capability matches requirements
     */
    public function matchesRequirements($requirements)
    {
        $score = 0;
        $maxScore = 100;

        // Proficiency level match
        $requiredLevel = $requirements['proficiency_level'] ?? null;
        if ($requiredLevel) {
            $levelScores = [
                'beginner' => 1,
                'intermediate' => 2,
                'advanced' => 3,
                'expert' => 4,
                'master' => 5
            ];
            
            $currentScore = $levelScores[$this->proficiency_level] ?? 0;
            $requiredScore = $levelScores[$requiredLevel] ?? 0;
            
            if ($currentScore >= $requiredScore) {
                $score += 30;
            }
        }

        // Tools/technologies match
        $requiredTools = $requirements['tools_technologies'] ?? [];
        if (!empty($requiredTools) && is_array($this->tools_technologies)) {
            $matchingTools = array_intersect($requiredTools, $this->tools_technologies);
            $matchPercentage = count($matchingTools) / count($requiredTools);
            $score += $matchPercentage * 25;
        }

        // Experience requirement
        $requiredExperience = $requirements['min_experience'] ?? 0;
        if ($this->years_experience >= $requiredExperience) {
            $score += 20;
        }

        // Budget compatibility
        $budget = $requirements['budget'] ?? null;
        if ($budget && $this->canHandleBudget($budget)) {
            $score += 15;
        }

        // Verification bonus
        if ($this->is_verified) {
            $score += 10;
        }

        return min($score, $maxScore);
    }

    /**
     * Check if capability can handle budget
     */
    public function canHandleBudget($budget)
    {
        if ($this->pricing_model === 'hourly' && $this->hourly_rate) {
            // For hourly, assume 40 hours for comparison
            $estimatedCost = $this->hourly_rate * 40;
            return $budget >= $estimatedCost * 0.8; // 20% buffer
        }

        if ($this->project_rate_min && $budget < $this->project_rate_min) {
            return false;
        }

        if ($this->project_rate_max && $budget > $this->project_rate_max) {
            return true; // Budget is higher than max, which is good
        }

        return true; // No specific constraints
    }

    /**
     * Get related capabilities
     */
    public function getRelatedCapabilities($limit = 5)
    {
        return static::where('subcategory_id', $this->subcategory_id)
                    ->where('id', '!=', $this->id)
                    ->where('is_active', true)
                    ->whereIn('proficiency_level', ['advanced', 'expert', 'master'])
                    ->orderBy('proficiency_level', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Export to array for API
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization ? $this->organization->name : null,
            'subcategory_id' => $this->subcategory_id,
            'subcategory_name' => $this->subcategory ? $this->subcategory->name : null,
            'capability_name' => $this->capability_name,
            'capability_type' => $this->capability_type,
            'proficiency_level' => $this->proficiency_level,
            'years_experience' => $this->years_experience,
            'project_count' => $this->project_count,
            'tools_technologies' => $this->tools_technologies,
            'certifications' => $this->certifications,
            'pricing_info' => $this->getPricingInfo(),
            'pricing_text' => $this->getPricingText(),
            'availability_hours' => $this->availability_hours,
            'response_time_hours' => $this->response_time_hours,
            'proficiency_score' => $this->getProficiencyScore(),
            'is_featured' => $this->is_featured,
            'is_verified' => $this->is_verified,
            'verification_date' => $this->verification_date ? $this->verification_date->format('Y-m-d') : null,
            'is_active' => $this->is_active
        ];
    }
}