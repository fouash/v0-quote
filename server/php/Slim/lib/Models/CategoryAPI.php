<?php
/**
 * Category API
 *
 * PHP version 7+
 *
 * @category   API
 * @package    GetlancerV3
 * @subpackage Category
 * @author     Getlancer Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

namespace Models;

class CategoryAPI extends AppModel
{
    /**
     * Get all categories with subcategories
     */
    public static function getCategories($request, $response, $args)
    {
        try {
            $queryParams = $request->getQueryParams();
            $includeSubcategories = isset($queryParams['include_subcategories']) ? 
                                  filter_var($queryParams['include_subcategories'], FILTER_VALIDATE_BOOLEAN) : true;
            $includeStats = isset($queryParams['include_stats']) ? 
                          filter_var($queryParams['include_stats'], FILTER_VALIDATE_BOOLEAN) : false;

            $query = Category::active();

            if ($includeSubcategories) {
                $query->with(['subcategories' => function($q) {
                    $q->where('is_active', true)->orderBy('display_order');
                }]);
            }

            $categories = $query->orderBy('display_order')->get();

            $result = [];
            foreach ($categories as $category) {
                $categoryData = $category->toApiArray();
                
                if ($includeStats) {
                    $categoryData['statistics'] = [
                        'organization_count' => $category->organizations()->count(),
                        'subcategory_count' => $category->subcategories()->where('is_active', true)->count(),
                        'average_matching_score' => CategoryMatchingScore::where('category_id', $category->id)->avg('score') ?? 0
                    ];
                }
                
                $result[] = $categoryData;
            }

            return renderWithJson($result, 'Categories retrieved successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to retrieve categories', $e->getMessage(), 1);
        }
    }

    /**
     * Get category by ID
     */
    public static function getCategory($request, $response, $args)
    {
        try {
            $categoryId = $args['id'];
            $queryParams = $request->getQueryParams();
            $includeSubcategories = isset($queryParams['include_subcategories']) ? 
                                  filter_var($queryParams['include_subcategories'], FILTER_VALIDATE_BOOLEAN) : true;

            $query = Category::where('id', $categoryId)->where('is_active', true);

            if ($includeSubcategories) {
                $query->with(['subcategories' => function($q) {
                    $q->where('is_active', true)->orderBy('display_order');
                }]);
            }

            $category = $query->first();

            if (!$category) {
                return renderWithJson(null, 'Category not found', '', 1);
            }

            $categoryData = $category->toDetailedArray();
            $categoryData['statistics'] = [
                'organization_count' => $category->organizations()->count(),
                'subcategory_count' => $category->subcategories()->where('is_active', true)->count(),
                'top_organizations' => CategoryMatchingScore::getTopOrganizations($categoryId, 5)
            ];

            return renderWithJson($categoryData, 'Category retrieved successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to retrieve category', $e->getMessage(), 1);
        }
    }

    /**
     * Get subcategories for a category
     */
    public static function getSubcategories($request, $response, $args)
    {
        try {
            $categoryId = $args['category_id'];
            $queryParams = $request->getQueryParams();
            $includeStats = isset($queryParams['include_stats']) ? 
                          filter_var($queryParams['include_stats'], FILTER_VALIDATE_BOOLEAN) : false;

            $subcategories = Subcategory::where('category_id', $categoryId)
                                       ->where('is_active', true)
                                       ->orderBy('display_order')
                                       ->get();

            $result = [];
            foreach ($subcategories as $subcategory) {
                $subcategoryData = $subcategory->toApiArray();
                
                if ($includeStats) {
                    $subcategoryData['statistics'] = OrganizationSubcategory::getSubcategoryStats($subcategory->id);
                }
                
                $result[] = $subcategoryData;
            }

            return renderWithJson($result, 'Subcategories retrieved successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to retrieve subcategories', $e->getMessage(), 1);
        }
    }

    /**
     * Get organizations by category
     */
    public static function getOrganizationsByCategory($request, $response, $args)
    {
        try {
            $categoryId = $args['category_id'];
            $queryParams = $request->getQueryParams();
            
            $filters = [
                'expertise_level' => $queryParams['expertise_level'] ?? null,
                'min_budget' => isset($queryParams['min_budget']) ? floatval($queryParams['min_budget']) : null,
                'max_budget' => isset($queryParams['max_budget']) ? floatval($queryParams['max_budget']) : null,
                'max_delivery_days' => isset($queryParams['max_delivery_days']) ? intval($queryParams['max_delivery_days']) : null,
                'location' => $queryParams['location'] ?? null,
                'featured_only' => isset($queryParams['featured_only']) ? filter_var($queryParams['featured_only'], FILTER_VALIDATE_BOOLEAN) : false,
                'sort_by' => $queryParams['sort_by'] ?? 'expertise_level',
                'sort_order' => $queryParams['sort_order'] ?? 'desc',
                'limit' => isset($queryParams['limit']) ? intval($queryParams['limit']) : 20,
                'offset' => isset($queryParams['offset']) ? intval($queryParams['offset']) : 0
            ];

            $query = Organization::where('is_active', true)
                                ->where('is_verified', true)
                                ->whereHas('organizationCategories', function($q) use ($categoryId, $filters) {
                                    $q->where('category_id', $categoryId);
                                    if ($filters['expertise_level']) {
                                        $q->where('expertise_level', $filters['expertise_level']);
                                    }
                                });

            // Apply location filter
            if ($filters['location']) {
                $query->where(function($q) use ($filters) {
                    $q->where('city', 'ILIKE', '%' . $filters['location'] . '%')
                      ->orWhere('country', 'ILIKE', '%' . $filters['location'] . '%');
                });
            }

            // Apply sorting
            switch ($filters['sort_by']) {
                case 'name':
                    $query->orderBy('name', $filters['sort_order']);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $filters['sort_order']);
                    break;
                default:
                    $query->orderByRaw("
                        (SELECT CASE expertise_level 
                         WHEN 'specialist' THEN 4 
                         WHEN 'expert' THEN 3 
                         WHEN 'intermediate' THEN 2 
                         WHEN 'beginner' THEN 1 
                         ELSE 0 END 
                         FROM organization_categories 
                         WHERE organization_categories.organization_id = organizations.id 
                         AND organization_categories.category_id = ? 
                         LIMIT 1) " . strtoupper($filters['sort_order'])
                    , [$categoryId]);
            }

            $total = $query->count();
            $organizations = $query->with(['organizationCategories' => function($q) use ($categoryId) {
                                        $q->where('category_id', $categoryId);
                                    }])
                                   ->offset($filters['offset'])
                                   ->limit($filters['limit'])
                                   ->get();

            $result = [
                'organizations' => $organizations->map(function($org) {
                    return $org->toApiArray();
                }),
                'pagination' => [
                    'total' => $total,
                    'limit' => $filters['limit'],
                    'offset' => $filters['offset'],
                    'has_more' => ($filters['offset'] + $filters['limit']) < $total
                ]
            ];

            return renderWithJson($result, 'Organizations retrieved successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to retrieve organizations', $e->getMessage(), 1);
        }
    }

    /**
     * Get organizations by subcategory
     */
    public static function getOrganizationsBySubcategory($request, $response, $args)
    {
        try {
            $subcategoryId = $args['subcategory_id'];
            $queryParams = $request->getQueryParams();
            
            $filters = [
                'expertise_level' => $queryParams['expertise_level'] ?? null,
                'min_budget' => isset($queryParams['min_budget']) ? floatval($queryParams['min_budget']) : null,
                'max_budget' => isset($queryParams['max_budget']) ? floatval($queryParams['max_budget']) : null,
                'max_delivery_days' => isset($queryParams['max_delivery_days']) ? intval($queryParams['max_delivery_days']) : null,
                'availability' => $queryParams['availability'] ?? null,
                'featured_only' => isset($queryParams['featured_only']) ? filter_var($queryParams['featured_only'], FILTER_VALIDATE_BOOLEAN) : false,
                'min_success_rate' => isset($queryParams['min_success_rate']) ? floatval($queryParams['min_success_rate']) : null,
                'min_satisfaction' => isset($queryParams['min_satisfaction']) ? floatval($queryParams['min_satisfaction']) : null,
                'sort_by' => $queryParams['sort_by'] ?? 'expertise_level',
                'sort_order' => $queryParams['sort_order'] ?? 'desc'
            ];

            $organizations = OrganizationSubcategory::getOrganizationsBySubcategory($subcategoryId, $filters);

            $result = [
                'organizations' => $organizations->map(function($orgSubcat) {
                    return [
                        'organization' => $orgSubcat->organization->toApiArray(),
                        'subcategory_details' => $orgSubcat->toApiArray()
                    ];
                }),
                'statistics' => OrganizationSubcategory::getSubcategoryStats($subcategoryId)
            ];

            return renderWithJson($result, 'Organizations retrieved successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to retrieve organizations', $e->getMessage(), 1);
        }
    }

    /**
     * Search organizations by capabilities
     */
    public static function searchCapabilities($request, $response, $args)
    {
        try {
            $queryParams = $request->getQueryParams();
            $keyword = $queryParams['q'] ?? '';
            
            if (empty($keyword)) {
                return renderWithJson([], 'Search keyword is required', '', 1);
            }

            $filters = [
                'category_id' => isset($queryParams['category_id']) ? intval($queryParams['category_id']) : null,
                'proficiency_level' => $queryParams['proficiency_level'] ?? null,
                'limit' => isset($queryParams['limit']) ? intval($queryParams['limit']) : 20
            ];

            $capabilities = ServiceCapability::searchCapabilities($keyword, $filters);

            $result = [
                'capabilities' => $capabilities->map(function($capability) {
                    return $capability->toApiArray();
                }),
                'search_keyword' => $keyword,
                'total_results' => $capabilities->count()
            ];

            return renderWithJson($result, 'Capabilities search completed', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to search capabilities', $e->getMessage(), 1);
        }
    }

    /**
     * Find matching organizations for quote requirements
     */
    public static function findMatches($request, $response, $args)
    {
        try {
            $data = $request->getParsedBody();
            
            $requiredFields = ['category_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return renderWithJson(null, "Field '{$field}' is required", '', 1);
                }
            }

            $quoteData = [
                'category_id' => $data['category_id'],
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'budget' => $data['budget'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'location' => $data['location'] ?? [],
                'requirements' => $data['requirements'] ?? []
            ];

            $options = [
                'limit' => $data['limit'] ?? 20,
                'min_score' => $data['min_score'] ?? 50
            ];

            $matches = QuoteMatchingEngine::findMatches($quoteData, $options);

            $result = [
                'matches' => array_map(function($match) {
                    return [
                        'organization' => $match['organization']->toApiArray(),
                        'matching_score' => $match['score'],
                        'match_reasons' => $match['match_reasons']
                    ];
                }, $matches),
                'total_matches' => count($matches),
                'search_criteria' => $quoteData
            ];

            return renderWithJson($result, 'Matching organizations found', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to find matches', $e->getMessage(), 1);
        }
    }

    /**
     * Get recommended organizations for a category
     */
    public static function getRecommendedOrganizations($request, $response, $args)
    {
        try {
            $categoryId = $args['category_id'];
            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? intval($queryParams['limit']) : 10;

            $organizations = QuoteMatchingEngine::getRecommendedOrganizations($categoryId, $limit);

            $result = [
                'recommended_organizations' => $organizations->map(function($org) {
                    return $org->toApiArray();
                }),
                'category_id' => $categoryId,
                'recommendation_criteria' => 'Based on expertise level and verification status'
            ];

            return renderWithJson($result, 'Recommended organizations retrieved', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to get recommendations', $e->getMessage(), 1);
        }
    }

    /**
     * Update organization category specialization
     */
    public static function updateOrganizationCategory($request, $response, $args)
    {
        try {
            $organizationId = $args['organization_id'];
            $categoryId = $args['category_id'];
            $data = $request->getParsedBody();

            // Validate organization access
            $currentUser = getToken($request);
            if (!$currentUser) {
                return renderWithJson(null, 'Authentication required', '', 1);
            }

            $organization = Organization::find($organizationId);
            if (!$organization) {
                return renderWithJson(null, 'Organization not found', '', 1);
            }

            // Check permissions
            if (!OrganizationAccessControl::hasOrganizationAccess($currentUser['id'], $organizationId, 'manage')) {
                return renderWithJson(null, 'Insufficient permissions', '', 1);
            }

            $validationRules = [
                'expertise_level' => 'required|in:beginner,intermediate,expert,specialist',
                'years_experience' => 'nullable|integer|min:0|max:50',
                'portfolio_items' => 'nullable|integer|min:0',
                'certifications' => 'nullable|array'
            ];

            // Basic validation
            if (!isset($data['expertise_level']) || !in_array($data['expertise_level'], ['beginner', 'intermediate', 'expert', 'specialist'])) {
                return renderWithJson(null, 'Valid expertise_level is required', '', 1);
            }

            $orgCategory = OrganizationCategory::updateSpecialization($organizationId, $categoryId, $data);

            return renderWithJson($orgCategory->toApiArray(), 'Category specialization updated successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to update category specialization', $e->getMessage(), 1);
        }
    }

    /**
     * Update organization subcategory specialization
     */
    public static function updateOrganizationSubcategory($request, $response, $args)
    {
        try {
            $organizationId = $args['organization_id'];
            $subcategoryId = $args['subcategory_id'];
            $data = $request->getParsedBody();

            // Validate organization access
            $currentUser = getToken($request);
            if (!$currentUser) {
                return renderWithJson(null, 'Authentication required', '', 1);
            }

            $organization = Organization::find($organizationId);
            if (!$organization) {
                return renderWithJson(null, 'Organization not found', '', 1);
            }

            // Check permissions
            if (!OrganizationAccessControl::hasOrganizationAccess($currentUser['id'], $organizationId, 'manage')) {
                return renderWithJson(null, 'Insufficient permissions', '', 1);
            }

            $validationRules = OrganizationSubcategory::$rules;
            unset($validationRules['organization_id'], $validationRules['subcategory_id']);

            // Basic validation
            if (!isset($data['expertise_level']) || !in_array($data['expertise_level'], ['beginner', 'intermediate', 'expert', 'specialist'])) {
                return renderWithJson(null, 'Valid expertise_level is required', '', 1);
            }

            $orgSubcategory = OrganizationSubcategory::updateSpecialization($organizationId, $subcategoryId, $data);

            return renderWithJson($orgSubcategory->toApiArray(), 'Subcategory specialization updated successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to update subcategory specialization', $e->getMessage(), 1);
        }
    }

    /**
     * Get category statistics
     */
    public static function getCategoryStatistics($request, $response, $args)
    {
        try {
            $categoryId = $args['category_id'];
            
            $stats = [
                'category_stats' => CategoryMatchingScore::getCategoryStats($categoryId),
                'organization_distribution' => OrganizationCategory::getCategoryStats($categoryId),
                'top_organizations' => CategoryMatchingScore::getTopOrganizations($categoryId, 10)
            ];

            return renderWithJson($stats, 'Category statistics retrieved', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to retrieve statistics', $e->getMessage(), 1);
        }
    }

    /**
     * Update matching scores for all organizations
     */
    public static function updateMatchingScores($request, $response, $args)
    {
        try {
            // This should be an admin-only operation
            $currentUser = getToken($request);
            if (!$currentUser || $currentUser['role'] !== 'admin') {
                return renderWithJson(null, 'Admin access required', '', 1);
            }

            QuoteMatchingEngine::updateMatchingScores();

            return renderWithJson(null, 'Matching scores updated successfully', '', 0);

        } catch (\Exception $e) {
            return renderWithJson(null, 'Failed to update matching scores', $e->getMessage(), 1);
        }
    }
}