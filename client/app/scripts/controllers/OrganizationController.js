/**
 * Organization Management Controller
 *
 * @category   Controller
 * @package    GetlancerV3
 * @subpackage Organization
 * @author     Getlancer Team
 */

'use strict';

angular.module('getlancerApp')
    .controller('OrganizationController', ['$scope', '$http', '$state', '$stateParams', '$filter', 'flash', '$rootScope', '$location', 'OrganizationFactory', 'Upload', '$timeout',
        function($scope, $http, $state, $stateParams, $filter, flash, $rootScope, $location, OrganizationFactory, Upload, $timeout) {

            $scope.organizations = [];
            $scope.organization = {};
            $scope.currentUser = $rootScope.user;
            $scope.isLoading = false;
            $scope.pagination = {
                currentPage: 1,
                totalItems: 0,
                itemsPerPage: 10
            };
            $scope.filters = {
                search: '',
                type: '',
                verified: '',
                active: ''
            };

            // Saudi Arabian specific data
            $scope.organizationTypes = ['Buyer', 'Supplier', 'Both'];
            $scope.attachmentTypes = ['VAT', 'CR', 'AVL', 'NWC', 'SE', 'MODON', 'Other'];
            $scope.userRoles = ['owner', 'admin', 'manager', 'member', 'viewer'];

            // Initialize controller based on state
            $scope.init = function() {
                switch ($state.current.name) {
                    case 'organizations':
                        $scope.loadOrganizations();
                        break;
                    case 'organization_view':
                        $scope.loadOrganization($stateParams.id);
                        break;
                    case 'organization_create':
                        $scope.initCreateForm();
                        break;
                    case 'organization_edit':
                        $scope.loadOrganization($stateParams.id);
                        break;
                }
            };

            // Load organizations list
            $scope.loadOrganizations = function() {
                $scope.isLoading = true;
                var params = angular.extend({}, $scope.filters, {
                    page: $scope.pagination.currentPage,
                    limit: $scope.pagination.itemsPerPage
                });

                OrganizationFactory.getOrganizations(params)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            $scope.organizations = response.data.data;
                            $scope.pagination.totalItems = response.data.total || $scope.organizations.length;
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error loading organizations', 'error', false);
                    })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            };

            // Load single organization
            $scope.loadOrganization = function(id) {
                $scope.isLoading = true;
                OrganizationFactory.getOrganization(id)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            $scope.organization = response.data.data;
                            $scope.loadOrganizationAttachments(id);
                            $scope.loadOrganizationUsers(id);
                            $scope.loadOrganizationSettings(id);
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error loading organization', 'error', false);
                    })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            };

            // Initialize create form
            $scope.initCreateForm = function() {
                $scope.organization = {
                    organization_type: 'Buyer',
                    country: 'Saudi Arabia',
                    is_active: true
                };
            };

            // Create organization
            $scope.createOrganization = function() {
                if ($scope.organizationForm.$invalid) {
                    flash.set('Please fill all required fields correctly', 'error', false);
                    return;
                }

                $scope.isLoading = true;
                OrganizationFactory.createOrganization($scope.organization)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            flash.set('Organization created successfully', 'success', false);
                            $state.go('organization_view', { id: response.data.data.id });
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error creating organization', 'error', false);
                    })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            };

            // Update organization
            $scope.updateOrganization = function() {
                if ($scope.organizationForm.$invalid) {
                    flash.set('Please fill all required fields correctly', 'error', false);
                    return;
                }

                $scope.isLoading = true;
                OrganizationFactory.updateOrganization($scope.organization.id, $scope.organization)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            flash.set('Organization updated successfully', 'success', false);
                            $scope.organization = response.data.data;
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error updating organization', 'error', false);
                    })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            };

            // Delete organization
            $scope.deleteOrganization = function(id) {
                if (!confirm('Are you sure you want to delete this organization?')) {
                    return;
                }

                OrganizationFactory.deleteOrganization(id)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            flash.set('Organization deleted successfully', 'success', false);
                            $scope.loadOrganizations();
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error deleting organization', 'error', false);
                    });
            };

            // File upload functionality
            $scope.uploadFile = function(file, attachmentType) {
                if (!file) return;

                $scope.uploadProgress = 0;
                $scope.isUploading = true;

                Upload.upload({
                    url: '/api/organizations/' + $scope.organization.id + '/attachments',
                    data: {
                        file: file,
                        attachment_type: attachmentType
                    },
                    headers: {
                        'Authorization': 'Bearer ' + $rootScope.auth.access_token
                    }
                }).then(function(response) {
                    if (response.data.error.code === 0) {
                        flash.set('File uploaded successfully', 'success', false);
                        $scope.loadOrganizationAttachments($scope.organization.id);
                    } else {
                        flash.set(response.data.error.message, 'error', false);
                    }
                }, function(error) {
                    flash.set('Error uploading file', 'error', false);
                }, function(evt) {
                    $scope.uploadProgress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                }).finally(function() {
                    $scope.isUploading = false;
                    $scope.uploadProgress = 0;
                });
            };

            // Load organization attachments
            $scope.loadOrganizationAttachments = function(id) {
                OrganizationFactory.getOrganizationAttachments(id)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            $scope.organization.attachments = response.data.data;
                        }
                    });
            };

            // Load organization users
            $scope.loadOrganizationUsers = function(id) {
                OrganizationFactory.getOrganizationUsers(id)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            $scope.organization.users = response.data.data;
                        }
                    });
            };

            // Load organization settings
            $scope.loadOrganizationSettings = function(id) {
                OrganizationFactory.getOrganizationSettings(id)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            $scope.organization.settings = response.data.data;
                        }
                    });
            };

            // Add user to organization
            $scope.addUser = function() {
                if (!$scope.newUser.user_id || !$scope.newUser.role) {
                    flash.set('Please select user and role', 'error', false);
                    return;
                }

                var userData = angular.extend({}, $scope.newUser);
                OrganizationFactory.addOrganizationUser($scope.organization.id, userData)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            flash.set('User added successfully', 'success', false);
                            $scope.loadOrganizationUsers($scope.organization.id);
                            $scope.newUser = {};
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error adding user', 'error', false);
                    });
            };

            // Update user role
            $scope.updateUserRole = function(userId, newRole) {
                OrganizationFactory.updateOrganizationUserRole($scope.organization.id, userId, { role: newRole })
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            flash.set('User role updated successfully', 'success', false);
                            $scope.loadOrganizationUsers($scope.organization.id);
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error updating user role', 'error', false);
                    });
            };

            // Update organization setting
            $scope.updateSetting = function(setting) {
                OrganizationFactory.updateOrganizationSetting($scope.organization.id, setting)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            flash.set('Setting updated successfully', 'success', false);
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error updating setting', 'error', false);
                    });
            };

            // Verify attachment (admin only)
            $scope.verifyAttachment = function(attachmentId) {
                if (!$scope.currentUser.isAdmin) {
                    flash.set('Access denied', 'error', false);
                    return;
                }

                OrganizationFactory.verifyAttachment($scope.organization.id, attachmentId)
                    .then(function(response) {
                        if (response.data.error.code === 0) {
                            flash.set('Attachment verified successfully', 'success', false);
                            $scope.loadOrganizationAttachments($scope.organization.id);
                        } else {
                            flash.set(response.data.error.message, 'error', false);
                        }
                    })
                    .catch(function(error) {
                        flash.set('Error verifying attachment', 'error', false);
                    });
            };

            // Pagination
            $scope.pageChanged = function() {
                $scope.loadOrganizations();
            };

            // Filter change
            $scope.filterChanged = function() {
                $scope.pagination.currentPage = 1;
                $scope.loadOrganizations();
            };

            // Validation helpers
            $scope.isValidVATNumber = function(vat) {
                return vat && /^3[0-9]{14}$/.test(vat);
            };

            $scope.isValidCRNumber = function(cr) {
                return cr && /^[0-9]{10}$/.test(cr);
            };

            $scope.isValidNationalAddress = function(address) {
                return address && /^[0-9]{8}$/.test(address);
            };

            // Utility functions
            $scope.getAttachmentIcon = function(type) {
                var icons = {
                    'VAT': 'fa-file-text',
                    'CR': 'fa-certificate',
                    'AVL': 'fa-file-o',
                    'NWC': 'fa-file-o',
                    'SE': 'fa-file-o',
                    'MODON': 'fa-file-o',
                    'Other': 'fa-file'
                };
                return icons[type] || 'fa-file';
            };

            $scope.getVerificationStatusClass = function(isVerified) {
                return isVerified ? 'label-success' : 'label-warning';
            };

            $scope.getVerificationStatusText = function(isVerified) {
                return isVerified ? 'Verified' : 'Pending';
            };

            // Initialize controller
            $scope.init();
        }
    ]);