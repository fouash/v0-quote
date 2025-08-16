<?php
/**
 * Organization Category Model
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

class OrganizationCategory extends AppModel
{
    protected $table = 'organization_categories';
    
    protected $fillable = [
        'organization_id',
        'category_id',
        'is_primary',
        'expertise_level',
        'years_experience',
        'certifications',
        'portfolio_items'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'years_experience' => 'integer',
        'portfolio_items' => 'integer',
        'certifications' => 'array'
    ];

    // Expertise levels
    const LEVEL_BEGINNER = 'beginner';
    const LEVEL_INTERMEDIATE = 'intermediate';
    const LEVEL_EXPERT = 'expert';
    const LEVEL_SPECIALIST = 'specialist';

    // Validation rules
    public static $rules = [
        'organization_id' => 'required|integer|exists:organizations,id',
        'category_id' => 'required|integer|exists:categories,id',
        'is_primary' => 'boolean',
        'expertise_level' => 'required|in:beginner,intermediate,expert,specialist',
        'years_experience' => 'integer|min:0|max:50',
        'certifications' => 'array',
        'portfolio_items' => 'integer|min:0'
    ];

    /**
     * Relationships
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Scopes
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByExpertiseLevel($query, $level)
    {
        return $query->where('expertise_level', $level);
    }

    public function scopeExpert($query)
    {
        return $query->whereIn('expertise_level', [self::LEVEL_EXPERT, self::LEVEL_SPECIALIST]);
    }

    public function scopeExperienced($query, $minYears = 5)
    {
        return $query->where('years_experience', '>=', $minYears);
    }

    /**
     * Get expertise level display name
     */
    public function getExpertiseLevelDisplayAttribute()
    {
        $levels = [
            self::LEVEL_BEGINNER => 'Beginner',
            self::LEVEL_INTERMEDIATE => 'Intermediate',
            self::LEVEL_EXPERT => 'Expert',
            self::LEVEL_SPECIALIST => 'Specialist'
        ];

        return $levels[$this->expertise_level] ?? 'Unknown';
    }

    /**
     * Get expertise level badge class
     */
    public function getExpertiseBadgeClass()
    {
        $classes = [
            self::LEVEL_BEGINNER => 'badge-secondary',
            self::LEVEL_INTERMEDIATE => 'badge-info',
            self::LEVEL_EXPERT => 'badge-success',
            self::LEVEL_SPECIALIST => 'badge-warning'
        ];

        return $classes[$this->expertise_level] ?? 'badge-secondary';
    }

    /**
     * Get expertise score for ranking
     */
    public function getExpertiseScore()
    {
        $scores = [
            self::LEVEL_BEGINNER => 1,
            self::LEVEL_INTERMEDIATE => 2,
            self::LEVEL_EXPERT => 3,
            self::LEVEL_SPECIALIST => 4
        ];

        $baseScore = $scores[$this->expertise_level] ?? 1;
        
        // Add experience bonus (max 2 points)
        $experienceBonus = min(2, $this->years_experience / 10);
        
        // Add portfolio bonus (max 1 point)
        $portfolioBonus = min(1, $this->portfolio_items / 10);
        
        // Add certification bonus (max 1 point)
        $certificationBonus = min(1, count($this->certifications ?? []) / 5);

        return $baseScore + $experienceBonus + $portfolioBonus + $certificationBonus;
    }

    /**
     * Check if organization is qualified for category
     */
    public function isQualified($minExpertiseLevel = self::LEVEL_INTERMEDIATE, $minExperience = 2)
    {
        $levelOrder = [
            self::LEVEL_BEGINNER => 1,
            self::LEVEL_INTERMEDIATE => 2,
            self::LEVEL_EXPERT => 3,
            self::LEVEL_SPECIALIST => 4
        ];

        $currentLevel = $levelOrder[$this->expertise_level] ?? 1;
        $requiredLevel = $levelOrder[$minExpertiseLevel] ?? 2;

        return $currentLevel >= $requiredLevel && $this->years_experience >= $minExperience;
    }

    /**
     * Get certifications as formatted list
     */
    public function getCertificationsList()
    {
        if (empty($this->certifications)) {
            return [];
        }

        return array_map(function($cert) {
            return is_array($cert) ? $cert : ['name' => $cert];
        }, $this->certifications);
    }

    /**
     * Add certification
     */
    public function addCertification($name, $issuer = null, $date = null, $expiryDate = null)
    {
        $certifications = $this->certifications ?? [];
        
        $certification = [
            'name' => $name,
            'issuer' => $issuer,
            'date' => $date,
            'expiry_date' => $expiryDate,
            'added_at' => date('Y-m-d H:i:s')
        ];

        $certifications[] = $certification;
        $this->certifications = $certifications;
        
        return $this->save();
    }

    /**
     * Remove certification
     */
    public function removeCertification($index)
    {
        $certifications = $this->certifications ?? [];
        
        if (isset($certifications[$index])) {
            unset($certifications[$index]);
            $this->certifications = array_values($certifications);
            return $this->save();
        }
        
        return false;
    }

    /**
     * Update portfolio items count
     */
    public function updatePortfolioCount()
    {
        // This would count actual portfolio items from a portfolio table
        // For now, we'll just increment
        $this->portfolio_items = ($this->portfolio_items ?? 0) + 1;
        return $this->save();
    }

    /**
     * Get organizations by category with expertise filtering
     */
    public static function getQualifiedOrganizations($categoryId, $filters = [])
    {
        $query = self::with(['organization', 'category'])
                     ->where('category_id', $categoryId)
                     ->whereHas('organization', function($q) {
                         $q->where('is_active', true)
                           ->where('is_verified', true);
                     });

        // Filter by expertise level
        if (!empty($filters['min_expertise_level'])) {
            $levelOrder = [
                self::LEVEL_BEGINNER => 1,
                self::LEVEL_INTERMEDIATE => 2,
                self::LEVEL_EXPERT => 3,
                self::LEVEL_SPECIALIST => 4
            ];
            
            $minLevel = $levelOrder[$filters['min_expertise_level']] ?? 2;
            $query->whereIn('expertise_level', array_keys(array_filter($levelOrder, function($level) use ($minLevel) {
                return $level >= $minLevel;
            })));
        }

        // Filter by minimum experience
        if (!empty($filters['min_experience'])) {
            $query->where('years_experience', '>=', $filters['min_experience']);
        }

        // Filter by minimum portfolio items
        if (!empty($filters['min_portfolio'])) {
            $query->where('portfolio_items', '>=', $filters['min_portfolio']);
        }

        // Filter by certifications
        if (!empty($filters['has_certifications'])) {
            $query->whereNotNull('certifications')
                  ->whereRaw('JSON_LENGTH(certifications) > 0');
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'expertise_score';
        switch ($sortBy) {
            case 'experience':
                $query->orderBy('years_experience', 'desc');
                break;
            case 'portfolio':
                $query->orderBy('portfolio_items', 'desc');
                break;
            case 'name':
                $query->join('organizations', 'organization_categories.organization_id', '=', 'organizations.id')
                      ->orderBy('organizations.organization_name');
                break;
            case 'expertise_score':
            default:
                // Order by expertise level first, then experience
                $query->orderByRaw("
                    CASE expertise_level 
                    WHEN 'specialist' THEN 4 
                    WHEN 'expert' THEN 3 
                    WHEN 'intermediate' THEN 2 
                    WHEN 'beginner' THEN 1 
                    ELSE 0 END DESC
                ")->orderBy('years_experience', 'desc');
                break;
        }

        return $query;
    }

    /**
     * Get category statistics for organization
     */
    public static function getOrganizationCategoryStats($organizationId)
    {
        $categories = self::where('organization_id', $organizationId)
                          ->with('category')
                          ->get();

        return [
            'total_categories' => $categories->count(),
            'primary_categories' => $categories->where('is_primary', true)->count(),
            'expert_categories' => $categories->whereIn('expertise_level', [self::LEVEL_EXPERT, self::LEVEL_SPECIALIST])->count(),
            'avg_experience' => $categories->avg('years_experience'),
            'total_portfolio_items' => $categories->sum('portfolio_items'),
            'total_certifications' => $categories->sum(function($cat) {
                return count($cat->certifications ?? []);
            }),
            'categories' => $categories->map(function($cat) {
                return [
                    'id' => $cat->category_id,
                    'name' => $cat->category->name,
                    'is_primary' => $cat->is_primary,
                    'expertise_level' => $cat->expertise_level,
                    'years_experience' => $cat->years_experience,
                    'expertise_score' => $cat->getExpertiseScore()
                ];
            })
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($orgCategory) {
            // Ensure only one primary category per organization
            if ($orgCategory->is_primary) {
                self::where('organization_id', $orgCategory->organization_id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
        
        static::updating(function ($orgCategory) {
            // Ensure only one primary category per organization
            if ($orgCategory->is_primary && $orgCategory->isDirty('is_primary')) {
                self::where('organization_id', $orgCategory->organization_id)
                    ->where('id', '!=', $orgCategory->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }
}