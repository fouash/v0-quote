<?php
/**
 * Quote Matching Engine
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

class QuoteMatchingEngine extends AppModel
{
    /**
     * Find matching organizations for a quote request
     */
    public static function findMatches($quoteData, $options = [])
    {
        $categoryId = $quoteData['category_id'] ?? null;
        $subcategoryId = $quoteData['subcategory_id'] ?? null;
        $budget = $quoteData['budget'] ?? null;
        $deadline = $quoteData['deadline'] ?? null;
        $location = $quoteData['location'] ?? null;
        $requirements = $quoteData['requirements'] ?? [];

        // Start with organizations in the specified category/subcategory
        $query = Organization::where('is_active', true)
                            ->where('is_verified', true)
                            ->where('organization_type', 'Supplier');

        // Filter by category
        if ($categoryId) {
            $query->whereHas('organizationCategories', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // Filter by subcategory if specified
        if ($subcategoryId) {
            $query->whereHas('organizationSubcategories', function($q) use ($subcategoryId, $budget, $deadline) {
                $q->where('subcategory_id', $subcategoryId);
                
                // Budget filtering
                if ($budget) {
                    $q->where(function($budgetQ) use ($budget) {
                        $budgetQ->where('min_project_value', '<=', $budget)
                               ->orWhereNull('min_project_value');
                    })->where(function($budgetQ) use ($budget) {
                        $budgetQ->where('max_project_value', '>=', $budget)
                               ->orWhereNull('max_project_value');
                    });
                }
                
                // Deadline filtering
                if ($deadline) {
                    $daysUntilDeadline = self::calculateDaysUntilDeadline($deadline);
                    $q->where(function($deadlineQ) use ($daysUntilDeadline) {
                        $deadlineQ->where('typical_delivery_days', '<=', $daysUntilDeadline)
                                 ->orWhereNull('typical_delivery_days');
                    });
                }
            });
        }

        // Location filtering
        if (!empty($location['city'])) {
            $query->where('city', 'ILIKE', '%' . $location['city'] . '%');
        }
        
        if (!empty($location['country'])) {
            $query->where('country', 'ILIKE', '%' . $location['country'] . '%');
        }

        // Get organizations with their category/subcategory relationships
        $organizations = $query->with([
            'organizationCategories' => function($q) use ($categoryId) {
                if ($categoryId) {
                    $q->where('category_id', $categoryId);
                }
            },
            'organizationSubcategories' => function($q) use ($subcategoryId) {
                if ($subcategoryId) {
                    $q->where('subcategory_id', $subcategoryId);
                }
            }
        ])->get();

        // Calculate matching scores
        $matches = [];
        foreach ($organizations as $org) {
            $score = self::calculateMatchingScore($org, $quoteData, $options);
            
            if ($score['total_score'] >= ($options['min_score'] ?? 50)) {
                $matches[] = [
                    'organization' => $org,
                    'score' => $score,
                    'match_reasons' => $score['reasons']
                ];
            }
        }

        // Sort by score
        usort($matches, function($a, $b) {
            return $b['score']['total_score'] <=> $a['score']['total_score'];
        });

        // Limit results
        $limit = $options['limit'] ?? 20;
        return array_slice($matches, 0, $limit);
    }

    /**
     * Calculate matching score for an organization
     */
    private static function calculateMatchingScore($organization, $quoteData, $options = [])
    {
        $score = 0;
        $maxScore = 100;
        $reasons = [];

        // Category expertise score (30 points)
        $categoryScore = self::calculateCategoryScore($organization, $quoteData);
        $score += $categoryScore['score'];
        $reasons = array_merge($reasons, $categoryScore['reasons']);

        // Budget compatibility score (20 points)
        $budgetScore = self::calculateBudgetScore($organization, $quoteData);
        $score += $budgetScore['score'];
        $reasons = array_merge($reasons, $budgetScore['reasons']);

        // Timeline compatibility score (15 points)
        $timelineScore = self::calculateTimelineScore($organization, $quoteData);
        $score += $timelineScore['score'];
        $reasons = array_merge($reasons, $timelineScore['reasons']);

        // Location proximity score (10 points)
        $locationScore = self::calculateLocationScore($organization, $quoteData);
        $score += $locationScore['score'];
        $reasons = array_merge($reasons, $locationScore['reasons']);

        // Organization quality score (15 points)
        $qualityScore = self::calculateQualityScore($organization, $quoteData);
        $score += $qualityScore['score'];
        $reasons = array_merge($reasons, $qualityScore['reasons']);

        // Requirements matching score (10 points)
        $requirementsScore = self::calculateRequirementsScore($organization, $quoteData);
        $score += $requirementsScore['score'];
        $reasons = array_merge($reasons, $requirementsScore['reasons']);

        return [
            'total_score' => min($score, $maxScore),
            'category_score' => $categoryScore['score'],
            'budget_score' => $budgetScore['score'],
            'timeline_score' => $timelineScore['score'],
            'location_score' => $locationScore['score'],
            'quality_score' => $qualityScore['score'],
            'requirements_score' => $requirementsScore['score'],
            'reasons' => $reasons
        ];
    }

    /**
     * Calculate category expertise score
     */
    private static function calculateCategoryScore($organization, $quoteData)
    {
        $score = 0;
        $reasons = [];
        $categoryId = $quoteData['category_id'] ?? null;
        $subcategoryId = $quoteData['subcategory_id'] ?? null;

        if ($categoryId) {
            $orgCategory = $organization->organizationCategories
                                      ->where('category_id', $categoryId)
                                      ->first();

            if ($orgCategory) {
                // Base score for being in category
                $score += 10;
                $reasons[] = "Operates in required category";

                // Expertise level bonus
                $expertiseBonus = [
                    'specialist' => 15,
                    'expert' => 12,
                    'intermediate' => 8,
                    'beginner' => 5
                ];
                $bonus = $expertiseBonus[$orgCategory->expertise_level] ?? 0;
                $score += $bonus;
                $reasons[] = "Expertise level: " . ucfirst($orgCategory->expertise_level);

                // Experience bonus (up to 5 points)
                $experienceBonus = min(5, $orgCategory->years_experience / 2);
                $score += $experienceBonus;
                if ($orgCategory->years_experience > 0) {
                    $reasons[] = "{$orgCategory->years_experience} years of experience";
                }
            }
        }

        if ($subcategoryId) {
            $orgSubcategory = $organization->organizationSubcategories
                                         ->where('subcategory_id', $subcategoryId)
                                         ->first();

            if ($orgSubcategory) {
                // Additional bonus for subcategory match
                $score += 5;
                $reasons[] = "Specializes in required service";

                // Featured service bonus
                if ($orgSubcategory->is_featured) {
                    $score += 3;
                    $reasons[] = "Featured service provider";
                }
            }
        }

        return ['score' => min($score, 30), 'reasons' => $reasons];
    }

    /**
     * Calculate budget compatibility score
     */
    private static function calculateBudgetScore($organization, $quoteData)
    {
        $score = 0;
        $reasons = [];
        $budget = $quoteData['budget'] ?? null;
        $subcategoryId = $quoteData['subcategory_id'] ?? null;

        if (!$budget || !$subcategoryId) {
            return ['score' => 10, 'reasons' => ['Budget not specified']]; // Neutral score
        }

        $orgSubcategory = $organization->organizationSubcategories
                                     ->where('subcategory_id', $subcategoryId)
                                     ->first();

        if ($orgSubcategory) {
            $minValue = $orgSubcategory->min_project_value;
            $maxValue = $orgSubcategory->max_project_value;

            if ($minValue && $maxValue) {
                if ($budget >= $minValue && $budget <= $maxValue) {
                    $score = 20; // Perfect match
                    $reasons[] = "Budget fits perfectly within range";
                } elseif ($budget >= $minValue) {
                    $score = 15; // Above range but acceptable
                    $reasons[] = "Budget above typical range";
                } elseif ($budget <= $maxValue) {
                    $score = 10; // Below range
                    $reasons[] = "Budget below typical range";
                } else {
                    $score = 5; // Outside range
                    $reasons[] = "Budget outside typical range";
                }
            } elseif ($minValue && $budget >= $minValue) {
                $score = 15;
                $reasons[] = "Budget meets minimum requirement";
            } elseif ($maxValue && $budget <= $maxValue) {
                $score = 15;
                $reasons[] = "Budget within maximum limit";
            } else {
                $score = 10; // No range specified
                $reasons[] = "Flexible pricing available";
            }
        } else {
            $score = 5; // No subcategory match
        }

        return ['score' => min($score, 20), 'reasons' => $reasons];
    }

    /**
     * Calculate timeline compatibility score
     */
    private static function calculateTimelineScore($organization, $quoteData)
    {
        $score = 0;
        $reasons = [];
        $deadline = $quoteData['deadline'] ?? null;
        $subcategoryId = $quoteData['subcategory_id'] ?? null;

        if (!$deadline || !$subcategoryId) {
            return ['score' => 8, 'reasons' => ['Timeline not specified']]; // Neutral score
        }

        $daysUntilDeadline = self::calculateDaysUntilDeadline($deadline);
        
        $orgSubcategory = $organization->organizationSubcategories
                                     ->where('subcategory_id', $subcategoryId)
                                     ->first();

        if ($orgSubcategory && $orgSubcategory->typical_delivery_days) {
            $deliveryDays = $orgSubcategory->typical_delivery_days;
            
            if ($deliveryDays <= $daysUntilDeadline) {
                $buffer = $daysUntilDeadline - $deliveryDays;
                if ($buffer >= 7) {
                    $score = 15; // Plenty of time
                    $reasons[] = "Can deliver well before deadline";
                } elseif ($buffer >= 3) {
                    $score = 12; // Good buffer
                    $reasons[] = "Can deliver with good buffer time";
                } else {
                    $score = 8; // Tight but doable
                    $reasons[] = "Can meet deadline with tight schedule";
                }
            } else {
                $score = 3; // Might be challenging
                $reasons[] = "Timeline may be challenging";
            }
        } else {
            $score = 8; // No delivery time specified
            $reasons[] = "Delivery time to be discussed";
        }

        return ['score' => min($score, 15), 'reasons' => $reasons];
    }

    /**
     * Calculate location proximity score
     */
    private static function calculateLocationScore($organization, $quoteData)
    {
        $score = 0;
        $reasons = [];
        $location = $quoteData['location'] ?? [];

        if (empty($location)) {
            return ['score' => 5, 'reasons' => ['Location not specified']];
        }

        // Same city
        if (!empty($location['city']) && 
            stripos($organization->city, $location['city']) !== false) {
            $score += 10;
            $reasons[] = "Located in same city";
        }
        // Same country
        elseif (!empty($location['country']) && 
                stripos($organization->country, $location['country']) !== false) {
            $score += 6;
            $reasons[] = "Located in same country";
        }
        // Different location
        else {
            $score += 3;
            $reasons[] = "Remote service available";
        }

        return ['score' => min($score, 10), 'reasons' => $reasons];
    }

    /**
     * Calculate organization quality score
     */
    private static function calculateQualityScore($organization, $quoteData)
    {
        $score = 0;
        $reasons = [];

        // Verification status
        if ($organization->is_verified) {
            $score += 5;
            $reasons[] = "Verified organization";
        }

        // Document verification
        $verifiedDocs = $organization->attachments()
                                   ->where('is_verified', true)
                                   ->count();
        if ($verifiedDocs >= 2) {
            $score += 3;
            $reasons[] = "Documents verified";
        }

        // Multiple categories (shows versatility)
        $categoryCount = $organization->organizationCategories()->count();
        if ($categoryCount >= 3) {
            $score += 2;
            $reasons[] = "Multi-category expertise";
        }

        // Years in business (based on created_at)
        $yearsInBusiness = (new \DateTime())->diff(new \DateTime($organization->created_at))->y;
        if ($yearsInBusiness >= 5) {
            $score += 3;
            $reasons[] = "Established business";
        } elseif ($yearsInBusiness >= 2) {
            $score += 2;
            $reasons[] = "Experienced business";
        }

        // Active user count
        $activeUsers = $organization->organizationUsers()
                                  ->where('is_active', true)
                                  ->count();
        if ($activeUsers >= 5) {
            $score += 2;
            $reasons[] = "Large team";
        } elseif ($activeUsers >= 2) {
            $score += 1;
            $reasons[] = "Team-based organization";
        }

        return ['score' => min($score, 15), 'reasons' => $reasons];
    }

    /**
     * Calculate requirements matching score
     */
    private static function calculateRequirementsScore($organization, $quoteData)
    {
        $score = 0;
        $reasons = [];
        $requirements = $quoteData['requirements'] ?? [];

        if (empty($requirements)) {
            return ['score' => 5, 'reasons' => ['No specific requirements']];
        }

        // Check for certification requirements
        if (!empty($requirements['certifications'])) {
            $requiredCerts = $requirements['certifications'];
            $orgCerts = [];
            
            foreach ($organization->organizationCategories as $orgCat) {
                $orgCerts = array_merge($orgCerts, $orgCat->certifications ?? []);
            }
            
            $matchingCerts = array_intersect($requiredCerts, $orgCerts);
            if (count($matchingCerts) > 0) {
                $score += 5;
                $reasons[] = "Has required certifications";
            }
        }

        // Check for minimum team size
        if (!empty($requirements['min_team_size'])) {
            $teamSize = $organization->organizationUsers()
                                   ->where('is_active', true)
                                   ->count();
            if ($teamSize >= $requirements['min_team_size']) {
                $score += 3;
                $reasons[] = "Meets team size requirement";
            }
        }

        // Check for language requirements
        if (!empty($requirements['languages'])) {
            // This would need to be implemented based on organization language capabilities
            $score += 2;
            $reasons[] = "Language capabilities available";
        }

        return ['score' => min($score, 10), 'reasons' => $reasons];
    }

    /**
     * Calculate days until deadline
     */
    private static function calculateDaysUntilDeadline($deadline)
    {
        $deadlineDate = is_string($deadline) ? new \DateTime($deadline) : $deadline;
        $now = new \DateTime();
        
        return max(0, $now->diff($deadlineDate)->days);
    }

    /**
     * Get recommended organizations for a category
     */
    public static function getRecommendedOrganizations($categoryId, $limit = 10)
    {
        return Organization::where('is_active', true)
                          ->where('is_verified', true)
                          ->whereHas('organizationCategories', function($q) use ($categoryId) {
                              $q->where('category_id', $categoryId)
                                ->whereIn('expertise_level', ['expert', 'specialist']);
                          })
                          ->with(['organizationCategories' => function($q) use ($categoryId) {
                              $q->where('category_id', $categoryId);
                          }])
                          ->orderByRaw("
                              (SELECT CASE expertise_level 
                               WHEN 'specialist' THEN 4 
                               WHEN 'expert' THEN 3 
                               WHEN 'intermediate' THEN 2 
                               WHEN 'beginner' THEN 1 
                               ELSE 0 END 
                               FROM organization_categories 
                               WHERE organization_categories.organization_id = organizations.id 
                               AND organization_categories.category_id = ? 
                               LIMIT 1) DESC
                          ", [$categoryId])
                          ->limit($limit)
                          ->get();
    }

    /**
     * Update matching scores for all organizations
     */
    public static function updateMatchingScores()
    {
        $categories = Category::active()->get();
        
        foreach ($categories as $category) {
            $organizations = $category->organizations()
                                    ->where('organizations.is_active', true)
                                    ->where('organizations.is_verified', true)
                                    ->get();
            
            foreach ($organizations as $org) {
                $score = self::calculateOrganizationCategoryScore($org, $category);
                
                CategoryMatchingScore::updateOrCreate([
                    'organization_id' => $org->id,
                    'category_id' => $category->id,
                    'subcategory_id' => null
                ], [
                    'score' => $score['total_score'],
                    'factors' => json_encode($score),
                    'last_calculated' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    /**
     * Calculate organization score for a category
     */
    private static function calculateOrganizationCategoryScore($organization, $category)
    {
        $orgCategory = $organization->organizationCategories()
                                  ->where('category_id', $category->id)
                                  ->first();

        if (!$orgCategory) {
            return ['total_score' => 0];
        }

        $score = 0;
        $factors = [];

        // Expertise level (40 points)
        $expertiseScores = [
            'specialist' => 40,
            'expert' => 32,
            'intermediate' => 24,
            'beginner' => 16
        ];
        $expertiseScore = $expertiseScores[$orgCategory->expertise_level] ?? 0;
        $score += $expertiseScore;
        $factors['expertise'] = $expertiseScore;

        // Experience (25 points)
        $experienceScore = min(25, $orgCategory->years_experience * 2.5);
        $score += $experienceScore;
        $factors['experience'] = $experienceScore;

        // Portfolio items (15 points)
        $portfolioScore = min(15, $orgCategory->portfolio_items * 1.5);
        $score += $portfolioScore;
        $factors['portfolio'] = $portfolioScore;

        // Certifications (10 points)
        $certificationScore = min(10, count($orgCategory->certifications ?? []) * 2);
        $score += $certificationScore;
        $factors['certifications'] = $certificationScore;

        // Organization quality (10 points)
        $qualityScore = 0;
        if ($organization->is_verified) $qualityScore += 5;
        if ($organization->attachments()->where('is_verified', true)->count() >= 2) $qualityScore += 3;
        if ($organization->organizationUsers()->where('is_active', true)->count() >= 3) $qualityScore += 2;
        $factors['quality'] = $qualityScore;
        $score += $qualityScore;

        return [
            'total_score' => min($score, 100),
            'factors' => $factors
        ];
    }
}