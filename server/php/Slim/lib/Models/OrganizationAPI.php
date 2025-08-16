<?php
/**
 * Organization API Endpoints
 *
 * PHP version 7+
 *
 * @category   API
 * @package    GetlancerV3
 * @subpackage Organization
 * @author     Getlancer Team
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 */

namespace Models;

class OrganizationAPI
{
    /**
     * Get all organizations with filtering and pagination
     */
    public static function getOrganizations($request, $response, $args)
    {
        global $authUser;
        $queryParams = $request->getQueryParams();
        
        try {
            $organizations = Organization::with(['attachments', 'users', 'settings'])
                ->filter($queryParams);
            
            // Apply role-based filtering
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                // Non-admin users can only see organizations they belong to
                $userOrgIds = OrganizationUser::where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->pluck('organization_id')
                    ->toArray();
                
                $organizations = $organizations->whereIn('id', $userOrgIds);
            }
            
            $result = $organizations->get();
            
            return renderWithJson($result, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error fetching organizations: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Get single organization by ID
     */
    public static function getOrganization($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        
        try {
            $organization = Organization::with([
                'attachments', 
                'users', 
                'settings', 
                'verifications',
                'createdBy',
                'updatedBy'
            ])->find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check access permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $hasAccess = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->exists();
                
                if (!$hasAccess) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            // Add statistics
            $organization->stats = $organization->getStats();
            $organization->verification_status = $organization->getVerificationStatus();
            
            return renderWithJson($organization, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error fetching organization: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Create new organization
     */
    public static function createOrganization($request, $response, $args)
    {
        global $authUser;
        $data = $request->getParsedBody();
        
        try {
            // Validate input
            $organization = new Organization();
            $validationErrors = $organization->validate($data);
            
            if (!empty($validationErrors)) {
                return renderWithJson($validationErrors, 'Validation failed', '', 1);
            }
            
            // Create organization
            $organization = Organization::create($data);
            
            // Load relationships
            $organization->load(['attachments', 'users', 'settings']);
            
            return renderWithJson($organization, 'Organization created successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error creating organization: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Update organization
     */
    public static function updateOrganization($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $data = $request->getParsedBody();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser || !in_array($orgUser->role, ['owner', 'admin'])) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            // Validate input
            $validationErrors = $organization->validateForUpdate($data, $organizationId);
            
            if (!empty($validationErrors)) {
                return renderWithJson($validationErrors, 'Validation failed', '', 1);
            }
            
            // Update organization
            $organization->update($data);
            $organization->load(['attachments', 'users', 'settings']);
            
            return renderWithJson($organization, 'Organization updated successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error updating organization: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Delete organization
     */
    public static function deleteOrganization($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions (only admin or owner can delete)
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('role', 'owner')
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            // Soft delete
            $organization->delete();
            
            return renderWithJson([], 'Organization deleted successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error deleting organization: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Upload organization attachment
     */
    public static function uploadAttachment($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $uploadedFiles = $request->getUploadedFiles();
        $data = $request->getParsedBody();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser || !$orgUser->canManageDocuments()) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            if (empty($uploadedFiles['file'])) {
                return renderWithJson([], 'No file uploaded', '', 1);
            }
            
            $uploadedFile = $uploadedFiles['file'];
            
            // Validate file
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                return renderWithJson([], 'File upload error', '', 1);
            }
            
            // Use the secure file upload system
            $fileUploadSecurity = new \FileUploadSecurity();
            $uploadResult = $fileUploadSecurity->validateAndProcessUpload($uploadedFile, [
                'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
                'max_size' => 10 * 1024 * 1024, // 10MB
                'upload_path' => 'media/Organization/' . $organizationId . '/'
            ]);
            
            if (!$uploadResult['success']) {
                return renderWithJson([], $uploadResult['error'], '', 1);
            }
            
            // Create attachment record
            $attachment = OrganizationAttachment::create([
                'organization_id' => $organizationId,
                'attachment_type' => $data['attachment_type'] ?? 'Other',
                'file_name' => $uploadResult['filename'],
                'file_path' => $uploadResult['filepath'],
                'original_name' => $uploadedFile->getClientFilename(),
                'mime_type' => $uploadedFile->getClientMediaType(),
                'file_size_kb' => round($uploadedFile->getSize() / 1024),
                'file_hash' => $uploadResult['hash']
            ]);
            
            return renderWithJson($attachment, 'File uploaded successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error uploading file: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Get organization attachments
     */
    public static function getAttachments($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $queryParams = $request->getQueryParams();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $hasAccess = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->exists();
                
                if (!$hasAccess) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            $attachments = OrganizationAttachment::where('organization_id', $organizationId)
                ->with(['uploadedBy', 'verifiedBy'])
                ->filter($queryParams)
                ->get();
            
            return renderWithJson($attachments, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error fetching attachments: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Verify attachment
     */
    public static function verifyAttachment($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $attachmentId = $args['attachment_id'];
        
        try {
            // Check admin permissions
            if (!$authUser || $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                return renderWithJson([], 'Access denied', '', 1);
            }
            
            $attachment = OrganizationAttachment::where('organization_id', $organizationId)
                ->where('id', $attachmentId)
                ->first();
            
            if (!$attachment) {
                return renderWithJson([], 'Attachment not found', '', 1);
            }
            
            $attachment->verify();
            
            return renderWithJson($attachment, 'Attachment verified successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error verifying attachment: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Get organization users
     */
    public static function getOrganizationUsers($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $queryParams = $request->getQueryParams();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            $users = OrganizationUser::where('organization_id', $organizationId)
                ->with(['user', 'invitedBy', 'approvedBy'])
                ->filter($queryParams)
                ->get();
            
            return renderWithJson($users, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error fetching organization users: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Add user to organization
     */
    public static function addUser($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $data = $request->getParsedBody();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser || !$orgUser->canManageUsers()) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            // Validate input
            $data['organization_id'] = $organizationId;
            $organizationUser = new OrganizationUser();
            $validationErrors = $organizationUser->validate($data);
            
            if (!empty($validationErrors)) {
                return renderWithJson($validationErrors, 'Validation failed', '', 1);
            }
            
            // Check if user already exists in organization
            $existingUser = OrganizationUser::where('organization_id', $organizationId)
                ->where('user_id', $data['user_id'])
                ->first();
            
            if ($existingUser) {
                return renderWithJson([], 'User already exists in organization', '', 1);
            }
            
            // Create organization user
            $organizationUser = OrganizationUser::create($data);
            $organizationUser->load(['user', 'invitedBy']);
            
            return renderWithJson($organizationUser, 'User added successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error adding user: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Update user role in organization
     */
    public static function updateUserRole($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $userId = $args['user_id'];
        $data = $request->getParsedBody();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser || !$orgUser->canManageUsers()) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            $organizationUser = OrganizationUser::where('organization_id', $organizationId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$organizationUser) {
                return renderWithJson([], 'User not found in organization', '', 1);
            }
            
            // Update role
            $organizationUser->updateRole(
                $data['role'],
                $data['permissions'] ?? null
            );
            
            $organizationUser->load(['user']);
            
            return renderWithJson($organizationUser, 'User role updated successfully', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error updating user role: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Get organization settings
     */
    public static function getSettings($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $queryParams = $request->getQueryParams();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
                
                // Non-admin users can only see public settings
                if (!$orgUser->canManageSettings()) {
                    $queryParams['public'] = true;
                }
            }
            
            $settings = OrganizationSetting::where('organization_id', $organizationId)
                ->filter($queryParams)
                ->get();
            
            return renderWithJson($settings, 'Success', '', 0);
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error fetching settings: ' . $e->getMessage(), '', 1);
        }
    }
    
    /**
     * Update organization setting
     */
    public static function updateSetting($request, $response, $args)
    {
        global $authUser;
        $organizationId = $args['id'];
        $data = $request->getParsedBody();
        
        try {
            $organization = Organization::find($organizationId);
            
            if (!$organization) {
                return renderWithJson([], 'Organization not found', '', 1);
            }
            
            // Check permissions
            if ($authUser && $authUser['role_id'] != \Constants\ConstUserTypes::Admin) {
                $orgUser = OrganizationUser::where('organization_id', $organizationId)
                    ->where('user_id', $authUser['id'])
                    ->where('is_active', true)
                    ->first();
                
                if (!$orgUser || !$orgUser->canManageSettings()) {
                    return renderWithJson([], 'Access denied', '', 1);
                }
            }
            
            // Update setting
            $success = OrganizationSetting::setValue(
                $organizationId,
                $data['setting_key'],
                $data['setting_value'],
                $data['setting_type'] ?? 'string',
                $data['is_encrypted'] ?? false,
                $data['is_public'] ?? true,
                $data['description'] ?? null
            );
            
            if ($success) {
                $setting = OrganizationSetting::where('organization_id', $organizationId)
                    ->where('setting_key', $data['setting_key'])
                    ->first();
                
                return renderWithJson($setting, 'Setting updated successfully', '', 0);
            } else {
                return renderWithJson([], 'Failed to update setting', '', 1);
            }
            
        } catch (\Exception $e) {
            return renderWithJson([], 'Error updating setting: ' . $e->getMessage(), '', 1);
        }
    }
}