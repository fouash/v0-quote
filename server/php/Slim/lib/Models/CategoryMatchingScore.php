<?php
/**
 * Category Matching Score Model
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

class CategoryMatchingScore extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'category_matching_scores';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'category_id',
        'subcategory_id',
        'score',
        'factors',
        'last_calculated'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'score' => 'integer',
        'factors' => 'array',
        'last_calculated' => 'datetime'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'organization_id' => 'required|integer|exists:organizations,id',
        'category_id' => 'required|integer|exists:categories,id',
        'subcategory_id' => 'nullable|integer|exists:subcategories,id',
        'score' => 'required|integer|min:0|max:100',
        'factors' => 'nullable|array'
    ];

    /**
     * Get the organization that owns the score
     */
    public function organization()
    {
        return $this->belongsTo('Models\Organization');
    }

    /**
     * Get the category for this score
     */
    public function category()
    {
        return $this->belongsTo('Models\Category');
    }

    /**
     * Get the subcategory for this score
     */
    public function subcategory()
    {
        return $this->belongsTo('Models\Subcategory');
    }

    /**
     * Scope for high scoring organizations
     */
    public function scopeHighScore($query, $minScore = 70)
    {
        return $query->where('score', '>=', $minScore);
    }

    /**
     * Scope for recent calculations
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('last_calculated', '>=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
    }

    /**
     * Scope for specific category
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for specific subcategory
     */
    public function scopeForSubcategory($query, $subcategoryId)
    {
        return $query->where('subcategory_id', $subcategoryId);
    }

    /**
     * Get top organizations for a category
     */
    public static function getTopOrganizations($categoryId, $limit = 10, $subcategoryId = null)
    {
        $query = static::with(['organization', 'category'])
                      ->where('category_id', $categoryId)
                      ->orderBy('score', 'desc');

        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get organization's score for a category
     */
    public static function getOrganizationScore($organizationId, $categoryId, $subcategoryId = null)
    {
        $query = static::where('organization_id', $organizationId)
                      ->where('category_id', $categoryId);

        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        }

        return $query->first();
    }

    /**
     * Update or create score
     */
    public static function updateScore($organizationId, $categoryId, $score, $factors = [], $subcategoryId = null)
    {
        return static::updateOrCreate([
            'organization_id' => $organizationId,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId
        ], [
            'score' => $score,
            'factors' => $factors,
            'last_calculated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get scores that need recalculation
     */
    public static function getStaleScores($days = 30)
    {
        return static::where('last_calculated', '<', date('Y-m-d H:i:s', strtotime("-{$days} days")))
                    ->orWhereNull('last_calculated')
                    ->get();
    }

    /**
     * Get category statistics
     */
    public static function getCategoryStats($categoryId)
    {
        $scores = static::where('category_id', $categoryId)->get();
        
        if ($scores->isEmpty()) {
            return [
                'total_organizations' => 0,
                'average_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'score_distribution' => []
            ];
        }

        $scoreValues = $scores->pluck('score');
        
        return [
            'total_organizations' => $scores->count(),
            'average_score' => round($scoreValues->avg(), 2),
            'highest_score' => $scoreValues->max(),
            'lowest_score' => $scoreValues->min(),
            'score_distribution' => [
                'excellent' => $scores->where('score', '>=', 90)->count(),
                'good' => $scores->whereBetween('score', [70, 89])->count(),
                'average' => $scores->whereBetween('score', [50, 69])->count(),
                'below_average' => $scores->where('score', '<', 50)->count()
            ]
        ];
    }

    /**
     * Get organization's ranking in category
     */
    public function getRanking()
    {
        return static::where('category_id', $this->category_id)
                    ->where('score', '>', $this->score)
                    ->count() + 1;
    }

    /**
     * Get percentile ranking
     */
    public function getPercentileRanking()
    {
        $totalOrgs = static::where('category_id', $this->category_id)->count();
        $ranking = $this->getRanking();
        
        if ($totalOrgs == 0) return 0;
        
        return round((($totalOrgs - $ranking + 1) / $totalOrgs) * 100, 2);
    }

    /**
     * Check if score needs recalculation
     */
    public function needsRecalculation($days = 30)
    {
        if (!$this->last_calculated) {
            return true;
        }
        
        $threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->last_calculated < $threshold;
    }

    /**
     * Get score breakdown
     */
    public function getScoreBreakdown()
    {
        $factors = $this->factors ?? [];
        
        return [
            'total_score' => $this->score,
            'expertise_score' => $factors['expertise'] ?? 0,
            'experience_score' => $factors['experience'] ?? 0,
            'portfolio_score' => $factors['portfolio'] ?? 0,
            'certification_score' => $factors['certifications'] ?? 0,
            'quality_score' => $factors['quality'] ?? 0,
            'last_updated' => $this->last_calculated ? $this->last_calculated->format('Y-m-d H:i:s') : null
        ];
    }

    /**
     * Bulk update scores for multiple organizations
     */
    public static function bulkUpdateScores($scores)
    {
        $updated = 0;
        
        foreach ($scores as $scoreData) {
            static::updateOrCreate([
                'organization_id' => $scoreData['organization_id'],
                'category_id' => $scoreData['category_id'],
                'subcategory_id' => $scoreData['subcategory_id'] ?? null
            ], [
                'score' => $scoreData['score'],
                'factors' => $scoreData['factors'] ?? [],
                'last_calculated' => date('Y-m-d H:i:s')
            ]);
            $updated++;
        }
        
        return $updated;
    }

    /**
     * Get trending organizations (improving scores)
     */
    public static function getTrendingOrganizations($categoryId, $days = 30, $limit = 10)
    {
        // This would require historical score tracking
        // For now, return recent high-scoring organizations
        return static::with('organization')
                    ->where('category_id', $categoryId)
                    ->where('score', '>=', 70)
                    ->where('last_calculated', '>=', date('Y-m-d H:i:s', strtotime("-{$days} days")))
                    ->orderBy('score', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Export scores to array
     */
    public function toExportArray()
    {
        return [
            'organization_id' => $this->organization_id,
            'organization_name' => $this->organization ? $this->organization->name : null,
            'category_id' => $this->category_id,
            'category_name' => $this->category ? $this->category->name : null,
            'subcategory_id' => $this->subcategory_id,
            'subcategory_name' => $this->subcategory ? $this->subcategory->name : null,
            'score' => $this->score,
            'ranking' => $this->getRanking(),
            'percentile' => $this->getPercentileRanking(),
            'factors' => $this->factors,
            'last_calculated' => $this->last_calculated ? $this->last_calculated->format('Y-m-d H:i:s') : null
        ];
    }
}