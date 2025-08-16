<?php
/**
 * Quote Category Model
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

class QuoteCategory extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'quote_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quote_id',
        'category_id',
        'subcategory_id',
        'priority',
        'requirements',
        'budget_allocation',
        'timeline_days',
        'complexity_level',
        'skill_requirements',
        'deliverables',
        'matching_criteria',
        'auto_match_enabled',
        'manual_selection_only',
        'preferred_expertise_level',
        'location_preference',
        'language_requirements',
        'certification_requirements',
        'is_primary',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'priority' => 'integer',
        'requirements' => 'array',
        'budget_allocation' => 'decimal:2',
        'timeline_days' => 'integer',
        'skill_requirements' => 'array',
        'deliverables' => 'array',
        'matching_criteria' => 'array',
        'auto_match_enabled' => 'boolean',
        'manual_selection_only' => 'boolean',
        'location_preference' => 'array',
        'language_requirements' => 'array',
        'certification_requirements' => 'array',
        'is_primary' => 'boolean'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'quote_id' => 'required|integer|exists:quotes,id',
        'category_id' => 'required|integer|exists:categories,id',
        'subcategory_id' => 'nullable|integer|exists:subcategories,id',
        'priority' => 'nullable|integer|min:1|max:10',
        'budget_allocation' => 'nullable|numeric|min:0',
        'timeline_days' => 'nullable|integer|min:1|max:365',
        'complexity_level' => 'nullable|in:simple,moderate,complex,expert_level',
        'preferred_expertise_level' => 'nullable|in:beginner,intermediate,expert,specialist',
        'auto_match_enabled' => 'boolean',
        'manual_selection_only' => 'boolean',
        'is_primary' => 'boolean'
    ];

    /**
     * Get the quote that owns this category assignment
     */
    public function quote()
    {
        return $this->belongsTo('Models\Quote');
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo('Models\Category');
    }

    /**
     * Get the subcategory
     */
    public function subcategory()
    {
        return $this->belongsTo('Models\Subcategory');
    }

    /**
     * Get the user who created this assignment
     */
    public function creator()
    {
        return $this->belongsTo('Models\User', 'created_by');
    }

    /**
     * Get the user who last updated this assignment
     */
    public function updater()
    {
        return $this->belongsTo('Models\User', 'updated_by');
    }

    /**
     * Scope for primary categories
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for auto-match enabled
     */
    public function scopeAutoMatch($query)
    {
        return $query->where('auto_match_enabled', true);
    }

    /**
     * Scope for manual selection only
     */
    public function scopeManualOnly($query)
    {
        return $query->where('manual_selection_only', true);
    }

    /**
     * Scope for specific complexity level
     */
    public function scopeComplexityLevel($query, $level)
    {
        return $query->where('complexity_level', $level);
    }

    /**
     * Scope for budget range
     */
    public function scopeBudgetRange($query, $minBudget, $maxBudget = null)
    {
        $query->where('budget_allocation', '>=', $minBudget);
        
        if ($maxBudget) {
            $query->where('budget_allocation', '<=', $maxBudget);
        }
        
        return $query;
    }

    /**
     * Get quote categories with matching organizations
     */
    public static function getQuoteCategoriesWithMatches($quoteId)
    {
        $quoteCategories = static::with(['category', 'subcategory'])
                                ->where('quote_id', $quoteId)
                                ->orderBy('priority', 'asc')
                                ->get();

        $results = [];
        
        foreach ($quoteCategories as $quoteCategory) {
            $matches = $quoteCategory->findMatchingOrganizations();
            
            $results[] = [
                'quote_category' => $quoteCategory,
                'matching_organizations' => $matches,
                'match_count' => count($matches)
            ];
        }

        return $results;
    }

    /**
     * Find matching organizations for this quote category
     */
    public function findMatchingOrganizations($limit = 20)
    {
        // Prepare quote data for matching engine
        $quoteData = [
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'budget' => $this->budget_allocation,
            'deadline' => $this->timeline_days ? date('Y-m-d', strtotime("+{$this->timeline_days} days")) : null,
            'requirements' => $this->getMatchingRequirements(),
            'location' => $this->location_preference ?? []
        ];

        // Use the matching engine
        return QuoteMatchingEngine::findMatches($quoteData, [
            'limit' => $limit,
            'min_score' => $this->getMinimumMatchingScore()
        ]);
    }

    /**
     * Get matching requirements formatted for the matching engine
     */
    public function getMatchingRequirements()
    {
        $requirements = [];

        if ($this->certification_requirements) {
            $requirements['certifications'] = $this->certification_requirements;
        }

        if ($this->language_requirements) {
            $requirements['languages'] = $this->language_requirements;
        }

        if ($this->skill_requirements) {
            $requirements['skills'] = $this->skill_requirements;
        }

        if ($this->preferred_expertise_level) {
            $requirements['min_expertise_level'] = $this->preferred_expertise_level;
        }

        return $requirements;
    }

    /**
     * Get minimum matching score based on complexity and requirements
     */
    public function getMinimumMatchingScore()
    {
        $baseScore = 50;

        // Adjust based on complexity
        switch ($this->complexity_level) {
            case 'expert_level':
                $baseScore = 80;
                break;
            case 'complex':
                $baseScore = 70;
                break;
            case 'moderate':
                $baseScore = 60;
                break;
            case 'simple':
                $baseScore = 40;
                break;
        }

        // Adjust based on manual selection preference
        if ($this->manual_selection_only) {
            $baseScore += 10;
        }

        // Adjust based on certification requirements
        if (!empty($this->certification_requirements)) {
            $baseScore += 5;
        }

        return min($baseScore, 90);
    }

    /**
     * Auto-assign organizations based on matching criteria
     */
    public function autoAssignOrganizations($maxAssignments = 5)
    {
        if (!$this->auto_match_enabled || $this->manual_selection_only) {
            return [];
        }

        $matches = $this->findMatchingOrganizations($maxAssignments * 2);
        $assignments = [];

        $assignedCount = 0;
        foreach ($matches as $match) {
            if ($assignedCount >= $maxAssignments) {
                break;
            }

            $organization = $match['organization'];
            $score = $match['score'];

            // Check if organization meets minimum criteria
            if ($score['total_score'] >= $this->getMinimumMatchingScore()) {
                // Create quote assignment (this would be in a separate QuoteAssignment model)
                $assignments[] = [
                    'quote_id' => $this->quote_id,
                    'quote_category_id' => $this->id,
                    'organization_id' => $organization->id,
                    'matching_score' => $score['total_score'],
                    'assignment_type' => 'auto_matched',
                    'match_reasons' => $match['match_reasons'],
                    'assigned_at' => date('Y-m-d H:i:s')
                ];

                $assignedCount++;
            }
        }

        return $assignments;
    }

    /**
     * Get category requirements summary
     */
    public function getRequirementsSummary()
    {
        $summary = [];

        if ($this->complexity_level) {
            $summary['complexity'] = ucfirst(str_replace('_', ' ', $this->complexity_level));
        }

        if ($this->preferred_expertise_level) {
            $summary['expertise'] = ucfirst($this->preferred_expertise_level);
        }

        if ($this->budget_allocation) {
            $summary['budget'] = 'SAR ' . number_format($this->budget_allocation);
        }

        if ($this->timeline_days) {
            $summary['timeline'] = $this->timeline_days . ' days';
        }

        if (!empty($this->skill_requirements)) {
            $summary['skills'] = implode(', ', array_slice($this->skill_requirements, 0, 3));
            if (count($this->skill_requirements) > 3) {
                $summary['skills'] .= ' +' . (count($this->skill_requirements) - 3) . ' more';
            }
        }

        if (!empty($this->certification_requirements)) {
            $summary['certifications'] = count($this->certification_requirements) . ' required';
        }

        if (!empty($this->language_requirements)) {
            $summary['languages'] = implode(', ', $this->language_requirements);
        }

        return $summary;
    }

    /**
     * Get deliverables summary
     */
    public function getDeliverablesSummary()
    {
        if (empty($this->deliverables)) {
            return 'No specific deliverables defined';
        }

        if (count($this->deliverables) <= 3) {
            return implode(', ', $this->deliverables);
        }

        return implode(', ', array_slice($this->deliverables, 0, 3)) . 
               ' +' . (count($this->deliverables) - 3) . ' more';
    }

    /**
     * Check if category can be auto-matched
     */
    public function canAutoMatch()
    {
        return $this->auto_match_enabled && 
               !$this->manual_selection_only && 
               $this->category_id && 
               ($this->subcategory_id || !empty($this->skill_requirements));
    }

    /**
     * Get matching statistics
     */
    public function getMatchingStats()
    {
        $matches = $this->findMatchingOrganizations(100); // Get more for stats
        
        if (empty($matches)) {
            return [
                'total_matches' => 0,
                'high_score_matches' => 0,
                'average_score' => 0,
                'score_distribution' => []
            ];
        }

        $scores = array_column(array_column($matches, 'score'), 'total_score');
        
        return [
            'total_matches' => count($matches),
            'high_score_matches' => count(array_filter($scores, function($score) {
                return $score >= 80;
            })),
            'average_score' => round(array_sum($scores) / count($scores), 2),
            'score_distribution' => [
                'excellent' => count(array_filter($scores, function($s) { return $s >= 90; })),
                'good' => count(array_filter($scores, function($s) { return $s >= 70 && $s < 90; })),
                'average' => count(array_filter($scores, function($s) { return $s >= 50 && $s < 70; })),
                'below_average' => count(array_filter($scores, function($s) { return $s < 50; }))
            ]
        ];
    }

    /**
     * Update matching criteria
     */
    public function updateMatchingCriteria($criteria)
    {
        $this->matching_criteria = array_merge($this->matching_criteria ?? [], $criteria);
        $this->save();
        
        return $this;
    }

    /**
     * Clone category for another quote
     */
    public function cloneForQuote($newQuoteId, $userId)
    {
        $clone = $this->replicate();
        $clone->quote_id = $newQuoteId;
        $clone->created_by = $userId;
        $clone->updated_by = $userId;
        $clone->is_primary = false; // New quote should set primary manually
        $clone->save();
        
        return $clone;
    }

    /**
     * Export to array for API
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'quote_id' => $this->quote_id,
            'category_id' => $this->category_id,
            'category_name' => $this->category ? $this->category->name : null,
            'subcategory_id' => $this->subcategory_id,
            'subcategory_name' => $this->subcategory ? $this->subcategory->name : null,
            'priority' => $this->priority,
            'requirements_summary' => $this->getRequirementsSummary(),
            'deliverables_summary' => $this->getDeliverablesSummary(),
            'budget_allocation' => $this->budget_allocation,
            'timeline_days' => $this->timeline_days,
            'complexity_level' => $this->complexity_level,
            'preferred_expertise_level' => $this->preferred_expertise_level,
            'auto_match_enabled' => $this->auto_match_enabled,
            'manual_selection_only' => $this->manual_selection_only,
            'can_auto_match' => $this->canAutoMatch(),
            'is_primary' => $this->is_primary,
            'matching_stats' => $this->getMatchingStats(),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null
        ];
    }

    /**
     * Get detailed export with all data
     */
    public function toDetailedArray()
    {
        return array_merge($this->toApiArray(), [
            'requirements' => $this->requirements,
            'skill_requirements' => $this->skill_requirements,
            'deliverables' => $this->deliverables,
            'matching_criteria' => $this->matching_criteria,
            'location_preference' => $this->location_preference,
            'language_requirements' => $this->language_requirements,
            'certification_requirements' => $this->certification_requirements,
            'minimum_matching_score' => $this->getMinimumMatchingScore()
        ]);
    }
}
