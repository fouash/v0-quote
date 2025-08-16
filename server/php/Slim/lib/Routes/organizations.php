<?php
/**
 * Organization API Routes
 *
 * PHP version 7+
 *
 * @category   Routes
 * @package    GetlancerV3
 * @subpackage Organization
 * @author     Getlancer Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

use Models\OrganizationAPI;

// Organization CRUD operations
$app->group('/organizations', function () {
    
    // Get all organizations
    $this->get('', function ($request, $response, $args) {
        return OrganizationAPI::getOrganizations($request, $response, $args);
    })->add(new ACL('canListOrganizations'));
    
    // Create new organization
    $this->post('', function ($request, $response, $args) {
        return OrganizationAPI::createOrganization($request, $response, $args);
    })->add(new ACL('canCreateOrganization'));
    
    // Get single organization
    $this->get('/{id:[0-9]+}', function ($request, $response, $args) {
        return OrganizationAPI::getOrganization($request, $response, $args);
    })->add(new ACL('canViewOrganization'));
    
    // Update organization
    $this->put('/{id:[0-9]+}', function ($request, $response, $args) {
        return OrganizationAPI::updateOrganization($request, $response, $args);
    })->add(new ACL('canUpdateOrganization'));
    
    // Delete organization
    $this->delete('/{id:[0-9]+}', function ($request, $response, $args) {
        return OrganizationAPI::deleteOrganization($request, $response, $args);
    })->add(new ACL('canDeleteOrganization'));
    
    // Organization attachments
    $this->group('/{id:[0-9]+}/attachments', function () {
        
        // Get organization attachments
        $this->get('', function ($request, $response, $args) {
            return OrganizationAPI::getAttachments($request, $response, $args);
        })->add(new ACL('canViewOrganizationAttachments'));
        
        // Upload attachment
        $this->post('', function ($request, $response, $args) {
            return OrganizationAPI::uploadAttachment($request, $response, $args);
        })->add(new ACL('canUploadOrganizationAttachment'));
        
        // Verify attachment (admin only)
        $this->post('/{attachment_id:[0-9]+}/verify', function ($request, $response, $args) {
            return OrganizationAPI::verifyAttachment($request, $response, $args);
        })->add(new ACL('canVerifyOrganizationAttachment'));
        
    });
    
    // Organization users
    $this->group('/{id:[0-9]+}/users', function () {
        
        // Get organization users
        $this->get('', function ($request, $response, $args) {
            return OrganizationAPI::getOrganizationUsers($request, $response, $args);
        })->add(new ACL('canViewOrganizationUsers'));
        
        // Add user to organization
        $this->post('', function ($request, $response, $args) {
            return OrganizationAPI::addUser($request, $response, $args);
        })->add(new ACL('canAddOrganizationUser'));
        
        // Update user role
        $this->put('/{user_id:[0-9]+}/role', function ($request, $response, $args) {
            return OrganizationAPI::updateUserRole($request, $response, $args);
        })->add(new ACL('canUpdateOrganizationUserRole'));
        
    });
    
    // Organization settings
    $this->group('/{id:[0-9]+}/settings', function () {
        
        // Get organization settings
        $this->get('', function ($request, $response, $args) {
            return OrganizationAPI::getSettings($request, $response, $args);
        })->add(new ACL('canViewOrganizationSettings'));
        
        // Update organization setting
        $this->post('', function ($request, $response, $args) {
            return OrganizationAPI::updateSetting($request, $response, $args);
        })->add(new ACL('canUpdateOrganizationSettings'));
        
    });
    
});

// Organization verification routes (admin only)
$app->group('/admin/organizations', function () {
    
    // Get organizations pending verification
    $this->get('/pending-verification', function ($request, $response, $args) {
        global $authUser;
        $queryParams = $request->getQueryParams();
        
        try {
            if (!$authUser || $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                return renderWithJson([], 'Access denied', '', 1);
            }
            
            $organizations = \Models\Organization::with(['attachments', 'verifications'])
                ->where('is_verified', false)
                ->filter($queryParams)
                ->get();
            
            return renderWithJson($organizations, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error: ' . $e->getMessage(), '', 1);
        }
    })->add(new ACL('canViewPendingOrganizations'));
    
    // Approve organization
    $this->post('/{id:[0-9]+}/approve', function ($request, $response, $args) {
        global $authUser;
        $organizationId = $args['id'];
        
        try {
            if (!$authUser || $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                return renderWithJson([], 'Access denied', '', 1);
            }
            
            $organization = \Models\Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            $organization->is_verified = true;
            $organization->verified_at = date('Y-m-d H:i:s');
            $organization->save();
            
            return renderWithJson($organization, 'Organization approved successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error: ' . $e->getMessage(), '', 1);
        }
    })->add(new ACL('canApproveOrganization'));
    
    // Reject organization
    $this->post('/{id:[0-9]+}/reject', function ($request, $response, $args) {
        global $authUser;
        $organizationId = $args['id'];
        $data = $request->getParsedBody();
        
        try {
            if (!$authUser || $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                return renderWithJson([], 'Access denied', '', 1);
            }
            
            $organization = \Models\Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            $organization->is_verified = false;
            $organization->verified_at = null;
            $organization->rejection_reason = $data['reason'] ?? 'Not specified';
            $organization->save();
            
            return renderWithJson($organization, 'Organization rejected', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error: ' . $e->getMessage(), '', 1);
        }
    })->add(new ACL('canRejectOrganization'));
    
});

// Organization statistics and reports
$app->group('/organizations/{id:[0-9]+}/reports', function () {
    
    // Get organization statistics
    $this->get('/stats', function ($request, $response, $args) {
        global $authUser;
        $organizationId = $args['id'];
        
        try {
            $organization = \Models\Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = \Models\OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser || !$orgUser->hasPermission('view_reports')) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            $stats = $organization->getStats();
            
            return renderWithJson($stats, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error: ' . $e->getMessage(), '', 1);
        }
    })->add(new ACL('canViewOrganizationStats'));
    
    // Get verification status
    $this->get('/verification-status', function ($request, $response, $args) {
        global $authUser;
        $organizationId = $args['id'];
        
        try {
            $organization = \Models\Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = \Models\OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            $verificationStatus = $organization->getVerificationStatus();
            
            return renderWithJson($verificationStatus, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error: ' . $e->getMessage(), '', 1);
        }
    })->add(new ACL('canViewOrganizationVerificationStatus'));
    
});

// Public organization endpoints (no authentication required)
$app->group('/public/organizations', function () {
    
    // Get verified organizations (public directory)
    $this->get('/verified', function ($request, $response, $args) {
        $queryParams = $request->getQueryParams();
        
        try {
            $organizations = \Models\Organization::with(['attachments'])
                ->where('is_verified', true)
                ->where('is_active', true)
                ->filter($queryParams)
                ->select(['id', 'organization_name', 'organization_type', 'city', 'state_province', 'country', 'created_at'])
                ->get();
            
            return renderWithJson($organizations, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error: ' . $e->getMessage(), '', 1);
        }
    });
    
    // Get organization public profile
    $this->get('/{id:[0-9]+}/profile', function ($request, $response, $args) {
        $organizationId = $args['id'];
        
        try {
            $organization = \Models\Organization::with(['settings' => function($query) {
                $query->where('is_public', true);
            }])
                ->where('id', $organizationId)
                ->where('is_verified', true)
                ->where('is_active', true)
                ->select(['id', 'organization_name', 'organization_type', 'city', 'state_province', 'country', 'created_at'])
                ->first();
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            return renderWithJson($organization, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error: ' . $e->getMessage(), '', 1);
        }
    });
    
});