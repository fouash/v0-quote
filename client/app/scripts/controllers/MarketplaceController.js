'use strict';

/**
 * Marketplace Controller
 * 
 * Handles marketplace functionality including category browsing,
 * vendor filtering, quote posting, and featured services
 */
angular.module('getlancerApp')
    .controller('MarketplaceController', ['$scope', '$state', '$location', '$timeout', 'CategoryFactory', 'OrganizationFactory', 'QuoteFactory', 'flash', '$filter', 
        function($scope, $state, $location, $timeout, CategoryFactory, OrganizationFactory, QuoteFactory, flash, $filter) {
            
            // Initialize scope variables
            $scope.activeTab = 'browse';
            $scope.viewMode = 'grid';
            $scope.isLoading = false;
            $scope.loadingMessage = '';
            
            // Data containers
            $scope.categories = [];
            $scope.filteredCategories = [];
            $scope.vendors = [];
            $scope.filteredVendors = [];
            $scope.featuredServices = [];
            
            // Filter variables
            $scope.selectedCategories = {};
            $scope.selectedSubcategories = {};
            $scope.selectedExpertise = {};
            $scope.filters = {
                minBudget: null,
                maxBudget: null,
                location: '',
                verifiedOnly: false,
                featuredOnly: false
            };
            
            // Vendor-specific filters
            $scope.vendorFilters = {
                categoryId: '',
                subcategoryId: '',
                ratings: {},
                availableNow: false,
                responseTime24h: false
            };
            
            // Pagination
            $scope.vendorLimit = 12;
            $scope.totalVendors = 0;
            
            // Form data
            $scope.quote = {};
            $scope.submittingQuote = false;
            $scope.searchQuery = '';
            
            // Options arrays
            $scope.expertiseLevels = [
                { value: 'beginner', label: 'BEGINNER' },
                { value: 'intermediate', label: 'INTERMEDIATE' },
                { value: 'expert', label: 'EXPERT' },
                { value: 'specialist', label: 'SPECIALIST' }
            ];
            
            $scope.ratingOptions = [
                { value: '5', label: '5_STARS', stars: [true, true, true, true, true] },
                { value: '4', label: '4_STARS_UP', stars: [true, true, true, true, false] },
                { value: '3', label: '3_STARS_UP', stars: [true, true, true, false, false] },
                { value: '2', label: '2_STARS_UP', stars: [true, true, false, false, false] }
            ];
            
            $scope.vendorSubcategories = [];
            $scope.quoteSubcategories = [];
            $scope.vendorSortBy = 'relevance';
            $scope.today = new Date().toISOString().split('T')[0];
            
            /**
             * Initialize the marketplace
             */
            $scope.init = function() {
                $scope.loadCategories();
                $scope.loadVendors();
                $scope.loadFeaturedServices();
                
                // Set active tab from URL parameter
                var tabParam = $location.search().tab;
                if (tabParam && ['browse', 'vendors', 'quotes', 'featured'].indexOf(tabParam) !== -1) {
                    $scope.activeTab = tabParam;
                }
            };
            
            /**
             * Load all categories with subcategories
             */
            $scope.loadCategories = function() {
                $scope.isLoading = true;
                $scope.loadingMessage = 'Loading categories...';
                
                CategoryFactory.getCategories({ include_subcategories: true, include_stats: true })
                    .then(function(response) {
                        $scope.categories = response.data;
                        $scope.filteredCategories = angular.copy($scope.categories);
                        $scope.isLoading = false;
                    })
                    .catch(function(error) {
                        console.error('Error loading categories:', error);
                        flash.set('Error loading categories. Please try again.', 'error', false);
                        $scope.isLoading = false;
                    });
            };
            
            /**
             * Load vendors
             */
            $scope.loadVendors = function() {
                $scope.isLoading = true;
                $scope.loadingMessage = 'Loading vendors...';
                
                OrganizationFactory.getOrganizations({
                    organization_type: 'Supplier',
                    is_verified: true,
                    include_categories: true
                })
                    .then(function(response) {
                        $scope.vendors = response.data.organizations || response.data;
                        $scope.totalVendors = $scope.vendors.length;
                        $scope.filteredVendors = angular.copy($scope.vendors);
                        $scope.isLoading = false;
                    })
                    .catch(function(error) {
                        console.error('Error loading vendors:', error);
                        flash.set('Error loading vendors. Please try again.', 'error', false);
                        $scope.isLoading = false;
                    });
            };
            
            /**
             * Load featured services
             */
            $scope.loadFeaturedServices = function() {
                // Mock featured services for now
                $scope.featuredServices = [
                    {
                        id: 1,
                        title: 'Professional Website Development',
                        description: 'Create modern, responsive websites for your business',
                        image: '/images/featured-web-dev.jpg',
                        starting_price: 5000,
                        vendor: {
                            name: 'TechSolutions',
                            logo: '/images/vendors/techsolutions.png'
                        }
                    },
                    {
                        id: 2,
                        title: 'Digital Marketing Strategy',
                        description: 'Comprehensive digital marketing campaigns',
                        image: '/images/featured-marketing.jpg',
                        starting_price: 3000,
                        vendor: {
                            name: 'MarketPro',
                            logo: '/images/vendors/marketpro.png'
                        }
                    },
                    {
                        id: 3,
                        title: 'Business Consulting',
                        description: 'Strategic business consulting and planning',
                        image: '/images/featured-consulting.jpg',
                        starting_price: 8000,
                        vendor: {
                            name: 'BizConsult',
                            logo: '/images/vendors/bizconsult.png'
                        }
                    }
                ];
            };
            
            /**
             * Set active tab
             */
            $scope.setActiveTab = function(tab) {
                $scope.activeTab = tab;
                $location.search('tab', tab);
                
                // Load data for specific tabs
                if (tab === 'vendors' && $scope.vendors.length === 0) {
                    $scope.loadVendors();
                } else if (tab === 'featured' && $scope.featuredServices.length === 0) {
                    $scope.loadFeaturedServices();
                }
            };
            
            /**
             * Set view mode (grid/list)
             */
            $scope.setViewMode = function(mode) {
                $scope.viewMode = mode;
            };
            
            /**
             * Filter by category
             */
            $scope.filterByCategory = function(categoryId) {
                $scope.applyFilters();
            };
            
            /**
             * Filter by subcategory
             */
            $scope.filterBySubcategory = function(subcategoryId) {
                $scope.applyFilters();
            };
            
            /**
             * Apply all filters to categories
             */
            $scope.applyFilters = function() {
                $scope.filteredCategories = $scope.categories.filter(function(category) {
                    // Category selection filter
                    if (Object.keys($scope.selectedCategories).length > 0) {
                        var categorySelected = false;
                        for (var catId in $scope.selectedCategories) {
                            if ($scope.selectedCategories[catId] && catId == category.id) {
                                categorySelected = true;
                                break;
                            }
                        }
                        if (!categorySelected) return false;
                    }
                    
                    // Budget filter (if applicable)
                    if ($scope.filters.minBudget || $scope.filters.maxBudget) {
                        // This would need to be implemented based on category budget ranges
                    }
                    
                    return true;
                });
            };
            
            /**
             * Clear all filters
             */
            $scope.clearFilters = function() {
                $scope.selectedCategories = {};
                $scope.selectedSubcategories = {};
                $scope.selectedExpertise = {};
                $scope.filters = {
                    minBudget: null,
                    maxBudget: null,
                    location: '',
                    verifiedOnly: false,
                    featuredOnly: false
                };
                $scope.vendorFilters = {
                    categoryId: '',
                    subcategoryId: '',
                    ratings: {},
                    availableNow: false,
                    responseTime24h: false
                };
                $scope.vendorSubcategories = [];
                $scope.applyFilters();
                $scope.filterVendors();
            };
            
            /**
             * Search services
             */
            $scope.searchServices = function() {
                if (!$scope.searchQuery || $scope.searchQuery.trim() === '') {
                    return;
                }
                
                var query = $scope.searchQuery.trim().toLowerCase();
                
                // Filter categories by search query
                $scope.filteredCategories = $scope.categories.filter(function(category) {
                    // Search in category name and description
                    if (category.name.toLowerCase().indexOf(query) !== -1 ||
                        category.description.toLowerCase().indexOf(query) !== -1) {
                        return true;
                    }
                    
                    // Search in subcategories
                    if (category.subcategories) {
                        return category.subcategories.some(function(subcategory) {
                            return subcategory.name.toLowerCase().indexOf(query) !== -1 ||
                                   subcategory.description.toLowerCase().indexOf(query) !== -1;
                        });
                    }
                    
                    return false;
                });
                
                // Switch to browse tab to show results
                $scope.setActiveTab('browse');
            };
            
            /**
             * View category details
             */
            $scope.viewCategory = function(category) {
                $state.go('category-detail', { categoryId: category.id });
            };
            
            /**
             * View subcategory details
             */
            $scope.viewSubcategory = function(subcategory) {
                $state.go('subcategory-detail', { subcategoryId: subcategory.id });
            };
            
            /**
             * Load vendor subcategories
             */
            $scope.loadVendorSubcategories = function() {
                if (!$scope.vendorFilters.categoryId) {
                    $scope.vendorSubcategories = [];
                    return;
                }
                
                var category = $scope.categories.find(function(cat) {
                    return cat.id == $scope.vendorFilters.categoryId;
                });
                
                if (category && category.subcategories) {
                    $scope.vendorSubcategories = category.subcategories;
                } else {
                    // Load from API if not available
                    CategoryFactory.getSubcategories($scope.vendorFilters.categoryId)
                        .then(function(response) {
                            $scope.vendorSubcategories = response.data;
                        })
                        .catch(function(error) {
                            console.error('Error loading subcategories:', error);
                            $scope.vendorSubcategories = [];
                        });
                }
            };
            
            /**
             * Filter vendors
             */
            $scope.filterVendors = function() {
                $scope.filteredVendors = $scope.vendors.filter(function(vendor) {
                    // Category filter
                    if ($scope.vendorFilters.categoryId) {
                        var hasCategory = vendor.categories && vendor.categories.some(function(cat) {
                            return cat.id == $scope.vendorFilters.categoryId;
                        });
                        if (!hasCategory) return false;
                    }
                    
                    // Subcategory filter
                    if ($scope.vendorFilters.subcategoryId) {
                        var hasSubcategory = vendor.subcategories && vendor.subcategories.some(function(subcat) {
                            return subcat.id == $scope.vendorFilters.subcategoryId;
                        });
                        if (!hasSubcategory) return false;
                    }
                    
                    // Rating filter
                    if (Object.keys($scope.vendorFilters.ratings).length > 0) {
                        var ratingMatch = false;
                        for (var rating in $scope.vendorFilters.ratings) {
                            if ($scope.vendorFilters.ratings[rating] && vendor.rating >= parseInt(rating)) {
                                ratingMatch = true;
                                break;
                            }
                        }
                        if (!ratingMatch) return false;
                    }
                    
                    // Availability filter
                    if ($scope.vendorFilters.availableNow && vendor.availability_status !== 'available') {
                        return false;
                    }
                    
                    // Response time filter
                    if ($scope.vendorFilters.responseTime24h && (!vendor.response_time_hours || vendor.response_time_hours > 24)) {
                        return false;
                    }
                    
                    return true;
                });
                
                $scope.sortVendors();
            };
            
            /**
             * Sort vendors
             */
            $scope.sortVendors = function() {
                switch ($scope.vendorSortBy) {
                    case 'rating':
                        $scope.filteredVendors.sort(function(a, b) {
                            return (b.rating || 0) - (a.rating || 0);
                        });
                        break;
                    case 'experience':
                        $scope.filteredVendors.sort(function(a, b) {
                            return (b.years_experience || 0) - (a.years_experience || 0);
                        });
                        break;
                    case 'recent':
                        $scope.filteredVendors.sort(function(a, b) {
                            return new Date(b.created_at) - new Date(a.created_at);
                        });
                        break;
                    case 'alphabetical':
                        $scope.filteredVendors.sort(function(a, b) {
                            return a.name.localeCompare(b.name);
                        });
                        break;
                    default: // relevance
                        // Keep current order or implement relevance algorithm
                        break;
                }
            };
            
            /**
             * Load more vendors
             */
            $scope.loadMoreVendors = function() {
                $scope.vendorLimit += 12;
            };
            
            /**
             * View vendor profile
             */
            $scope.viewVendorProfile = function(vendor) {
                $state.go('organization-detail', { organizationId: vendor.id });
            };
            
            /**
             * Contact vendor
             */
            $scope.contactVendor = function(vendor) {
                $state.go('contact-vendor', { organizationId: vendor.id });
            };
            
            /**
             * Load quote subcategories
             */
            $scope.loadQuoteSubcategories = function() {
                if (!$scope.quote.categoryId) {
                    $scope.quoteSubcategories = [];
                    return;
                }
                
                var category = $scope.categories.find(function(cat) {
                    return cat.id == $scope.quote.categoryId;
                });
                
                if (category && category.subcategories) {
                    $scope.quoteSubcategories = category.subcategories;
                } else {
                    CategoryFactory.getSubcategories($scope.quote.categoryId)
                        .then(function(response) {
                            $scope.quoteSubcategories = response.data;
                        })
                        .catch(function(error) {
                            console.error('Error loading subcategories:', error);
                            $scope.quoteSubcategories = [];
                        });
                }
            };
            
            /**
             * Submit quote
             */
            $scope.submitQuote = function() {
                if ($scope.submittingQuote) return;
                
                $scope.submittingQuote = true;
                
                var quoteData = {
                    title: $scope.quote.title,
                    description: $scope.quote.description,
                    category_id: $scope.quote.categoryId,
                    subcategory_id: $scope.quote.subcategoryId,
                    budget_range: $scope.quote.budgetRange,
                    deadline: $scope.quote.deadline,
                    skills: $scope.quote.skills
                };
                
                QuoteFactory.createQuote(quoteData)
                    .then(function(response) {
                        flash.set('Quote request submitted successfully!', 'success', false);
                        $scope.quote = {};
                        $scope.quoteSubcategories = [];
                        $scope.submittingQuote = false;
                        
                        // Redirect to quote details or dashboard
                        $state.go('quote-detail', { quoteId: response.data.id });
                    })
                    .catch(function(error) {
                        console.error('Error submitting quote:', error);
                        flash.set('Error submitting quote. Please try again.', 'error', false);
                        $scope.submittingQuote = false;
                    });
            };
            
            /**
             * View service details
             */
            $scope.viewService = function(service) {
                $state.go('service-detail', { serviceId: service.id });
            };
            
            // Initialize the controller
            $scope.init();
            
            // Watch for tab changes to update URL
            $scope.$watch('activeTab', function(newTab, oldTab) {
                if (newTab !== oldTab) {
                    $location.search('tab', newTab);
                }
            });
            
            // Watch for search query changes
            $scope.$watch('searchQuery', function(newQuery, oldQuery) {
                if (newQuery !== oldQuery && newQuery && newQuery.length > 2) {
                    // Debounce search
                    if ($scope.searchTimeout) {
                        $timeout.cancel($scope.searchTimeout);
                    }
                    $scope.searchTimeout = $timeout(function() {
                        $scope.searchServices();
                    }, 500);
                }
            });
        }
    ]);