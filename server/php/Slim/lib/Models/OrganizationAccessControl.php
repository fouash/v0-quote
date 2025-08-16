<?php
/**
 * Organization Access Control System
 *
 * PHP version 7+
 *
 * @category   Model
 * @package    GetlancerV3
 * @subpackage Organization
 * @author     Getlancer Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

namespace Models;

class OrganizationAccessControl
{
    /**
     * Check if user has access to organization
     */
    public static function hasOrganizationAccess($userId, $organizationId, $permission = null)
    {
        global $authUser;
        
        // Admin users have access to all organizations
        if ($authUser && $authUser['role_id'] == \Constants\ConstUserTypes::Admin) {
            return true;
        }

        // Check if user is member of organization
        $orgUser = OrganizationUser::where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$orgUser) {
            return false;
        }

        // If no specific permission required, just check membership
        if (!$permission) {
            return true;
        }

        // Check specific permission
        return $orgUser->hasPermission($permission);
    }

    /**
     * Get user's organizations
     */
    public static function getUserOrganizations($userId, $activeOnly = true)
    {
        $query = Organization::whereHas('organizationUsers', function($q) use ($userId, $activeOnly) {
            $q->where('user_id', $userId);
            if ($activeOnly) {
                $q->where('is_active', true);
            }
        });

        return $query->get();
    }

    /**
     * Get user's organization IDs
     */
    public static function getUserOrganizationIds($userId, $activeOnly = true)
    {
        return self::getUserOrganizations($userId, $activeOnly)->pluck('id')->toArray();
    }

    /**
     * Filter query by user's organization access
     */
    public static function filterByOrganizationAccess($query, $userId, $organizationField = 'organization_id')
    {
        global $authUser;
        
        // Admin users see everything
        if ($authUser && $authUser['role_id'] == \Constants\ConstUserTypes::Admin) {
            return $query;
        }

        $organizationIds = self::getUserOrganizationIds($userId);
        
        if (empty($organizationIds)) {
            // User has no organization access, return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($organizationField, $organizationIds);
    }

    /**
     * Check if user can create quotes for organization
     */
    public static function canCreateQuotes($userId, $organizationId)
    {
        $organization = Organization::find($organizationId);
        
        if (!$organization || !$organization->is_active || !$organization->is_verified) {
            return false;
        }

        if (!in_array($organization->organization_type, ['Buyer', 'Both'])) {
            return false;
        }

        return self::hasOrganizationAccess($userId, $organizationId, 'manage_projects');
    }

    /**
     * Check if user can submit bids for organization
     */
    public static function canSubmitBids($userId, $organizationId)
    {
        $organization = Organization::find($organizationId);
        
        if (!$organization || !$organization->is_active || !$organization->is_verified) {
            return false;
        }

        if (!in_array($organization->organization_type, ['Supplier', 'Both'])) {
            return false;
        }

        return self::hasOrganizationAccess($userId, $organizationId, 'manage_projects');
    }

    /**
     * Get organization context for user
     */
    public static function getOrganizationContext($userId)
    {
        $organizations = self::getUserOrganizations($userId);
        $context = [];

        foreach ($organizations as $org) {
            $orgUser = $org->organizationUsers()->where('user_id', $userId)->first();
            
            $context[] = [
                'id' => $org->id,
                'name' => $org->organization_name,
                'type' => $org->organization_type,
                'role' => $orgUser->role,
                'permissions' => $orgUser->permissions,
                'is_verified' => $org->is_verified,
                'can_create_quotes' => self::canCreateQuotes($userId, $org->id),
                'can_submit_bids' => self::canSubmitBids($userId, $org->id)
            ];
        }

        return $context;
    }

    /**
     * Apply organization-based access control to quotes
     */
    public static function applyQuoteAccessControl($query, $userId)
    {
        global $authUser;
        
        // Admin users see all quotes
        if ($authUser && $authUser['role_id'] == \Constants\ConstUserTypes::Admin) {
            return $query;
        }

        // Get user's organizations that can create quotes
        $buyerOrgIds = [];
        $supplierOrgIds = [];
        
        $organizations = self::getUserOrganizations($userId);
        foreach ($organizations as $org) {
            if (in_array($org->organization_type, ['Buyer', 'Both'])) {
                $buyerOrgIds[] = $org->id;
            }
            if (in_array($org->organization_type, ['Supplier', 'Both'])) {
                $supplierOrgIds[] = $org->id;
            }
        }

        // User can see quotes from their buyer organizations or quotes they can bid on
        return $query->where(function($q) use ($buyerOrgIds, $supplierOrgIds, $userId) {
            // Quotes from user's buyer organizations
            if (!empty($buyerOrgIds)) {
                $q->whereIn('organization_id', $buyerOrgIds);
            }
            
            // Public quotes that user's supplier organizations can bid on
            if (!empty($supplierOrgIds)) {
                $q->orWhere(function($subQ) {
                    $subQ->where('is_public', true)
                         ->where('status', 'open');
                });
            }
            
            // Quotes where user has submitted bids
            $q->orWhereHas('bids', function($bidQ) use ($userId) {
                $bidQ->where('user_id', $userId);
            });
        });
    }

    /**
     * Apply organization-based access control to projects
     */
    public static function applyProjectAccessControl($query, $userId)
    {
        global $authUser;
        
        // Admin users see all projects
        if ($authUser && $authUser['role_id'] == \Constants\ConstUserTypes::Admin) {
            return $query;
        }

        $organizationIds = self::getUserOrganizationIds($userId);
        
        return $query->where(function($q) use ($organizationIds, $userId) {
            // Projects from user's organizations
            if (!empty($organizationIds)) {
                $q->whereIn('client_organization_id', $organizationIds)
                  ->orWhereIn('freelancer_organization_id', $organizationIds);
            }
            
            // Projects where user is directly involved
            $q->orWhere('client_id', $userId)
              ->orWhere('freelancer_id', $userId);
        });
    }

    /**
     * Check organization verification requirements
     */
    public static function checkVerificationRequirements($organizationId)
    {
        $organization = Organization::with(['attachments', 'verifications'])->find($organizationId);
        
        if (!$organization) {
            return ['verified' => false, 'message' => 'Organization not found'];
        }

        $requirements = [
            'basic_info' => false,
            'saudi_numbers' => false,
            'required_documents' => false,
            'document_verification' => false
        ];

        // Check basic information
        if ($organization->organization_name && 
            $organization->contact_email && 
            $organization->contact_phone && 
            $organization->address_line1) {
            $requirements['basic_info'] = true;
        }

        // Check Saudi numbers
        if (preg_match('/^3[0-9]{14}$/', $organization->vat_number) && 
            preg_match('/^[0-9]{10}$/', $organization->cr_number)) {
            $requirements['saudi_numbers'] = true;
        }

        // Check required documents
        $requiredTypes = ['VAT', 'CR'];
        $uploadedTypes = $organization->attachments->pluck('attachment_type')->toArray();
        if (count(array_intersect($requiredTypes, $uploadedTypes)) === count($requiredTypes)) {
            $requirements['required_documents'] = true;
        }

        // Check document verification
        $verifiedTypes = $organization->attachments->where('is_verified', true)->pluck('attachment_type')->toArray();
        if (count(array_intersect($requiredTypes, $verifiedTypes)) === count($requiredTypes)) {
            $requirements['document_verification'] = true;
        }

        $allVerified = array_reduce($requirements, function($carry, $item) {
            return $carry && $item;
        }, true);

        return [
            'verified' => $allVerified,
            'requirements' => $requirements,
            'message' => $allVerified ? 'Organization fully verified' : 'Verification incomplete'
        ];
    }

    /**
     * Get organization-based dashboard data
     */
    public static function getDashboardData($userId)
    {
        $organizations = self::getUserOrganizations($userId);
        $data = [
            'organizations' => [],
            'summary' => [
                'total_organizations' => $organizations->count(),
                'verified_organizations' => $organizations->where('is_verified', true)->count(),
                'pending_verifications' => 0,
                'total_quotes' => 0,
                'total_bids' => 0,
                'total_projects' => 0
            ]
        ];

        foreach ($organizations as $org) {
            $orgData = [
                'id' => $org->id,
                'name' => $org->organization_name,
                'type' => $org->organization_type,
                'is_verified' => $org->is_verified,
                'verification_status' => self::checkVerificationRequirements($org->id),
                'stats' => $org->getStats()
            ];

            // Add organization-specific counts
            if (in_array($org->organization_type, ['Buyer', 'Both'])) {
                // Count quotes from this organization
                $quoteCount = 0; // This would query the quotes table
                $orgData['quotes_count'] = $quoteCount;
                $data['summary']['total_quotes'] += $quoteCount;
            }

            if (in_array($org->organization_type, ['Supplier', 'Both'])) {
                // Count bids from this organization
                $bidCount = 0; // This would query the bids table
                $orgData['bids_count'] = $bidCount;
                $data['summary']['total_bids'] += $bidCount;
            }

            $data['organizations'][] = $orgData;
        }

        return $data;
    }

    /**
     * Middleware function for organization access control
     */
    public static function middleware($requiredPermission = null, $organizationIdParam = 'id')
    {
        return function($request, $response, $next) use ($requiredPermission, $organizationIdParam) {
            global $authUser;
            
            if (!$authUser) {
                return renderWithJson([], 'Authentication required', '', 1);
            }

            // Get organization ID from route parameters
            $route = $request->getAttribute('route');
            $organizationId = $route->getArgument($organizationIdParam);

            if ($organizationId && !self::hasOrganizationAccess($authUser['id'], $organizationId, $requiredPermission)) {
                return renderWithJson([], 'Access denied to organization', '', 1);
            }

            return $next($request, $response);
        };
    }

    /**
     * Get organization selection options for user
     */
    public static function getOrganizationOptions($userId, $type = null)
    {
        $organizations = self::getUserOrganizations($userId);
        
        if ($type) {
            $organizations = $organizations->filter(function($org) use ($type) {
                return $org->organization_type === $type || $org->organization_type === 'Both';
            });
        }

        return $organizations->map(function($org) {
            return [
                'id' => $org->id,
                'name' => $org->organization_name,
                'type' => $org->organization_type,
                'is_verified' => $org->is_verified
            ];
        })->toArray();
    }

    /**
     * Switch user's active organization context
     */
    public static function switchOrganizationContext($userId, $organizationId)
    {
        if (!self::hasOrganizationAccess($userId, $organizationId)) {
            return false;
        }

        // Store in session or cache
        $_SESSION['active_organization_id'] = $organizationId;
        
        return true;
    }

    /**
     * Get user's active organization
     */
    public static function getActiveOrganization($userId)
    {
        $activeOrgId = $_SESSION['active_organization_id'] ?? null;
        
        if ($activeOrgId && self::hasOrganizationAccess($userId, $activeOrgId)) {
            return Organization::find($activeOrgId);
        }

        // Default to first organization
        $organizations = self::getUserOrganizations($userId);
        return $organizations->first();
    }

    /**
     * Log organization access for audit
     */
    public static function logAccess($userId, $organizationId, $action, $details = null)
    {
        // This would log to an audit table
        $logData = [
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Log to file or database
        error_log('Organization Access: ' . json_encode($logData));
    }
}