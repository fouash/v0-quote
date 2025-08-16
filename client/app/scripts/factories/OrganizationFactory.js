/**
 * Organization Factory
 *
 * @category   Factory
 * @package    GetlancerV3
 * @subpackage Organization
 * @author     Getlancer Team
 */

'use strict';

angular.module('getlancerApp')
    .factory('OrganizationFactory', ['$http', '$q', 'API_URL',
        function($http, $q, API_URL) {

            var factory = {};

            // Base API URL for organizations
            var baseUrl = API_URL + '/organizations';

            /**
             * Get all organizations
             */
            factory.getOrganizations = function(params) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: baseUrl,
                    params: params || {}
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get single organization
             */
            factory.getOrganization = function(id) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: baseUrl + '/' + id
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Create new organization
             */
            factory.createOrganization = function(data) {
                var deferred = $q.defer();
                
                $http({
                    method: 'POST',
                    url: baseUrl,
                    data: data
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Update organization
             */
            factory.updateOrganization = function(id, data) {
                var deferred = $q.defer();
                
                $http({
                    method: 'PUT',
                    url: baseUrl + '/' + id,
                    data: data
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Delete organization
             */
            factory.deleteOrganization = function(id) {
                var deferred = $q.defer();
                
                $http({
                    method: 'DELETE',
                    url: baseUrl + '/' + id
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get organization attachments
             */
            factory.getOrganizationAttachments = function(id, params) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: baseUrl + '/' + id + '/attachments',
                    params: params || {}
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Upload organization attachment
             */
            factory.uploadOrganizationAttachment = function(id, formData) {
                var deferred = $q.defer();
                
                $http({
                    method: 'POST',
                    url: baseUrl + '/' + id + '/attachments',
                    data: formData,
                    headers: {
                        'Content-Type': undefined
                    },
                    transformRequest: angular.identity
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Verify attachment (admin only)
             */
            factory.verifyAttachment = function(organizationId, attachmentId) {
                var deferred = $q.defer();
                
                $http({
                    method: 'POST',
                    url: baseUrl + '/' + organizationId + '/attachments/' + attachmentId + '/verify'
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get organization users
             */
            factory.getOrganizationUsers = function(id, params) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: baseUrl + '/' + id + '/users',
                    params: params || {}
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Add user to organization
             */
            factory.addOrganizationUser = function(id, userData) {
                var deferred = $q.defer();
                
                $http({
                    method: 'POST',
                    url: baseUrl + '/' + id + '/users',
                    data: userData
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Update organization user role
             */
            factory.updateOrganizationUserRole = function(organizationId, userId, roleData) {
                var deferred = $q.defer();
                
                $http({
                    method: 'PUT',
                    url: baseUrl + '/' + organizationId + '/users/' + userId + '/role',
                    data: roleData
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get organization settings
             */
            factory.getOrganizationSettings = function(id, params) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: baseUrl + '/' + id + '/settings',
                    params: params || {}
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Update organization setting
             */
            factory.updateOrganizationSetting = function(id, settingData) {
                var deferred = $q.defer();
                
                $http({
                    method: 'POST',
                    url: baseUrl + '/' + id + '/settings',
                    data: settingData
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get organization statistics
             */
            factory.getOrganizationStats = function(id) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: baseUrl + '/' + id + '/reports/stats'
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get organization verification status
             */
            factory.getOrganizationVerificationStatus = function(id) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: baseUrl + '/' + id + '/reports/verification-status'
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get verified organizations (public)
             */
            factory.getVerifiedOrganizations = function(params) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: API_URL + '/public/organizations/verified',
                    params: params || {}
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            /**
             * Get organization public profile
             */
            factory.getOrganizationPublicProfile = function(id) {
                var deferred = $q.defer();
                
                $http({
                    method: 'GET',
                    url: API_URL + '/public/organizations/' + id + '/profile'
                }).then(function(response) {
                    deferred.resolve(response);
                }, function(error) {
                    deferred.reject(error);
                });

                return deferred.promise;
            };

            // Admin functions
            factory.admin = {
                /**
                 * Get organizations pending verification
                 */
                getPendingOrganizations: function(params) {
                    var deferred = $q.defer();
                    
                    $http({
                        method: 'GET',
                        url: API_URL + '/admin/organizations/pending-verification',
                        params: params || {}
                    }).then(function(response) {
                        deferred.resolve(response);
                    }, function(error) {
                        deferred.reject(error);
                    });

                    return deferred.promise;
                },

                /**
                 * Approve organization
                 */
                approveOrganization: function(id) {
                    var deferred = $q.defer();
                    
                    $http({
                        method: 'POST',
                        url: API_URL + '/admin/organizations/' + id + '/approve'
                    }).then(function(response) {
                        deferred.resolve(response);
                    }, function(error) {
                        deferred.reject(error);
                    });

                    return deferred.promise;
                },

                /**
                 * Reject organization
                 */
                rejectOrganization: function(id, reason) {
                    var deferred = $q.defer();
                    
                    $http({
                        method: 'POST',
                        url: API_URL + '/admin/organizations/' + id + '/reject',
                        data: { reason: reason }
                    }).then(function(response) {
                        deferred.resolve(response);
                    }, function(error) {
                        deferred.reject(error);
                    });

                    return deferred.promise;
                }
            };

            return factory;
        }
    ]);