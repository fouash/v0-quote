'use strict';

/**
 * Category Factory
 * 
 * Handles API calls for category-related operations
 */
angular.module('getlancerApp')
    .factory('CategoryFactory', ['$http', '$q', 'API_URL', function($http, $q, API_URL) {
        
        var CategoryFactory = {};
        
        /**
         * Get all categories
         */
        CategoryFactory.getCategories = function(params) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/categories', {
                params: params || {}
            })
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Get category by ID
         */
        CategoryFactory.getCategory = function(categoryId, params) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/categories/' + categoryId, {
                params: params || {}
            })
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Get subcategories for a category
         */
        CategoryFactory.getSubcategories = function(categoryId, params) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/categories/' + categoryId + '/subcategories', {
                params: params || {}
            })
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Get organizations by category
         */
        CategoryFactory.getOrganizationsByCategory = function(categoryId, params) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/categories/' + categoryId + '/organizations', {
                params: params || {}
            })
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Get organizations by subcategory
         */
        CategoryFactory.getOrganizationsBySubcategory = function(subcategoryId, params) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/subcategories/' + subcategoryId + '/organizations', {
                params: params || {}
            })
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Search capabilities
         */
        CategoryFactory.searchCapabilities = function(params) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/capabilities/search', {
                params: params || {}
            })
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Find matching organizations
         */
        CategoryFactory.findMatches = function(matchData) {
            var deferred = $q.defer();
            
            $http.post(API_URL + '/matching/find', matchData)
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Get recommended organizations for category
         */
        CategoryFactory.getRecommendedOrganizations = function(categoryId, params) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/categories/' + categoryId + '/recommended', {
                params: params || {}
            })
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Get category statistics
         */
        CategoryFactory.getCategoryStatistics = function(categoryId) {
            var deferred = $q.defer();
            
            $http.get(API_URL + '/categories/' + categoryId + '/statistics')
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Update organization category specialization
         */
        CategoryFactory.updateOrganizationCategory = function(organizationId, categoryId, data) {
            var deferred = $q.defer();
            
            $http.put(API_URL + '/organizations/' + organizationId + '/categories/' + categoryId, data)
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        /**
         * Update organization subcategory specialization
         */
        CategoryFactory.updateOrganizationSubcategory = function(organizationId, subcategoryId, data) {
            var deferred = $q.defer();
            
            $http.put(API_URL + '/organizations/' + organizationId + '/subcategories/' + subcategoryId, data)
            .then(function(response) {
                deferred.resolve(response.data);
            })
            .catch(function(error) {
                deferred.reject(error);
            });
            
            return deferred.promise;
        };
        
        return CategoryFactory;
    }]);