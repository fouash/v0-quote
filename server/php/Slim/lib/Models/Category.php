<?php
/**
 * Category Model
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

class Category extends AppModel
{
    protected $table = 'categories';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Validation rules
    public static $rules = [
        'name' => 'required|string|max:255|unique:categories,name',
        'slug' => 'required|string|max:255|unique:categories,slug',
        'description' => 'string',
        'icon' => 'string|max:100',
        'color' => 'string|max:7',
        'sort_order' => 'integer|min:0',
        'is_active' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }

    public function allSubcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id')
                    ->orderBy('sort_order');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_categories', 'category_id', 'organization_id')
                    ->withPivot('is_primary', 'expertise_level', 'years_experience', 'certifications', 'portfolio_items')
                    ->withTimestamps();
    }

    public function organizationCategories()
    {
        return $this->hasMany(OrganizationCategory::class, 'category_id');
    }

    public function quoteCategories()
    {
        return $this->hasMany(QuoteCategory::class, 'category_id');
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

    public function scopeWithSubcategories($query)
    {
        return $query->with(['subcategories' => function($q) {
            $q->active()->ordered();
        }]);
    }

    public function scopeWithOrganizationCounts($query)
    {
        return $query->withCount(['organizations' => function($q) {
            $q->where('organizations.is_active', true)
              ->where('organizations.is_verified', true);
        }]);
    }

    /**
     * Get category by slug
     */
    public static function findBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Get categories with organization counts
     */
    public static function getWithCounts()
    {
        return self::active()
                   ->ordered()
                   ->withSubcategories()
                   ->withOrganizationCounts()
                   ->get();
    }

    /**
     * Get popular categories based on organization count
     */
    public static function getPopular($limit = 6)
    {
        return self::active()
                   ->withOrganizationCounts()
                   ->orderBy('organizations_count', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Search categories and subcategories
     */
    public static function search($query)
    {
        return self::active()
                   ->where(function($q) use ($query) {
                       $q->where('name', 'ILIKE', '%' . $query . '%')
                         ->orWhere('description', 'ILIKE', '%' . $query . '%');
                   })
                   ->orWhereHas('subcategories', function($q) use ($query) {
                       $q->active()
                         ->where(function($subQ) use ($query) {
                             $subQ->where('name', 'ILIKE', '%' . $query . '%')
                                  ->orWhere('description', 'ILIKE', '%' . $query . '%')
                                  ->orWhere('keywords', 'ILIKE', '%' . $query . '%');
                         });
                   })
                   ->with(['subcategories' => function($q) use ($query) {
                       $q->active()
                         ->where(function($subQ) use ($query) {
                             $subQ->where('name', 'ILIKE', '%' . $query . '%')
                                  ->orWhere('description', 'ILIKE', '%' . $query . '%')
                                  ->orWhere('keywords', 'ILIKE', '%' . $query . '%');
                         })
                         ->ordered();
                   }])
                   ->ordered()
                   ->get();
    }

    /**
     * Get category statistics
     */
    public function getStats()
    {
        return [
            'total_subcategories' => $this->subcategories()->count(),
            'total_organizations' => $this->organizations()->count(),
            'verified_organizations' => $this->organizations()
                ->where('organizations.is_verified', true)
                ->count(),
            'active_organizations' => $this->organizations()
                ->where('organizations.is_active', true)
                ->where('organizations.is_verified', true)
                ->count(),
            'expert_organizations' => $this->organizationCategories()
                ->where('expertise_level', 'expert')
                ->count(),
            'specialist_organizations' => $this->organizationCategories()
                ->where('expertise_level', 'specialist')
                ->count()
        ];
    }

    /**
     * Get organizations in this category with filters
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

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'expertise';
        switch ($sortBy) {
            case 'experience':
                $query->orderBy('organization_categories.years_experience', 'desc');
                break;
            case 'name':
                $query->orderBy('organizations.organization_name');
                break;
            case 'expertise':
            default:
                $query->orderByRaw("
                    CASE organization_categories.expertise_level 
                    WHEN 'specialist' THEN 4 
                    WHEN 'expert' THEN 3 
                    WHEN 'intermediate' THEN 2 
                    WHEN 'beginner' THEN 1 
                    ELSE 0 END DESC
                ");
                break;
        }

        return $query;
    }

    /**
     * Generate category tree for navigation
     */
    public static function getTree()
    {
        return self::active()
                   ->ordered()
                   ->with(['subcategories' => function($q) {
                       $q->active()->ordered();
                   }])
                   ->get()
                   ->map(function($category) {
                       return [
                           'id' => $category->id,
                           'name' => $category->name,
                           'slug' => $category->slug,
                           'icon' => $category->icon,
                           'color' => $category->color,
                           'subcategories' => $category->subcategories->map(function($sub) {
                               return [
                                   'id' => $sub->id,
                                   'name' => $sub->name,
                                   'slug' => $sub->slug,
                                   'description' => $sub->description
                               ];
                           })
                       ];
                   });
    }

    /**
     * Get category breadcrumb
     */
    public function getBreadcrumb()
    {
        return [
            [
                'name' => 'Categories',
                'url' => '/categories'
            ],
            [
                'name' => $this->name,
                'url' => '/categories/' . $this->slug
            ]
        ];
    }

    /**
     * Generate SEO-friendly URL
     */
    public function getUrl()
    {
        return '/categories/' . $this->slug;
    }

    /**
     * Get related categories based on organizations
     */
    public function getRelatedCategories($limit = 4)
    {
        // Get organizations in this category
        $organizationIds = $this->organizations()
                               ->pluck('organizations.id')
                               ->toArray();

        if (empty($organizationIds)) {
            return collect();
        }

        // Find other categories these organizations are in
        return self::active()
                   ->where('id', '!=', $this->id)
                   ->whereHas('organizations', function($q) use ($organizationIds) {
                       $q->whereIn('organizations.id', $organizationIds);
                   })
                   ->withCount(['organizations' => function($q) use ($organizationIds) {
                       $q->whereIn('organizations.id', $organizationIds);
                   }])
                   ->orderBy('organizations_count', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Check if category has active organizations
     */
    public function hasActiveOrganizations()
    {
        return $this->organizations()
                    ->where('organizations.is_active', true)
                    ->where('organizations.is_verified', true)
                    ->exists();
    }

    /**
     * Get category icon with fallback
     */
    public function getIconAttribute($value)
    {
        return $value ?: 'fa-folder';
    }

    /**
     * Get category color with fallback
     */
    public function getColorAttribute($value)
    {
        return $value ?: '#007bff';
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = str_slug($category->name);
            }
            
            if (empty($category->sort_order)) {
                $category->sort_order = self::max('sort_order') + 1;
            }
        });
        
        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = str_slug($category->name);
            }
        });
    }
}