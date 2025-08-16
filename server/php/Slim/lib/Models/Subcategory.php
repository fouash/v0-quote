<?php
/**
 * Subcategory Model
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

class Subcategory extends AppModel
{
    protected $table = 'subcategories';
    
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'keywords',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'category_id' => 'integer'
    ];

    // Validation rules
    public static $rules = [
        'category_id' => 'required|integer|exists:categories,id',
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255',
        'description' => 'string',
        'keywords' => 'string',
        'sort_order' => 'integer|min:0',
        'is_active' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_subcategories', 'subcategory_id', 'organization_id')
                    ->withPivot('expertise_level', 'years_experience', 'min_project_value', 'max_project_value', 'typical_delivery_days', 'certifications', 'description', 'is_featured')
                    ->withTimestamps();
    }

    public function organizationSubcategories()
    {
        return $this->hasMany(OrganizationSubcategory::class, 'subcategory_id');
    }

    public function serviceCapabilities()
    {
        return $this->hasMany(ServiceCapability::class, 'subcategory_id')
                    ->where('is_active', true);
    }

    public function quoteCategories()
    {
        return $this->hasMany(QuoteCategory::class, 'subcategory_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeWithOrganizationCounts($query)
    {
        return $query->withCount(['organizations' => function($q) {
            $q->where('organizations.is_active', true)
              ->where('organizations.is_verified', true);
        }]);
    }

    public function scopeFeatured($query)
    {
        return $query->whereHas('organizationSubcategories', function($q) {
            $q->where('is_featured', true);
        });
    }

    /**
     * Get subcategory by slug within category
     */
    public static function findBySlug($categorySlug, $subcategorySlug)
    {
        return self::whereHas('category', function($q) use ($categorySlug) {
                       $q->where('slug', $categorySlug);
                   })
                   ->where('slug', $subcategorySlug)
                   ->first();
    }

    /**
     * Search subcategories
     */
    public static function search($query, $categoryId = null)
    {
        $search = self::active();

        if ($categoryId) {
            $search->where('category_id', $categoryId);
        }

        return $search->where(function($q) use ($query) {
                         $q->where('name', 'ILIKE', '%' . $query . '%')
                           ->orWhere('description', 'ILIKE', '%' . $query . '%')
                           ->orWhere('keywords', 'ILIKE', '%' . $query . '%');
                     })
                     ->with('category')
                     ->ordered()
                     ->get();
    }

    /**
     * Get subcategory statistics
     */
    public function getStats()
    {
        return [
            'total_organizations' => $this->organizations()->count(),
            'verified_organizations' => $this->organizations()
                ->where('organizations.is_verified', true)
                ->count(),
            'active_organizations' => $this->organizations()
                ->where('organizations.is_active', true)
                ->where('organizations.is_verified', true)
                ->count(),
            'expert_organizations' => $this->organizationSubcategories()
                ->where('expertise_level', 'expert')
                ->count(),
            'featured_organizations' => $this->organizationSubcategories()
                ->where('is_featured', true)
                ->count(),
            'avg_project_value' => $this->organizationSubcategories()
                ->whereNotNull('min_project_value')
                ->avg('min_project_value'),
            'avg_delivery_days' => $this->organizationSubcategories()
                ->whereNotNull('typical_delivery_days')
                ->avg('typical_delivery_days')
        ];
    }

    /**
     * Get organizations in this subcategory with filters
     */
    public function getOrganizations($filters = [])
    {
        $query = $this->organizations()
                      ->where('organizations.is_active', true)
                      ->where('organizations.is_verified', true);

        // Filter by expertise level
        if (!empty($filters['expertise_level'])) {
            $query->wherePivot('expertise_level', $filters['expertise_level']);
        }

        // Filter by minimum years of experience
        if (!empty($filters['min_experience'])) {
            $query->wherePivot('years_experience', '>=', $filters['min_experience']);
        }

        // Filter by project value range
        if (!empty($filters['min_project_value'])) {
            $query->where(function($q) use ($filters) {
                $q->wherePivot('min_project_value', '<=', $filters['min_project_value'])
                  ->orWherePivotNull('min_project_value');
            });
        }

        if (!empty($filters['max_project_value'])) {
            $query->where(function($q) use ($filters) {
                $q->wherePivot('max_project_value', '>=', $filters['max_project_value'])
                  ->orWherePivotNull('max_project_value');
            });
        }

        // Filter by delivery time
        if (!empty($filters['max_delivery_days'])) {
            $query->where(function($q) use ($filters) {
                $q->wherePivot('typical_delivery_days', '<=', $filters['max_delivery_days'])
                  ->orWherePivotNull('typical_delivery_days');
            });
        }

        // Filter by location
        if (!empty($filters['city'])) {
            $query->where('organizations.city', 'ILIKE', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['country'])) {
            $query->where('organizations.country', 'ILIKE', '%' . $filters['country'] . '%');
        }

        // Filter by organization type
        if (!empty($filters['organization_type'])) {
            $query->where('organizations.organization_type', $filters['organization_type']);
        }

        // Show featured first
        if (!empty($filters['featured_first'])) {
            $query->orderBy('organization_subcategories.is_featured', 'desc');
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'expertise';
        switch ($sortBy) {
            case 'experience':
                $query->orderBy('organization_subcategories.years_experience', 'desc');
                break;
            case 'price_low':
                $query->orderBy('organization_subcategories.min_project_value', 'asc');
                break;
            case 'price_high':
                $query->orderBy('organization_subcategories.min_project_value', 'desc');
                break;
            case 'delivery':
                $query->orderBy('organization_subcategories.typical_delivery_days', 'asc');
                break;
            case 'name':
                $query->orderBy('organizations.organization_name');
                break;
            case 'expertise':
            default:
                $query->orderByRaw("
                    CASE organization_subcategories.expertise_level 
                    WHEN 'expert' THEN 4 
                    WHEN 'intermediate' THEN 3 
                    WHEN 'beginner' THEN 2 
                    ELSE 1 END DESC
                ");
                break;
        }

        return $query;
    }

    /**
     * Get subcategory breadcrumb
     */
    public function getBreadcrumb()
    {
        return [
            [
                'name' => 'Categories',
                'url' => '/categories'
            ],
            [
                'name' => $this->category->name,
                'url' => '/categories/' . $this->category->slug
            ],
            [
                'name' => $this->name,
                'url' => '/categories/' . $this->category->slug . '/' . $this->slug
            ]
        ];
    }

    /**
     * Generate SEO-friendly URL
     */
    public function getUrl()
    {
        return '/categories/' . $this->category->slug . '/' . $this->slug;
    }

    /**
     * Get related subcategories
     */
    public function getRelatedSubcategories($limit = 4)
    {
        // Get organizations in this subcategory
        $organizationIds = $this->organizations()
                               ->pluck('organizations.id')
                               ->toArray();

        if (empty($organizationIds)) {
            // Fallback to subcategories in same category
            return $this->category
                        ->subcategories()
                        ->where('id', '!=', $this->id)
                        ->limit($limit)
                        ->get();
        }

        // Find other subcategories these organizations are in
        return self::active()
                   ->where('id', '!=', $this->id)
                   ->whereHas('organizations', function($q) use ($organizationIds) {
                       $q->whereIn('organizations.id', $organizationIds);
                   })
                   ->withCount(['organizations' => function($q) use ($organizationIds) {
                       $q->whereIn('organizations.id', $organizationIds);
                   }])
                   ->with('category')
                   ->orderBy('organizations_count', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get popular subcategories
     */
    public static function getPopular($limit = 10)
    {
        return self::active()
                   ->withOrganizationCounts()
                   ->with('category')
                   ->orderBy('organizations_count', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get keywords as array
     */
    public function getKeywordsArray()
    {
        if (empty($this->keywords)) {
            return [];
        }
        
        return array_map('trim', explode(',', $this->keywords));
    }

    /**
     * Check if subcategory has active organizations
     */
    public function hasActiveOrganizations()
    {
        return $this->organizations()
                    ->where('organizations.is_active', true)
                    ->where('organizations.is_verified', true)
                    ->exists();
    }

    /**
     * Get price range for organizations in this subcategory
     */
    public function getPriceRange()
    {
        $stats = $this->organizationSubcategories()
                     ->whereNotNull('min_project_value')
                     ->selectRaw('MIN(min_project_value) as min_price, MAX(max_project_value) as max_price')
                     ->first();

        return [
            'min_price' => $stats->min_price ?? 0,
            'max_price' => $stats->max_price ?? 0
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($subcategory) {
            if (empty($subcategory->slug)) {
                $subcategory->slug = str_slug($subcategory->name);
            }
            
            if (empty($subcategory->sort_order)) {
                $maxOrder = self::where('category_id', $subcategory->category_id)
                               ->max('sort_order');
                $subcategory->sort_order = ($maxOrder ?? 0) + 1;
            }
        });
        
        static::updating(function ($subcategory) {
            if ($subcategory->isDirty('name') && empty($subcategory->slug)) {
                $subcategory->slug = str_slug($subcategory->name);
            }
        });
    }
}