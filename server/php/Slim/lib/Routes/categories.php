<?php
/**
 * Category Routes
 *
 * PHP version 7+
 *
 * @category   Routes
 * @package    GetlancerV3
 * @subpackage Category
 * @author     Getlancer Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

// Categories
$app->group('/categories', function () use ($app) {
    
    // Get all categories
    $app->get('', function ($request, $response, $args) {
        return Models\CategoryAPI::getCategories($request, $response, $args);
    });
    
    // Get category by ID
    $app->get('/{id:[0-9]+}', function ($request, $response, $args) {
        return Models\CategoryAPI::getCategory($request, $response, $args);
    });
    
    // Get category statistics
    $app->get('/{category_id:[0-9]+}/statistics', function ($request, $response, $args) {
        return Models\CategoryAPI::getCategoryStatistics($request, $response, $args);
    });
    
    // Get subcategories for a category
    $app->get('/{category_id:[0-9]+}/subcategories', function ($request, $response, $args) {
        return Models\CategoryAPI::getSubcategories($request, $response, $args);
    });
    
    // Get organizations by category
    $app->get('/{category_id:[0-9]+}/organizations', function ($request, $response, $args) {
        return Models\CategoryAPI::getOrganizationsByCategory($request, $response, $args);
    });
    
    // Get recommended organizations for a category
    $app->get('/{category_id:[0-9]+}/recommended', function ($request, $response, $args) {
        return Models\CategoryAPI::getRecommendedOrganizations($request, $response, $args);
    });
    
});

// Subcategories
$app->group('/subcategories', function () use ($app) {
    
    // Get organizations by subcategory
    $app->get('/{subcategory_id:[0-9]+}/organizations', function ($request, $response, $args) {
        return Models\CategoryAPI::getOrganizationsBySubcategory($request, $response, $args);
    });
    
});

// Capabilities
$app->group('/capabilities', function () use ($app) {
    
    // Search capabilities
    $app->get('/search', function ($request, $response, $args) {
        return Models\CategoryAPI::searchCapabilities($request, $response, $args);
    });
    
});

// Matching
$app->group('/matching', function () use ($app) {
    
    // Find matching organizations
    $app->post('/find', function ($request, $response, $args) {
        return Models\CategoryAPI::findMatches($request, $response, $args);
    });
    
    // Update matching scores (admin only)
    $app->post('/update-scores', function ($request, $response, $args) {
        return Models\CategoryAPI::updateMatchingScores($request, $response, $args);
    });
    
});

// Organization category management
$app->group('/organizations', function () use ($app) {
    
    // Update organization category specialization
    $app->put('/{organization_id:[0-9]+}/categories/{category_id:[0-9]+}', function ($request, $response, $args) {
        return Models\CategoryAPI::updateOrganizationCategory($request, $response, $args);
    });
    
    // Update organization subcategory specialization
    $app->put('/{organization_id:[0-9]+}/subcategories/{subcategory_id:[0-9]+}', function ($request, $response, $args) {
        return Models\CategoryAPI::updateOrganizationSubcategory($request, $response, $args);
    });
    
});